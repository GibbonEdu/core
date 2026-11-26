<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Filesystem;

/**
 * Validates filesystem paths and performs safe filesystem operations.
 *
 * @see FilesystemTest
 */
class Filesystem
{
    /**
     * @var array<int, string>
     */
    public const DEFAULT_OPERATIONS = ['read', 'write', 'create', 'rename', 'delete', 'mkdir', 'rmdir'];

    /**
     * Check whether a safe sibling location outside an application root can be used.
     *
     * The root must be an existing, non-symlink directory with an existing,
     * non-symlink parent. The parent is probed for read, write, create, rename,
     * delete, mkdir, and rmdir support. Probe entries are randomized and are
     * removed by probeDirectory() before this method returns.
     *
     * @param string $root
     * @return bool
     */
    public function checkParentDirectory(string $root): bool
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        if ($root === '' || !is_dir($root) || is_link($root)) {
            return false;
        }

        $rootReal = realpath($root);
        $parent = dirname($root);
        $parentReal = realpath($parent);

        if ($rootReal === false
            || $parent === $root
            || $parentReal === false
            || $parentReal === DIRECTORY_SEPARATOR
            || !is_dir($parentReal)
            || is_link($parent)
            || $this->isInside($parentReal, $rootReal, true)) {
            return false;
        }

