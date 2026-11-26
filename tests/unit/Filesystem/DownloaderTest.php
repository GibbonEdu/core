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
use ZipArchive;

/**
 * @covers \Gibbon\Filesystem\Downloader
 */
class DownloaderTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturePath = sys_get_temp_dir().'/DownloaderTest-'.bin2hex(random_bytes(8));
        mkdir($this->fixturePath.'/backups/fileUpdate', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeFixtureDirectory($this->fixturePath);

        parent::tearDown();
    }

    public function testSelectsBackupDirectoryOutsideWebRootWhenParentIsUsable(): void
    {
        $webRoot = $this->fixturePath.'/web';
        mkdir($webRoot, 0755, true);
        $session = $this->createMock(Session::class);
        $session->method('get')->with('absolutePath')->willReturn($webRoot);

        $downloader = new Downloader($session, $this->createMock(VersionCatalog::class), new Filesystem());

        $this->assertSame(dirname(realpath($webRoot)).'/backups/fileUpdate', $downloader->getBaseDirectory());
    }

    public function testFallsBackInsideWebRootWhenParentIsUnavailable(): void
    {
        $webRoot = $this->fixturePath.'/web';
        mkdir($webRoot, 0755, true);
        $session = $this->createMock(Session::class);
        $session->method('get')->with('absolutePath')->willReturn($webRoot);
        $filesystem = new DownloaderFilesystem();
        $filesystem->parentDirectoryAvailable = false;

        $downloader = new Downloader($session, $this->createMock(VersionCatalog::class), $filesystem);

        $this->assertSame($webRoot.'/'.Downloader::BASE_DIR, $downloader->getBaseDirectory());
    }

    public function testExplicitBaseDirectoryIsNotOverridden(): void
    {
        $webRoot = $this->fixturePath.'/web';
        mkdir($webRoot, 0755, true);
        $session = $this->createMock(Session::class);
        $session->method('get')->with('absolutePath')->willReturn($webRoot);
        $downloader = new Downloader($session, $this->createMock(VersionCatalog::class), new Filesystem());
        $explicit = $this->fixturePath.'/explicit-backup';

        $downloader->setBaseDirectory($explicit);

        $this->assertSame($explicit, $downloader->getBaseDirectory());
    }

    public function testExtractReportsByteAndFileProgress(): void
    {
        $zipPath = $this->fixturePath.'/backups/fileUpdate/package.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE) === true);
        $zip->addFromString('version.php', str_repeat('v', 100));
        $zip->addFromString('src/Large.php', str_repeat('x', 300));
        $zip->addFromString('src/Empty.php', '');
        $zip->close();

        $session = $this->createMock(Session::class);
        $session->method('get')->with('absolutePath')->willReturn($this->fixturePath);

        $progress = [];
        $filesystem = new DownloaderFilesystem();
        $downloader = new Downloader($session, $this->createMock(VersionCatalog::class), $filesystem);
        $downloader->setBaseDirectory($this->fixturePath.'/backups/fileUpdate');
        $downloader->setProgressCallback(function (int $percent) use (&$progress): void {
            $progress[] = $percent;
        });

        $files = $downloader->extract($zipPath);

        $this->assertEqualsCanonicalizing(['version.php', 'src/Large.php', 'src/Empty.php'], $files);
        $this->assertCount(6, $filesystem->validatedPaths);
        $this->assertEqualsCanonicalizing(
            ['version.php', 'src/Large.php', 'src/Empty.php', 'version.php', 'src/Large.php', 'src/Empty.php'],
            $filesystem->validatedPaths
        );
        $this->assertCount(3, $filesystem->containedPaths);
        $this->assertContains(95, $progress);
        $this->assertContains(99, $progress);
        $this->assertSame(100, $progress[array_key_last($progress)]);
        $this->assertGreaterThan(5, count($progress));

        $sortedProgress = $progress;
        sort($sortedProgress);
        $this->assertSame($sortedProgress, $progress);
    }

    public function testExtractMapsUnsafeArchivePathsToInvalidError(): void
    {
        $zipPath = $this->fixturePath.'/backups/fileUpdate/package.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE) === true);
        $zip->addFromString('../outside.php', 'unsafe');
        $zip->close();

        $session = $this->createMock(Session::class);
        $session->method('get')->with('absolutePath')->willReturn($this->fixturePath);
        $downloader = new Downloader($session, $this->createMock(VersionCatalog::class), new DownloaderFilesystem());
        $downloader->setBaseDirectory($this->fixturePath.'/backups/fileUpdate');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(Downloader::ERROR_INVALID);
        $downloader->extract($zipPath);
    }

    public function testExtractRefusesAPackageThatExpandsBeyondTheLimit(): void
    {
        $zipPath = $this->createZip(['version.php' => str_repeat('v', 600), 'src/Large.php' => str_repeat('x', 500)]);
        $downloader = $this->createDownloader(new DownloaderFilesystem(), 1000);

        try {
            $downloader->extract($zipPath);
            $this->fail('A package larger than the extraction limit must be refused.');
        } catch (\RuntimeException $error) {
            $this->assertSame(Downloader::ERROR_INVALID, $error->getMessage());
        }

        $this->assertFileDoesNotExist($zipPath);
        $this->assertSame([], glob($this->fixturePath.'/backups/fileUpdate/staging/*'));
    }

    public function testExtractAcceptsAPackageThatIsExactlyAtTheLimit(): void
    {
        $zipPath = $this->createZip(['version.php' => str_repeat('v', 600), 'src/Large.php' => str_repeat('x', 400)]);
        $downloader = $this->createDownloader(new DownloaderFilesystem(), 1000);

        $this->assertEqualsCanonicalizing(['version.php', 'src/Large.php'], $downloader->extract($zipPath));
    }

    public function testExtractRefusesAPackageThatDoesNotFitOnDiskWithItsBackup(): void
    {
        $zipPath = $this->createZip(['version.php' => str_repeat('v', 500), 'src/Large.php' => str_repeat('x', 500)]);
        $filesystem = new DownloaderFilesystem();
        $filesystem->fakeFreeSpace = true;
        $filesystem->freeSpace = 1999.0; // The staged files and their backup need 2 x 1000 bytes

        try {
            $this->createDownloader($filesystem)->extract($zipPath);
            $this->fail('A package that cannot fit on disk must be refused before extraction.');
        } catch (\RuntimeException $error) {
            $this->assertSame(Downloader::ERROR_WRITE, $error->getMessage());
        }

        $this->assertFileDoesNotExist($zipPath);
        $this->assertSame([], glob($this->fixturePath.'/backups/fileUpdate/staging/*'));
    }

    /**
     * @dataProvider enoughOrUnknownFreeSpaceProvider
     */
    public function testExtractContinuesWhenThereIsEnoughOrUnknownFreeSpace(?float $freeSpace): void
    {
        $zipPath = $this->createZip(['version.php' => str_repeat('v', 500), 'src/Large.php' => str_repeat('x', 500)]);
        $filesystem = new DownloaderFilesystem();
        $filesystem->fakeFreeSpace = true;
        $filesystem->freeSpace = $freeSpace;

        $this->assertEqualsCanonicalizing(['version.php', 'src/Large.php'], $this->createDownloader($filesystem)->extract($zipPath));
    }

    public static function enoughOrUnknownFreeSpaceProvider(): array
    {
        return [
            'exactly twice the package size' => [2000.0],
            'plenty of space' => [1.0e12],
            'cannot be determined' => [null],
        ];
    }

    public function testExtractFailsWhenLeftoversInStagingCannotBeCleared(): void
    {
        // Files left by an earlier run would otherwise be listed and installed with this package
        $this->writeStagingFile('leftover.php');
        $zipPath = $this->createZip(['version.php' => "<?php\n\$version = '30.0.01';\n"]);
        $filesystem = new DownloaderFilesystem();
        $filesystem->failWipe = true;

        try {
            $this->createDownloader($filesystem)->extract($zipPath);
            $this->fail('Extraction must not continue when staging cannot be cleared.');
        } catch (\RuntimeException $error) {
            $this->assertSame(Downloader::ERROR_WRITE, $error->getMessage());
        }

        $this->assertFileDoesNotExist($zipPath);
        $this->assertFileDoesNotExist($this->fixturePath.'/backups/fileUpdate/staging/version.php');
    }

    public function testExtractDoesNotListLeftoversFromAnEarlierRun(): void
    {
        $this->writeStagingFile('leftover.php');
        $zipPath = $this->createZip(['version.php' => "<?php\n\$version = '30.0.01';\n"]);

        $files = $this->createDownloader(new DownloaderFilesystem())->extract($zipPath);

        $this->assertSame(['version.php'], $files);
        $this->assertFileDoesNotExist($this->fixturePath.'/backups/fileUpdate/staging/leftover.php');
    }

    public function testRunReturnsThePackageWhenItsVersionMatchesTheCatalog(): void
    {
        $files = $this->runWithPackage(
            ['version.php' => "<?php\n\$version = '30.0.01';\n", 'src/Example.php' => 'example'],
            '30.0.01'
        );

        $this->assertEqualsCanonicalizing(['version.php', 'src/Example.php'], $files);
        $this->assertFileDoesNotExist($this->fixturePath.'/backups/fileUpdate/package.zip');
    }

    /**
     * @dataProvider wrongVersionProvider
     */
    public function testRunRefusesAPackageThatIsNotTheCatalogVersion(string $versionFile, string $catalogVersion): void
    {
        try {
            $this->runWithPackage(['version.php' => $versionFile, 'src/Example.php' => 'example'], $catalogVersion);
            $this->fail('A package that is not the catalog version must not be installed.');
        } catch (\RuntimeException $error) {
            $this->assertSame(Downloader::ERROR_INVALID, $error->getMessage());
        }

        $this->assertSame([], array_diff(scandir($this->fixturePath.'/backups/fileUpdate/staging'), ['.', '..']));
        $this->assertFileDoesNotExist($this->fixturePath.'/backups/fileUpdate/package.zip');
    }

    public static function wrongVersionProvider(): array
    {
        return [
            'older version' => ["<?php\n\$version = '30.0.00';\n", '30.0.01'],
            'newer version' => ["<?php\n\$version = '30.0.02';\n", '30.0.01'],
            'version only partly matches' => ["<?php\n\$version = '30.0.011';\n", '30.0.01'],
            'no version in version.php' => ["<?php\n\$other = '30.0.01';\n", '30.0.01'],
            'catalog has no version' => ["<?php\n\$version = '30.0.01';\n", ''],
        ];
    }

    /**
     * Run a full download using a local zip, with the checksum the catalog would hold.
     *
     * @param array<string, string> $files
     * @return array<int, string>
     */
    private function runWithPackage(array $files, string $catalogVersion): array
    {
        $zipPath = $this->createZip($files);
        $downloader = $this->createDownloader(new DownloaderFilesystem());
        $downloader->fixtureZip = $zipPath;

        return $downloader->run(['version' => $catalogVersion, 'checksum' => hash_file('sha256', $zipPath)]);
    }

    private function writeStagingFile(string $name): void
    {
        mkdir($this->fixturePath.'/backups/fileUpdate/staging', 0755, true);
        file_put_contents($this->fixturePath.'/backups/fileUpdate/staging/'.$name, 'left behind');
    }

    /**
     * @param array<string, string> $files
     */
    private function createZip(array $files): string
    {
        $zipPath = $this->fixturePath.'/backups/fileUpdate/package.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE) === true);
        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        return $zipPath;
    }

    private function createDownloader(Filesystem $filesystem, ?int $maxExtractedBytes = null): LimitedDownloader
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->with('absolutePath')->willReturn($this->fixturePath);

        $downloader = new LimitedDownloader($session, $this->createMock(VersionCatalog::class), $filesystem);
        $downloader->setBaseDirectory($this->fixturePath.'/backups/fileUpdate');
        if ($maxExtractedBytes !== null) {
            $downloader->setMaxExtractedBytes($maxExtractedBytes);
        }

        return $downloader;
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

