<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii files</h1>
    <br>
</p>

The package provides useful methods to manage files and directories.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/files/v/stable.png)](https://packagist.org/packages/yiisoft/files)
[![Total Downloads](https://poser.pugx.org/yiisoft/files/downloads.png)](https://packagist.org/packages/yiisoft/files)
[![Build Status](https://travis-ci.com/yiisoft/files.svg?branch=master)](https://travis-ci.com/yiisoft/files)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/files/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/files/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/files/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/files/?branch=master)

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
