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
use Gibbon\Domain\System\I18nGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Filesystem\Downloader;
use Gibbon\Filesystem\FileUpdater;
use Gibbon\Filesystem\VersionCatalog;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

require_once dirname(__DIR__, 4).'/modules/System Admin/src/SystemUpdateProgress.php';
require_once dirname(__DIR__, 4).'/modules/System Admin/src/SystemUpdateProcess.php';

/**
 * @covers \Gibbon\Module\SystemAdmin\SystemUpdateProcess
 */
class SystemUpdateProcessTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturePath = sys_get_temp_dir().'/SystemUpdateProcessTest-'.bin2hex(random_bytes(8));
        mkdir($this->fixturePath.'/modules/System Admin', 0755, true);
        mkdir($this->fixturePath.'/uploads/cache', 0755, true);
        mkdir($this->fixturePath.'/var', 0755, true);
        mkdir($this->fixturePath.'/backups/fileUpdate/staging', 0755, true);

        file_put_contents(
            $this->fixturePath.'/modules/System Admin/moduleFunctions.php',
            '<?php require_once '.var_export(dirname(__DIR__, 4).'/modules/System Admin/moduleFunctions.php', true).';'
        );
    }

    protected function tearDown(): void
    {
        $this->removeFixtureDirectory($this->fixturePath);

        parent::tearDown();
    }

    public function testFileUpdateUsesExistingCollaboratorsInOrderAndRecordsCompletion(): void
    {
        $events = [];
        $downloadProgress = null;
        $installProgress = null;
        [$settings, $state] = $this->createSettingGateway($events);
        file_put_contents($this->fixturePath.'/backups/fileUpdate/staging/temporary.txt', 'temporary');
        file_put_contents($this->fixturePath.'/backups/fileUpdate/package.zip', 'package');
        file_put_contents($this->fixturePath.'/uploads/cache/cached.txt', 'cache');

        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '30.0.00';
        $updater->method('isCuttingEdge')->willReturn(false);

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->expects($this->once())
            ->method('getNextRelease')
            ->with('30.0.00', false)
            ->willReturnCallback(function () use (&$events): array {
                $events[] = 'catalog';

                return ['filePath' => 'gibbon-30.0.01.zip'];
            });

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

        $this->assertSame([
            'progress::working',
            'progress:preparing:working',
            'setting:maintenanceMode:Y',
            'setting:fileUpdater:running',
            'progress:downloading:working',
            'catalog',
            'downloader',
            'progress:downloading:working',
            'progress:installing:working',
            'file-updater',
            'progress:installing:working',
            'progress:finalizing:working',
            'session:forget:pageLoads',
            'setting:fileUpdater:idle',
            'setting:fileUpdaterBackup:30.0.00',
            'setting:maintenanceMode:N',
            'progress:finalizing:complete',
        ], $events);
        $this->assertSame([
            [null, 'working', null],
            ['preparing', 'working', 5],
            ['downloading', 'working', 10],
            ['downloading', 'working', 40],
            ['installing', 'working', 70],
            ['installing', 'working', 82],
            ['finalizing', 'working', 95],
            ['finalizing', 'complete', 100],
        ], $state->snapshots);
        $this->assertSame('N', $state->values['System Admin/maintenanceMode']);
        $this->assertFileDoesNotExist($this->fixturePath.'/backups/fileUpdate/staging/temporary.txt');
        $this->assertFileDoesNotExist($this->fixturePath.'/backups/fileUpdate/package.zip');
        $this->assertFileDoesNotExist($this->fixturePath.'/uploads/cache/cached.txt');
        $this->assertFileLockWasReleased();
    }

    /**
     * @dataProvider databaseModeProvider
     */
    public function testDatabaseModesUseUpdaterThenLanguageAndCacheCleanup(string $mode): void
    {
        $events = [];
        [$settings, $state] = $this->createSettingGateway($events);
        file_put_contents($this->fixturePath.'/uploads/cache/cached.txt', 'cache');
        file_put_contents($this->fixturePath.'/var/cached.txt', 'cache');

        $updater = $this->createMock(Updater::class);
        $updater->versionDB = '30.0.01';
        $updater->expects($this->once())->method('isVersionValid')->willReturnCallback(function () use (&$events): bool {
            $events[] = 'updater:valid';
            return true;
        });
        $updater->expects($this->once())->method('isUpdateRequired')->willReturnCallback(function () use (&$events): bool {
            $events[] = 'updater:required';
            return true;
        });
        $updater->expects($this->once())->method('update')->willReturnCallback(function () use (&$events): array {
            $events[] = 'updater:update';
            return [];
        });

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $downloader = $this->createMock(Downloader::class);
        $downloader->expects($this->never())->method('run');
        $fileUpdater = $this->createMock(FileUpdater::class);
        $fileUpdater->expects($this->never())->method('run');

        $process = $this->createProcess($settings, $updater, $versionCatalog, $downloader, $fileUpdater, $events);
        $process->setContainer($this->createLanguageContainer($events));
        $process->run($mode);

        $this->assertSame([
            'progress::working',
            'progress:preparing:working',
            'updater:valid',
            'updater:required',
            'progress:installing:working',
            'updater:update',
            'progress:finalizing:working',
            'language:refresh',
            'session:forget:pageLoads',
            'progress:finalizing:complete',
        ], $events);
        $this->assertSame([
            [null, 'working', null],
            ['preparing', 'working', 10],
            ['installing', 'working', 70],
            ['finalizing', 'working', 90],
            ['finalizing', 'complete', 100],
        ], $state->snapshots);
        $this->assertFileDoesNotExist($this->fixturePath.'/uploads/cache/cached.txt');
        $this->assertDirectoryDoesNotExist($this->fixturePath.'/var');
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
        file_put_contents($this->fixturePath.'/backups/fileUpdate/staging/temporary.txt', 'temporary');
        file_put_contents($this->fixturePath.'/backups/fileUpdate/package.zip', 'package');

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
        } catch (\RuntimeException $error) {
            $this->assertSame($failure, $error);
        }

        $this->assertSame([
            [null, 'working', null],
            ['preparing', 'working', 5],
            ['downloading', 'working', 10],
            ['downloading', 'failed', 10],
        ], $state->snapshots);
        $this->assertSame('N', $state->values['System Admin/maintenanceMode']);
        $this->assertSame('failed', $state->values['System Admin/fileUpdater']);
        $this->assertFileDoesNotExist($this->fixturePath.'/backups/fileUpdate/staging/temporary.txt');
        $this->assertFileDoesNotExist($this->fixturePath.'/backups/fileUpdate/package.zip');
        $this->assertFileLockWasReleased();
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
            $this->assertSame([
                [null, 'working', null],
                ['preparing', 'working', 10],
                ['installing', 'working', 70],
                ['installing', 'failed', 70],
            ], $state->snapshots);
        }
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

    private function createProcess(
        SettingGateway $settings,
        Updater $updater,
        VersionCatalog $versionCatalog,
        Downloader $downloader,
        FileUpdater $fileUpdater,
        array &$events
    ): SystemUpdateProcess {
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturnCallback(function (string $key) {
            return $key === 'absolutePath' ? $this->fixturePath : null;
        });
        $session->method('forget')->willReturnCallback(function (string $key) use (&$events): void {
            $events[] = 'session:forget:'.$key;
        });

        return new SystemUpdateProcess(
            $session,
            $settings,
            $updater,
            $versionCatalog,
            $downloader,
            $fileUpdater,
            new SystemUpdateProgress($settings)
        );
    }

    private function createLanguageContainer(array &$events): ContainerInterface
    {
        $i18nGateway = $this->createMock(I18nGateway::class);
        $i18nGateway->expects($this->once())
            ->method('selectBy')
            ->with(['active' => 'Y'])
            ->willReturnCallback(function () use (&$events): object {
                $events[] = 'language:refresh';

                return new class {
                    public function fetchAll(): array
                    {
                        return [];
                    }
                };
            });

        $session = $this->createMock(Session::class);
        $session->method('get')->with('absolutePath')->willReturn($this->fixturePath);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function (string $id) use ($session, $i18nGateway): object {
            return match ($id) {
                'session' => $session,
                I18nGateway::class => $i18nGateway,
            };
        });

        return $container;
    }

    private function assertFileLockWasReleased(): void
    {
        $lock = fopen($this->fixturePath.'/backups/fileUpdate/fileUpdate.lock', 'c');
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