        return $this->probeDirectory($parentReal, self::DEFAULT_OPERATIONS);
    }

    /**
     * Return a preferred directory below the parent of a root when that
     * location is usable, otherwise return the existing in-root fallback.
     *
     * This method performs no filesystem mutation beyond the temporary probe
     * performed by checkParentDirectory().
     *
     * @param string $root
     * @param string $relativePath
     * @return string
     */
    public function getSecureDirectory(string $root, string $relativePath): string
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        $fallback = $root.'/'.$relativePath;

        if (!$this->checkParentDirectory($root)) {
            return $fallback;
        }

        $preferredRoot = dirname(realpath($root));
        $preferred = $preferredRoot.'/'.$relativePath;
        if ($this->containsSymlink($preferredRoot, $relativePath)) {
            return $fallback;
        }

        return $preferred;
    }

    /**
     * Return whether a path component below a trusted directory is a symlink.
     *
     * @param string $root
     * @param string $relativePath
     * @return bool
     */
    private function containsSymlink(string $root, string $relativePath): bool
    {
        $current = rtrim($root, '/');
        foreach (explode('/', trim($relativePath, '/')) as $component) {
            if ($component === '' || $component === '.') {
                continue;
            }
            if ($component === '..' || strpos($component, "\0") !== false) {
                return true;
            }

            $current .= '/'.$component;
            if (is_link($current)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check that each supplied directory supports the requested operations.
     * Missing directories are created when the create operation is requested.
     *
     * @param array<int, string> $paths
     * @param array<int, string> $operations
     * @return bool
     */
    public function checkPermissions(array $paths, array $operations = self::DEFAULT_OPERATIONS): bool
    {
        foreach ($paths as $path) {
            if (!is_string($path) || $path === '') {
                return false;
            }

            if (!is_dir($path) && in_array('create', $operations, true) && !$this->ensureDirectory($path)) {
                return false;
            }

            if (!$this->probeDirectory($path, $operations)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Probe caller-selected operations in a directory using temporary entries.
     *
     * Supported operations are read, write, create, rename, delete, mkdir, and
     * rmdir. The temporary entries are always removed before this method
     * returns.
     *
     * @param string $directory
     * @param array<int, string> $operations
     * @return bool
     */
    public function probeDirectory(string $directory, array $operations = self::DEFAULT_OPERATIONS): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $operations = array_values(array_unique($operations));
        $supported = ['read', 'write', 'create', 'rename', 'delete', 'mkdir', 'rmdir'];
        if (array_diff($operations, $supported) !== []) {
            return false;
        }
        if (in_array('read', $operations, true) && !is_readable($directory)) {
            return false;
        }
        if (in_array('write', $operations, true) && !is_writable($directory)) {
            return false;
        }

        try {
            $token = bin2hex(random_bytes(8));
        } catch (\Throwable $error) {
            return false;
        }

        $file = $directory.'/.gibbon-filesystem-probe-'.$token;
        $renamedFile = $file.'.renamed';
        $probeDirectory = $directory.'/.gibbon-filesystem-probe-'.$token.'.dir';
        $renamedDirectory = $probeDirectory.'.renamed';
        $handle = null;

        try {
            if (in_array('create', $operations, true)) {
                $handle = @fopen($file, 'x');
                if ($handle === false || @fwrite($handle, 'filesystem permission probe') === false) {
                    return false;
                }
                fclose($handle);
                $handle = null;
            }

            if (in_array('rename', $operations, true)) {
                if (!is_file($file) || !@rename($file, $renamedFile)) {
                    return false;
                }
            }

            if (in_array('delete', $operations, true)) {
                $path = is_file($renamedFile) ? $renamedFile : $file;
                if (!is_file($path) || !@unlink($path)) {
                    return false;
                }
            }

            if (in_array('mkdir', $operations, true)) {
                if (!@mkdir($probeDirectory, 0755) || !@rename($probeDirectory, $renamedDirectory)) {
                    return false;
                }
            }

            if (in_array('rmdir', $operations, true)) {
                $path = is_dir($renamedDirectory) ? $renamedDirectory : $probeDirectory;
                if (!is_dir($path) || !@rmdir($path)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $error) {
            return false;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (is_file($file)) {
                @unlink($file);
            }
            if (is_file($renamedFile)) {
                @unlink($renamedFile);
            }
            if (is_dir($probeDirectory)) {
                $this->deleteDirectory($probeDirectory);
            }
            if (is_dir($renamedDirectory)) {
                $this->deleteDirectory($renamedDirectory);
            }
        }
    }

    /**
     * Read the version number from a Gibbon version.php file without running it.
     *
     * @param string $versionFile
     * @return string|null The version, or null when the file is missing or has none.
     */
    public function readVersion(string $versionFile): ?string
    {
        if (!is_file($versionFile) || is_link($versionFile)) {
            return null;
        }

        $contents = (string) @file_get_contents($versionFile);

        return preg_match('/\$version\s*=\s*([\'"])(.+?)\1\s*;/', $contents, $matches) === 1
            ? $matches[2]
            : null;
    }

    /**
     * Return the free disk space in bytes for a directory, or null when it cannot be determined.
     *
     * @param string $directory
     * @return float|null
     */
    public function getFreeSpace(string $directory): ?float
    {
        $free = @disk_free_space($directory);

        return $free === false ? null : $free;
    }

    /**
     * Ensure a directory exists.
     *
     * @param string $directory
     * @return bool
     */
    public function ensureDirectory(string $directory): bool
    {
        return is_dir($directory) || @mkdir($directory, 0755, true);
    }

    /**
     * Copy a file and create its parent directory when needed.
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function copyFile(string $source, string $destination): bool
    {
        return $this->ensureDirectory(dirname($destination))
            && @copy($source, $destination)
            && is_file($destination);
    }

    /**
     * Copy a file directly onto a destination, replacing a destination symlink.
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function replaceFile(string $source, string $destination): bool
    {
        if (!is_file($source) || !$this->ensureDirectory(dirname($destination))) {
            return false;
        }

        if (is_link($destination) && !@unlink($destination)) {
            return false;
        }

        return @copy($source, $destination) && is_file($destination);
    }

    /**
     * Recursively copy a directory without following symlinks.
     *
     * The destination must not exist. When supplied, the callback receives the
     * source path of each successfully copied file.
     *
     * @param string $source
     * @param string $destination
     * @param callable|null $onFileCopied
     * @return bool
     */
    public function copyDirectory(string $source, string $destination, ?callable $onFileCopied = null): bool
    {
        if (!is_dir($source) || is_link($source) || file_exists($destination) || is_link($destination)) {
            return false;
        }

        if (!@mkdir($destination, 0755, true)) {
            return false;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isLink()) {
                    $this->deleteDirectory($destination);
                    return false;
                }

                $relative = substr(
                    str_replace('\\', '/', $fileInfo->getPathname()),
                    strlen(rtrim(str_replace('\\', '/', $source), '/')) + 1
                );
                $target = $destination.'/'.$relative;

                if ($fileInfo->isDir()) {
                    if (!$this->ensureDirectory($target)) {
                        $this->deleteDirectory($destination);
                        return false;
                    }
                    continue;
                }

                if (!$fileInfo->isFile() || !@copy($fileInfo->getPathname(), $target) || !is_file($target)) {
                    $this->deleteDirectory($destination);
                    return false;
                }

                if ($onFileCopied !== null) {
                    $onFileCopied($fileInfo->getPathname());
                }
            }
        } catch (\Throwable $error) {
            $this->deleteDirectory($destination);
            return false;
        }

        return true;
    }

    /**
     * Copy a directory beside its destination, then swap it into place.
     *
     * @param string $source
     * @param string $destination
     * @param callable|null $onFileCopied
     * @return bool
     */
    public function replaceDirectory(string $source, string $destination, ?callable $onFileCopied = null): bool
    {
        if (!is_dir($source) || is_link($source) || !$this->ensureDirectory(dirname($destination))) {
            return false;
        }

        try {
            $suffix = bin2hex(random_bytes(8));
        } catch (\Throwable $error) {
            return false;
        }

        $tempPath = $destination.'.fileupdate.'.$suffix.'.tmp';
        $previousPath = $destination.'.fileupdate.'.$suffix.'.previous';

        if (!$this->copyDirectory($source, $tempPath, $onFileCopied)) {
            return false;
        }

        $hadDestination = file_exists($destination) || is_link($destination);
        if ($hadDestination && (!is_dir($destination) || is_link($destination) || !@rename($destination, $previousPath))) {
            $this->deleteDirectory($tempPath);
            return false;
        }

        if (!@rename($tempPath, $destination) || !is_dir($destination)) {
            $this->deleteDirectory($tempPath);
            if ($hadDestination) {
                @rename($previousPath, $destination);
            }
            return false;
        }

        if ($hadDestination && !$this->deleteDirectory($previousPath)) {
            return false;
        }

        return true;
    }

    /**
     * Remove a regular file or symlink when it exists.
     *
     * @param string $path
     * @return bool
     */
    public function deleteIfExists(string $path): bool
    {
        if ($path === '' || (!is_file($path) && !is_link($path))) {
            return true;
        }

        return @unlink($path);
    }

    /**
     * Recursively delete a directory and its contents, or unlink a file or
     * symlink. Returns true when the path was removed or did not exist.
     *
     * @param string $directory
     * @return bool
     */
    public function deleteDirectory(string $directory): bool
    {
        if (is_link($directory) || is_file($directory)) {
            return @unlink($directory);
        }

        if (!is_dir($directory)) {
            return true;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $fileInfo) {
                $deleted = $fileInfo->isLink() || $fileInfo->isFile()
                    ? @unlink($fileInfo->getPathname())
                    : @rmdir($fileInfo->getPathname());
                if (!$deleted) {
                    return false;
                }
            }
        } catch (\Throwable $error) {
            return false;
        }

        return @rmdir($directory);
    }

    /**
     * Return whether a path is inside a root directory.
     *
     * @param string $path
     * @param string $root
     * @param bool $allowEqual
     * @return bool
     */
    public function isInside(string $path, string $root, bool $allowEqual = false): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $root = rtrim(str_replace('\\', '/', $root), '/');

        if ($path === $root) {
            return $allowEqual && $root !== '';
        }

        return $root !== '' && str_starts_with($path, $root.'/');
    }

    /**
     * Return whether a path is safe to join to a root directory.
     *
     * @param string $path
     * @return bool
     */
    public function isSafeRelativePath(string $path): bool
    {
        $path = str_replace('\\', '/', $path);
        $lower = strtolower($path);

        return $path !== ''
            && !str_starts_with($path, '/')
            && strpos($path, "\0") === false
            && strpos($path, '..') === false
            && !(strlen($path) > 1 && $path[1] === ':' && ctype_alpha($path[0]))
            && $lower !== 'config.php'
            && !str_starts_with($lower, 'backups/')
            && !str_starts_with($lower, '.git/');
    }

    /**
     * Remove directory contents while preserving the directory itself.
     *
     * Missing directories are treated as already empty. The method never
     * follows directory symlinks: nested symlinks are unlinked and a symlink
     * supplied as the root directory is rejected.
     *
     * @param string $directory
     * @return bool
     */
    public function wipeDirectory(string $directory): bool
    {
        if (is_link($directory)) {
            return false;
        }

        if (!file_exists($directory)) {
            return true;
        }

        if (!is_dir($directory)) {
            return false;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $fileInfo) {
                $deleted = $fileInfo->isLink() || $fileInfo->isFile()
                    ? @unlink($fileInfo->getPathname())
                    : @rmdir($fileInfo->getPathname());

                if (!$deleted) {
                    return false;
                }
            }
        } catch (\Throwable $error) {
            return false;
        }

        return true;
    }
}
