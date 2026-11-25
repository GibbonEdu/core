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

namespace Gibbon\Filesystem;

use Gibbon\Contracts\Services\Session;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Gibbon\Filesystem\FileUpdater
 */
class FileUpdaterTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturePath = sys_get_temp_dir().'/FileUpdaterTest-'.bin2hex(random_bytes(8));
        mkdir($this->fixturePath.'/backups/fileUpdate/staging/vendor/package', 0755, true);
        mkdir($this->fixturePath.'/vendor/package', 0755, true);
        mkdir($this->fixturePath.'/modules/Example', 0755, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->fixturePath);

        parent::tearDown();
    }

    public function testVendorIsReplacedAsOneDirectoryWhileOtherFilesAreInstalledIndividually(): void
    {
        $this->writeFile('/vendor/package/old.php', 'old vendor file');
        $this->writeFile('/vendor/obsolete.php', 'obsolete vendor file');
        $this->writeFile('/modules/Example/update.php', 'old module file');
        $this->writeFile('/modules/Example/unchanged.php', 'unchanged module file');

        $this->writeFile('/backups/fileUpdate/staging/vendor/package/new.php', 'new vendor file');
        $this->writeFile('/backups/fileUpdate/staging/vendor/autoload.php', 'new autoloader');
        $this->writeFile('/backups/fileUpdate/staging/modules/Example/update.php', 'new module file');

        $filesystem = new FileUpdaterFilesystem();
        $updater = $this->createFileUpdater($filesystem);
        $progress = [];
        $updater->setProgressCallback(function (int $percent) use (&$progress): void {
            $progress[] = $percent;
        });
        $updater->run([
            'vendor/package/new.php',
            'modules/Example/update.php',
            'vendor/autoload.php',
        ]);

        $stagingPath = str_replace('\\', '/', realpath($this->fixturePath.'/backups/fileUpdate/staging'));
        $this->assertSame([
            [$stagingPath.'/vendor', $this->fixturePath.'/vendor'],
        ], $filesystem->replacedDirectories);
        $this->assertSame([
            [$stagingPath.'/modules/Example/update.php', $this->fixturePath.'/modules/Example/update.php'],
        ], $filesystem->replacedFiles);

        $this->assertFileExists($this->fixturePath.'/vendor/package/new.php');
        $this->assertFileDoesNotExist($this->fixturePath.'/vendor/package/old.php');
        $this->assertFileDoesNotExist($this->fixturePath.'/vendor/obsolete.php');
        $this->assertSame('new module file', file_get_contents($this->fixturePath.'/modules/Example/update.php'));
        $this->assertSame('unchanged module file', file_get_contents($this->fixturePath.'/modules/Example/unchanged.php'));
        $this->assertFileExists($this->fixturePath.'/backups/fileUpdate/previousVersionBackup/vendor/package/old.php');
        $this->assertSame('old module file', file_get_contents($this->fixturePath.'/backups/fileUpdate/previousVersionBackup/modules/Example/update.php'));
        $this->assertSame(0, $progress[0]);
        $this->assertSame(100, $progress[array_key_last($progress)]);

        $sortedProgress = $progress;
        sort($sortedProgress);
        $this->assertSame($sortedProgress, $progress);
    }

    public function testRestoreAlsoReplacesVendorAsOneDirectory(): void
    {
        $this->writeFile('/vendor/old.php', 'old vendor file');
        $this->writeFile('/modules/Example/update.php', 'old module file');
        $this->writeFile('/backups/fileUpdate/staging/vendor/new.php', 'new vendor file');
        $this->writeFile('/backups/fileUpdate/staging/modules/Example/update.php', 'new module file');

        $filesystem = new FileUpdaterFilesystem();
        $updater = $this->createFileUpdater($filesystem);
        $updater->run(['vendor/new.php', 'modules/Example/update.php']);
        $filesystem->replacedDirectories = [];
        $filesystem->replacedFiles = [];

        $updater->restore();

        $this->assertCount(1, $filesystem->replacedDirectories);
        $this->assertSame($this->fixturePath.'/vendor', $filesystem->replacedDirectories[0][1]);
        $this->assertSame([
            [
                $this->fixturePath.'/backups/fileUpdate/previousVersionBackup/modules/Example/update.php',
                $this->fixturePath.'/modules/Example/update.php',
            ],
        ], $filesystem->replacedFiles);
        $this->assertFileExists($this->fixturePath.'/vendor/old.php');
        $this->assertFileDoesNotExist($this->fixturePath.'/vendor/new.php');
        $this->assertSame('old module file', file_get_contents($this->fixturePath.'/modules/Example/update.php'));
    }

    public function testFailedFileReplacementRollsBackVendorAsOneDirectory(): void
    {
        $this->writeFile('/vendor/old.php', 'old vendor file');
        $this->writeFile('/modules/Example/update.php', 'old module file');
        $this->writeFile('/backups/fileUpdate/staging/vendor/new.php', 'new vendor file');
        $this->writeFile('/backups/fileUpdate/staging/modules/Example/update.php', 'new module file');

        $filesystem = new FileUpdaterFilesystem();
        $filesystem->failReplaceFileDestination = $this->fixturePath.'/modules/Example/update.php';
        $updater = $this->createFileUpdater($filesystem);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(FileUpdater::ERROR_REPLACE);
        try {
            $updater->run(['vendor/new.php', 'modules/Example/update.php']);
        } finally {
            $this->assertCount(2, $filesystem->replacedDirectories);
            $this->assertFileExists($this->fixturePath.'/vendor/old.php');
            $this->assertFileDoesNotExist($this->fixturePath.'/vendor/new.php');
            $this->assertSame('old module file', file_get_contents($this->fixturePath.'/modules/Example/update.php'));
        }
    }

    public function testChecksManagedDirectoriesLockAndExactUpdatePaths(): void
    {
        $this->writeFile('/backups/fileUpdate/staging/modules/Example/update.php', 'new module file');

        $updater = $this->createFileUpdater(new FileUpdaterFilesystem());

        $this->assertTrue($updater->checkPermissions(['modules/Example/update.php']));
        $this->assertFileExists($this->fixturePath.'/backups/fileUpdate/fileUpdate.lock');
        $this->assertDirectoryExists($this->fixturePath.'/backups/fileUpdate/previousVersionBackup');
    }

    public function testRejectsAnUnwritableTargetDirectoryInsteadOfCheckingOnlyTheApplicationRoot(): void
    {
        $this->writeFile('/backups/fileUpdate/staging/modules/Example/update.php', 'new module file');
        $filesystem = new FileUpdaterFilesystem();
        $filesystem->blockedDirectory = $this->fixturePath.'/modules/Example';

        $this->assertFalse($this->createFileUpdater($filesystem)->checkPermissions(['modules/Example/update.php']));
    }

    public function testCalculatesUpdateWorkAndProbesTheUpdateLock(): void
    {
        $directory = $this->fixturePath.'/work';
        mkdir($directory.'/nested', 0755, true);
        file_put_contents($directory.'/small.txt', 'abc');
        file_put_contents($directory.'/nested/large.txt', '12345');

        $updater = $this->createFileUpdaterProbe(new Filesystem());
        $updater->setProgressCallback(static function (): void {
        });

        $this->assertTrue($updater->probeLockForTest($this->fixturePath.'/backups/fileUpdate/fileUpdate.lock'));
        $this->assertSame(3, $updater->fileWorkForTest($directory.'/small.txt'));
        $this->assertSame(8, $updater->pathWorkForTest($directory));
        $this->assertSame(8, $updater->calculateWorkForTest(['work'], $this->fixturePath));
        $this->assertSame(1, $updater->fileWorkForTest($directory.'/missing.txt'));
    }

    public function testUsesInjectedFilesystemForPathSafety(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->with('absolutePath')->willReturn($this->fixturePath);
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())
            ->method('isSafeRelativePath')
            ->with('../outside.php')
            ->willReturn(false);

        $updater = new FileUpdater($session, $filesystem);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(FileUpdater::ERROR_INVALID);
        $updater->run(['../outside.php']);
    }

    private function createFileUpdaterProbe(Filesystem $filesystem): FileUpdaterProbe
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->with('absolutePath')->willReturn($this->fixturePath);

        return new FileUpdaterProbe($session, $filesystem);
    }

    private function createFileUpdater(Filesystem $filesystem): FileUpdater
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->with('absolutePath')->willReturn($this->fixturePath);

        return new FileUpdater($session, $filesystem);
    }

    private function writeFile(string $relativePath, string $contents): void
    {
        $path = $this->fixturePath.$relativePath;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $contents);
    }
}

