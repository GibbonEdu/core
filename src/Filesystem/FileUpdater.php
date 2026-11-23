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

use Gibbon\Contracts\Services\Session;

/**
 * Backs up live files, replaces them from staging, and rolls back on failure.
 *
 * @version v30
 * @since   v30
 */
class FileUpdater
{
    public const ERROR_INVALID = 'invalid';
    public const ERROR_BACKUP = 'backup';
    public const ERROR_REPLACE = 'replace';
    public const ERROR_ROLLBACK = 'rollback';

    protected $absolutePath;
    protected $added = []; // files that did not exist on live before replace
    protected $backupDir = 'previousVersionBackup';

    public function __construct(Session $session)
    {
        $this->absolutePath = rtrim(str_replace('\\', '/', $session->get('absolutePath')), '/');
    }

    /**
     * Backup existing files, then replace from staging. Roll back on failure.
     *
     * @param array $relativeFiles
     */
    public function run(array $relativeFiles)
    {
        $files = $this->normalizeFileList($relativeFiles);
        $this->backup($files);

        try {
            $this->replace($files);
        } catch (\Throwable $e) {
            // Restore live files from backup, then re-throw the original error
            try {
                $this->rollback($files);
            } catch (\Throwable $rollbackError) {
                $this->fail(self::ERROR_ROLLBACK, 'Rollback failed: '.$rollbackError->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Copy existing live files into previousVersionBackup/.
     *
     * @param array $files
     */
    public function backup(array $files)
    {
        $backupDir = $this->absolutePath.'/'.Downloader::BASE_DIR.'/'.$this->backupDir;
        $this->wipeDirectory($backupDir);

        if (!$this->ensureDirectory($backupDir) || !is_writable($backupDir)) {
            $this->fail(self::ERROR_BACKUP, 'Failed to create writable backup directory: '.$backupDir);
        }

        $this->added = [];

        foreach ($files as $relative) {
            $livePath = $this->absolutePath.'/'.$relative;

            // New file — nothing to back up
            if (!is_file($livePath)) {
                $this->added[] = $relative;
                continue;
            }

            if (!is_readable($livePath) || !$this->copyFile($livePath, $backupDir.'/'.$relative)) {
                $this->fail(self::ERROR_BACKUP, 'Failed to back up: '.$livePath);
            }
        }
    }

    /**
     * Copy staging files onto the live install.
     *
     * @param array $files
     */
    public function replace(array $files)
    {
        $stagingReal = realpath($this->absolutePath.'/'.Downloader::BASE_DIR.'/staging');
        $absoluteReal = realpath($this->absolutePath);

        if ($stagingReal === false || $absoluteReal === false) {
            $this->fail($stagingReal === false ? self::ERROR_REPLACE : self::ERROR_INVALID, 'Failed to resolve real path: '.($stagingReal === false ? $this->absolutePath.'/'.Downloader::BASE_DIR.'/staging' : $this->absolutePath));
        }

        $stagingRoot = str_replace('\\', '/', $stagingReal);

        // First pass: staging file exists, live path is writable, stays inside the install
        foreach ($files as $relative) {
            $this->assertCanReplace($stagingRoot, $absoluteReal, $relative);
        }

        // Second pass: copy each file from staging onto the live install
        foreach ($files as $relative) {
            if (!$this->installFile($stagingRoot.'/'.$relative, $this->absolutePath.'/'.$relative)) {
                $this->fail(self::ERROR_REPLACE, 'Failed to replace: '.$this->absolutePath.'/'.$relative);
            }
        }
    }

    /**
     * Restore from previousVersionBackup/; remove files that were newly added.
     *
     * @param array $files
     */
    public function rollback(array $files)
    {
        $backupDir = $this->absolutePath.'/'.Downloader::BASE_DIR.'/'.$this->backupDir;
        $added = array_fill_keys($this->added, true);

        foreach ($files as $relative) {
            $livePath = $this->absolutePath.'/'.$relative;

            // Was new in this package — just delete it
            if (isset($added[$relative])) {
                $this->deleteIfExists($livePath);
                continue;
            }

            // Existed before — restore the backed-up copy
            $backupPath = $backupDir.'/'.$relative;
            if (!is_file($backupPath) || !$this->installFile($backupPath, $livePath)) {
                $this->fail(self::ERROR_ROLLBACK, 'Failed to restore: '.$livePath);
            }
        }
    }

    /**
     * Restore from previousVersionBackup/; remove files that were newly added.
     */
    public function restore()
    {
        $backupDir = $this->absolutePath.'/'.Downloader::BASE_DIR.'/'.$this->backupDir;
        if (!is_dir($backupDir)) {
            $this->fail(self::ERROR_ROLLBACK, 'Backup directory does not exist: '.$backupDir);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            $relative = substr(str_replace('\\', '/', $fileInfo->getPathname()), strlen($backupDir) + 1);
            $livePath = $this->absolutePath.'/'.$relative;

            if ($fileInfo->isFile()) {
                if (!$this->installFile($fileInfo->getPathname(), $livePath)) {
                    $this->fail(self::ERROR_ROLLBACK, 'Failed to restore: '.$livePath);
                }
            }
        }
    }

    // Re-check every path before touching disk
    protected function normalizeFileList(array $relativeFiles) : array
    {
        if (empty($this->absolutePath) || !is_dir($this->absolutePath) || empty($relativeFiles)) {
            $this->fail(self::ERROR_INVALID, 'Invalid absolute path or empty file list');
        }

        $files = [];
        foreach ($relativeFiles as $relative) {
            if (!is_string($relative)) {
                $this->fail(self::ERROR_INVALID, 'Relative path not a string: '.var_export($relative, true));
            }
            $files[] = $this->assertSafeRelativePath($relative);
        }

        return $files;
    }

    /**
     * Confirm we can safely replace this path before writing anything.
     *
     * @param string $stagingRoot
     * @param string $absoluteReal
     * @param string $relative
     */
    protected function assertCanReplace(string $stagingRoot, string $absoluteReal, string $relative)
    {
        $sourceReal = is_file($stagingRoot.'/'.$relative) ? realpath($stagingRoot.'/'.$relative) : false;
        if ($sourceReal === false || !$this->isInside($sourceReal, $stagingRoot)) {
            $this->fail(self::ERROR_INVALID, 'Staging path not inside staging: '.$stagingRoot.'/'.$relative);
        }

        $livePath = $this->absolutePath.'/'.$relative;
        $parent = dirname($livePath);

        if ((is_file($livePath) && !is_writable($livePath)) || !$this->ensureDirectory($parent) || !is_writable($parent)) {
            $this->fail(self::ERROR_REPLACE, 'Live path not writable: '.$livePath);
        }

        $parentReal = realpath($parent);
        if ($parentReal === false || !$this->isInside($parentReal, $absoluteReal, true)) {
            $this->fail(self::ERROR_INVALID, 'Live path not inside install: '.$livePath);
        }
    }

    /**
     * Copy via a temp file in the same folder (safer on Windows).
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    protected function installFile(string $source, string $destination) : bool
    {
        if (!is_file($source) || !$this->ensureDirectory(dirname($destination))) {
            return false;
        }

        $tempPath = $destination.'.fileupdate.tmp';
        $this->deleteIfExists($tempPath);

        if (!@copy($source, $tempPath) || !is_file($tempPath)) {
            $this->deleteIfExists($tempPath);
            return false;
        }

        if (is_file($destination) && !@unlink($destination)) {
            $this->deleteIfExists($tempPath);
            return false;
        }

        if (!@rename($tempPath, $destination) || !is_file($destination)) {
            $this->deleteIfExists($tempPath);
            return false;
        }

        return true;
    }

    protected function copyFile(string $source, string $destination) : bool
    {
        return $this->ensureDirectory(dirname($destination)) && @copy($source, $destination) && is_file($destination);
    }

    // Whether $path is inside $root
    protected function isInside(string $path, string $root, bool $allowEqual = false) : bool
    {
        $path = str_replace('\\', '/', $path);
        $root = rtrim(str_replace('\\', '/', $root), '/');

        return ($allowEqual && $path === $root) || strpos($path, $root.'/') === 0;
    }

    // Block path traversal and sensitive folders
    protected function assertSafeRelativePath(string $relative) : string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        $lower = strtolower($relative);

        if ($relative == ''
            || strpos($relative, "\0") !== false
            || strpos($relative, '..') !== false
            || (strlen($relative) > 1 && $relative[1] == ':' && ctype_alpha($relative[0]))
            || $lower == 'config.php'
            // || strpos($lower, 'uploads/') === 0
            || strpos($lower, 'backups/') === 0
            || strpos($lower, '.git/') === 0
            || strpos($lower, 'vendor/') === 0
        ) {
            $this->fail(self::ERROR_INVALID, 'Relative path not safe: '.$relative);
        }

        return $relative;
    }

    protected function ensureDirectory(string $dir) : bool
    {
        return is_dir($dir) || mkdir($dir, 0755, true);
    }

    protected function fail(string $code, string $message = '')
    {
        error_log('FileUpdater: '.$code.' '.$message);
        throw new \RuntimeException($code);
    }

    protected function deleteIfExists(string $path)
    {
        if ($path != '' && is_file($path)) {
            unlink($path);
        }
    }

    // Empty a directory but leave the folder itself
    protected function wipeDirectory(string $dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            $fileInfo->isDir() ? rmdir($fileInfo->getPathname()) : unlink($fileInfo->getPathname());
        }
    }
}
