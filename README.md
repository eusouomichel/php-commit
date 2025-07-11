# 🚀 **PHP Commit**

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/eusouomichel/php-commit.svg)](https://packagist.org/packages/eusouomichel/php-commit)

**PHP Commit** is a powerful, user-friendly Open Source library that revolutionizes how you create Git commit messages. With intuitive interactive prompts and intelligent automation, it ensures your commit history is always clean, consistent, and professional.

✨ **Key Features:**
- 🎯 **Interactive commit creation** with guided prompts
- 🌍 **Multi-language support** (English, Portuguese, and more)
- 🔄 **Smart automation** (auto-add files, auto-push)
- 🛡️ **Pre-commit validation** with custom rules
- 📋 **Conventional Commits compliant**
- ⚡ **Fast and lightweight**

Built following [Conventional Commits](https://www.conventionalcommits.org) specifications, PHP Commit transforms your development workflow by making commit messages meaningful, searchable, and automatically processable by tools.


##  Installation



To use this library, ensure **Composer** is installed on your system. If not, you can install it by following the official instructions at [https://getcomposer.org](https://getcomposer.org).



Once Composer is installed, you can add the library to your project by running:


```bash

composer require --dev michelmileski/php-commit

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



## 🎯 Usage

### 🚀 Quick Start

1. **Initialize the configuration:**
   ```bash
   php vendor/bin/commit init
   ```

2. **Create your first commit:**
   ```bash
   php vendor/bin/commit message
   ```

3. **Quick WIP commit:**
   ```bash
   php vendor/bin/commit message --wip
   ```

### 📝 Creating Custom Commit Messages

To create a custom commit message, use:

```bash
php vendor/bin/commit message
```

This launches an **interactive session** with beautiful prompts:

```
🔥 Choose the commit type:
  [1] feat: A new feature
  [2] fix: Bug fix
  [3] docs: Documentation changes
  [4] style: Style changes (formatting, spacing, etc.)
  [5] refactor: Code refactoring without behavior changes
  ...

📝 Enter commit context (optional, max. 20 characters):
> auth

✨ Enter commit summary (max. 50 characters):
> add user authentication system

📖 Describe the changes (optional, max. 500 characters):
> Implemented JWT-based authentication with login and logout endpoints

⚠️  Provide breaking change (optional):
> 

🔗 Reference (optional):
> #123
```

**Generated commit message:**
```
feat(auth): add user authentication system

Implemented JWT-based authentication with login and logout endpoints

Refs: #123
```



###  Creating Automatic WIP Commits



For quick "Work In Progress" (WIP) commits, use the command:

```bash

php vendor/bin/commit message --wip

```



This will automatically add files (if configured) and create a WIP commit with a predefined message.



###  Pre-Commit Commands and Prohibited Strings



You can configure pre-commit commands in the `php-commit.json` file. These commands will run before the commit is created. You can also specify prohibited strings that should not appear in files being committed.



If prohibited strings are detected, the commit will be blocked, and detailed error messages will be displayed.



### 🌍 Multi-Language Support

The library supports multiple languages for interactive prompts. You can set the desired language by updating the `language` field in the `php-commit.json` file.

**Available languages:**
- 🇺🇸 `en` (English)
- 🇧🇷 `pt_BR` (Portuguese)
- 🚀 More languages coming soon!

## ⚙️ Advanced Configuration

### Pre-commit Commands
Automate your workflow by adding pre-commit commands:

```json
{
  "pre_commit_commands": [
    "npm run lint",
    "composer phpcs",
    "php artisan test"
  ]
}
```

### Prohibited Strings
Prevent sensitive data from being committed:

```json
{
  "no_commit_strings": [
    "TODO",
    "FIXME",
    "console.log",
    "var_dump",
    "dd("
  ]
}
```

## ❓ FAQ

**Q: Can I use this with existing projects?**
A: Yes! Just run `php vendor/bin/commit init` in your project root.

**Q: What if I want to skip the interactive prompts?**
A: Use `--wip` flag for quick commits: `php vendor/bin/commit message --wip`

**Q: How do I add a new language?**
A: Create a new JSON file in `src/lang/` following the existing structure.

**Q: Can I customize commit types?**
A: Currently, we use standard Conventional Commits types. Custom types are planned for future releases.

**Q: Does this work with Git hooks?**
A: Yes! You can integrate PHP Commit with your existing Git hooks workflow.

## 🔧 Troubleshooting

**Configuration file not found?**
- Make sure you've run `php vendor/bin/commit init` first
- Check that `php-commit.json` exists in your project root

**Command not found?**
- Ensure Composer's vendor/bin directory is in your PATH
- Try using the full path: `./vendor/bin/commit`

**Permission denied?**
- Make sure the `commit` file is executable: `chmod +x vendor/bin/commit`



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
