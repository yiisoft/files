<?php
declare(strict_types = 1);

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
     *
     * @return void
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
                    self::removeDirectory($path, $options);
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
     * Removes a file or symlink in a cross-platform way.
     *
     * @param string $path
     *
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
     * - fileMode:  integer, the permission to be set for newly copied files. Defaults to the current environment
     *   setting.
     * - filter: callback, a PHP callback that is called for each directory or file.
     *   The signature of the callback should be: `function ($path)`, where `$path` refers the full path to be filtered.
     *   The callback can return one of the following values:
     *
     *   * true: the directory or file will be copied (the "only" and "except" options will be ignored).
     *   * false: the directory or file will NOT be copied (the "only" and "except" options will be ignored).
     *   * null: the "only" and "except" options will determine whether the directory or file should be copied.
     *
     * - only: array, list of patterns that the file paths should match if they want to be copied. A path matches a
     *   pattern if it contains the pattern string at its end. For example, '.php' matches all file paths ending with
     *   '.php'.
     *   Note, the '/' characters in a pattern matches both '/' and '\' in the paths. If a file path matches a pattern
     *   in both "only" and "except", it will NOT be copied.
     * - except: array, list of patterns that the files or directories should match if they want to be excluded from
     *   being copied. A path matches a pattern if it contains the pattern string at its end. Patterns ending with '/'
     *   apply to directory paths only, and patterns not ending with '/' apply to file paths only. For example, '/a/b'
     *   matches all file paths ending with '/a/b'; and '.svn/' matches directory paths ending with '.svn'. Note, the
     *   '/' characters in a pattern matches both '/' and '\' in the paths.
     * - caseSensitive: boolean, whether patterns specified at "only" or "except" should be case sensitive. Defaults to
     *   true.
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
     * @throws \InvalidArgumentException if unable to open directory
     * @throws \Exception
     *
     * @return void
     */
    public static function copyDirectory(string $source, string $destination, $options = []): void
    {
        $source = static::normalizePath($source);
        $destination = static::normalizePath($destination);

        if ($source === $destination || strpos($destination, $source . '/') === 0) {
            throw new \InvalidArgumentException('Trying to copy a directory to itself or a subdirectory.');
        }

        $destinationExists = is_dir($destination);

        if (!$destinationExists && (!isset($options['copyEmptyDirectories']) || $options['copyEmptyDirectories'])) {
            static::createDirectory($destination, $options['dirMode'] ?? 0775, true);
            $destinationExists = true;
        }

        $handle = opendir($source);

        if ($handle === false) {
            throw new \InvalidArgumentException("Unable to open directory: $source");
        }

        if (!isset($options['basePath'])) {
            // this should be done only once
            $options['basePath'] = realpath($source);
            $options = static::normalizeOptions($options);
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $from = $source . '/' . $file;
            $to = $destination . '/' . $file;
            if (static::filterPath($from, $options)) {
                if (isset($options['beforeCopy']) && !\call_user_func($options['beforeCopy'], $from, $to)) {
                    continue;
                }
                if (is_file($from)) {
                    if (!$destinationExists) {
                        // delay creation of destination directory until the first file is copied to avoid creating empty directories
                        static::createDirectory($destination, $options['dirMode'] ?? 0775, true);
                        $destinationExists = true;
                    }
                    copy($from, $to);
                    if (isset($options['fileMode'])) {
                        @chmod($to, $options['fileMode']);
                    }
                } elseif (!isset($options['recursive']) || $options['recursive']) {
                    // recursive copy, defaults to true
                    static::copyDirectory($from, $to, $options);
                }
                if (isset($options['afterCopy'])) {
                    \call_user_func($options['afterCopy'], $from, $to);
                }
            }
        }

        closedir($handle);
    }

    /**
     * Normalize options.
     *
     * @param array $options raw options.
     *
     * @return array normalized options.
     */
    protected static function normalizeOptions(array $options): array
    {
        if (!array_key_exists('caseSensitive', $options)) {
            $options['caseSensitive'] = true;
        }

        if (isset($options['except'])) {
            foreach ($options['except'] as $key => $value) {
                if (\is_string($value)) {
                    $options['except'][$key] = self::parseExcludePattern($value, $options['caseSensitive']);
                }
            }
        }

        if (isset($options['only'])) {
            foreach ($options['only'] as $key => $value) {
                if (\is_string($value)) {
                    $options['only'][$key] = self::parseExcludePattern($value, $options['caseSensitive']);
                }
            }
        }

        return $options;
    }

    /**
     * Checks if the given file path satisfies the filtering options.
     *
     * @param string $path the path of the file or directory to be checked.
     * @param array $options the filtering options.
     *
     * @return bool whether the file or directory satisfies the filtering options.
     */
    public static function filterPath($path, $options): bool
    {
        if (isset($options['filter'])) {
            $result = \call_user_func($options['filter'], $path);
            if (\is_bool($result)) {
                return $result;
            }
        }

        if (empty($options['except']) && empty($options['only'])) {
            return true;
        }

        $path = str_replace('\\', '/', $path);

        if (!empty($options['except'])) {
            $except = self::lastExcludeMatchingFromList($options['basePath'], $path, $options['except']);
            if ($except !== null) {
                return $except['flags'] & self::PATTERN_NEGATIVE;
            }
        }

        if (!empty($options['only']) && !is_dir($path)) {
            // don't check PATTERN_NEGATIVE since those entries are not prefixed with !
            return self::lastExcludeMatchingFromList($options['basePath'], $path, $options['only']) !== null;
        }

        return true;
    }
}
