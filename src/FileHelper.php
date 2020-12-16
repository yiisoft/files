<?php

declare(strict_types=1);

namespace Yiisoft\Files;

use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * FileHelper provides useful methods to manage files and directories
 */
class FileHelper
{
    /**
     * Opens a file or URL.
     *
     * This method is similar to the PHP `fopen()` function, except that it suppresses the `E_WARNING`
     * level error and throws the `\RuntimeException` exception if it can't open the file.
     *
     * @param string $filename The file or URL.
     * @param string $mode The type of access.
     * @param bool $useIncludePath Whether to search for a file in the include path.
     * @param resource|null $context The stream context or `null`.
     *
     * @throws RuntimeException If the file could not be opened.
     *
     * @return resource The file pointer resource.
     *
     * @psalm-suppress PossiblyNullArgument
     */
    public static function openFile(string $filename, string $mode, bool $useIncludePath = false, $context = null)
    {
        $filePointer = @fopen($filename, $mode, $useIncludePath, $context);

        if ($filePointer === false) {
            throw new RuntimeException("The file \"{$filename}\" could not be opened.");
        }

        return $filePointer;
    }

    /**
     * Creates a new directory.
     *
     * This method is similar to the PHP `mkdir()` function except that it uses `chmod()` to set the permission of the
     * created directory in order to avoid the impact of the `umask` setting.
     *
     * @param string $path path of the directory to be created.
     * @param int $mode the permission to be set for the created directory.
     *
     * @return bool whether the directory is created successfully.
     */
    public static function createDirectory(string $path, int $mode = 0775): bool
    {
        $path = static::normalizePath($path);

        try {
            if (!mkdir($path, $mode, true) && !is_dir($path)) {
                return false;
            }
        } catch (Exception $e) {
            if (!is_dir($path)) {
                throw new RuntimeException(
                    'Failed to create directory "' . $path . '": ' . $e->getMessage(),
                    (int)$e->getCode(),
                    $e
                );
            }
        }

        return chmod($path, $mode);
    }

    /**
     * Normalizes a file/directory path.
     *
     * The normalization does the following work:
     *
     * - Convert all directory separators into `/` (e.g. "\a/b\c" becomes "/a/b/c")
     * - Remove trailing directory separators (e.g. "/a/b/c/" becomes "/a/b/c")
     * - Turn multiple consecutive slashes into a single one (e.g. "/a///b/c" becomes "/a/b/c")
     * - Remove ".." and "." based on their meanings (e.g. "/a/./b/../c" becomes "/a/c")
     *
     * @param string $path the file/directory path to be normalized
     *
     * @return string the normalized file/directory path
     */
    public static function normalizePath(string $path): string
    {
        $isWindowsShare = strpos($path, '\\\\') === 0;

        if ($isWindowsShare) {
            $path = substr($path, 2);
        }

        $path = rtrim(strtr($path, '/\\', '//'), '/');

        if (strpos('/' . $path, '/.') === false && strpos($path, '//') === false) {
            return $isWindowsShare ? "\\\\$path" : $path;
        }

        $parts = [];

        foreach (explode('/', $path) as $part) {
            if ($part === '..' && !empty($parts) && end($parts) !== '..') {
                array_pop($parts);
            } elseif ($part !== '.' && ($part !== '' || empty($parts))) {
                $parts[] = $part;
            }
        }

        $path = implode('/', $parts);

        if ($isWindowsShare) {
            $path = '\\\\' . $path;
        }

        return $path === '' ? '.' : $path;
    }

    /**
     * Removes a directory (and all its content) recursively.
     *
     * @param string $directory the directory to be deleted recursively.
     * @param array $options options for directory remove ({@see clearDirectory()}).
     */
    public static function removeDirectory(string $directory, array $options = []): void
    {
        try {
            static::clearDirectory($directory, $options);
        } catch (InvalidArgumentException $e) {
            return;
        }

        if (is_link($directory)) {
            self::unlink($directory);
        } else {
            rmdir($directory);
        }
    }

