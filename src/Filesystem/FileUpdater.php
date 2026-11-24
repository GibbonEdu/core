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

    private const VENDOR_DIRECTORY = 'vendor';

    protected $absolutePath;
    protected $added = []; // files that did not exist on live before replace
    protected $backupDir = 'previousVersionBackup';
    protected $progressCallback;
    protected $lastProgress;
    protected $progressCompleted = 0;
    protected $progressTotal = 1;
    protected $progressStart = 0;
    protected $progressEnd = 100;
    protected $trackCopyProgress = false;

    public function __construct(Session $session)
    {
        $this->absolutePath = rtrim(str_replace('\\', '/', $session->get('absolutePath')), '/');
    }

    public function setProgressCallback(?callable $progressCallback): void
    {
        $this->progressCallback = $progressCallback;
        $this->lastProgress = null;
    }

    /**
     * Backup existing files, then replace from staging. Roll back on failure.
     *
     * @param array $relativeFiles
     */
    public function run(array $relativeFiles)
    {
        $this->lastProgress = null;
        $this->reportProgress(0);
        $files = $this->normalizeFileList($relativeFiles);
        $this->backup($files);

        try {
            $this->replace($files);
            $this->reportProgress(100);
        } catch (\Throwable $e) {
            $this->trackCopyProgress = false;
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
        $this->beginProgressPhase(0, 45, $this->calculateWork($files, $this->absolutePath));
        $this->trackCopyProgress = true;

        try {
            foreach ($files as $relative) {
                $livePath = $this->absolutePath.'/'.$relative;

                if ($relative === self::VENDOR_DIRECTORY) {
                    if (!is_dir($livePath)) {
                        $this->added[] = $relative;
                        $this->advanceProgress(1);
                        continue;
                    }

                    if (is_link($livePath) || !is_readable($livePath) || !$this->copyDirectory($livePath, $backupDir.'/'.$relative)) {
                        $this->fail(self::ERROR_BACKUP, 'Failed to back up: '.$livePath);
                    }
                    continue;
                }

                // New file — nothing to back up
                if (!is_file($livePath)) {
                    $this->added[] = $relative;
                    $this->advanceProgress(1);
                    continue;
                }

                if (!is_readable($livePath) || !$this->copyFile($livePath, $backupDir.'/'.$relative)) {
                    $this->fail(self::ERROR_BACKUP, 'Failed to back up: '.$livePath);
                }
                $this->advanceProgress($this->fileWork($livePath));
            }
        } finally {
            $this->trackCopyProgress = false;
        }

        $this->reportProgress(45);
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

        // First pass: staging source exists, live path is writable, stays inside the install
        $validated = 0;
        $totalFiles = count($files);
        foreach ($files as $relative) {
            if ($relative === self::VENDOR_DIRECTORY) {
                $this->assertCanReplaceDirectory($stagingRoot, $absoluteReal, $relative);
            } else {
                $this->assertCanReplace($stagingRoot, $absoluteReal, $relative);
            }

            ++$validated;
            $this->reportProgressRange($validated, $totalFiles, 45, 50);
        }

        // Second pass: replace vendor as one directory; install all other paths one file at a time
        $this->beginProgressPhase(50, 100, $this->calculateWork($files, $stagingRoot));
        $this->trackCopyProgress = true;

        try {
            foreach ($files as $relative) {
                $source = $stagingRoot.'/'.$relative;
                $installed = $relative === self::VENDOR_DIRECTORY
                    ? $this->installDirectory($source, $this->absolutePath.'/'.$relative)
                    : $this->installFile($source, $this->absolutePath.'/'.$relative);

                if (!$installed) {
                    $this->fail(self::ERROR_REPLACE, 'Failed to replace: '.$this->absolutePath.'/'.$relative);
                }

                if ($relative !== self::VENDOR_DIRECTORY) {
                    $this->advanceProgress($this->fileWork($source));
                }
            }
        } finally {
            $this->trackCopyProgress = false;
        }

        $this->reportProgress(100);
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
                if ($relative === self::VENDOR_DIRECTORY) {
                    if (!$this->deleteDirectory($livePath)) {
                        $this->fail(self::ERROR_ROLLBACK, 'Failed to remove: '.$livePath);
                    }
                } else {
                    $this->deleteIfExists($livePath);
                }
                continue;
            }

            // Existed before — restore the backed-up copy
            $backupPath = $backupDir.'/'.$relative;
            $restored = $relative === self::VENDOR_DIRECTORY
                ? is_dir($backupPath) && $this->installDirectory($backupPath, $livePath)
                : is_file($backupPath) && $this->installFile($backupPath, $livePath);

            if (!$restored) {
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

        $vendorBackup = $backupDir.'/'.self::VENDOR_DIRECTORY;
        if (is_dir($vendorBackup) && !$this->installDirectory($vendorBackup, $this->absolutePath.'/'.self::VENDOR_DIRECTORY)) {
            $this->fail(self::ERROR_ROLLBACK, 'Failed to restore: '.$this->absolutePath.'/'.self::VENDOR_DIRECTORY);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            $relative = substr(str_replace('\\', '/', $fileInfo->getPathname()), strlen($backupDir) + 1);
            $livePath = $this->absolutePath.'/'.$relative;

            if ($relative === self::VENDOR_DIRECTORY || strpos($relative, self::VENDOR_DIRECTORY.'/') === 0) {
                continue;
            }

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
        $vendorAdded = false;

        foreach ($relativeFiles as $relative) {
            if (!is_string($relative)) {
                $this->fail(self::ERROR_INVALID, 'Relative path not a string: '.var_export($relative, true));
            }

            $relative = $this->assertSafeRelativePath($relative);
            if ($this->isVendorPath($relative)) {
                if (!$vendorAdded) {
                    $files[] = self::VENDOR_DIRECTORY;
                    $vendorAdded = true;
                }
                continue;
            }

            $files[] = $relative;
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
     * Confirm a staging directory can replace its live counterpart.
     */
    protected function assertCanReplaceDirectory(string $stagingRoot, string $absoluteReal, string $relative)
    {
        $sourcePath = $stagingRoot.'/'.$relative;
        $sourceReal = is_dir($sourcePath) && !is_link($sourcePath) ? realpath($sourcePath) : false;
        if ($sourceReal === false || !$this->isInside($sourceReal, $stagingRoot)) {
            $this->fail(self::ERROR_INVALID, 'Staging directory not inside staging: '.$sourcePath);
        }

        $livePath = $this->absolutePath.'/'.$relative;
        $parent = dirname($livePath);

        $liveExists = file_exists($livePath) || is_link($livePath);
        if (($liveExists && (!is_dir($livePath) || is_link($livePath))) || !is_readable($sourcePath) || !is_writable($parent)) {
            $this->fail(self::ERROR_REPLACE, 'Live directory not replaceable: '.$livePath);
        }

        $parentReal = realpath($parent);
        if ($parentReal === false || !$this->isInside($parentReal, $absoluteReal, true)) {
            $this->fail(self::ERROR_INVALID, 'Live directory not inside install: '.$livePath);
        }
    }

    /**
     * Copy a file directly onto the live install.
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

        if (is_link($destination) && !@unlink($destination)) {
            return false;
        }

        return @copy($source, $destination) && is_file($destination);
    }

    /**
     * Copy a directory beside its destination, then swap it into place.
     */
    protected function installDirectory(string $source, string $destination) : bool
    {
        if (!is_dir($source) || is_link($source) || !$this->ensureDirectory(dirname($destination))) {
            return false;
        }

        try {
            $suffix = bin2hex(random_bytes(8));
        } catch (\Throwable $e) {
            return false;
        }

        $tempPath = $destination.'.fileupdate.'.$suffix.'.tmp';
        $previousPath = $destination.'.fileupdate.'.$suffix.'.previous';

        if (!$this->copyDirectory($source, $tempPath)) {
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

    protected function copyDirectory(string $source, string $destination) : bool
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

                $relative = substr(str_replace('\\', '/', $fileInfo->getPathname()), strlen(rtrim(str_replace('\\', '/', $source), '/')) + 1);
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

                if ($this->trackCopyProgress) {
                    $this->advanceProgress($this->fileWork($fileInfo->getPathname()));
                }
            }
        } catch (\Throwable $e) {
            $this->deleteDirectory($destination);
            return false;
        }

        return true;
    }

    protected function beginProgressPhase(int $start, int $end, int $total) : void
    {
        $this->progressStart = $start;
        $this->progressEnd = $end;
        $this->progressCompleted = 0;
        $this->progressTotal = max(1, $total);
        $this->reportProgress($start);
    }

    protected function advanceProgress(int $work) : void
    {
        if ($this->progressCallback === null) {
            return;
        }

        $this->progressCompleted = min($this->progressCompleted + max(1, $work), $this->progressTotal);
        $this->reportProgressRange(
            $this->progressCompleted,
            $this->progressTotal,
            $this->progressStart,
            $this->progressEnd
        );
    }

    protected function calculateWork(array $files, string $root) : int
    {
        if ($this->progressCallback === null) {
            return 1;
        }

        $work = 0;
        foreach ($files as $relative) {
            $work += $this->pathWork($root.'/'.$relative);
        }

        return max(1, $work);
    }

    protected function pathWork(string $path) : int
    {
        if (!is_dir($path) || is_link($path)) {
            return $this->fileWork($path);
        }

        $work = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile() && !$fileInfo->isLink()) {
                    $work += $this->fileWork($fileInfo->getPathname());
                }
            }
        } catch (\Throwable $e) {
            return 1;
        }

        return max(1, $work);
    }

    protected function fileWork(string $path) : int
    {
        $size = is_file($path) ? filesize($path) : false;

        return max(1, $size === false ? 0 : $size);
    }

    protected function reportProgressRange(int $completed, int $total, int $start, int $end) : void
    {
        if ($total <= 0) {
            $this->reportProgress($end);
            return;
        }

        $completed = min(max($completed, 0), $total);
        $progress = $start + (int) floor(($end - $start) * ($completed / $total));
        $this->reportProgress($progress);
    }

    protected function reportProgress(int $progress) : void
    {
        $progress = min(max($progress, 0), 100);
        if ($this->progressCallback === null || ($this->lastProgress !== null && $progress <= $this->lastProgress)) {
            return;
        }

        $this->lastProgress = $progress;
        ($this->progressCallback)($progress);
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

    protected function isVendorPath(string $relative) : bool
    {
        $relative = strtolower($relative);

        return $relative === self::VENDOR_DIRECTORY || strpos($relative, self::VENDOR_DIRECTORY.'/') === 0;
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
            // || strpos($lower, 'vendor/') === 0
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

    protected function deleteDirectory(string $dir) : bool
    {
        if (is_link($dir) || is_file($dir)) {
            return @unlink($dir);
        }

        if (!is_dir($dir)) {
            return true;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
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
        } catch (\Throwable $e) {
            return false;
        }

        return @rmdir($dir);
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
