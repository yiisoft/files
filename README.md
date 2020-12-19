<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Files</h1>
    <br>
</p>

The package provides useful methods to manage files and directories.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/files/v/stable.png)](https://packagist.org/packages/yiisoft/files)
[![Total Downloads](https://poser.pugx.org/yiisoft/files/downloads.png)](https://packagist.org/packages/yiisoft/files)
[![Build Status](https://github.com/yiisoft/files/workflows/build/badge.svg)](https://github.com/yiisoft/files/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/files/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/files/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/files/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/files/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Ffiles%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/files/master)
[![static analysis](https://github.com/yiisoft/files/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/files/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/files/coverage.svg)](https://shepherd.dev/github/yiisoft/files)

## Requirements

- PHP 7.4 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/files --prefer-dist
```

## General usage

Create a new directory:

```php
use \Yiisoft\Files\FileHelper;

$directory = '/path/to/dir';
FileHelper::createDirectory($directory);
```

Create a new directory with the permission to be set:

```php
use \Yiisoft\Files\FileHelper;

$directory = '/path/to/dir';
FileHelper::createDirectory($directory, 0775);
```

Remove a directory:

```php
use \Yiisoft\Files\FileHelper;

$directory = '/path/to/dir';
FileHelper::removeDirectory($directory);
```

Remove a file or symlink:

```php
use \Yiisoft\Files\FileHelper;

$file = '/path/to/file.txt';
FileHelper::unlink($file);
```

Normalize path:

```php
use \Yiisoft\Files\FileHelper;

$path = '/home/samdark/./test/..///dev\yii/';
echo FileHelper::normalizePath($path);
// outputs:
// /home/samdark/dev/yii
```

## File helper usage

File helper methods are static so usage is like the following:

```php
\Yiisoft\Files\FileHelper::copyDirectory('/home/master/inbox', '/backup/inbox') ?>
```

Overall the helper has the following method groups.

### Workinkg with directories

- createDirectory
- copyDirectory
- clearDirectory
- removeDirectory
- isEmptyDirectory

### Search

- findDirectories
- findFiles

### Other

- openFile
- normalizePath
- unlink
- lastModifiedTime

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

## License

The Yii Files is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
