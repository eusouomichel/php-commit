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
        // Carregar configurações JSON
        $configPath = 'php-commit.json';
        $config = json_decode(file_get_contents($configPath), true);

        // Carregar traduções
        $language = $config['language'] ?? 'en';
        $this->loadTranslations($language);

        $helper = $this->getHelper('question');

        $question = new OutputFormatterStyle('blue', 'default', ['bold']);
        $output->getFormatter()->setStyle('question', $question);

        $danger = new OutputFormatterStyle('red', 'default');
        $output->getFormatter()->setStyle('danger', $danger);

        $warning = new OutputFormatterStyle('yellow', 'default');
        $output->getFormatter()->setStyle('warning', $warning);

        // Verificar se há mudanças para commit
        $statusOutput = trim(shell_exec('git status --porcelain') ?? '');

        if (empty($statusOutput)) {
            $output->writeln("<error>" . $this->t('no_changes') . "</error>");
            return Command::FAILURE;
        }

        $preCommitCommands = $config['pre_commit_commands'] ?? [];

        // Executar comandos de pré-commit
        if (!empty($preCommitCommands)) {
            $output->writeln("<info>" . $this->t('running_pre_commit_commands') . "</info>");
            foreach ($preCommitCommands as $command) {
                $output->writeln("<info>" . $this->t('running_command') . ": $command</info>");
                $result = shell_exec($command);
            }
        }

        $noCommitStrings = $config['no_commit_strings'] ?? [];

        // Ignorar a checagem se não há strings proibidas
        if (!empty($noCommitStrings)) {
            $filesToCheck = $this->getFilesToCommit();
            $filesToCheck = array_filter($filesToCheck, fn ($file) => $file !== $configPath);
            $invalidFiles = $this->checkFilesForProhibitedStrings($filesToCheck, $noCommitStrings);

            if (!empty($invalidFiles)) {
                $output->writeln("\n<error>" . $this->t('prohibited_strings_detected') . "</error>");
                foreach ($invalidFiles as $file => $issues) {
                    $output->writeln("<danger>- {$this->t('file')}: {$file}</danger>");
                    foreach ($issues as $issue) {
                        [$string, $line, $lineContent] = $issue;
                        $output->writeln("<danger>  - {$this->t('line')}: {$line} - {$this->t('string')}: {$string}</danger>");
                        $output->writeln("    <danger>{$this->t('content')}: $lineContent</danger>");
                    }
                }
                return Command::FAILURE;
            }
        }

        // Verificar se a opção --wip foi usada
        if ($input->getOption('wip')) {
            $output->writeln("<info>" . $this->t('creating_wip_commit') . "</info>");

            if ($config['auto_add_files'] ?? false) {
                $output->writeln("<info>" . $this->t('adding_files') . "</info>");
                shell_exec('git add -A');
            }

            $commitMessage = 'chore: Work In Progress (WIP)';
            shell_exec("git commit -m \"$commitMessage\"");

            if ($config['auto_push'] ?? false) {
                $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
                $output->writeln("<info>" . $this->t('pushing_branch') . "</info>");
                shell_exec("git push --set-upstream origin $branch");
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

        $output->writeln('<question>' . $this->t('choose_commit_type') . '</question>');
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
        $output->writeln("\n<question>" . $this->t('commit_context', ['max' => $maxLength]) . "</question>");
        $question = new Question('');
        $context = $this->askWithCharacterCount($input, $output, $question, $maxLength);

        // Prompt resumo do commit
        $maxLength = 50;
        $output->writeln("\n<question>" . $this->t('commit_summary', ['max' => $maxLength]) . "</question>");
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
        $output->writeln("\n<question>" . $this->t('commit_description', ['max' => $maxLength]) . "</question>");
        $question = new Question('');
        $description = $this->askMultipleLinesWithCharacterCount($input, $output, $question, $maxLength);

        // Prompt breakingChange
        $maxLength = 50;
        $output->writeln("\n<question>" . $this->t('commit_breaking_change', ['max' => $maxLength]) . "</question>");
        $question = new Question('');
        $breakingChange = $this->askWithCharacterCount($input, $output, $question, $maxLength);

        // Prompt reference
        $maxLength = 50;
        $output->writeln("\n<question>" . $this->t('commit_referer', ['max' => $maxLength]) . "</question>");
        $question = new Question('');
        $reference = $this->askWithCharacterCount($input, $output, $question, $maxLength);

        if ($config['auto_add_files'] ?? false) {
            $output->writeln("<info>" . $this->t('adding_files') . "</info>");
            shell_exec('git add -A');
        }

        // Gera a mensagem de commit
        $commitMessage = CommitMessage::generate($type, $context, $summary, $description, $breakingChange, $reference);
        shell_exec("git commit -m \"$commitMessage\"");

        if ($config['auto_push'] ?? false) {
            $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
            $output->writeln("<info>" . $this->t('pushing_branch') . "</info>");

            shell_exec("git push --set-upstream origin $branch");
        }

        return Command::SUCCESS;
    }

    private function getFilesToCommit(): array
    {
        $statusOutput = shell_exec('git status --porcelain');
        $files = [];

        foreach (explode("\n", trim($statusOutput)) as $line) {
            if (!empty($line)) {
                $file = preg_replace('/^[A-Z?]+\s+/', '', $line);
                if ($file) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    private function checkFilesForProhibitedStrings(array $files, array $prohibitedStrings): array
    {
        $invalidFiles = [];

        foreach ($files as $file) {
            if (file_exists($file)) {
                $fileContent = file($file);
                $foundIssues = [];

                foreach ($fileContent as $lineNumber => $lineContent) {
                    foreach ($prohibitedStrings as $string) {
                        if (strpos($lineContent, $string) !== false) {
                            $foundIssues[] = [$string, $lineNumber + 1, trim($lineContent)];
                        }
                    }
                }

                if (!empty($foundIssues)) {
                    $invalidFiles[$file] = $foundIssues;
                }
            }
        }

        return $invalidFiles;
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
