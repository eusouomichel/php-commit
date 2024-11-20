<?php

namespace PhpCommit;

use Symfony\Component\Console\Application as BaseApplication;
use PhpCommit\CommitMessageCommand;
use PhpCommit\InitCommand;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('PHP Commit', '0.1.0');

        // Registra os comandos
        $this->add(new InitCommand());
        $this->add(new CommitMessageCommand());
        // $this->setDefaultCommand('commit', true);
    }

    public function getHelp(): string
    {
        return <<<HELP
PHP Commit - Gerencie seus commits de maneira eficiente e personalizada.

Comandos disponíveis:
  init       Inicializa o arquivo de configuração `php-commit.json`.
  commit     Gera uma mensagem de commit personalizada.

Opções globais:
  --help     Exibe esta ajuda.
  --version  Exibe a versão do PHP Commit.

Exemplo de uso:
  php commit init
  php commit commit
HELP;
    }
}
