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
    private Filesystem $filesystem;

    /**
     * @param Session $session
     * @param Filesystem $filesystem
     * @return void
     */
    public function __construct(Session $session, Filesystem $filesystem)
    {
        $this->absolutePath = rtrim(str_replace('\\', '/', $session->get('absolutePath')), '/');
        $this->filesystem = $filesystem;
    }

    /**
     * @param callable|null $progressCallback
     * @return void
     */
    public function setProgressCallback(?callable $progressCallback): void
    {
        $this->progressCallback = $progressCallback;
        $this->lastProgress = null;
    }

    /**
     * Check the filesystem operations required by a file update.
     *
     * The probe creates, renames, and removes temporary entries in every
     * updater-managed directory. When the extracted file list is available,
     * it also checks the exact live paths and staging entries that will be
     * read, replaced, renamed, or removed.
     *
     * @param array<int, string> $relativeFiles
     * @return bool
     */
    public function checkPermissions(array $relativeFiles = []): bool
    {
        if ($this->absolutePath === '' || !is_dir($this->absolutePath) || !$this->filesystem->probeDirectory($this->absolutePath)) {
            return false;
        }

        $baseDir = $this->absolutePath.'/'.Downloader::BASE_DIR;
        if (!is_dir($baseDir) && !$this->filesystem->probeDirectory($this->absolutePath)) {
            return false;
        }
        if (!$this->filesystem->ensureDirectory($baseDir) || !$this->filesystem->probeDirectory($baseDir)) {
            return false;
        }

        foreach (['staging', $this->backupDir] as $directory) {
            $path = $baseDir.'/'.$directory;
            if (!$this->filesystem->ensureDirectory($path) || !$this->filesystem->probeDirectory($path)) {
                return false;
            }
        }

        // A held lock means another update is running, not that the
        // application lacks permission. The action performs the authoritative
        // non-blocking busy check before dispatch.
        if (!$this->probeLock($baseDir.'/fileUpdate.lock')) {
            return false;
        }

        foreach ($relativeFiles as $relative) {
            if (!is_string($relative)) {
                return false;
            }

            if (!$this->filesystem->isSafeRelativePath($relative)) {
                return false;
            }
            $relative = str_replace('\\', '/', $relative);

            $livePath = $this->absolutePath.'/'.$relative;
            $parent = dirname($livePath);
            if (!$this->filesystem->ensureDirectory($parent) || !$this->filesystem->probeDirectory($parent)) {
                return false;
            }

            $stagingPath = $baseDir.'/staging/'.$relative;
            if (is_dir($stagingPath)) {
                if (is_link($stagingPath) || !$this->filesystem->probeDirectory($stagingPath)) {
                    return false;
                }
            } elseif (!is_file($stagingPath) || !is_readable($stagingPath)) {
                return false;
            }

            if (is_file($livePath) && (!is_readable($livePath) || !is_writable($livePath))) {
                return false;
            }

            if (is_link($livePath) || (file_exists($livePath) && !is_dir($livePath) && !is_file($livePath))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Probe whether the update lock can be opened and exclusively locked.
     *
     * A held lock confirms that the filesystem supports locking. It represents
     * an active update rather than a permission failure, so this probe does
     * not retain the lock.
     */
    protected function probeLock(string $path): bool
    {
        $lock = @fopen($path, 'c');
        if ($lock === false) {
            return false;
        }

        try {
            if (@flock($lock, LOCK_EX | LOCK_NB)) {
                @flock($lock, LOCK_UN);
            }
        } finally {
            fclose($lock);
        }

        return true;
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
        $this->filesystem->wipeDirectory($backupDir);

        if (!$this->filesystem->ensureDirectory($backupDir) || !is_writable($backupDir)) {
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

                    if (is_link($livePath) || !is_readable($livePath) || !$this->filesystem->copyDirectory($livePath, $backupDir.'/'.$relative, $this->getCopyProgressCallback())) {
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

                if (!is_readable($livePath) || !$this->filesystem->copyFile($livePath, $backupDir.'/'.$relative)) {
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
                    ? $this->filesystem->replaceDirectory($source, $this->absolutePath.'/'.$relative, $this->getCopyProgressCallback())
                    : $this->filesystem->replaceFile($source, $this->absolutePath.'/'.$relative);

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
                    if (!$this->filesystem->deleteDirectory($livePath)) {
                        $this->fail(self::ERROR_ROLLBACK, 'Failed to remove: '.$livePath);
                    }
                } else {
                    $this->filesystem->deleteIfExists($livePath);
                }
                continue;
            }

            // Existed before — restore the backed-up copy
            $backupPath = $backupDir.'/'.$relative;
            $restored = $relative === self::VENDOR_DIRECTORY
                ? is_dir($backupPath) && $this->filesystem->replaceDirectory($backupPath, $livePath)
                : is_file($backupPath) && $this->filesystem->replaceFile($backupPath, $livePath);

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
        if (is_dir($vendorBackup) && !$this->filesystem->replaceDirectory($vendorBackup, $this->absolutePath.'/'.self::VENDOR_DIRECTORY)) {
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
                if (!$this->filesystem->replaceFile($fileInfo->getPathname(), $livePath)) {
                    $this->fail(self::ERROR_ROLLBACK, 'Failed to restore: '.$livePath);
                }
            }
        }
    }

    /**
     * Normalize the file list, ensuring all entries are safe relative paths.
     *
     * @param array<int, string> $relativeFiles
     * @return array<int, string>
     */
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

            if (!$this->filesystem->isSafeRelativePath($relative)) {
                $this->fail(self::ERROR_INVALID, 'Unsafe relative path: '.$relative);
            }
            $relative = str_replace('\\', '/', $relative);
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
        if ($sourceReal === false || !$this->filesystem->isInside($sourceReal, $stagingRoot)) {
            $this->fail(self::ERROR_INVALID, 'Staging path not inside staging: '.$stagingRoot.'/'.$relative);
        }

        $livePath = $this->absolutePath.'/'.$relative;
        $parent = dirname($livePath);

        if ((is_file($livePath) && !is_writable($livePath)) || !$this->filesystem->ensureDirectory($parent) || !is_writable($parent)) {
            $this->fail(self::ERROR_REPLACE, 'Live path not writable: '.$livePath);
        }

        $parentReal = realpath($parent);
        if ($parentReal === false || !$this->filesystem->isInside($parentReal, $absoluteReal, true)) {
            $this->fail(self::ERROR_INVALID, 'Live path not inside install: '.$livePath);
        }
    }

    /**
     * Confirm a staging directory can replace its live counterpart.
     * 
     * @param string $stagingRoot
     * @param string $absoluteReal
     * @param string $relative
     */
    protected function assertCanReplaceDirectory(string $stagingRoot, string $absoluteReal, string $relative)
    {
        $sourcePath = $stagingRoot.'/'.$relative;
        $sourceReal = is_dir($sourcePath) && !is_link($sourcePath) ? realpath($sourcePath) : false;
        if ($sourceReal === false || !$this->filesystem->isInside($sourceReal, $stagingRoot)) {
            $this->fail(self::ERROR_INVALID, 'Staging directory not inside staging: '.$sourcePath);
        }

        $livePath = $this->absolutePath.'/'.$relative;
        $parent = dirname($livePath);

        $liveExists = file_exists($livePath) || is_link($livePath);
        if (($liveExists && (!is_dir($livePath) || is_link($livePath))) || !is_readable($sourcePath) || !is_writable($parent)) {
            $this->fail(self::ERROR_REPLACE, 'Live directory not replaceable: '.$livePath);
        }

        $parentReal = realpath($parent);
        if ($parentReal === false || !$this->filesystem->isInside($parentReal, $absoluteReal, true)) {
            $this->fail(self::ERROR_INVALID, 'Live directory not inside install: '.$livePath);
        }
    }

    /**
     * Return a copy progress callback that advances the updater's progress.
     *
     * @return callable|null
     */
    private function getCopyProgressCallback(): ?callable
    {
        if (!$this->trackCopyProgress) {
            return null;
        }

        return function (string $source): void {
            $this->advanceProgress($this->fileWork($source));
        };
    }

    /**
     * Begin a new progress phase, reporting the start and end percentages.
     *
     * @param int $start
     * @param int $end
     * @param int $total
     */
    protected function beginProgressPhase(int $start, int $end, int $total) : void
    {
        $this->progressStart = $start;
        $this->progressEnd = $end;
        $this->progressCompleted = 0;
        $this->progressTotal = max(1, $total);
        $this->reportProgress($start);
    }

    /**
     * Advance progress by a given amount of work, reporting to the callback.
     *
     * @param int $work
     */
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

    /**
     * Calculate update work from the staged or live file list.
     *
     * @param array<int, string> $files
     */
    protected function calculateWork(array $files, string $root): int
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

    /**
     * Calculate work for an update path, recursively including regular files.
     * 
     * @param string $path
     * @return int
     */
    protected function pathWork(string $path): int
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
        } catch (\Throwable $error) {
            return 1;
        }

        return max(1, $work);
    }

    /**
     * Calculate work for a single file.
     * 
     * @param string $path
     * @return int
     */
    protected function fileWork(string $path): int
    {
        $size = is_file($path) ? filesize($path) : false;

        return max(1, $size === false ? 0 : $size);
    }

    /**
     * Report progress as a percentage of the current phase.
     *
     * @param int $completed
     * @param int $total
     * @param int $start
     * @param int $end
     */
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

    /**
     * Report progress to the callback, if set, and only if it has increased.
     *
     * @param int $progress
     */
    protected function reportProgress(int $progress) : void
    {
        $progress = min(max($progress, 0), 100);
        if ($this->progressCallback === null || ($this->lastProgress !== null && $progress <= $this->lastProgress)) {
            return;
        }

        $this->lastProgress = $progress;
        ($this->progressCallback)($progress);
    }

    /**
     * Determine whether a relative path is the vendor directory or inside it.
     * 
     * @param string $relative
     * @return bool
     */
    protected function isVendorPath(string $relative) : bool
    {
        $relative = strtolower($relative);

        return $relative === self::VENDOR_DIRECTORY || strpos($relative, self::VENDOR_DIRECTORY.'/') === 0;
    }

    /**
     * Log an error and throw a RuntimeException with the given code.
     *
     * @param string $code
     * @param string $message
     * @throws \RuntimeException
     */
    protected function fail(string $code, string $message = '')
    {
        error_log('FileUpdater: '.$code.' '.$message);
        throw new \RuntimeException($code);
    }
}
