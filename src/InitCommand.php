<?php

namespace Eusouomichel\PhpCommit;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Eusouomichel\PhpCommit\Utils\StyleManager;

class InitCommand extends Command
{
    private $translations = [];
    private $language = 'en'; // Idioma padrão é inglês

    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initializes the php-commit.json configuration file.')
            ->setHelp(
                'This command creates a `php-commit.json` file with the desired configuration for commits.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Setup beautiful styles
        StyleManager::setupStyles($output);
        
        // Display beautiful init header
        $output->writeln([
            '',
            '<question>╔════════════════════════════════════════════════════════════════════════════════════════╗</question>',
            '<question>║                                ⚙️  PHP Commit Setup                                    ║</question>',
            '<question>║                         Configure your commit workflow                              ║</question>',
            '<question>╚════════════════════════════════════════════════════════════════════════════════════════╝</question>',
            ''
        ]);

        $helper = $this->getHelper('question');

        // Carregar idiomas disponíveis
        $languages = $this->getAvailableLanguages();

        // Perguntar o idioma
        $output->writeln(StyleManager::getStepMessage(1, "Select the language for the next questions:"));
        $question = new ChoiceQuestion('', $languages, array_search($this->language, $languages));
        $this->language = $helper->ask($input, $output, $question);

        // Carregar traduções com base no idioma selecionado
        $this->loadTranslations($this->language);

        // Perguntar configurações
        $output->writeln(StyleManager::getSuccessMessage($this->t('welcome_message')));

        $output->writeln("\n" . StyleManager::getStepMessage(2, $this->t('auto_add_files')));
        $question = new Question("[yes]: ", 'yes');
        $autoAdd = strtolower($helper->ask($input, $output, $question)) === 'yes';

        $output->writeln("\n" . StyleManager::getStepMessage(3, $this->t('auto_push')));
        $question = new Question("[yes]: ", 'yes');
        $autoPush = strtolower($helper->ask($input, $output, $question)) === 'yes';

        $output->writeln("\n" . StyleManager::getStepMessage(4, $this->t('pre_commit_commands')));
        $output->writeln("<info>  💡 Common examples: 'npm run lint', 'composer phpcs', 'php artisan test'</info>");
        $preCommitCommands = [];
        while (true) {
            $question = new Question('  🔧 ');
            $command = $helper->ask($input, $output, $question);
            if (empty($command)) {
                break;
            }
            $preCommitCommands[] = $command;
            $output->writeln("<success>  ✅ Added: $command</success>");
        }

        $output->writeln("\n" . StyleManager::getStepMessage(5, $this->t('no_commit_strings')));
        $output->writeln("<info>  💡 Common examples: 'TODO', 'FIXME', 'console.log', 'var_dump'</info>");
        $noCommitStrings = [];
        while (true) {
            $question = new Question('  🚫 ');
            $string = $helper->ask($input, $output, $question);
            if (empty($string)) {
                break;
            }
            $noCommitStrings[] = $string;
            $output->writeln("<warning>  ⚠️  Will block commits containing: $string</warning>");
        }

        // Configurar limites de mensagens
        $output->writeln("\n" . StyleManager::getStepMessage(6, $this->t('configure_message_limits')));
        $output->writeln("<info>  💡 " . $this->t('message_limits_info') . "</info>");
        
        $messageLimits = [];
        
        // Context limit
        $output->writeln("\n  📝 " . $this->t('context_limit_prompt'));
        $question = new Question("  [20]: ", '20');
        $question->setValidator(function ($answer) {
            $value = (int) $answer;
            if ($value <= 0) {
                throw new \InvalidArgumentException($this->t('invalid_limit_value'));
            }
            return $value;
        });
        $messageLimits['context'] = (int) $helper->ask($input, $output, $question);
        
        // Summary limit
        $output->writeln("\n  📝 " . $this->t('summary_limit_prompt'));
        $question = new Question("  [50]: ", '50');
        $question->setValidator(function ($answer) {
            $value = (int) $answer;
            if ($value <= 0) {
                throw new \InvalidArgumentException($this->t('invalid_limit_value'));
            }
            return $value;
        });
        $messageLimits['summary'] = (int) $helper->ask($input, $output, $question);
        
        // Description limit
        $output->writeln("\n  📝 " . $this->t('description_limit_prompt'));
        $question = new Question("  [500]: ", '500');
        $question->setValidator(function ($answer) {
            $value = (int) $answer;
            if ($value <= 0) {
                throw new \InvalidArgumentException($this->t('invalid_limit_value'));
            }
            return $value;
        });
        $messageLimits['description'] = (int) $helper->ask($input, $output, $question);
        
        // Breaking change limit
        $output->writeln("\n  📝 " . $this->t('breaking_change_limit_prompt'));
        $question = new Question("  [50]: ", '50');
        $question->setValidator(function ($answer) {
            $value = (int) $answer;
            if ($value <= 0) {
                throw new \InvalidArgumentException($this->t('invalid_limit_value'));
            }
            return $value;
        });
        $messageLimits['breaking_change'] = (int) $helper->ask($input, $output, $question);
        
        // Reference limit
        $output->writeln("\n  📝 " . $this->t('reference_limit_prompt'));
        $question = new Question("  [50]: ", '50');
        $question->setValidator(function ($answer) {
            $value = (int) $answer;
            if ($value <= 0) {
                throw new \InvalidArgumentException($this->t('invalid_limit_value'));
            }
            return $value;
        });
        $messageLimits['reference'] = (int) $helper->ask($input, $output, $question);

        $config = [
            'language' => $this->language,
            'auto_add_files' => $autoAdd,
            'auto_push' => $autoPush,
            'pre_commit_commands' => $preCommitCommands,
            'no_commit_strings' => $noCommitStrings,
            'message_limits' => $messageLimits,
        ];

        $filePath = getcwd() . '/php-commit.json';
        file_put_contents($filePath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $output->writeln("\n" . StyleManager::getSuccessMessage($this->t('config_saved', ['filePath' => $filePath])));
        $output->writeln("\n<info>🎉 " . $this->t('setup_complete') . "</info>");

        return Command::SUCCESS;
    }

    private function getAvailableLanguages(): array
    {
        $langDir = __DIR__ . '/lang/';
        $languages = [];

        if (is_dir($langDir)) {
            foreach (scandir($langDir) as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $languages[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }

        return $languages;
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
}
