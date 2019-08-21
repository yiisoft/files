<?php
declare(strict_types = 1);

namespace Yiisoft\Files;

use Yiisoft\Strings\StringHelper;

/**
 * FileHelper provides useful methods to manage files and directories
 */
class FileHelper
{
    /**
     * @var int PATTERN_NO_DIR
     */
    private const PATTERN_NO_DIR = 1;

    /**
     * @var int PATTERN_ENDS_WITH
     */
    private const PATTERN_ENDS_WITH = 4;

    /**
     * @var int PATTERN_MUST_BE_DIR
     */
    private const PATTERN_MUST_BE_DIR = 8;

    /**
     * @var int PATTERN_NEGATIVE
     */
    private const PATTERN_NEGATIVE = 16;

    /**
     * @var int PATTERN_CASE_INSENSITIVE
     */
    private const PATTERN_CASE_INSENSITIVE = 32;

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
        } catch (\Exception $e) {
            if (!is_dir($path)) {
                throw new \RuntimeException(
                    "Failed to create directory \"$path\": " . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        }

        return static::chmod($path, $mode);
    }

    /**
     * Set permissions directory.
     *
     * @param string $path
     * @param integer $mode
     *
     * @throws \RuntimeException
     *
     * @return boolean|null
     */
    private static function chmod(string $path, int $mode): ?bool
    {
        try {
            return chmod($path, $mode);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Failed to change permissions for directory \"$path\": " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
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
    public static function removeDirectory(string $directory, array $options = []): void
    {
        if (!empty($options['traverseSymlinks']) || !is_link($directory)) {
            if (!($handle = @opendir($directory))) {
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
    public static function unlink(string $path): bool
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
    public static function copyDirectory(string $source, string $destination, array $options = []): void
    {
        $source = static::normalizePath($source);
        $destination = static::normalizePath($destination);

        static::isSelfDirectory($source, $destination);

        $destinationExists = static::setDestination($destination, $options);

        $handle = static::isSourceDirectory($source);

        $options = static::setBasePath($source, $options);

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $from = $source . '/' . $file;
            $to = $destination . '/' . $file;

            if (static::filterPath($from, $options)) {
                if (is_file($from)) {
                    if (!$destinationExists) {
                        static::createDirectory($destination, $options['dirMode'] ?? 0775);
                        $destinationExists = true;
                    }
                    copy($from, $to);
                    if (isset($options['fileMode'])) {
                        @chmod($to, $options['fileMode']);
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
     * @throws \InvalidArgumentException
     *
     * @return boolean
     */
    private static function isSelfDirectory(string $source, string $destination)
    {
        if ($source === $destination || strpos($destination, $source . '/') === 0) {
            throw new \InvalidArgumentException('Trying to copy a directory to itself or a subdirectory.');
        }
    }

    /**
     * Undocumented function
     *
     * @param string $source
     *
     * @throws \InvalidArgumentException
     *
     * @return resource
     */
    private static function isSourceDirectory(string $source)
    {
        $handle = @opendir($source);

        if ($handle === false) {
            throw new \InvalidArgumentException("Unable to open directory: $source");
        }

        return $handle;
    }

    /**
     * Set base path directory.
     *
     * @param string $source
     * @param array $options
     *
     * @return array
     */
    private static function setBasePath(string $source, array $options): array
    {
        if (!isset($options['basePath'])) {
            // this should be done only once
            $options['basePath'] = realpath($source);
            $options = static::normalizeOptions($options);
        }

        return $options;
    }

    /**
     * Set destination directory.
     *
     * @param string $destination
     * @param array $options
     *
     * @return bool
     */
    private static function setDestination(string $destination, array $options): bool
    {
        $destinationExists = is_dir($destination);

        if (!$destinationExists && (!isset($options['copyEmptyDirectories']) || $options['copyEmptyDirectories'])) {
            static::createDirectory($destination, $options['dirMode'] ?? 0775);
            $destinationExists = true;
        }

        return $destinationExists;
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
    public static function filterPath(string $path, array $options): bool
    {
        $path = str_replace('\\', '/', $path);

        if (!empty($options['except'])) {
            if ((self::lastExcludeMatchingFromList($options['basePath'], $path, $options['except'])) !== null) {
                return false;
            }
        }

        if (!empty($options['only']) && !is_dir($path)) {
            // don't check PATTERN_NEGATIVE since those entries are not prefixed with !
            return self::lastExcludeMatchingFromList($options['basePath'], $path, $options['only']) !== null;
        }

        return true;
    }

    /**
     * Searches for the first wildcard character in the pattern.
     *
     * @param string $pattern the pattern to search in.
     *
     * @return int|bool position of first wildcard character or false if not found.
     */
    private static function firstWildcardInPattern(string $pattern)
    {
        $wildcards = ['*', '?', '[', '\\'];
        $wildcardSearch = function ($carry, $item) use ($pattern) {
            $position = strpos($pattern, $item);
            if ($position === false) {
                return $carry === false ? $position : $carry;
            }
            return $carry === false ? $position : min($carry, $position);
        };
        return array_reduce($wildcards, $wildcardSearch, false);
    }


    /**
     * Scan the given exclude list in reverse to see whether pathname should be ignored.
     *
     * The first match (i.e. the last on the list), if any, determines the fate.  Returns the element which matched,
     * or null for undecided.
     *
     * Based on last_exclude_matching_from_list() from dir.c of git 1.8.5.3 sources.
     *
     * @param string $basePath.
     * @param string $path.
     * @param array $excludes list of patterns to match $path against.
     *
     * @return null|array null or one of $excludes item as an array with keys: 'pattern', 'flags'.
     *
     * @throws \InvalidArgumentException if any of the exclude patterns is not a string or an array with keys: pattern,
     *                                   flags, firstWildcard.
     */
    private static function lastExcludeMatchingFromList(string $basePath, string $path, array $excludes): ?array
    {
        foreach (array_reverse($excludes) as $exclude) {
            if (\is_string($exclude)) {
                $exclude = self::parseExcludePattern($exclude, false);
            }

            if (!isset($exclude['pattern'], $exclude['flags'], $exclude['firstWildcard'])) {
                throw new \InvalidArgumentException(
                    'If exclude/include pattern is an array it must contain the pattern, flags and firstWildcard keys.'
                );
            }

            if (($exclude['flags'] & self::PATTERN_MUST_BE_DIR) && !is_dir($path)) {
                continue;
            }

            if ($exclude['flags'] & self::PATTERN_NO_DIR) {
                if (self::matchBasename(basename($path), $exclude['pattern'], $exclude['firstWildcard'], $exclude['flags'])) {
                    return $exclude;
                }
                continue;
            }

            if (self::matchPathname($path, $basePath, $exclude['pattern'], $exclude['firstWildcard'], $exclude['flags'])) {
                return $exclude;
            }
        }

        return null;
    }

    /**
     * Performs a simple comparison of file or directory names.
     *
     * Based on match_basename() from dir.c of git 1.8.5.3 sources.
     *
     * @param string $baseName file or directory name to compare with the pattern.
     * @param string $pattern the pattern that $baseName will be compared against.
     * @param int|bool $firstWildcard location of first wildcard character in the $pattern.
     * @param int $flags pattern flags
     *
     * @return bool whether the name matches against pattern
     */
    private static function matchBasename(string $baseName, string $pattern, $firstWildcard, int $flags): bool
    {
        if ($firstWildcard === false) {
            if ($pattern === $baseName) {
                return true;
            }
        } elseif ($flags & self::PATTERN_ENDS_WITH) {
            /* "*literal" matching against "fooliteral" */
            $n = StringHelper::byteLength($pattern);
            if (StringHelper::byteSubstr($pattern, 1, $n) === StringHelper::byteSubstr($baseName, -$n, $n)) {
                return true;
            }
        }

        $matchOptions = [];

        if ($flags & self::PATTERN_CASE_INSENSITIVE) {
            $matchOptions['caseSensitive'] = false;
        }

        return StringHelper::matchWildcard($pattern, $baseName, $matchOptions);
    }

    /**
     * Compares a path part against a pattern with optional wildcards.
     *
     * Based on match_pathname() from dir.c of git 1.8.5.3 sources.
     *
     * @param string $path full path to compare
     * @param string $basePath base of path that will not be compared
     * @param string $pattern the pattern that path part will be compared against
     * @param int|bool $firstWildcard location of first wildcard character in the $pattern
     * @param int $flags pattern flags
     *
     * @return bool whether the path part matches against pattern
     */
    private static function matchPathname(string $path, string $basePath, string $pattern, $firstWildcard, int $flags): bool
    {
        // match with FNM_PATHNAME; the pattern has base implicitly in front of it.
        if (strpos($pattern, '/') === 0) {
            $pattern = StringHelper::byteSubstr($pattern, 1, StringHelper::byteLength($pattern));
            if ($firstWildcard !== false && $firstWildcard !== 0) {
                $firstWildcard--;
            }
        }

        $namelen = StringHelper::byteLength($path) - (empty($basePath) ? 0 : StringHelper::byteLength($basePath) + 1);
        $name = StringHelper::byteSubstr($path, -$namelen, $namelen);

        if ($firstWildcard !== 0) {
            if ($firstWildcard === false) {
                $firstWildcard = StringHelper::byteLength($pattern);
            }

            // if the non-wildcard part is longer than the remaining pathname, surely it cannot match.
            if ($firstWildcard > $namelen) {
                return false;
            }

            if (strncmp($pattern, $name, $firstWildcard)) {
                return false;
            }

            $pattern = StringHelper::byteSubstr($pattern, $firstWildcard, StringHelper::byteLength($pattern));
            $name = StringHelper::byteSubstr($name, $firstWildcard, $namelen);

            // If the whole pattern did not have a wildcard, then our prefix match is all we need; we do not need to call fnmatch at all.
            if (empty($pattern) && empty($name)) {
                return true;
            }
        }

        $matchOptions = [
            'filePath' => true
        ];

        if ($flags & self::PATTERN_CASE_INSENSITIVE) {
            $matchOptions['caseSensitive'] = false;
        }

        return StringHelper::matchWildcard($pattern, $name, $matchOptions);
    }

    /**
     * Processes the pattern, stripping special characters like / and ! from the beginning and settings flags instead.
     *
     * @param string $pattern
     * @param bool $caseSensitive
     *
     * @return array with keys: (string) pattern, (int) flags, (int|bool) firstWildcard
     */
    private static function parseExcludePattern(string $pattern, bool $caseSensitive): array
    {
        $result = [
            'pattern' => $pattern,
            'flags' => 0,
            'firstWildcard' => false,
        ];

        $result = static::isCaseInsensitive($caseSensitive, $result);

        if (!isset($pattern[0])) {
            return $result;
        }

        if (strpos($pattern, '!') === 0) {
            $result['flags'] |= self::PATTERN_NEGATIVE;
            $pattern = StringHelper::byteSubstr($pattern, 1, StringHelper::byteLength($pattern));
        }

        if (StringHelper::byteLength($pattern) && StringHelper::byteSubstr($pattern, -1, 1) === '/') {
            $pattern = StringHelper::byteSubstr($pattern, 0, -1);
            $result['flags'] |= self::PATTERN_MUST_BE_DIR;
        }

        $result = static::isPatternNoDir($pattern, $result);

        $result['firstWildcard'] = self::firstWildcardInPattern($pattern);

        $result = static::isPatternEndsWith($pattern, $result);

        $result['pattern'] = $pattern;

        return $result;
    }

    /**
     * Check isCaseInsensitive.
     *
     * @param boolean $caseSensitive
     * @param array $result
     *
     * @return array
     */
    private static function isCaseInsensitive(bool $caseSensitive, array $result): array
    {
        if (!$caseSensitive) {
            $result['flags'] |= self::PATTERN_CASE_INSENSITIVE;
        }

        return $result;
    }

    /**
     * Check pattern no directory.
     *
     * @param string $pattern
     * @param array $result
     *
     * @return array
     */
    private static function isPatternNoDir(string $pattern, array $result): array
    {
        if (strpos($pattern, '/') === false) {
            $result['flags'] |= self::PATTERN_NO_DIR;
        }

        return $result;
    }

    /**
     * Check pattern ends with
     *
     * @param string $pattern
     * @param array $result
     *
     * @return array
     */
    private static function isPatternEndsWith(string $pattern, array $result): array
    {
        if (strpos($pattern, '*') === 0 && self::firstWildcardInPattern(StringHelper::byteSubstr($pattern, 1, StringHelper::byteLength($pattern))) === false) {
            $result['flags'] |= self::PATTERN_ENDS_WITH;
        }

        return $result;
    }
}