class FileUpdaterFilesystem extends Filesystem
{
    public array $replacedDirectories = [];
    public array $replacedFiles = [];
    public ?string $failReplaceFileDestination = null;
    public ?string $blockedDirectory = null;

    private bool $replaceFileFailed = false;

    public function probeDirectory(string $directory, array $operations = self::DEFAULT_OPERATIONS): bool
    {
        if ($this->blockedDirectory !== null && rtrim($directory, '/') === rtrim($this->blockedDirectory, '/')) {
            return false;
        }

        return parent::probeDirectory($directory, $operations);
    }

    public function replaceDirectory(string $source, string $destination, ?callable $onFileCopied = null): bool
    {
        $this->replacedDirectories[] = [$source, $destination];

        return parent::replaceDirectory($source, $destination, $onFileCopied);
    }

    public function replaceFile(string $source, string $destination): bool
    {
        $this->replacedFiles[] = [$source, $destination];

        if ($destination === $this->failReplaceFileDestination && !$this->replaceFileFailed) {
            $this->replaceFileFailed = true;
            return false;
        }

        return parent::replaceFile($source, $destination);
    }
}

class FileUpdaterProbe extends FileUpdater
{
    public function probeLockForTest(string $path): bool
    {
        return $this->probeLock($path);
    }

    public function calculateWorkForTest(array $files, string $root): int
    {
        return $this->calculateWork($files, $root);
    }

    public function pathWorkForTest(string $path): int
    {
        return $this->pathWork($path);
    }

    public function fileWorkForTest(string $path): int
    {
        return $this->fileWork($path);
    }
}
