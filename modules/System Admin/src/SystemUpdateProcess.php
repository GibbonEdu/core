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

declare(strict_types=1);

namespace Gibbon\Module\SystemAdmin;

use Gibbon\Contracts\Services\Session;
use Gibbon\Database\Updater;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Filesystem\Downloader;
use Gibbon\Filesystem\FileUpdater;
use Gibbon\Filesystem\Filesystem;
use Gibbon\Filesystem\VersionCatalog;
use Gibbon\Services\BackgroundProcess;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * Runs core file updates, restores, and database updates in a background process.
 *
 * @version v30
 * @since   v30
 */
class SystemUpdateProcess extends BackgroundProcess implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Session $session
     * @param SettingGateway $settingGateway
     * @param Updater $updater
     * @param VersionCatalog $versionCatalog
     * @param Downloader $downloader
     * @param FileUpdater $fileUpdater
     * @param SystemUpdateProgress $progress
     * @param Filesystem $filesystem
     * @return void
     */
    public function __construct(
        private Session $session,
        private SettingGateway $settingGateway,
        private Updater $updater,
        private VersionCatalog $versionCatalog,
        private Downloader $downloader,
        private FileUpdater $fileUpdater,
        private SystemUpdateProgress $progress,
        private Filesystem $filesystem,
    ) {

    }

    /**
     * Run a file update, restore, or database update in a background process.
     *
     * @param string $mode The update mode: 'file', 'regularRelease', 'cuttingEdge', or 'restore'
     * @throws \InvalidArgumentException When the requested update mode is unsupported.
     * @throws \RuntimeException When an update cannot complete.
     * @return void
     */
    public function run(string $mode): void
    {
        if (!in_array($mode, ['file', 'regularRelease', 'cuttingEdge', 'restore'], true)) {
            throw new \InvalidArgumentException('Unsupported system update mode.');
        }

        // Each mode starts the shared progress itself, once it holds the update lock. A
        // process that loses the lock must not reset or fail the update that is running.
        if ($mode === 'file') {
            $this->runFileUpdate();

            return;
        }

        if ($mode === 'restore') {
            $this->runRestore();

            return;
        }

        $this->runDatabaseUpdate();
    }

    /**
     * Take the exclusive update lock without waiting. The lock is released by the
     * system if the process dies, so a crash cannot leave it held.
     *
     * @param string $lockPath
     * @return resource|null The lock, or null when the lock file cannot be used.
     * @throws \RuntimeException When another update holds the lock.
     */
    private function acquireLock(string $lockPath)
    {
        $lock = @fopen($lockPath, 'c');
        if ($lock === false) {
            return null;
        }

        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);

            throw new \RuntimeException('Another system update is already running.');
        }

        return $lock;
    }

    /**
     * Run the file update process.
     *
     * @param string $stage
     * @return void
     * @throws \RuntimeException
     */
    private function runFileUpdate(): void
    {
        $stage = 'preparing';

        $absolutePath = (string) $this->session->get('absolutePath');
        $baseDir = $this->filesystem->getSecureDirectory($absolutePath, Downloader::BASE_DIR);
        $lockPath = $baseDir.'/fileUpdate.lock';

        if ($absolutePath === ''
            || !$this->filesystem->checkPermissions([$absolutePath, $baseDir])) {
            $this->fail($stage, new \RuntimeException('File update directory is unavailable.'));
            throw new \RuntimeException('File update directory is unavailable.');
        }

        // Fail now, before maintenance mode is switched on, rather than during the download
        if (!$this->hasZipSupport()) {
            $this->fail($stage, new \RuntimeException('The PHP Zip extension is not available.'));
            throw new \RuntimeException('The PHP Zip extension is not available.');
        }

        $lock = $this->acquireLock($lockPath);
        if ($lock === null) {
            $this->fail($stage, new \RuntimeException('The update lock file is unavailable.'));
            throw new \RuntimeException('The update lock file is unavailable.');
        }

        $this->progress->begin('file');
        $this->progress->update($stage, 'working', 5);

        // Keep the current updater timeout and maintenance-mode behavior.
        ignore_user_abort(true);
        @set_time_limit(3000);

        $previousMaintenance = 'N';
        $rollbackFailed = false;

        try {
            try {
                $previousMaintenance = (string) $this->settingGateway->getSettingByScope('System Admin', 'maintenanceMode');
                $this->settingGateway->updateSettingByScope('System Admin', 'maintenanceMode', 'Y');
                $this->settingGateway->updateSettingByScope('System Admin', 'fileUpdater', 'running');

                $stage = 'downloading';
                $this->progress->update($stage, 'working', 10);
                $release = $this->versionCatalog->getNextRelease(
                    $this->updater->versionCode,
                    $this->updater->isCuttingEdge()
                );
                if (empty($release) || empty($release['filePath'])) {
                    throw new \RuntimeException(Downloader::ERROR_INVALID);
                }

                // The update page also blocks this, but the page can be stale or bypassed
                $requiredPhp = (string) ($release['phpVersion'] ?? '');
                if ($requiredPhp !== '' && version_compare(PHP_VERSION, $requiredPhp, '<')) {
                    throw new \RuntimeException(sprintf(
                        'Version %s requires PHP %s or higher, this server runs PHP %s.',
                        $release['version'] ?? '',
                        $requiredPhp,
                        PHP_VERSION
                    ));
                }

                $this->downloader->setProgressCallback($this->createProgressCallback('downloading', 10, 70));
                $files = $this->downloader->run($release);
                if (!$this->fileUpdater->checkPermissions($files)) {
                    throw new \RuntimeException('File update permissions are unavailable.');
                }

                $stage = 'installing';
                $this->progress->update($stage, 'working', 70);
                $this->fileUpdater->setProgressCallback($this->createProgressCallback('installing', 70, 95));
                $this->fileUpdater->run($files);

                $stage = 'finalizing';
                $this->progress->update($stage, 'working', 95);
                $this->filesystem->wipeDirectory($absolutePath.'/uploads/cache');
                $this->session->forget('pageLoads');

                $this->settingGateway->updateSettingByScope('System Admin', 'fileUpdater', 'idle');
                $this->settingGateway->updateSettingByScope('System Admin', 'fileUpdaterBackup', $this->updater->versionCode);
            } catch (\Throwable $error) {
                $rollbackFailed = $error->getMessage() === FileUpdater::ERROR_ROLLBACK;

                throw $error;
            } finally {
                try {
                    // Keep the backup folder, but remove staging and any downloaded package.
                    $this->filesystem->wipeDirectory($baseDir.'/staging');
                    if (is_file($baseDir.'/package.zip')) {
                        unlink($baseDir.'/package.zip');
                    }

                    $this->releaseMaintenanceMode($previousMaintenance, $rollbackFailed);
                } finally {
                    flock($lock, LOCK_UN);
                    fclose($lock);
                }
            }

            $this->progress->update($stage, 'complete', 100);
        } catch (\Throwable $error) {
            $this->fail($stage, $error);

            try {
                $this->settingGateway->updateSettingByScope('System Admin', 'fileUpdater', 'failed');
            } catch (\Throwable $settingError) {
                error_log('SystemUpdateProcess file update failure-state error: '.$settingError->getMessage());
            }

            throw $error;
        }
    }

    /**
     * Run the restore process.
     *
     * @return void
     * @throws \RuntimeException
     *
     * @return void
     */

    private function runRestore(): void
    {
        $stage = 'preparing';

        $absolutePath = (string) $this->session->get('absolutePath');
        $baseDir = $this->filesystem->getSecureDirectory($absolutePath, Downloader::BASE_DIR);
        $lockPath = $baseDir.'/fileUpdate.lock';

        if ($absolutePath === ''
            || !$this->filesystem->checkPermissions([$absolutePath, $baseDir])) {
            $this->fail($stage, new \RuntimeException('File restore directory is unavailable.'));
            throw new \RuntimeException('File restore directory is unavailable.');
        }

        $lock = $this->acquireLock($lockPath);
        if ($lock === null) {
            $this->fail($stage, new \RuntimeException('The update lock file is unavailable.'));
            throw new \RuntimeException('The update lock file is unavailable.');
        }

        $this->progress->begin('restore');
        $this->progress->update($stage, 'working', 10);

        ignore_user_abort(true);
        @set_time_limit(3000);

        $previousMaintenance = 'N';
        $rollbackFailed = false;

        try {
            try {
                $previousMaintenance = (string) $this->settingGateway->getSettingByScope('System Admin', 'maintenanceMode');
                $this->settingGateway->updateSettingByScope('System Admin', 'maintenanceMode', 'Y');
                $this->settingGateway->updateSettingByScope('System Admin', 'fileUpdater', 'running');

                // The restore only needs the local backup, not the remote version catalog.
                $backupVersion = (string) $this->settingGateway->getSettingByScope('System Admin', 'fileUpdaterBackup');

                // Restored files older than the database would leave the schema ahead of the code
                if (version_compare($backupVersion, (string) $this->updater->versionDB, '<')) {
                    throw new \RuntimeException(sprintf(
                        'The database is at version %s, newer than the backup at version %s.',
                        $this->updater->versionDB,
                        $backupVersion
                    ));
                }

                $stage = 'restoring';
                $this->progress->update($stage, 'working', 70);
                $this->fileUpdater->restore($backupVersion);

                $stage = 'finalizing';
                $this->progress->update($stage, 'working', 90);
                $this->filesystem->wipeDirectory($absolutePath.'/uploads/cache');
                $this->session->forget('pageLoads');

                $this->settingGateway->updateSettingByScope('System Admin', 'fileUpdater', 'idle');
                $this->settingGateway->updateSettingByScope('System Admin', 'fileUpdaterBackup', '');
            } catch (\Throwable $error) {
                $rollbackFailed = $error->getMessage() === FileUpdater::ERROR_ROLLBACK;

                throw $error;
            } finally {
                $this->filesystem->wipeDirectory($baseDir.'/staging');
                if (is_file($baseDir.'/package.zip')) {
                    unlink($baseDir.'/package.zip');
                }

                $this->releaseMaintenanceMode($previousMaintenance, $rollbackFailed);
                flock($lock, LOCK_UN);
                fclose($lock);
            }

            $this->progress->update($stage, 'complete', 100);
        } catch (\Throwable $error) {
            $this->fail($stage, $error);

            try {
                $this->settingGateway->updateSettingByScope('System Admin', 'fileUpdater', 'failed');
            } catch (\Throwable $settingError) {
                error_log('SystemUpdateProcess restore failure-state error: '.$settingError->getMessage());
            }

            throw $error;
        }
    }

    /**
     * Run the database update process.
     *
     * @return void
     * @throws \RuntimeException
     *
     * @return void
     */
    private function runDatabaseUpdate(): void
    {
        $stage = 'preparing';

        // Share the lock with file updates and restores. A database update must still run
        // when the lock folder does not exist or cannot be used, as when files are updated
        // by hand, and in that case no file update can be running either.
        $baseDir = $this->filesystem->getSecureDirectory((string) $this->session->get('absolutePath'), Downloader::BASE_DIR);
        $lock = is_dir($baseDir) ? $this->acquireLock($baseDir.'/fileUpdate.lock') : null;

        $this->progress->begin('database');
        $this->progress->update($stage, 'working', 10);

        try {
            if (!$this->updater->isVersionValid() || !$this->updater->isUpdateRequired()) {
                throw new \RuntimeException('A database update is not available.');
            }

            $stage = 'installing';
            $this->progress->update($stage, 'working', 70);
            $errors = $this->updater->update();
            if (!empty($errors)) {
                error_log('SystemUpdateProcess database update errors: '.print_r($errors, true));
                throw new \RuntimeException('Database update failed.');
            }

            $stage = 'finalizing';
            $this->progress->update($stage, 'working', 90);
            $this->filesystem->wipeDirectory((string) $this->session->get('absolutePath').'/uploads/cache');
            $this->session->forget('pageLoads');
            $this->progress->update($stage, 'complete', 100);
        } catch (\Throwable $error) {
            $this->fail($stage, $error);
            throw $error;
        } finally {
            if ($lock !== null) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    /**
     * Whether the PHP Zip extension, needed to unpack the update, is available.
     *
     * @return bool
     */
    protected function hasZipSupport(): bool
    {
        return class_exists(\ZipArchive::class);
    }

    /**
     * Put maintenance mode back to the value it had before the update started.
     * It stays on after a failed rollback, because the install may be half replaced.
     *
     * @param string $previousMaintenance The maintenanceMode value before the update.
     * @param bool $rollbackFailed Whether the file rollback or restore failed.
     * @return void
     */
    private function releaseMaintenanceMode(string $previousMaintenance, bool $rollbackFailed): void
    {
        if ($rollbackFailed) {
            error_log('SystemUpdateProcess: maintenance mode left on because the file rollback failed.');

            return;
        }

        $this->settingGateway->updateSettingByScope('System Admin', 'maintenanceMode', $previousMaintenance === 'Y' ? 'Y' : 'N');
    }

    /**
     * Create a progress callback that maps local percentage to overall update range.
     *
     * @param string $stage The update stage name
     * @param int $start The start percentage for this operation
     * @param int $end The end percentage for this operation
     * @return callable
     */
    private function createProgressCallback(string $stage, int $start, int $end): callable
    {
        $lastProgress = $start;

        return function (int $progress) use ($stage, $start, $end, &$lastProgress): void {
            $progress = min(max($progress, 0), 100);
            $mappedProgress = $start + (int) floor(($end - $start) * ($progress / 100));
            if ($mappedProgress <= $lastProgress) {
                return;
            }

            $lastProgress = $mappedProgress;
            $this->progress->update($stage, 'working', $mappedProgress);
        };
    }

    /**
     * Record a safe failure snapshot and write technical details only to server logs.
     *
     * @param string $stage
     * @param \Throwable $error
     * @return void
     *
     * @return void
     */

    private function fail(string $stage, \Throwable $error): void
    {
        error_log(sprintf('SystemUpdateProcess %s update failed: %s', $stage, $error->getMessage()));

        try {
            $this->progress->fail($stage);
        } catch (\Throwable $progressError) {
            error_log('SystemUpdateProcess progress failure-state error: '.$progressError->getMessage());
        }
    }
}