class LimitedDownloader extends Downloader
{
    /** Use a local zip instead of downloading one when set. */
    public ?string $fixtureZip = null;

    public function download(array $release) : string
    {
        return $this->fixtureZip ?? parent::download($release);
    }

    public function setMaxExtractedBytes(int $bytes): void
    {
        $this->maxExtractedBytes = $bytes;
    }
}

class DownloaderFilesystem extends Filesystem
{
    /**
     * @var array<int, string>
     */
    public array $validatedPaths = [];

    /**
     * @var array<int, array{path: string, root: string}>
     */
    public array $containedPaths = [];
    public bool $parentDirectoryAvailable = true;
    public bool $fakeFreeSpace = false;
    public ?float $freeSpace = null;
    public bool $failWipe = false;

    public function wipeDirectory(string $directory): bool
    {
        return $this->failWipe ? false : parent::wipeDirectory($directory);
    }

    public function getFreeSpace(string $directory): ?float
    {
        return $this->fakeFreeSpace ? $this->freeSpace : parent::getFreeSpace($directory);
    }

    public function checkParentDirectory(string $root): bool
    {
        return $this->parentDirectoryAvailable && parent::checkParentDirectory($root);
    }

    public function isSafeRelativePath(string $relative): bool
    {
        $this->validatedPaths[] = $relative;

        return parent::isSafeRelativePath($relative);
    }

    public function isInside(string $path, string $root, bool $allowEqual = false): bool
    {
        $this->containedPaths[] = ['path' => $path, 'root' => $root];

        return parent::isInside($path, $root, $allowEqual);
    }
}
