<?php
namespace Yiisoft\Files;

/**
 * FileHelper provides useful methods to manage files and directories
 */
class FileHelper
{
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
            if ($isWindowsShare) {
                $path = $path = '\\\\' . $path;
            }
            return $path;
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
     * @param array $options options for directory remove. Valid options are:
     *
     * - traverseSymlinks: boolean, whether symlinks to the directories should be traversed too.
     *   Defaults to `false`, meaning the content of the symlinked directory would not be deleted.
     *   Only symlink would be removed in that default case.
     */
    public static function removeDirectory($directory, $options = []): void
    {
        if (!is_dir($directory)) {
            return;
        }
        if (!empty($options['traverseSymlinks']) || !is_link($directory)) {
            if (!($handle = opendir($directory))) {
                return;
            }
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $directory . '/' . $file;
                if (is_dir($path)) {
                    self::removeDirectory($path);
                } else {
                    self::unlink($path);
                }
            }
            closedir($handle);
        }
        if (is_link($directory)) {
            self::unlink($directory);
        } else {
            rmdir($directory);
        }
    }

    /**
     * Removes a file or symlink in a cross-platform way
     *
     * @param string $path
     * @return bool
     */
    public static function unlink($path): bool
    {
        $isWindows = DIRECTORY_SEPARATOR === '\\';

        if (!$isWindows) {
            return unlink($path);
        }

        if (is_link($path) && is_dir($path)) {
            return rmdir($path);
        }

        return unlink($path);
    }

    /**
     * Creates a new directory
     *
     * This method is similar to the PHP `mkdir()` function except that
     * it uses `chmod()` to set the permission of the created directory
     * in order to avoid the impact of the `umask` setting.
     *
     * @param string $path path of the directory to be created.
     * @param int $mode the permission to be set for the created directory.
     * @return bool whether the directory is created successfully
     */
    public static function createDirectory($path, $mode = 0775): bool
    {
        if (is_dir($path)) {
            return true;
        }
        try {
            if (!mkdir($path, $mode, true) && !is_dir($path)) {
                return false;
            }
        } catch (\Exception $e) {
            if (!is_dir($path)) { // https://github.com/yiisoft/yii2/issues/9288
                throw new \RuntimeException("Failed to create directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
            }
        }

        try {
            return chmod($path, $mode);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to change permissions for directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
