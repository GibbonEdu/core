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
 * Backs up, replaces, and rolls back live files from backup directory.
 *
 * @version v30
 * @since   v30
 */
class Updater
{
    public const ERROR_INVALID = 'invalid';
    public const ERROR_BACKUP = 'backup';
    public const ERROR_REPLACE = 'replace';
    public const ERROR_ROLLBACK = 'rollback';

    /** Folder under backups/autoUpdate for backup copies of changed files. */
    private const BACKUP_DIR = 'previousVersionBackup';

    /**
     * @var string
     */
    protected $absolutePath;

    /**
     * Relative paths that did not exist on the live tree before replace.
     *
     * @var string[]
     */
    protected $added = [];

    public function __construct(Session $session)
    {
        $this->absolutePath = rtrim(str_replace('\\', '/', (string) $session->get('absolutePath')), '/');
    }

    /**
     * Backup then replace; roll back live changes if replace fails.
     *
     * @param string[] $relativeFiles
     */
    public function run(array $relativeFiles) : void
    {
        $files = $this->normalizeFileList($relativeFiles);
        $this->backup($files);

        try {
            $this->replace($files);
        } catch (\Throwable $e) {
            try {
                $this->rollback($files);
            } catch (\Throwable $rollbackError) {
                $this->fail(self::ERROR_ROLLBACK, $rollbackError);
            }

            throw $e;
        }
    }

    /**
     * Copy existing changed files into previousVersionBackup/;
     *
     * @param string[] $files Already normalized relative paths
     */
    public function backup(array $files) : void
    {
        $backupDir = $this->absolutePath.'/'.Downloader::BASE_DIR.'/'.self::BACKUP_DIR;
        $this->wipeDirectory($backupDir);

        if (!$this->ensureDirectory($backupDir) || !is_writable($backupDir)) {
            $this->fail(self::ERROR_BACKUP);
        }

        $this->added = [];

        foreach ($files as $relative) {
            $livePath = $this->absolutePath.'/'.$relative;

            if (!is_file($livePath)) {
                $this->added[] = $relative;
                continue;
            }

            if (!is_readable($livePath) || !$this->copyFile($livePath, $backupDir.'/'.$relative)) {
                $this->fail(self::ERROR_BACKUP);
            }
        }
    }

    /**
     * Copy staging files onto the live server.
     *
     * @param string[] $files Already normalized relative paths
     */
    public function replace(array $files) : void
    {
        $stagingReal = realpath($this->absolutePath.'/'.Downloader::BASE_DIR.'/staging');
        $absoluteReal = realpath($this->absolutePath);

        if ($stagingReal === false || $absoluteReal === false) {
            $this->fail($stagingReal === false ? self::ERROR_REPLACE : self::ERROR_INVALID);
        }

        $stagingRoot = str_replace('\\', '/', $stagingReal);

        foreach ($files as $relative) {
            $this->assertCanReplace($stagingRoot, $absoluteReal, $relative);
        }

        foreach ($files as $relative) {
            if (!$this->installFile($stagingRoot.'/'.$relative, $this->absolutePath.'/'.$relative)) {
                $this->fail(self::ERROR_REPLACE);
            }
        }
    }

    /**
     * Restore from previousVersionBackup/; remove files that were newly added.
     *
     * @param string[] $files Already normalized relative paths
     */
    public function rollback(array $files) : void
    {
        $backupDir = $this->absolutePath.'/'.Downloader::BASE_DIR.'/'.self::BACKUP_DIR;
        $added = array_fill_keys($this->added, true);

        foreach ($files as $relative) {
            $livePath = $this->absolutePath.'/'.$relative;

            if (isset($added[$relative])) {
                $this->deleteIfExists($livePath);
                continue;
            }

            $backupPath = $backupDir.'/'.$relative;
            if (!is_file($backupPath) || !$this->installFile($backupPath, $livePath)) {
                $this->fail(self::ERROR_ROLLBACK);
            }
        }
    }

