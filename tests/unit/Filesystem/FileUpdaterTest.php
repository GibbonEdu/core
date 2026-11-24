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
        $this->removeFixtureDirectory($this->fixturePath);

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

        $updater = $this->createFileUpdater();
        $updater->run([
            'vendor/package/new.php',
            'modules/Example/update.php',
            'vendor/autoload.php',
        ]);

        $stagingPath = str_replace('\\', '/', realpath($this->fixturePath.'/backups/fileUpdate/staging'));
        $this->assertSame([
            [$stagingPath.'/vendor', $this->fixturePath.'/vendor'],
        ], $updater->installedDirectories);
        $this->assertSame([
            [$stagingPath.'/modules/Example/update.php', $this->fixturePath.'/modules/Example/update.php'],
        ], $updater->installedFiles);

        $this->assertFileExists($this->fixturePath.'/vendor/package/new.php');
        $this->assertFileExists($this->fixturePath.'/vendor/autoload.php');
        $this->assertFileDoesNotExist($this->fixturePath.'/vendor/package/old.php');
        $this->assertFileDoesNotExist($this->fixturePath.'/vendor/obsolete.php');
        $this->assertSame('new module file', file_get_contents($this->fixturePath.'/modules/Example/update.php'));
        $this->assertSame('unchanged module file', file_get_contents($this->fixturePath.'/modules/Example/unchanged.php'));

        $this->assertFileExists($this->fixturePath.'/backups/fileUpdate/previousVersionBackup/vendor/package/old.php');
        $this->assertFileExists($this->fixturePath.'/backups/fileUpdate/previousVersionBackup/vendor/obsolete.php');
        $this->assertSame(
            'old module file',
            file_get_contents($this->fixturePath.'/backups/fileUpdate/previousVersionBackup/modules/Example/update.php')
        );
    }

    public function testRestoreAlsoReplacesVendorAsOneDirectory(): void
    {
        $this->writeFile('/vendor/old.php', 'old vendor file');
        $this->writeFile('/modules/Example/update.php', 'old module file');
        $this->writeFile('/backups/fileUpdate/staging/vendor/new.php', 'new vendor file');
        $this->writeFile('/backups/fileUpdate/staging/modules/Example/update.php', 'new module file');

        $updater = $this->createFileUpdater();
        $updater->run(['vendor/new.php', 'modules/Example/update.php']);
        $updater->installedDirectories = [];
        $updater->installedFiles = [];

        $updater->restore();

        $this->assertCount(1, $updater->installedDirectories);
        $this->assertSame(
            $this->fixturePath.'/vendor',
            $updater->installedDirectories[0][1]
        );
        $this->assertSame([
            [
                $this->fixturePath.'/backups/fileUpdate/previousVersionBackup/modules/Example/update.php',
                $this->fixturePath.'/modules/Example/update.php',
            ],
        ], $updater->installedFiles);
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

        $updater = $this->createFileUpdater();
        $updater->failInstallFileDestination = $this->fixturePath.'/modules/Example/update.php';

        try {
            $updater->run(['vendor/new.php', 'modules/Example/update.php']);
            $this->fail('The update should fail when a file cannot be installed.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(FileUpdater::ERROR_REPLACE, $exception->getMessage());
        }

        $this->assertCount(2, $updater->installedDirectories);
        $this->assertSame($this->fixturePath.'/vendor', $updater->installedDirectories[0][1]);
        $this->assertSame($this->fixturePath.'/vendor', $updater->installedDirectories[1][1]);
        $this->assertFileExists($this->fixturePath.'/vendor/old.php');
        $this->assertFileDoesNotExist($this->fixturePath.'/vendor/new.php');
        $this->assertSame('old module file', file_get_contents($this->fixturePath.'/modules/Example/update.php'));
    }

    private function createFileUpdater(): FileUpdaterProbe
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->with('absolutePath')->willReturn($this->fixturePath);

        return new FileUpdaterProbe($session);
    }

    private function writeFile(string $relativePath, string $contents): void
    {
        $path = $this->fixturePath.$relativePath;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $contents);
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
            $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($path);
    }
}

class FileUpdaterProbe extends FileUpdater
{
    public array $installedDirectories = [];
    public array $installedFiles = [];
    public ?string $failInstallFileDestination = null;

    private bool $installFileFailed = false;

    protected function installDirectory(string $source, string $destination): bool
    {
        $this->installedDirectories[] = [$source, $destination];

        return parent::installDirectory($source, $destination);
    }

    protected function installFile(string $source, string $destination): bool
    {
        $this->installedFiles[] = [$source, $destination];

        if ($destination === $this->failInstallFileDestination && !$this->installFileFailed) {
            $this->installFileFailed = true;
            return false;
        }

        return parent::installFile($source, $destination);
    }
}
