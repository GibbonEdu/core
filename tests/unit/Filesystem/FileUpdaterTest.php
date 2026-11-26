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
        $this->writeFile('/version.php', $this->versionFile('30.0.00'));
        $this->writeFile('/backups/fileUpdate/staging/vendor/new.php', 'new vendor file');
        $this->writeFile('/backups/fileUpdate/staging/modules/Example/update.php', 'new module file');
        $this->writeFile('/backups/fileUpdate/staging/version.php', $this->versionFile('30.0.01'));

        $filesystem = new FileUpdaterFilesystem();
        $updater = $this->createFileUpdater($filesystem);
        $updater->run(['vendor/new.php', 'modules/Example/update.php', 'version.php']);
        $filesystem->replacedDirectories = [];
        $filesystem->replacedFiles = [];

        $updater->restore('30.0.00');

        $this->assertCount(1, $filesystem->replacedDirectories);
        $this->assertSame($this->fixturePath.'/vendor', $filesystem->replacedDirectories[0][1]);
        $restored = array_column($filesystem->replacedFiles, 1);
        sort($restored);
        $this->assertSame([
            $this->fixturePath.'/modules/Example/update.php',
            $this->fixturePath.'/version.php',
        ], $restored);
        $this->assertStringContainsString("'30.0.00'", file_get_contents($this->fixturePath.'/version.php'));
        $this->assertFileExists($this->fixturePath.'/vendor/old.php');
        $this->assertFileDoesNotExist($this->fixturePath.'/vendor/new.php');
        $this->assertSame('old module file', file_get_contents($this->fixturePath.'/modules/Example/update.php'));
    }

    public function testRestoreFailsWithoutChangingFilesWhenTheBackupDirectoryIsEmpty(): void
    {
        // An empty backup directory can exist, for example one made by an update that stopped early.
        $this->writeFile('/modules/Example/update.php', 'live module file');
        mkdir($this->fixturePath.'/backups/fileUpdate/previousVersionBackup', 0755, true);

        $filesystem = new FileUpdaterFilesystem();
        $updater = $this->createFileUpdater($filesystem);

        try {
            $updater->restore('30.0.00');
            $this->fail('Restoring from an empty backup must not report success.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame(FileUpdater::ERROR_INVALID, $error->getMessage());
        }

        $this->assertSame([], $filesystem->replacedFiles);
        $this->assertSame([], $filesystem->replacedDirectories);
        $this->assertSame('live module file', file_get_contents($this->fixturePath.'/modules/Example/update.php'));
    }

    public function testRestoreFailsWhenTheBackupIsForADifferentVersion(): void
    {
        $this->writeFile('/backups/fileUpdate/previousVersionBackup/version.php', $this->versionFile('29.0.00'));
        $this->writeFile('/backups/fileUpdate/previousVersionBackup/modules/Example/update.php', 'backup module file');
        $this->writeManifest(['version.php', 'modules/Example/update.php']);
        $this->writeFile('/modules/Example/update.php', 'live module file');

        $filesystem = new FileUpdaterFilesystem();
        $updater = $this->createFileUpdater($filesystem);

        try {
            $updater->restore('30.0.00');
            $this->fail('A backup of another version must not be restored.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame(FileUpdater::ERROR_INVALID, $error->getMessage());
        }

        $this->assertSame([], $filesystem->replacedFiles);
        $this->assertSame('live module file', file_get_contents($this->fixturePath.'/modules/Example/update.php'));
    }

    /**
     * @dataProvider backupVersionFileProvider
     */
    public function testHasBackupMatchesTheVersionRecordedInTheBackup(?string $contents, string $version, bool $expected): void
    {
        if ($contents !== null) {
            $this->writeFile('/backups/fileUpdate/previousVersionBackup/version.php', $contents);
        } else {
            mkdir($this->fixturePath.'/backups/fileUpdate/previousVersionBackup', 0755, true);
        }
        $this->writeManifest(['version.php']);

        $updater = $this->createFileUpdater(new FileUpdaterFilesystem());

        $this->assertSame($expected, $updater->hasBackup($version));
    }

    public static function backupVersionFileProvider(): array
    {
        return [
            'matching version' => ["<?php\n\$version = '30.0.03';\n", '30.0.03', true],
            'matching version with surrounding whitespace' => ["<?php\n\$version = '30.0.03';\n", ' 30.0.03 ', true],
            'double quoted version' => ["<?php\n\$version = \"30.0.03\";\n", '30.0.03', true],
            'different version' => ["<?php\n\$version = '30.0.02';\n", '30.0.03', false],
            'version only partly matches' => ["<?php\n\$version = '30.0.031';\n", '30.0.03', false],
            'empty expected version' => ["<?php\n\$version = '';\n", '', false],
            'no version in the file' => ["<?php\n\$other = '30.0.03';\n", '30.0.03', false],
            'no version file' => [null, '30.0.03', false],
        ];
    }

    public function testBackupWritesAManifestOfFilesAndAddedFilesLast(): void
    {
        $this->writeFile('/version.php', $this->versionFile('30.0.00'));
        $this->writeFile('/backups/fileUpdate/staging/version.php', $this->versionFile('30.0.01'));
        $this->writeFile('/backups/fileUpdate/staging/modules/Example/new.php', 'new file');

        $this->createFileUpdater(new FileUpdaterFilesystem())->run(['version.php', 'modules/Example/new.php']);

        $manifest = json_decode(file_get_contents($this->backupPath().'/'.FileUpdater::MANIFEST), true);
        $this->assertSame(['version.php', 'modules/Example/new.php'], $manifest['files']);
        $this->assertSame(['modules/Example/new.php'], $manifest['added']);
    }

    public function testBackupFailsWhenThePreviousBackupCannotBeCleared(): void
    {
        $this->writeFile('/version.php', $this->versionFile('30.0.00'));
        $this->writeFile('/backups/fileUpdate/staging/version.php', $this->versionFile('30.0.01'));
        $this->writeFile('/backups/fileUpdate/previousVersionBackup/stale.php', 'stale');

        $filesystem = new FileUpdaterFilesystem();
        $filesystem->failWipe = true;

        try {
            $this->createFileUpdater($filesystem)->run(['version.php']);
            $this->fail('A stale backup must not be mixed into a new one.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame(FileUpdater::ERROR_BACKUP, $error->getMessage());
        }

        $this->assertStringContainsString("'30.0.00'", file_get_contents($this->fixturePath.'/version.php'));
    }

    public function testRestoreUndoesAnUpdateByRemovingAddedFilesAndRestoringTheRest(): void
    {
        $this->writeFile('/version.php', $this->versionFile('30.0.00'));
        $this->writeFile('/modules/Example/update.php', 'old module file');
        $this->writeFile('/backups/fileUpdate/staging/version.php', $this->versionFile('30.0.01'));
        $this->writeFile('/backups/fileUpdate/staging/modules/Example/update.php', 'new module file');
        $this->writeFile('/backups/fileUpdate/staging/modules/Example/added.php', 'added by the update');

        $updater = $this->createFileUpdater(new FileUpdaterFilesystem());
        $updater->run(['version.php', 'modules/Example/update.php', 'modules/Example/added.php']);
        $this->assertFileExists($this->fixturePath.'/modules/Example/added.php');

        // A fresh instance has no memory of the update, as in the restore background process.
        $this->createFileUpdater(new FileUpdaterFilesystem())->restore('30.0.00');

        $this->assertFileDoesNotExist($this->fixturePath.'/modules/Example/added.php');
        $this->assertSame('old module file', file_get_contents($this->fixturePath.'/modules/Example/update.php'));
        $this->assertStringContainsString("'30.0.00'", file_get_contents($this->fixturePath.'/version.php'));
    }

    public function testRestoreIgnoresFilesInTheBackupFolderThatAreNotInTheManifest(): void
    {
        $this->writeFile('/version.php', $this->versionFile('30.0.01'));
        $this->writeFile('/backups/fileUpdate/previousVersionBackup/version.php', $this->versionFile('30.0.00'));
        $this->writeFile('/backups/fileUpdate/previousVersionBackup/modules/Example/stale.php', 'left over from an older update');
        $this->writeManifest(['version.php']);

        $this->createFileUpdater(new FileUpdaterFilesystem())->restore('30.0.00');

        $this->assertStringContainsString("'30.0.00'", file_get_contents($this->fixturePath.'/version.php'));
        $this->assertFileDoesNotExist($this->fixturePath.'/modules/Example/stale.php');
    }

    public function testRestoreRefusesAnIncompleteBackupWithoutAManifest(): void
    {
        // An update that stopped part way through backing up never wrote its manifest.
        $this->writeFile('/version.php', $this->versionFile('30.0.01'));
        $this->writeFile('/backups/fileUpdate/previousVersionBackup/version.php', $this->versionFile('30.0.00'));

        $updater = $this->createFileUpdater(new FileUpdaterFilesystem());
        $this->assertFalse($updater->hasBackup('30.0.00'));

        try {
            $updater->restore('30.0.00');
            $this->fail('An incomplete backup must not be restored.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame(FileUpdater::ERROR_INVALID, $error->getMessage());
        }

        $this->assertStringContainsString("'30.0.01'", file_get_contents($this->fixturePath.'/version.php'));
    }

    public function testRestoreChecksEveryBackupFileBeforeChangingAnything(): void
    {
        $this->writeFile('/version.php', $this->versionFile('30.0.01'));
        $this->writeFile('/modules/Example/update.php', 'live module file');
        $this->writeFile('/backups/fileUpdate/previousVersionBackup/version.php', $this->versionFile('30.0.00'));
        $this->writeManifest(['version.php', 'modules/Example/update.php']);

        $filesystem = new FileUpdaterFilesystem();

        try {
            $this->createFileUpdater($filesystem)->restore('30.0.00');
            $this->fail('A backup that is missing a file must not be partly restored.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame(FileUpdater::ERROR_INVALID, $error->getMessage());
        }

        $this->assertSame([], $filesystem->replacedFiles);
        $this->assertStringContainsString("'30.0.01'", file_get_contents($this->fixturePath.'/version.php'));
    }

    public function testRestoreReportsARollbackFailureWhenAFileCannotBeRestored(): void
    {
        $this->writeFile('/version.php', $this->versionFile('30.0.01'));
        $this->writeFile('/backups/fileUpdate/previousVersionBackup/version.php', $this->versionFile('30.0.00'));
        $this->writeManifest(['version.php']);

        $filesystem = new FileUpdaterFilesystem();
        $filesystem->failReplaceFileDestination = $this->fixturePath.'/version.php';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(FileUpdater::ERROR_ROLLBACK);
        $this->createFileUpdater($filesystem)->restore('30.0.00');
    }

    public function testHasBackupIsFalseWhenThereIsNoBackupDirectory(): void
    {
        $updater = $this->createFileUpdater(new FileUpdaterFilesystem());

        $this->assertFalse($updater->hasBackup('30.0.03'));
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

    public function testAFileIsNotInstalledThroughAFolderThatLeadsOutsideTheInstall(): void
    {
        // The folder must sit outside the install, which is the fixture folder itself
        $outside = sys_get_temp_dir().'/FileUpdaterTest-outside-'.bin2hex(random_bytes(8));
        mkdir($outside, 0755, true);
        rmdir($this->fixturePath.'/modules/Example');
        symlink($outside, $this->fixturePath.'/modules/Example');
        $this->writeFile('/backups/fileUpdate/staging/modules/Example/update.php', 'new file');

        try {
            $this->createFileUpdater(new FileUpdaterFilesystem())->run(['modules/Example/update.php']);
            $this->fail('A file must not be written outside the install.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame(FileUpdater::ERROR_INVALID, $error->getMessage());
        } finally {
            $this->assertFileDoesNotExist($outside.'/update.php');
            (new Filesystem())->deleteDirectory($outside);
        }
    }

    public function testAStagedFileThatLinksOutsideStagingIsNotInstalled(): void
    {
        $secret = $this->fixturePath.'/secret.php';
        file_put_contents($secret, 'not from the package');
        mkdir($this->fixturePath.'/backups/fileUpdate/staging/modules/Example', 0755, true);
        symlink($secret, $this->fixturePath.'/backups/fileUpdate/staging/modules/Example/update.php');

        try {
            $this->createFileUpdater(new FileUpdaterFilesystem())->run(['modules/Example/update.php']);
            $this->fail('Only files inside the staging folder may be installed.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame(FileUpdater::ERROR_INVALID, $error->getMessage());
        }

        $this->assertFileDoesNotExist($this->fixturePath.'/modules/Example/update.php');
        $this->assertSame('not from the package', file_get_contents($secret));
    }

    public function testChecksManagedDirectoriesLockAndExactUpdatePaths(): void
    {
        $this->writeFile('/backups/fileUpdate/staging/modules/Example/update.php', 'new module file');

        $updater = $this->createFileUpdater(new FileUpdaterFilesystem());

        $this->assertTrue($updater->checkPermissions(['modules/Example/update.php']));
        $this->assertFileExists($this->fixturePath.'/backups/fileUpdate/fileUpdate.lock');
        $this->assertDirectoryExists($this->fixturePath.'/backups/fileUpdate/previousVersionBackup');
    }

    public function testPermissionCheckDoesNotCreateDirectoriesInTheLiveInstall(): void
    {
        $this->writeFile('/backups/fileUpdate/staging/modules/NewModule/src/New.php', 'new file');
        $updater = $this->createFileUpdater(new FileUpdaterFilesystem());

        $this->assertTrue($updater->checkPermissions(['modules/NewModule/src/New.php']));

        $this->assertDirectoryDoesNotExist($this->fixturePath.'/modules/NewModule');
    }

    public function testPermissionCheckChecksTheExistingDirectoryAboveANewOne(): void
    {
        $this->writeFile('/backups/fileUpdate/staging/modules/NewModule/New.php', 'new file');
        $filesystem = new FileUpdaterFilesystem();
        $filesystem->blockedDirectory = $this->fixturePath.'/modules';

        $this->assertFalse($this->createFileUpdater($filesystem)->checkPermissions(['modules/NewModule/New.php']));
        $this->assertDirectoryDoesNotExist($this->fixturePath.'/modules/NewModule');
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

        $updater = new FileUpdater($session, $filesystem);
        $updater->setBaseDirectory($this->fixturePath.'/backups/fileUpdate');

        return $updater;
    }

    private function backupPath(): string
    {
        return $this->fixturePath.'/backups/fileUpdate/previousVersionBackup';
    }

    /**
     * Write the manifest that a completed backup leaves behind.
     *
     * @param array<int, string> $files
     * @param array<int, string> $added
     */
    private function writeManifest(array $files, array $added = []): void
    {
        $this->writeFile('/backups/fileUpdate/previousVersionBackup/'.FileUpdater::MANIFEST, json_encode([
            'files' => $files,
            'added' => $added,
        ]));
    }

    private function versionFile(string $version): string
    {
        return "<?php\n\$version = '".$version."';\n";
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
    public bool $failWipe = false;

    private bool $replaceFileFailed = false;

    public function wipeDirectory(string $directory): bool
    {
        return $this->failWipe ? false : parent::wipeDirectory($directory);
    }

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