    /**
     * Clear all directory content.
     *
     * @param string $directory the directory to be cleared.
     * @param array $options options for directory clear . Valid options are:
     *
     * - traverseSymlinks: boolean, whether symlinks to the directories should be traversed too.
     *   Defaults to `false`, meaning the content of the symlinked directory would not be deleted.
     *   Only symlink would be removed in that default case.
     *
     * @throws InvalidArgumentException if unable to open directory
     */
    public static function clearDirectory(string $directory, array $options = []): void
    {
        $handle = static::openDirectory($directory);
        if (!empty($options['traverseSymlinks']) || !is_link($directory)) {
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $directory . '/' . $file;
                if (is_dir($path)) {
                    self::removeDirectory($path, $options);
                } else {
                    self::unlink($path);
                }
            }
            closedir($handle);
        }
    }

    /**
     * Removes a file or symlink in a cross-platform way.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function unlink(string $path): bool
    {
        $isWindows = DIRECTORY_SEPARATOR === '\\';

        if (!$isWindows) {
            return unlink($path);
        }

        if (is_link($path) && is_dir($path)) {
            return rmdir($path);
        }

        if (file_exists($path) && !is_writable($path)) {
            chmod($path, 0777);
        }

        return unlink($path);
    }

    /**
     * Tells whether the path is a empty directory
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isEmptyDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        return !(new FilesystemIterator($path))->valid();
    }

    /**
     * Copies a whole directory as another one.
     *
     * The files and sub-directories will also be copied over.
     *
     * @param string $source the source directory.
     * @param string $destination the destination directory.
     * @param array $options options for directory copy. Valid options are:
     *
     * - dirMode: integer, the permission to be set for newly copied directories. Defaults to 0775.
     * - fileMode: integer, the permission to be set for newly copied files. Defaults to the current environment
     *   setting.
     * - filter: a filter to apply while copying files. It should be an instance of {@see PathMatcherInterface}.
     * - recursive: boolean, whether the files under the subdirectories should also be copied. Defaults to true.
     * - beforeCopy: callback, a PHP callback that is called before copying each sub-directory or file. If the callback
     *   returns false, the copy operation for the sub-directory or file will be cancelled. The signature of the
     *   callback should be: `function ($from, $to)`, where `$from` is the sub-directory or file to be copied from,
     *   while `$to` is the copy target.
     * - afterCopy: callback, a PHP callback that is called after each sub-directory or file is successfully copied.
     *   The signature of the callback should be: `function ($from, $to)`, where `$from` is the sub-directory or file
     *   copied from, while `$to` is the copy target.
     * - copyEmptyDirectories: boolean, whether to copy empty directories. Set this to false to avoid creating
     *   directories that do not contain files. This affects directories that do not contain files initially as well as
     *   directories that do not contain files at the target destination because files have been filtered via `only` or
     *   `except`. Defaults to true.
     *
     * @throws InvalidArgumentException if unable to open directory
     * @throws Exception
     *
     * @psalm-param array{
     *   dirMode?: int,
     *   fileMode?: int,
     *   filter?: \Yiisoft\Files\PathMatcher\PathMatcherInterface,
     *   recursive?: bool,
     *   beforeCopy?: callable,
     *   afterCopy?: callable,
     *   copyEmptyDirectories?: bool,
     * } $options
     */
    public static function copyDirectory(string $source, string $destination, array $options = []): void
    {
        $source = static::normalizePath($source);
        $destination = static::normalizePath($destination);

        static::assertNotSelfDirectory($source, $destination);

        $destinationExists = is_dir($destination);
        if (
            !$destinationExists &&
            (!isset($options['copyEmptyDirectories']) || $options['copyEmptyDirectories'])
        ) {
            static::createDirectory($destination, $options['dirMode'] ?? 0775);
            $destinationExists = true;
        }

        $handle = static::openDirectory($source);

        if (!isset($options['basePath'])) {
            $options['basePath'] = realpath($source);
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $from = $source . '/' . $file;
            $to = $destination . '/' . $file;

            if (!isset($options['filter']) || $options['filter']->match($from)) {
                if (is_file($from)) {
                    if (!$destinationExists) {
                        static::createDirectory($destination, $options['dirMode'] ?? 0775);
                        $destinationExists = true;
                    }
                    copy($from, $to);
                    if (isset($options['fileMode'])) {
                        chmod($to, $options['fileMode']);
                    }
                } elseif (!isset($options['recursive']) || $options['recursive']) {
                    static::copyDirectory($from, $to, $options);
                }
            }
        }

        closedir($handle);
    }

    /**
     * Check copy it self directory.
     *
     * @param string $source
     * @param string $destination
     *
     * @throws InvalidArgumentException
     */
    private static function assertNotSelfDirectory(string $source, string $destination): void
    {
        if ($source === $destination || strpos($destination, $source . '/') === 0) {
            throw new InvalidArgumentException('Trying to copy a directory to itself or a subdirectory.');
        }
    }

    /**
     * Open directory handle.
     *
     * @param string $directory
     *
     * @throws InvalidArgumentException
     *
     * @return resource
     */
    private static function openDirectory(string $directory)
    {
        $handle = @opendir($directory);

        if ($handle === false) {
            throw new InvalidArgumentException("Unable to open directory: $directory");
        }

        return $handle;
    }

    /**
     * Returns the last modification time for the given path.
     *
     * If the path is a directory, any nested files/directories will be checked as well.
     *
     * @param string ...$paths the directory to be checked
     *
     * @throws LogicException when path not set
     *
     * @return int Unix timestamp representing the last modification time
     */
    public static function lastModifiedTime(string ...$paths): int
    {
        if (empty($paths)) {
            throw new LogicException('Path is required.');
        }

        $times = [];

        foreach ($paths as $path) {
            $times[] = static::modifiedTime($path);

            if (is_file($path)) {
                continue;
            }

            /** @var iterable<string, string> $iterator */
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $p => $info) {
                $times[] = static::modifiedTime($p);
            }
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        return max($times);
    }

    private static function modifiedTime(string $path): int
    {
        return (int)filemtime($path);
    }

    /**
     * Returns the directories found under the specified directory and subdirectories.
     *
     * @param string $directory the directory under which the files will be looked for.
     * @param array $options options for directory searching. Valid options are:
     *
     * - filter: a filter to apply while looked directories. It should be an instance of {@see PathMatcherInterface}.
     * - recursive: boolean, whether the subdirectories should also be looked for. Defaults to `true`.
     *
     * @psalm-param array{
     *   filter?: \Yiisoft\Files\PathMatcher\PathMatcherInterface,
     *   recursive?: bool,
     * } $options
     *
     * @throws InvalidArgumentException if the directory is invalid.
     *
     * @return string[] directories found under the directory, in no particular order.
     * Ordering depends on the files system used.
     */
    public static function findDirectories(string $directory, array $options = []): array
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException("The \"directory\" argument must be a directory: $directory");
        }
        $directory = static::normalizePath($directory);

        $result = [];

        $handle = static::openDirectory($directory);
        while (false !== $file = readdir($handle)) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $directory . '/' . $file;
            if (is_file($path)) {
                continue;
            }

            if (!isset($options['filter']) || $options['filter']->match($path)) {
                $result[] = $path;
            }

            if (!isset($options['recursive']) || $options['recursive']) {
                $result = array_merge($result, static::findDirectories($path, $options));
            }
        }
        closedir($handle);

        return $result;
    }

    /**
     * Returns the files found under the specified directory and subdirectories.
     *
     * @param string $directory the directory under which the files will be looked for.
     * @param array $options options for file searching. Valid options are:
     *
     * - filter: a filter to apply while looked files. It should be an instance of {@see PathMatcherInterface}.
     * - recursive: boolean, whether the files under the subdirectories should also be looked for. Defaults to `true`.
     *
     * @psalm-param array{
     *   filter?: \Yiisoft\Files\PathMatcher\PathMatcherInterface,
     *   recursive?: bool,
     * } $options
     *
     * @throws InvalidArgumentException if the dir is invalid.
     *
     * @return array files found under the directory, in no particular order.
     * Ordering depends on the files system used.
     */
    public static function findFiles(string $directory, array $options = []): array
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException("The \"directory\" argument must be a directory: $directory");
        }
        $directory = static::normalizePath($directory);

        $result = [];

        $handle = static::openDirectory($directory);
        while (false !== $file = readdir($handle)) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $directory . '/' . $file;

            if (is_file($path)) {
                if (!isset($options['filter']) || $options['filter']->match($path)) {
                    $result[] = $path;
                }
                continue;
            }

            if (!isset($options['recursive']) || $options['recursive']) {
                $result = array_merge($result, static::findFiles($path, $options));
            }
        }
        closedir($handle);

        return $result;
    }
}
