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
