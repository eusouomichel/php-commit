# **PHP Commit**


**PHP Commit** is an Open Source library designed to simplify the creation of personalized Git commit messages. It uses interactive prompts to help standardize commit messages and supports automations such as automatically adding files and pushing after a commit.

This library is built on the specifications of [Conventional Commits](https://www.conventionalcommits.org), a widely adopted standard for writing clear, consistent, and meaningful commit messages. By adhering to this standard, PHP Commit helps improve the readability and traceability of your project’s history, making it easier to collaborate and maintain.


##  Installation



To use this library, ensure **Composer** is installed on your system. If not, you can install it by following the official instructions at [https://getcomposer.org](https://getcomposer.org).



Once Composer is installed, you can add the library to your project by running:


```bash

composer require --dev eusouomichel/php-commit

```



##  Initial Setup



After installation, run the following command to generate the `php-commit.json` configuration file:

```bash

php vendor/bin/commit init

```



During initialization, you will be prompted to configure:



*  The language for interactive prompts;

*  Automatic addition of files (`auto_add_files`);

*  Automatic push after committing (`auto_push`);

*  Pre-commit commands;

*  Strings that are prohibited in files being committed.



The `php-commit.json` file will be created in the current directory. You can edit this file later to adjust the configuration as needed.



Here is an example of a generated `php-commit.json` file:

```json

{
	"language": "en",
	"auto_add_files": true,
	"auto_push": true,
	"pre_commit_commands": [],
	"no_commit_strings": []
}

```



##  Usage



###  Creating Custom Commit Messages



To create a custom commit message, use the following command:

```bash

php vendor/bin/commit init

```



This command will launch an interactive session where you can:



1.  Select the type of commit (e.g., `feat`, `fix`, `docs`);

2.  Provide a context for the commit;

3.  Add a summary for the commit;

4.  Optionally provide a detailed description;

5.  Indicate if there are breaking changes;

6.  Add a reference (e.g., issue or ticket number).



###  Creating Automatic WIP Commits



For quick "Work In Progress" (WIP) commits, use the command:

```bash

php vendor/bin/commit message --wip

```



This will automatically add files (if configured) and create a WIP commit with a predefined message.



###  Pre-Commit Commands and Prohibited Strings



You can configure pre-commit commands in the `php-commit.json` file. These commands will run before the commit is created. You can also specify prohibited strings that should not appear in files being committed.



If prohibited strings are detected, the commit will be blocked, and detailed error messages will be displayed.



###  Multi-Language Support



The library supports multiple languages for interactive prompts. You can set the desired language by updating the `language` field in the `php-commit.json` file.



Available languages include:



*  `en` (English)

*  `pt_BR` (Portuguese)



##  Project Structure



*  `composer.json`: Composer configuration file.

*  `php-commit.json`: Configuration file generated during initialization.

*  `CommitMessageCommand.php`: The main command for creating commit messages.

*  `InitCommand.php`: The command to initialize the `php-commit.json` configuration file.



##  Contributing



We welcome contributions! To contribute:



1.  Fork the repository.

2.  Create a new branch for your feature: `git checkout -b my-feature`.

3.  Commit your changes: `git commit -m 'feat: My new feature'`.

4.  Push the branch: `git push origin my-feature`.

5.  Open a Pull Request.



##  License



This project is licensed under the [MIT License](LICENSE).
