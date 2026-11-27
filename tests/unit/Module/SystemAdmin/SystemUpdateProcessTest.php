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
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4).'/functions.php';

require_once dirname(__DIR__, 4).'/modules/System Admin/src/SystemUpdateProgress.php';
require_once dirname(__DIR__, 4).'/modules/System Admin/src/SystemUpdateProcess.php';

/**
 * @covers \Gibbon\Module\SystemAdmin\SystemUpdateProcess
 */
class SystemUpdateProcessTest extends TestCase
{
    private string $fixturePath;
    private string $baseDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturePath = sys_get_temp_dir().'/SystemUpdateProcessTest-'.bin2hex(random_bytes(8));
        mkdir($this->fixturePath.'/uploads/cache', 0755, true);
        $this->baseDirectory = (new Filesystem())->getSecureDirectory($this->fixturePath, Downloader::BASE_DIR);
        mkdir($this->baseDirectory.'/staging', 0755, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->baseDirectory);
        $this->removeFixtureDirectory($this->fixturePath);

        parent::tearDown();
    }

    public function testFileUpdateRunsBehindMaintenanceModeAndRecordsCompletion(): void
    {
        $events = [];
        $downloadProgress = null;
        $installProgress = null;
        [$settings, $state] = $this->createSettingGateway($events);
        file_put_contents($this->baseDirectory.'/staging/temporary.txt', 'temporary');
        file_put_contents($this->baseDirectory.'/package.zip', 'package');
        file_put_contents($this->fixturePath.'/uploads/cache/cached.txt', 'cache');

        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '30.0.00';
        $updater->method('isCuttingEdge')->willReturn(false);

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->expects($this->once())
            ->method('getNextRelease')
            ->with('30.0.00', false)
            ->willReturn(['filePath' => 'gibbon-30.0.01.zip']);

        $downloader = $this->createMock(Downloader::class);
        $downloader->expects($this->once())
            ->method('setProgressCallback')
            ->willReturnCallback(function (callable $callback) use (&$downloadProgress): void {
                $downloadProgress = $callback;
            });
        $downloader->expects($this->once())
            ->method('run')
            ->with(['filePath' => 'gibbon-30.0.01.zip'])
            ->willReturnCallback(function () use (&$events, &$downloadProgress): array {
                $events[] = 'downloader';
                $this->assertIsCallable($downloadProgress);
                $downloadProgress(50);

                return ['index.php'];
            });

        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->expects($this->once())
            ->method('checkPermissions')
            ->with(['index.php'])
            ->willReturn(true);
        $fileUpdater->expects($this->once())
            ->method('setProgressCallback')
            ->willReturnCallback(function (callable $callback) use (&$installProgress): void {
                $installProgress = $callback;
            });
        $fileUpdater->expects($this->once())
            ->method('run')
            ->with(['index.php'])
            ->willReturnCallback(function () use (&$events, &$installProgress): void {
                $events[] = 'file-updater';
                $this->assertIsCallable($installProgress);
                $installProgress(50);
            });

        $process = $this->createProcess($settings, $updater, $versionCatalog, $downloader, $fileUpdater, $events);
        $process->run('file');

        // Maintenance mode is on before any download or install, and off again after the install
        $this->assertLessThan(
            $this->eventPosition($events, 'downloader'),
            $this->eventPosition($events, 'setting:maintenanceMode:Y')
        );
        $this->assertLessThan(
            $this->eventPosition($events, 'setting:maintenanceMode:N'),
            $this->eventPosition($events, 'file-updater')
        );
        $this->assertSame(['finalizing', 'complete', 100], end($state->snapshots));
        $this->assertProgressNeverGoesBackwards($state->snapshots);
        $this->assertSame('N', $state->values['System Admin/maintenanceMode']);
        $this->assertSame('idle', $state->values['System Admin/fileUpdater']);
        $this->assertSame('30.0.00', $state->values['System Admin/fileUpdaterBackup']);
        $this->assertSame('', $state->values['System Admin/cuttingEdgeCommit'], 'A released version has no Cutting Edge commit.');
        $this->assertFileDoesNotExist($this->baseDirectory.'/staging/temporary.txt');
        $this->assertFileDoesNotExist($this->baseDirectory.'/package.zip');
        $this->assertFileDoesNotExist($this->fixturePath.'/uploads/cache/cached.txt');
        $this->assertFileLockWasReleased();
    }

    public function testRestoreRunsBehindMaintenanceModeAndRecordsCompletion(): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        $state->values['System Admin/fileUpdaterBackup'] = '29.0.00';
        $state->values['System Admin/cuttingEdgeCommit'] = str_repeat('a', 40);
        file_put_contents($this->baseDirectory.'/staging/temporary.txt', 'temporary');
        file_put_contents($this->baseDirectory.'/package.zip', 'package');
        file_put_contents($this->fixturePath.'/uploads/cache/cached.txt', 'cache');

        // A restore uses the local backup only, so it never asks the version catalog.
        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->expects($this->never())->method('getRelease');

        $downloader = $this->createMock(Downloader::class);
        $downloader->expects($this->never())->method('run');
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->expects($this->once())
            ->method('restore')
            ->with('29.0.00')
            ->willReturnCallback(function () use (&$events): void {
                $events[] = 'file-updater:restore';
            });

        $process = $this->createProcess(
            $settings,
            $this->createMock(Updater::class),
            $versionCatalog,
            $downloader,
            $fileUpdater,
            $events
        );
        $process->run('restore');

        $this->assertLessThan(
            $this->eventPosition($events, 'file-updater:restore'),
            $this->eventPosition($events, 'setting:maintenanceMode:Y')
        );
        $this->assertLessThan(
            $this->eventPosition($events, 'setting:maintenanceMode:N'),
            $this->eventPosition($events, 'file-updater:restore')
        );
        $this->assertSame(['finalizing', 'complete', 100], end($state->snapshots));
        $this->assertProgressNeverGoesBackwards($state->snapshots);
        $this->assertSame('N', $state->values['System Admin/maintenanceMode']);
        $this->assertSame('idle', $state->values['System Admin/fileUpdater']);
        $this->assertSame('', $state->values['System Admin/fileUpdaterBackup']);
        $this->assertSame('', $state->values['System Admin/cuttingEdgeCommit'], 'The restored files are not known to match the newest commit.');
        $this->assertFileDoesNotExist($this->baseDirectory.'/staging/temporary.txt');
        $this->assertFileDoesNotExist($this->baseDirectory.'/package.zip');
        $this->assertFileDoesNotExist($this->fixturePath.'/uploads/cache/cached.txt');
        $this->assertFileLockWasReleased();
    }

    /**
     * @dataProvider databaseModeProvider
     */
    public function testDatabaseModesApplyTheUpdateAndClearTheCache(string $mode): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        file_put_contents($this->fixturePath.'/uploads/cache/cached.txt', 'cache');

        $updater = $this->createMock(Updater::class);
        $updater->versionDB = '30.0.01';
        $updater->method('isVersionValid')->willReturn(true);
        $updater->method('isUpdateRequired')->willReturn(true);
        $updater->expects($this->once())->method('update')->willReturn([]);

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $downloader = $this->createMock(Downloader::class);
        $downloader->expects($this->never())->method('run');
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->expects($this->never())->method('run');

        $process = $this->createProcess($settings, $updater, $versionCatalog, $downloader, $fileUpdater, $events);
        $process->run($mode);

        $this->assertSame(['finalizing', 'complete', 100], end($state->snapshots));
        $this->assertProgressNeverGoesBackwards($state->snapshots);
        $this->assertFileDoesNotExist($this->fixturePath.'/uploads/cache/cached.txt');
    }

    public static function databaseModeProvider(): array
    {
        return [
            'regular release' => ['regularRelease'],
            'cutting edge' => ['cuttingEdge'],
        ];
    }

    public function testFileUpdateFailureRecordsFailureReleasesLockCleansFilesAndRethrows(): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        file_put_contents($this->baseDirectory.'/staging/temporary.txt', 'temporary');
        file_put_contents($this->baseDirectory.'/package.zip', 'package');

        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '30.0.00';
        $updater->method('isCuttingEdge')->willReturn(false);
        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('getNextRelease')->willReturn(['filePath' => 'gibbon-30.0.01.zip']);

        $failure = new \RuntimeException('Download failed.');
        $downloader = $this->createMock(Downloader::class);
        $downloader->expects($this->once())
            ->method('run')
            ->willThrowException($failure);
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->expects($this->never())->method('run');

        $process = $this->createProcess($settings, $updater, $versionCatalog, $downloader, $fileUpdater, $events);

        try {
            $process->run('file');
            $this->fail('The worker must rethrow download failures for BackgroundProcessor to record Error.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame($failure, $error);
        }

        $this->assertSame(['downloading', 'failed'], array_slice(end($state->snapshots), 0, 2));
        $this->assertProgressNeverGoesBackwards($state->snapshots);
        $this->assertSame('N', $state->values['System Admin/maintenanceMode']);
        $this->assertSame('failed', $state->values['System Admin/fileUpdater']);
        $this->assertFileDoesNotExist($this->baseDirectory.'/staging/temporary.txt');
        $this->assertFileDoesNotExist($this->baseDirectory.'/package.zip');
        $this->assertFileLockWasReleased();
    }

    /**
     * @dataProvider maintenanceModeProvider
     */
    public function testFileUpdateRestoresMaintenanceModeUnlessTheRollbackFailed(
        ?string $previous,
        ?string $failure,
        string $expected
    ): void {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        if ($previous !== null) {
            $state->values['System Admin/maintenanceMode'] = $previous;
        }

        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '30.0.00';
        $updater->method('isCuttingEdge')->willReturn(false);
        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('getNextRelease')->willReturn(['filePath' => 'gibbon-30.0.01.zip']);
        $downloader = $this->createMock(Downloader::class);
        $downloader->method('run')->willReturn(['index.php']);
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->method('checkPermissions')->willReturn(true);
        if ($failure !== null) {
            $fileUpdater->method('run')->willThrowException(new \RuntimeException($failure));
        }

        $process = $this->createProcess($settings, $updater, $versionCatalog, $downloader, $fileUpdater, $events);

        try {
            $process->run('file');
            $this->assertNull($failure, 'The update should only succeed when no failure was simulated.');
        } catch (\RuntimeException $error) {
            $this->assertSame($failure, $error->getMessage());
        }

        $this->assertSame($expected, $state->values['System Admin/maintenanceMode']);
        $this->assertFileLockWasReleased();
    }

    public static function maintenanceModeProvider(): array
    {
        return [
            'success, was off' => [null, null, 'N'],
            'success, was already on' => ['Y', null, 'Y'],
            'failure, was off' => ['N', FileUpdater::ERROR_REPLACE, 'N'],
            'failure, was already on' => ['Y', FileUpdater::ERROR_REPLACE, 'Y'],
            'rollback failed, was off' => ['N', FileUpdater::ERROR_ROLLBACK, 'Y'],
            'rollback failed, was already on' => ['Y', FileUpdater::ERROR_ROLLBACK, 'Y'],
        ];
    }

    /**
     * @dataProvider phpRequirementProvider
     */
    public function testFileUpdateChecksThePhpVersionTheReleaseRequires(?string $requiredPhp, bool $shouldInstall): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);

        $release = ['filePath' => 'gibbon-30.0.01.zip', 'version' => '30.0.01'];
        if ($requiredPhp !== null) {
            $release['phpVersion'] = $requiredPhp;
        }

        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '30.0.00';
        $updater->method('isCuttingEdge')->willReturn(false);
        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('getNextRelease')->willReturn($release);
        $downloader = $this->createMock(Downloader::class);
        $downloader->expects($shouldInstall ? $this->once() : $this->never())->method('run')->willReturn(['index.php']);
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->method('checkPermissions')->willReturn(true);
        $fileUpdater->expects($shouldInstall ? $this->once() : $this->never())->method('run');

        $process = $this->createProcess($settings, $updater, $versionCatalog, $downloader, $fileUpdater, $events);

        try {
            $process->run('file');
            $this->assertTrue($shouldInstall, 'An update that needs a newer PHP must not be installed.');
        } catch (\RuntimeException $error) {
            $this->assertFalse($shouldInstall);
            $this->assertStringContainsString('requires PHP '.$requiredPhp, $error->getMessage());
            $this->assertSame('failed', end($state->snapshots)[1]);
            $this->assertSame('failed', $state->values['System Admin/fileUpdater']);
        }

        $this->assertSame('N', $state->values['System Admin/maintenanceMode']);
        $this->assertFileLockWasReleased();
    }

    public static function phpRequirementProvider(): array
    {
        return [
            'newer PHP required' => ['99.0.0', false],
            'same PHP required' => [PHP_VERSION, true],
            'older PHP required' => ['8.0', true],
            'empty requirement' => ['', true],
            'no requirement in the catalog' => [null, true],
        ];
    }

    public function testFileUpdateStopsBeforeMaintenanceModeWhenTheZipExtensionIsMissing(): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);

        $downloader = $this->createMock(Downloader::class);
        $downloader->expects($this->never())->method('run');
        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->expects($this->never())->method('getNextRelease');

        $process = $this->createProcess(
            $settings,
            $this->createMock(Updater::class),
            $versionCatalog,
            $downloader,
            $this->createMock(FileUpdater::class),
            $events,
            NoZipSystemUpdateProcess::class
        );

        try {
            $process->run('file');
            $this->fail('An update cannot run without the Zip extension.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame('The PHP Zip extension is not available.', $error->getMessage());
        }

        $this->assertArrayNotHasKey('System Admin/maintenanceMode', $state->values);
        $this->assertSame('failed', end($state->snapshots)[1]);
        $this->assertFileLockWasReleased();
    }

    /**
     * @dataProvider updateModeProvider
     */
    public function testLosingTheUpdateLockLeavesTheRunningUpdateUntouched(string $mode): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        $state->values['System Admin/fileUpdaterBackup'] = '29.0.00';

        $updater = $this->createMock(Updater::class);
        $updater->method('isVersionValid')->willReturn(true);
        $updater->method('isUpdateRequired')->willReturn(true);
        $updater->expects($this->never())->method('update');
        $downloader = $this->createMock(Downloader::class);
        $downloader->expects($this->never())->method('run');
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->expects($this->never())->method('run');
        $fileUpdater->expects($this->never())->method('restore');

        $process = $this->createProcess($settings, $updater, $this->createMock(VersionCatalog::class), $downloader, $fileUpdater, $events);

        // Another update holds the lock. This test plays the part of that update.
        $otherUpdate = fopen($this->baseDirectory.'/fileUpdate.lock', 'c');
        $this->assertTrue(flock($otherUpdate, LOCK_EX | LOCK_NB));

        try {
            $process->run($mode);
            $this->fail('A second update must not run while another holds the lock.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame('Another system update is already running.', $error->getMessage());
        } finally {
            flock($otherUpdate, LOCK_UN);
            fclose($otherUpdate);
        }

        // The running update's progress, status settings and files were never touched
        $this->assertSame([], $state->snapshots);
        $this->assertSame([], $events);
        $this->assertArrayNotHasKey('System Admin/maintenanceMode', $state->values);
        $this->assertArrayNotHasKey('System Admin/fileUpdater', $state->values);
        $this->assertFileLockWasReleased();
    }

    public static function updateModeProvider(): array
    {
        return [
            'file update' => ['file'],
            'restore' => ['restore'],
            'database update' => ['regularRelease'],
        ];
    }

    public function testDatabaseUpdateHoldsTheLockWhileRunningAndReleasesItAfterwards(): void
    {
        $events = [];
        [$settings] = $this->createSettingGateway($events);
        $heldDuringUpdate = null;

        $updater = $this->createMock(Updater::class);
        $updater->method('isVersionValid')->willReturn(true);
        $updater->method('isUpdateRequired')->willReturn(true);
        $updater->method('update')->willReturnCallback(function () use (&$heldDuringUpdate): array {
            $probe = fopen($this->baseDirectory.'/fileUpdate.lock', 'c');
            $heldDuringUpdate = !flock($probe, LOCK_EX | LOCK_NB);
            fclose($probe);

            return [];
        });

        $this->createProcess(
            $settings,
            $updater,
            $this->createMock(VersionCatalog::class),
            $this->createMock(Downloader::class),
            $this->createMock(FileUpdater::class),
            $events
        )->run('regularRelease');

        $this->assertTrue($heldDuringUpdate, 'Another update could have taken the lock during the database update.');
        $this->assertFileLockWasReleased();
    }

    public function testDatabaseUpdateReleasesTheLockWhenItFails(): void
    {
        $events = [];
        [$settings] = $this->createSettingGateway($events);

        $updater = $this->createMock(Updater::class);
        $updater->method('isVersionValid')->willReturn(true);
        $updater->method('isUpdateRequired')->willReturn(true);
        $updater->method('update')->willReturn(['CHANGEDB.php failed']);

        try {
            $this->createProcess(
                $settings,
                $updater,
                $this->createMock(VersionCatalog::class),
                $this->createMock(Downloader::class),
                $this->createMock(FileUpdater::class),
                $events
            )->run('regularRelease');
            $this->fail('The failed database update must be reported.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame('Database update failed.', $error->getMessage());
        }

        $this->assertFileLockWasReleased();
    }

    public function testDatabaseUpdateStillRunsWhenThereIsNoLockFolder(): void
    {
        // Files can be updated by hand on hosts where the updater cannot create its folder.
        $this->assertTrue((new Filesystem())->deleteDirectory($this->baseDirectory));

        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        $updater = $this->createMock(Updater::class);
        $updater->method('isVersionValid')->willReturn(true);
        $updater->method('isUpdateRequired')->willReturn(true);
        $updater->expects($this->once())->method('update')->willReturn([]);

        $this->createProcess(
            $settings,
            $updater,
            $this->createMock(VersionCatalog::class),
            $this->createMock(Downloader::class),
            $this->createMock(FileUpdater::class),
            $events
        )->run('regularRelease');

        $this->assertSame('complete', end($state->snapshots)[1]);
        $this->assertDirectoryDoesNotExist($this->baseDirectory);
    }

    /**
     * @dataProvider restoreDatabaseVersionProvider
     */
    public function testRestoreIsRefusedWhenTheDatabaseIsNewerThanTheBackup(?string $databaseVersion, bool $shouldRestore): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        $state->values['System Admin/fileUpdaterBackup'] = '30.0.00';
        $state->values['System Admin/maintenanceMode'] = 'N';

        $updater = $this->createMock(Updater::class);
        $updater->versionDB = $databaseVersion;
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->expects($shouldRestore ? $this->once() : $this->never())->method('restore');

        $process = $this->createProcess(
            $settings,
            $updater,
            $this->createMock(VersionCatalog::class),
            $this->createMock(Downloader::class),
            $fileUpdater,
            $events
        );

        try {
            $process->run('restore');
            $this->assertTrue($shouldRestore, 'Files older than the database must not be restored.');
        } catch (\RuntimeException $error) {
            $this->assertFalse($shouldRestore);
            $this->assertStringContainsString('newer than the backup', $error->getMessage());
            $this->assertSame('failed', end($state->snapshots)[1]);
        }

        $this->assertSame('N', $state->values['System Admin/maintenanceMode']);
        $this->assertFileLockWasReleased();
    }

    public static function restoreDatabaseVersionProvider(): array
    {
        return [
            'database is newer than the backup' => ['30.0.01', false],
            'database is the same version as the backup' => ['30.0.00', true],
            'database is older than the backup' => ['29.0.00', true],
            'database version is unknown' => [null, true],
        ];
    }

    /**
     * @dataProvider swapOutcomeProvider
     */
    public function testNothingIsAutoloadedAfterTheFilesAreSwapped(?string $failure): void
    {
        $events = [];
        [$settings] = $this->createSettingGateway($events);
        $autoloaded = [];
        $spy = function (string $class) use (&$autoloaded): void {
            $autoloaded[] = $class;
        };

        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '30.0.00';
        $updater->method('isCuttingEdge')->willReturn(false);
        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('getNextRelease')->willReturn(['filePath' => 'gibbon-30.0.01.zip']);
        $downloader = $this->createMock(Downloader::class);
        $downloader->method('run')->willReturn(['index.php']);
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->method('checkPermissions')->willReturn(true);

        // From the moment the files are replaced, report any class that is loaded for the first time
        $fileUpdater->method('run')->willReturnCallback(function () use ($spy, $failure): void {
            spl_autoload_register($spy, true, true);

            if ($failure !== null) {
                throw new \RuntimeException($failure);
            }
        });

        $process = $this->createProcess($settings, $updater, $versionCatalog, $downloader, $fileUpdater, $events);

        try {
            $process->run('file');
        } catch (\RuntimeException $error) {
            $this->assertSame($failure, $error->getMessage());
        } finally {
            spl_autoload_unregister($spy);
        }

        $this->assertSame([], $autoloaded, 'Classes loaded after the swap would come from the new files.');
    }

    public static function swapOutcomeProvider(): array
    {
        return [
            'update succeeds' => [null],
            'files cannot be replaced' => [FileUpdater::ERROR_REPLACE],
            'rollback fails' => [FileUpdater::ERROR_ROLLBACK],
        ];
    }

    /**
     * @dataProvider restoreMaintenanceModeProvider
     */
    public function testRestoreRestoresMaintenanceModeUnlessTheRestoreFailedPartWay(
        string $previous,
        ?string $failure,
        string $expected
    ): void {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        $state->values['System Admin/fileUpdaterBackup'] = '29.0.00';
        $state->values['System Admin/maintenanceMode'] = $previous;

        $fileUpdater = $this->createMock(FileUpdater::class);
        if ($failure !== null) {
            $fileUpdater->method('restore')->willThrowException(new \RuntimeException($failure));
        }

        $process = $this->createProcess(
            $settings,
            $this->createMock(Updater::class),
            $this->createMock(VersionCatalog::class),
            $this->createMock(Downloader::class),
            $fileUpdater,
            $events
        );

        try {
            $process->run('restore');
            $this->assertNull($failure, 'The restore should only succeed when no failure was simulated.');
        } catch (\RuntimeException $error) {
            $this->assertSame($failure, $error->getMessage());
        }

        $this->assertSame($expected, $state->values['System Admin/maintenanceMode']);
        $this->assertFileLockWasReleased();
    }

    public static function restoreMaintenanceModeProvider(): array
    {
        return [
            'success, was off' => ['N', null, 'N'],
            'success, was already on' => ['Y', null, 'Y'],
            'failure before any file changed' => ['N', Downloader::ERROR_INVALID, 'N'],
            'restore failed part way' => ['N', FileUpdater::ERROR_ROLLBACK, 'Y'],
        ];
    }

    public function testDatabaseUpdateErrorsRecordFailureAndAreRethrown(): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);

        $updater = $this->createMock(Updater::class);
        $updater->method('isVersionValid')->willReturn(true);
        $updater->method('isUpdateRequired')->willReturn(true);
        $updater->expects($this->once())->method('update')->willReturn(['CHANGEDB.php failed']);

        $downloader = $this->createMock(Downloader::class);
        $downloader->expects($this->never())->method('run');
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->expects($this->never())->method('run');

        $process = $this->createProcess(
            $settings,
            $updater,
            $this->createMock(VersionCatalog::class),
            $downloader,
            $fileUpdater,
            $events
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database update failed.');

        try {
            $process->run('regularRelease');
        } finally {
            $this->assertSame(['installing', 'failed'], array_slice(end($state->snapshots), 0, 2));
        }
    }

    public function testCuttingEdgeCheckLeavesTheInstallAloneWhenTheCommitIsUnchanged(): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        $state->values['System Admin/cuttingEdgeCommit'] = $this->commit('a');

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('getCuttingEdgeRelease')->with('31.0.00')->willReturn($this->developmentRelease());
        $versionCatalog->method('getCuttingEdgeCommit')->willReturn($this->commit('a'));
        $versionCatalog->expects($this->never())->method('getNextRelease');

        $downloader = $this->createMock(Downloader::class);
        $downloader->expects($this->never())->method('run');
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->expects($this->never())->method('run');

        $process = $this->createProcess($settings, $this->createDevelopmentUpdater(), $versionCatalog, $downloader, $fileUpdater, $events);
        $process->run('cuttingEdgeCheck');

        $this->assertSame(['upToDate', 'complete', 100], end($state->snapshots));
        $this->assertSame([], array_filter($events, fn (string $event) => str_starts_with($event, 'setting:maintenanceMode')), 'Maintenance mode is not touched.');
        $this->assertSame($this->commit('a'), $state->values['System Admin/cuttingEdgeCommit']);
        $this->assertFileLockWasReleased();
    }

    /**
     * @dataProvider staleCommitProvider
     */
    public function testCuttingEdgeCheckInstallsTheBuildWhenTheCommitIsBlankOrDifferent(string $installedCommit): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        $state->values['System Admin/cuttingEdgeCommit'] = $installedCommit;

        $release = $this->developmentRelease();
        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('getCuttingEdgeRelease')->willReturn($release);
        $versionCatalog->method('getCuttingEdgeCommit')->willReturn($this->commit('b'));
        $versionCatalog->expects($this->never())->method('getNextRelease');

        $downloader = $this->createMock(Downloader::class);
        $downloader->expects($this->once())
            ->method('run')
            ->with($release)
            ->willReturn(['index.php']);
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->method('checkPermissions')->willReturn(true);
        $fileUpdater->expects($this->once())->method('run')->with(['index.php']);

        $process = $this->createProcess($settings, $this->createDevelopmentUpdater(), $versionCatalog, $downloader, $fileUpdater, $events);
        $process->run('cuttingEdgeCheck');

        $this->assertSame(['finalizing', 'complete', 100], end($state->snapshots));
        $this->assertSame($this->commit('b'), $state->values['System Admin/cuttingEdgeCommit'], 'The installed commit is recorded.');
        $this->assertSame('N', $state->values['System Admin/maintenanceMode']);
        $this->assertFileLockWasReleased();
    }

    public static function staleCommitProvider(): array
    {
        return [
            'no commit recorded' => [''],
            'an older commit' => [str_repeat('a', 40)],
        ];
    }

    public function testCuttingEdgeCheckFailureIsRecordedWithoutTouchingTheInstall(): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        $state->values['System Admin/cuttingEdgeCommit'] = $this->commit('a');

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('getCuttingEdgeRelease')->willReturn($this->developmentRelease());
        $versionCatalog->method('getCuttingEdgeCommit')->willThrowException(new \RuntimeException('GitHub is unreachable.'));

        $downloader = $this->createMock(Downloader::class);
        $downloader->expects($this->never())->method('run');
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->expects($this->never())->method('run');

        $process = $this->createProcess($settings, $this->createDevelopmentUpdater(), $versionCatalog, $downloader, $fileUpdater, $events);

        try {
            $process->run('cuttingEdgeCheck');
            $this->fail('A check that cannot reach GitHub must report a failure.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame('GitHub is unreachable.', $error->getMessage());
        }

        $this->assertSame(['checking', 'failed', 10], end($state->snapshots));
        $this->assertSame([], array_filter($events, fn (string $event) => str_starts_with($event, 'setting:maintenanceMode')));
        $this->assertSame($this->commit('a'), $state->values['System Admin/cuttingEdgeCommit']);
        $this->assertFileLockWasReleased();
    }

    public function testADevelopmentUpdateRecordsTheCommitItInstalled(): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('getNextRelease')->willReturn($this->developmentRelease('31.0.01'));
        $versionCatalog->method('getCuttingEdgeCommit')->willReturn($this->commit('c'));

        [$downloader, $fileUpdater] = $this->createInstallingDoubles();
        $process = $this->createProcess($settings, $this->createDevelopmentUpdater(), $versionCatalog, $downloader, $fileUpdater, $events);
        $process->run('file');

        $this->assertSame($this->commit('c'), $state->values['System Admin/cuttingEdgeCommit']);
        $this->assertSame(['finalizing', 'complete', 100], end($state->snapshots));
    }

    public function testADevelopmentUpdateStillInstallsWhenItsCommitCannotBeRead(): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        $state->values['System Admin/cuttingEdgeCommit'] = $this->commit('a');

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('getNextRelease')->willReturn($this->developmentRelease('31.0.01'));
        $versionCatalog->method('getCuttingEdgeCommit')->willThrowException(new \RuntimeException('GitHub is unreachable.'));

        [$downloader, $fileUpdater] = $this->createInstallingDoubles();
        $process = $this->createProcess($settings, $this->createDevelopmentUpdater(), $versionCatalog, $downloader, $fileUpdater, $events);
        $process->run('file');

        // A blank commit makes the next check install the build again, rather than trust an unknown commit
        $this->assertSame('', $state->values['System Admin/cuttingEdgeCommit']);
        $this->assertSame(['finalizing', 'complete', 100], end($state->snapshots));
        $this->assertSame('N', $state->values['System Admin/maintenanceMode']);
    }

    private function commit(string $character): string
    {
        return str_repeat($character, 40);
    }

    private function developmentRelease(string $version = '31.0.00'): array
    {
        return [
            'version' => $version,
            'status' => 'development',
            'checksum' => '',
            'filePath' => 'https://github.com/GibbonEdu/core/archive/refs/heads/v'.$version.'.zip',
            'phpVersion' => '8.2',
        ];
    }

    private function createDevelopmentUpdater(): Updater
    {
        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '31.0.00';
        $updater->method('isCuttingEdge')->willReturn(true);

        return $updater;
    }

    /**
     * @return array{0: Downloader, 1: FileUpdater}
     */
    private function createInstallingDoubles(): array
    {
        $downloader = $this->createMock(Downloader::class);
        $downloader->method('run')->willReturn(['index.php']);

        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->method('checkPermissions')->willReturn(true);

        return [$downloader, $fileUpdater];
    }

    public function testUnsupportedModeDoesNotStartAnUpdate(): void
    {
        $events = [];
        $settings = $this->createMock(SettingGateway::class);
        $settings->expects($this->never())->method('getSettingByScope');
        $settings->expects($this->never())->method('updateSettingByScope');

        $process = $this->createProcess(
            $settings,
            $this->createMock(Updater::class),
            $this->createMock(VersionCatalog::class),
            $this->createMock(Downloader::class),
            $this->createMock(FileUpdater::class),
            $events
        );

        $this->expectException(\InvalidArgumentException::class);
        $process->run('InnoDB');
    }

    /**
     * @return array{0: SettingGateway, 1: object{values: array<string, string>, snapshots: array<int, array{0: ?string, 1: string, 2: ?int}>}}
     */
    private function createSettingGateway(array &$events): array
    {
        $state = (object) [
            'values' => [],
            'snapshots' => [],
        ];
        $settings = $this->createMock(SettingGateway::class);
        $settings->method('getSettingByScope')
            ->willReturnCallback(function (string $scope, string $name) use ($state): string|false {
                return $state->values[$scope.'/'.$name] ?? false;
            });
        $settings->method('updateSettingByScope')
            ->willReturnCallback(function (string $scope, string $name, string $value) use ($state, &$events): bool {
                $state->values[$scope.'/'.$name] = $value;

                if ($scope === 'System Admin' && $name === 'backgroundUpdateProgress') {
                    $snapshot = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    $state->snapshots[] = [$snapshot['stage'], $snapshot['status'], $snapshot['progress']];
                    $events[] = 'progress:'.$snapshot['stage'].':'.$snapshot['status'];
                } else {
                    $events[] = 'setting:'.$name.':'.$value;
                }

                return true;
            });

        return [$settings, $state];
    }

    /**
     * The position of an event in the recorded sequence. Fails when the event never happened.
     *
     * @param array<int, string> $events
     * @param string $event
     * @return int
     */
    private function eventPosition(array $events, string $event): int
    {
        $position = array_search($event, $events, true);
        $this->assertNotFalse($position, sprintf('The event "%s" did not happen.', $event));

        return (int) $position;
    }

    /**
     * Progress only moves forward, so the progress bar never goes back.
     *
     * @param array<int, array{0: ?string, 1: string, 2: ?int}> $snapshots
     * @return void
     */
    private function assertProgressNeverGoesBackwards(array $snapshots): void
    {
        $percentages = array_values(array_filter(array_column($snapshots, 2), fn ($value) => $value !== null));
        $sorted = $percentages;
        sort($sorted);

        $this->assertSame($sorted, $percentages, 'Progress must never move backwards.');
    }

    private function createProcess(
        SettingGateway $settings,
        Updater $updater,
        VersionCatalog $versionCatalog,
        Downloader $downloader,
        FileUpdater $fileUpdater,
        array &$events,
        string $processClass = SystemUpdateProcess::class
    ): SystemUpdateProcess {
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturnCallback(function (string $key) {
            return $key === 'absolutePath' ? $this->fixturePath : null;
        });
        $session->method('forget')->willReturnCallback(function (string $key) use (&$events): void {
            $events[] = 'session:forget:'.$key;
        });

        return new $processClass(
            $session,
            $settings,
            $updater,
            $versionCatalog,
            $downloader,
            $fileUpdater,
            new SystemUpdateProgress($settings),
            new Filesystem()
        );
    }

    private function assertFileLockWasReleased(): void
    {
        $lock = fopen($this->baseDirectory.'/fileUpdate.lock', 'c');
        $this->assertNotFalse($lock);
        $this->assertTrue(flock($lock, LOCK_EX | LOCK_NB));
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    private function removeFixtureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($path);
    }
}

class NoZipSystemUpdateProcess extends SystemUpdateProcess
{
    protected function hasZipSupport(): bool
    {
        return false;
    }
}