    /**
     * @param string[] $relativeFiles
     * @return string[]
     */
    protected function normalizeFileList(array $relativeFiles) : array
    {
        if ($this->absolutePath == '' || !is_dir($this->absolutePath) || empty($relativeFiles)) {
            $this->fail(self::ERROR_INVALID);
        }

        $files = [];
        foreach ($relativeFiles as $relative) {
            if (!is_string($relative)) {
                $this->fail(self::ERROR_INVALID);
            }
            $files[] = $this->assertSafeRelativePath($relative);
        }

        return $files;
    }

    /**
     * Confirm the staging file exists and the live target path is writable and inside the install.
     *
     * @param string $stagingRoot Slash-normalized realpath of staging
     * @param string $absoluteReal
     * @param string $relative
     */
    protected function assertCanReplace(string $stagingRoot, string $absoluteReal, string $relative)
    {
        $sourceReal = is_file($stagingRoot.'/'.$relative) ? realpath($stagingRoot.'/'.$relative) : false;
        if ($sourceReal === false || !$this->isInside($sourceReal, $stagingRoot)) {
            $this->fail(self::ERROR_INVALID);
        }

        $livePath = $this->absolutePath.'/'.$relative;
        $parent = dirname($livePath);

        if ((is_file($livePath) && !is_writable($livePath)) || !$this->ensureDirectory($parent) || !is_writable($parent)) {
            $this->fail(self::ERROR_REPLACE);
        }

        $parentReal = realpath($parent);
        if ($parentReal === false || !$this->isInside($parentReal, $absoluteReal, true)) {
            $this->fail(self::ERROR_INVALID);
        }
    }

    /**
     * Copy source onto destination via a same-directory temp file (Windows-safe replace).
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

        $tempPath = $destination.'.autoupdate.tmp';
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

    /**
     * @param string $source
     * @param string $destination
     * @return bool
     */
    protected function copyFile(string $source, string $destination) : bool
    {
        return $this->ensureDirectory(dirname($destination)) && @copy($source, $destination) && is_file($destination);
    }

    /**
     * Whether $path is inside $root (slash-normalized for Windows/macOS/Linux).
     *
     * @param string $path
     * @param string $root
     * @param bool $allowEqual
     * @return bool
     */
    protected function isInside(string $path, string $root, bool $allowEqual = false) : bool
    {
        $path = str_replace('\\', '/', $path);
        $root = rtrim(str_replace('\\', '/', $root), '/');

        return ($allowEqual && $path === $root) || strpos($path, $root.'/') === 0;
    }

    /**
     * @param string $relative
     * @return string
     */
    protected function assertSafeRelativePath(string $relative) : string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        $lower = strtolower($relative);

        // Same idea as Page::isAddressValid: block traversal and sensitive roots.
        if ($relative == ''
            || strpos($relative, "\0") !== false
            || strpos($relative, '..') !== false
            || (strlen($relative) > 1 && $relative[1] == ':' && ctype_alpha($relative[0]))
            || $lower == 'config.php'
            || strpos($lower, 'uploads/') === 0
            || strpos($lower, 'backups/') === 0
            || strpos($lower, '.git/') === 0
            || strpos($lower, 'vendor/') === 0
        ) {
            $this->fail(self::ERROR_INVALID);
        }

        return $relative;
    }

    /**
     * @param string $dir
     * @return bool
     */
    protected function ensureDirectory(string $dir) : bool
    {
        return is_dir($dir) || mkdir($dir, 0755, true);
    }

    /**
     * @param string $code
     * @param \Throwable|null $previous
     */
    protected function fail(string $code, ?\Throwable $previous = null)
    {
        error_log('Updater: '.$code.($previous ? ' '.$previous->getMessage() : ''));
        throw new \RuntimeException($code, 0, $previous);
    }

    /**
     * @param string $path
     */
    protected function deleteIfExists(string $path)
    {
        if ($path != '' && is_file($path)) {
            unlink($path);
        }
    }

    /**
     * @param string $dir
     */
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
