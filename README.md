<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Files</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/files/v)](https://packagist.org/packages/yiisoft/files)
[![Total Downloads](https://poser.pugx.org/yiisoft/files/downloads)](https://packagist.org/packages/yiisoft/files)
[![Build status](https://github.com/yiisoft/files/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/files/actions/workflows/build.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/files/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/files/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/files/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/files/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Ffiles%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/files/master)
[![static analysis](https://github.com/yiisoft/files/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/files/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/files/coverage.svg)](https://shepherd.dev/github/yiisoft/files)

The package provides useful methods to manage files and directories.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/files
```

## FileHelper usage

FileHelper provides static methods you can use for various filesystem-related operations.

### Working with directories

Ensure a directory exists:

```php
use \Yiisoft\Files\FileHelper;

$directory = '/path/to/dir';
FileHelper::ensureDirectory($directory);
```

Ensure a directory exists, and permission specified is set:

```php
use \Yiisoft\Files\FileHelper;

$directory = '/path/to/dir';
FileHelper::ensureDirectory($directory, 0775);
```

Remove a directory:

```php
use \Yiisoft\Files\FileHelper;

$directory = '/path/to/dir';
FileHelper::removeDirectory($directory);
```

Remove everything within a directory but not directory itself:

```php
use \Yiisoft\Files\FileHelper;

$directory = '/path/to/dir';
FileHelper::clearDirectory($directory, [
    'traverseSymlinks' => false,
    'filter' => (new PathMatcher())
        ->only('**.png', '**.jpg')
        ->except('**/logo.png'),
]);
```

Check if directory is empty:

```php
use \Yiisoft\Files\FileHelper;

$directory = '/path/to/dir';
FileHelper::isEmptyDirectory($directory);
```

Copy directory:

```php
use \Yiisoft\Files\FileHelper;

$source = '/path/to/source';
$destination = '/path/to/destination';
FileHelper::copyDirectory($source, $destination);
```

Additional options could be specified as third argument such as `filter` or `copyEmptyDirectories`.
Check method phpdoc for a full list of options.

Copy file:

```php
use \Yiisoft\Files\FileHelper;

$source = '/path/to/file';
$destination = '/path/to/destination';
FileHelper::copyFile($source, $destination);
```

Additional options could be specified as third argument such as `beforeCopy` or `afterCopy`.
Check method phpdoc for a full list of options.

### Search

Searching for files:

```php
use \Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

$files = FileHelper::findFiles('/path/to/where/to/search', [
    'filter' => (new PathMatcher())
        ->only('**.png', '**.jpg')
        ->except('**/logo.png'),
]);
```

Searching for directories:

```php
use \Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

$directories = FileHelper::findDirectories('/path/to/where/to/search', [
    'filter' => (new PathMatcher())->except('**meta'),
]);
```

### Path matchers

A `PathMatcherInterface` can be passed by `filter` key in the `options` argument of a method.
Both `PathMatcher` and `CompositeMatcher` implement the `PathMatcherInterface`. `CompositeMatcher` allows
using a list of `PathMatcherInterface` sequentially.

Pattern matching is based on `yiisoft/strings` package [and is described in its readme](https://github.com/yiisoft/strings#wildcardpattern-usage).

### Other

Open a file. Same as PHP's `fopen()` but throwing exceptions.

```php
use \Yiisoft\Files\FileHelper;

$handler = FileHelper::openFile('/path/to/file', 'rb');
```

Get last modified time for a directory or file:

```php
use \Yiisoft\Files\FileHelper;

$directory = '/path/to/dir';
$time = FileHelper::lastModifiedTime($directory);
```

The method is different from PHP's `filemtime()` because it actually scans a directory and selects the largest
modification time from all files.

Remove a file or symlink:

```php
use \Yiisoft\Files\FileHelper;

$file = '/path/to/file.txt';
FileHelper::unlink($file);
```

Normalize a path:

```php
use \Yiisoft\Files\FileHelper;

$path = '/home/samdark/./test/..///dev\yii/';
echo FileHelper::normalizePath($path);
// outputs:
// /home/samdark/dev/yii
```

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Files is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
