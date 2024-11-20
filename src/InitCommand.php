<?php

namespace Eusouomichel\PhpCommit;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

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
        $helper = $this->getHelper('question');

        $question = new OutputFormatterStyle('blue', 'default', ['bold']);
        $output->getFormatter()->setStyle('question', $question);

        // Carregar idiomas disponíveis
        $languages = $this->getAvailableLanguages();

        // Perguntar o idioma
        $output->writeln("Select the language for the next questions:");
        $question = new ChoiceQuestion('', $languages, array_search($this->language, $languages));
        $this->language = $helper->ask($input, $output, $question);

        // Carregar traduções com base no idioma selecionado
        $this->loadTranslations($this->language);

        // Perguntar configurações
        $output->writeln("<info>" . $this->t('welcome_message') . "</info>");

        $question = new Question($this->t('auto_add_files') . " [yes]: ", 'yes');
        $autoAdd = strtolower($helper->ask($input, $output, $question)) === 'yes';

        $question = new Question($this->t('auto_push') . " [yes]: ", 'yes');
        $autoPush = strtolower($helper->ask($input, $output, $question)) === 'yes';

        $output->writeln($this->t('pre_commit_commands'));
        $preCommitCommands = [];
        while (true) {
            $question = new Question('');
            $command = $helper->ask($input, $output, $question);
            if (empty($command)) {
                break;
            }
            $preCommitCommands[] = $command;
        }

        $output->writeln($this->t('no_commit_strings'));
        $noCommitStrings = [];
        while (true) {
            $question = new Question('');
            $string = $helper->ask($input, $output, $question);
            if (empty($string)) {
                break;
            }
            $noCommitStrings[] = $string;
        }

        $config = [
            'language' => $this->language,
            'auto_add_files' => $autoAdd,
            'auto_push' => $autoPush,
            'pre_commit_commands' => $preCommitCommands,
            'no_commit_strings' => $noCommitStrings,
        ];

        $filePath = getcwd() . '/php-commit.json';
        file_put_contents($filePath, json_encode($config, JSON_PRETTY_PRINT));

        $output->writeln("<info>" . $this->t('config_saved', ['filePath' => $filePath]) . "</info>");

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
