<?php

namespace Eusouomichel\PhpCommit;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Eusouomichel\PhpCommit\Utils\GitHelper;
use Eusouomichel\PhpCommit\Utils\FileValidator;
use Eusouomichel\PhpCommit\Utils\StyleManager;

class CommitMessageCommand extends Command
{
    private $translations = [];

    protected function configure()
    {
        $this
            ->setName('message')
            ->setDescription('Gera uma mensagem de commit personalizada.')
            ->setHelp('Este comando permite que você crie uma mensagem de commit personalizada com prompts interativos.')
            ->addOption(
                'wip',
                null,
                InputOption::VALUE_NONE,
                'Gera automaticamente um commit de Work In Progress (WIP)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Setup beautiful styles
        StyleManager::setupStyles($output);
        
        // Display header
        StyleManager::displayHeader($output);
        
        // Carregar configurações JSON
        $configPath = 'php-commit.json';
        if (!file_exists($configPath)) {
            $output->writeln(StyleManager::getErrorMessage($this->t('config_not_found')));
            $output->writeln(StyleManager::getInfoMessage($this->t('run_init_first')));
            return Command::FAILURE;
        }
        
        $config = json_decode(file_get_contents($configPath), true);

        // Carregar traduções
        $language = $config['language'] ?? 'en';
        $this->loadTranslations($language);

        $helper = $this->getHelper('question');

        // Verificar se há mudanças para commit usando GitHelper
        if (!GitHelper::hasChanges()) {
            $output->writeln(StyleManager::getErrorMessage($this->t('no_changes')));
            return Command::FAILURE;
        }

        $preCommitCommands = $config['pre_commit_commands'] ?? [];

        // Executar comandos de pré-commit com melhor feedback
        if (!empty($preCommitCommands)) {
            $output->writeln(StyleManager::getInfoMessage($this->t('running_pre_commit_commands'), '⚡'));
            foreach ($preCommitCommands as $command) {
                $output->writeln(StyleManager::getInfoMessage($this->t('running_command') . ": $command", '🔧'));
                $result = GitHelper::runCommand($command);
                if (!$result['success']) {
                    $output->writeln(StyleManager::getErrorMessage($this->t('command_failed') . ": $command"));
                    $output->writeln("<danger>{$result['error']}</danger>");
                    return Command::FAILURE;
                }
            }
        }

        $noCommitStrings = $config['no_commit_strings'] ?? [];

        // Verificar strings proibidas com melhor performance
        if (!empty($noCommitStrings)) {
            $output->writeln(StyleManager::getInfoMessage($this->t('checking_prohibited_strings'), '🔍'));
            $filesToCheck = GitHelper::getFilesToCommit();
            $filesToCheck = array_filter($filesToCheck, fn ($file) => $file !== $configPath && !FileValidator::shouldIgnoreFile($file));
            $invalidFiles = FileValidator::checkFilesForProhibitedStrings($filesToCheck, $noCommitStrings);

            if (!empty($invalidFiles)) {
                $output->writeln(StyleManager::getErrorMessage($this->t('prohibited_strings_detected')));
                foreach ($invalidFiles as $file => $issues) {
                    $output->writeln("<danger>📄 {$this->t('file')}: {$file}</danger>");
                    foreach ($issues as $issue) {
                        [$string, $line, $lineContent] = $issue;
                        $output->writeln("<danger>  🔍 {$this->t('line')}: {$line} - {$this->t('string')}: {$string}</danger>");
                        $output->writeln("    <danger>💬 {$this->t('content')}: $lineContent</danger>");
                    }
                }
                return Command::FAILURE;
            }
        }

        // Verificar se a opção --wip foi usada
        if ($input->getOption('wip')) {
            $output->writeln(StyleManager::getInfoMessage($this->t('creating_wip_commit'), '⏳'));

            if ($config['auto_add_files'] ?? false) {
                $output->writeln(StyleManager::getInfoMessage($this->t('adding_files'), '➕'));
                GitHelper::addAllFiles();
            }

            $commitMessage = 'chore: Work In Progress (WIP)';
            GitHelper::commit($commitMessage);

            if ($config['auto_push'] ?? false) {
                $output->writeln(StyleManager::getInfoMessage($this->t('pushing_branch'), '🚀'));
                GitHelper::pushBranch();
            }

            return Command::SUCCESS;
        }

        // Processo interativo padrão para criação de mensagem de commit
        $choices = [
            'feat' => $this->t('choice_feat'),
            'fix' => $this->t('choice_fix'),
            'docs' => $this->t('choice_docs'),
            'style' => $this->t('choice_style'),
            'refactor' => $this->t('choice_refactor'),
            'test' => $this->t('choice_test'),
            'chore' => $this->t('choice_chore'),
            'build' => $this->t('choice_build'),
            'perf' => $this->t('choice_perf'),
            'ci' => $this->t('choice_ci'),
            'revert' => $this->t('choice_revert'),
        ];

        $choicesWithDescriptions = [];
        $count = 1;
        foreach ($choices as $key => $description) {
            $choicesWithDescriptions[$count++] = "$key: $description";
        }

        StyleManager::displayCommitTypes($output, $choices);
        $question = new ChoiceQuestion('', $choicesWithDescriptions);

        $question->setValidator(function ($answer) use ($choicesWithDescriptions) {
            if (empty($answer)) {
                throw new InvalidArgumentException($this->t('empty_choice'));
            }

            if (!key_exists($answer, $choicesWithDescriptions)) {
                throw new InvalidArgumentException($this->t('invalid_choice', ['choice' => $answer]));
            }

            return $choicesWithDescriptions[$answer];
        });

        $type = $helper->ask($input, $output, $question);

        // Prompt contexto do commit
        $maxLength = 20;
        $output->writeln("\n" . StyleManager::getStepMessage(1, $this->t('commit_context', ['max' => $maxLength])));
        $question = new Question('');
        $context = $this->askWithCharacterCount($input, $output, $question, $maxLength);

        // Prompt resumo do commit
        $maxLength = 50;
        $output->writeln("\n" . StyleManager::getStepMessage(2, $this->t('commit_summary', ['max' => $maxLength])));
        $question = new Question('');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new InvalidArgumentException($this->t('empty_choice'));
            }

            return $answer;
        });
        $summary = $this->askWithCharacterCount($input, $output, $question, $maxLength);

        // Prompt descrição do commit
        $maxLength = 500;
        $output->writeln("\n" . StyleManager::getStepMessage(3, $this->t('commit_description', ['max' => $maxLength])));
        $question = new Question('');
        $description = $this->askMultipleLinesWithCharacterCount($input, $output, $question, $maxLength);

        // Prompt breakingChange
        $maxLength = 50;
        $output->writeln("\n" . StyleManager::getStepMessage(4, $this->t('commit_breaking_change', ['max' => $maxLength])));
        $question = new Question('');
        $breakingChange = $this->askWithCharacterCount($input, $output, $question, $maxLength);

        // Prompt reference
        $maxLength = 50;
        $output->writeln("\n" . StyleManager::getStepMessage(5, $this->t('commit_referer', ['max' => $maxLength])));
        $question = new Question('');
        $reference = $this->askWithCharacterCount($input, $output, $question, $maxLength);

        if ($config['auto_add_files'] ?? false) {
            $output->writeln(StyleManager::getInfoMessage($this->t('adding_files'), '➕'));
            GitHelper::addAllFiles();
        }

        // Gera a mensagem de commit
        $commitMessage = CommitMessage::generate($type, $context, $summary, $description, $breakingChange, $reference);
        GitHelper::commit($commitMessage);

        if ($config['auto_push'] ?? false) {
            $output->writeln(StyleManager::getInfoMessage($this->t('pushing_branch'), '🚀'));
            GitHelper::pushBranch();
        }

        return Command::SUCCESS;
    }

    private function askWithCharacterCount(InputInterface $input, OutputInterface $output, Question $question, int $maxLength)
    {
        $helper = $this->getHelper('question');

        $question->setMaxAttempts(null);

        $inputText = '';

        while (true) {
            $inputText = $helper->ask($input, $output, $question);

            if (empty($inputText)) {
                break;
            }

            $currentLength = strlen($inputText);

            if ($currentLength <= $maxLength) {
                break;
            }

            $output->writeln("<error>" . $this->t('limit_exceeded', ['max' => $maxLength]) . "</error>");
        }

        return $inputText;
    }

    private function loadTranslations(string $lang): void
    {
        $filePath = __DIR__ . "/lang/$lang.json";
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Translation file for language '$lang' not found.");
        }

        $this->translations = json_decode(file_get_contents($filePath), true) ?? [];
    }

    private function t(string $key, array $placeholders = []): string
    {
        $message = $this->translations[$key] ?? $key;

        foreach ($placeholders as $placeholder => $value) {
            $message = str_replace("{{$placeholder}}", $value, $message);
        }

        return $message;
    }

    public function askMultipleLinesWithCharacterCount($input, $output, $question, $maxLength)
    {
        $description = [];
        $currentLength = 0;
        $continueInput = true;

        while ($continueInput) {
            $line = $this->askWithCharacterCount($input, $output, new Question(''), $maxLength);

            if (empty($line)) {
                $continueInput = false;
            } else {
                $newLength = $currentLength + strlen($line) + 1;
                if ($newLength <= $maxLength) {
                    $description[] = $line;
                    $currentLength = $newLength;
                } else {
                    $output->writeln("<error>" . $this->t('limit_exceeded', ['max' => $maxLength]) . "</error>");

                    break;
                }
            }
        }

        rtrim(implode("\n", $description));
    }
}
