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
    // What the updater should write to keep the backup folder closed to the web, on Apache 2.4 and 2.2
    private const RELEASE_URL = 'https://github.com/GibbonEdu/core/releases/download/v30.0.03/GibbonEduCore-InstallBundle.zip';

    private const HTACCESS = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n";

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
        $session->method('get')->willReturnCallback(fn (string $key) => $key === 'absolutePath' ? $webRoot : ($key === 'cuttingEdgeCode' ? 'Y' : null));

        $downloader = new Downloader($session, $this->createMock(VersionCatalog::class), new Filesystem());

        $this->assertSame(
            dirname(realpath($webRoot)).'/backups/fileUpdate-'.substr(hash('sha256', realpath($webRoot)), 0, 8),
            $downloader->getBaseDirectory()
        );
    }

    public function testFallsBackInsideWebRootWhenParentIsUnavailable(): void
    {
        $webRoot = $this->fixturePath.'/web';
        mkdir($webRoot, 0755, true);
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturnCallback(fn (string $key) => $key === 'absolutePath' ? $webRoot : ($key === 'cuttingEdgeCode' ? 'Y' : null));
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
        $session->method('get')->willReturnCallback(fn (string $key) => $key === 'absolutePath' ? $webRoot : ($key === 'cuttingEdgeCode' ? 'Y' : null));
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
        $session->method('get')->willReturnCallback(fn (string $key) => $key === 'absolutePath' ? $this->fixturePath : ($key === 'cuttingEdgeCode' ? 'Y' : null));

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
        $session->method('get')->willReturnCallback(fn (string $key) => $key === 'absolutePath' ? $this->fixturePath : ($key === 'cuttingEdgeCode' ? 'Y' : null));
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
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
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
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
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
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
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
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
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

    public function testTheBackupFolderIsClosedToTheWebOnApache24AndApache22(): void
    {
        $baseDir = $this->fixturePath.'/backups/fileUpdate';

        $this->createDownloader(new DownloaderFilesystem())->protectBaseDirectory();

        $htaccess = file_get_contents($baseDir.'/.htaccess');
        $this->assertSame(self::HTACCESS, $htaccess);
        $this->assertStringContainsString("<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>", $htaccess);
        $this->assertStringContainsString("<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>", $htaccess);
        $this->assertFileExists($baseDir.'/index.html');
    }

    /**
     * @dataProvider existingHtaccessProvider
     */
    public function testAnExistingHtaccessFileIsNeverOverwritten(string $contents): void
    {
        $baseDir = $this->fixturePath.'/backups/fileUpdate';
        file_put_contents($baseDir.'/.htaccess', $contents);

        $this->createDownloader(new DownloaderFilesystem())->protectBaseDirectory();

        $this->assertSame($contents, file_get_contents($baseDir.'/.htaccess'));
    }

    public static function existingHtaccessProvider(): array
    {
        return [
            'the same rules' => [self::HTACCESS],
            'a custom rule' => ["Require ip 10.0.0.0/8\n"],
            'a single rule' => ["Deny from all\n"],
            'an empty file' => [''],
        ];
    }

    public function testDownloadSavesThePackageAsTheBackupZip(): void
    {
        $bytes = $this->zipBytes(['version.php' => "<?php\n\$version = '30.0.03';\n"]);
        [$downloader] = $this->createScriptedDownloader([new \GuzzleHttp\Psr7\Response(200, [], $bytes)]);

        $zipPath = $downloader->download(['filePath' => self::RELEASE_URL]);

        $this->assertSame($this->fixturePath.'/backups/fileUpdate/package.zip', $zipPath);
        $this->assertSame($bytes, file_get_contents($zipPath));
    }

    public function testDownloadFollowsARedirectToGithubsReleaseStorage(): void
    {
        $bytes = $this->zipBytes(['version.php' => "<?php\n\$version = '30.0.03';\n"]);
        [$downloader] = $this->createScriptedDownloader([
            new \GuzzleHttp\Psr7\Response(302, ['Location' => 'https://release-assets.githubusercontent.com/asset/1/package.zip']),
            new \GuzzleHttp\Psr7\Response(200, [], $bytes),
        ]);

        // The package only arrives if the redirect was followed, since the redirect itself is not a 2xx
        $zipPath = $downloader->download(['filePath' => self::RELEASE_URL]);

        $this->assertSame($bytes, file_get_contents($zipPath));
    }

    /**
     * @dataProvider refusedDownloadProvider
     */
    public function testDownloadRefusesAPackageThatIsNotSafeToInstall(array $replies, string $expectedCode): void
    {
        [$downloader] = $this->createScriptedDownloader($replies);

        try {
            $downloader->download(['filePath' => self::RELEASE_URL]);
            $this->fail('The download must be refused.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame($expectedCode, $error->getMessage());
        }

        $this->assertFileDoesNotExist($this->fixturePath.'/backups/fileUpdate/package.zip');
    }

    public static function refusedDownloadProvider(): array
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', self::RELEASE_URL);

        return [
            'declared larger than the limit' => [
                [new \GuzzleHttp\Psr7\Response(200, ['Content-Length' => (string) (Downloader::MAX_PACKAGE_BYTES + 1)], 'x')],
                Downloader::ERROR_INVALID,
            ],
            'not found' => [[new \GuzzleHttp\Psr7\Response(404, [], 'Not Found')], Downloader::ERROR_DOWNLOAD],
            'server error' => [[new \GuzzleHttp\Psr7\Response(500, [], 'Error')], Downloader::ERROR_DOWNLOAD],
            'empty body' => [[new \GuzzleHttp\Psr7\Response(200, [], '')], Downloader::ERROR_DOWNLOAD],
            'not a zip archive' => [[new \GuzzleHttp\Psr7\Response(200, [], '<html>Sorry</html>')], Downloader::ERROR_DOWNLOAD],
            'connection failure' => [[new \GuzzleHttp\Exception\ConnectException('timed out', $request)], Downloader::ERROR_DOWNLOAD],
            'redirect to an unlisted host' => [
                [new \GuzzleHttp\Psr7\Response(302, ['Location' => 'https://example.com/package.zip'])],
                Downloader::ERROR_INVALID,
            ],
            'redirect to plain http' => [
                [new \GuzzleHttp\Psr7\Response(302, ['Location' => 'http://release-assets.githubusercontent.com/asset/1/package.zip'])],
                Downloader::ERROR_DOWNLOAD,
            ],
        ];
    }

    public function testDownloadNeverContactsAnUnsafeAddress(): void
    {
        // No replies are queued: any request would fail, so the refusal must come before the request
        [$downloader, $mock] = $this->createScriptedDownloader([]);

        try {
            $downloader->download(['filePath' => 'http://github.com/GibbonEdu/core/releases/download/v30.0.03/package.zip']);
            $this->fail('A plain http address must be refused.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame(Downloader::ERROR_INVALID, $error->getMessage());
        }

        $this->assertSame(0, $mock->count(), 'No request was made.');
    }

    public function testALeftoverSymlinkInStagingIsRemovedWithoutTouchingItsTarget(): void
    {
        $outside = $this->fixturePath.'/outside';
        mkdir($outside, 0755, true);
        file_put_contents($outside.'/keep.txt', 'not part of the update');
        mkdir($this->fixturePath.'/backups/fileUpdate/staging', 0755, true);
        symlink($outside, $this->fixturePath.'/backups/fileUpdate/staging/leftover');
        $zipPath = $this->createZip(['version.php' => "<?php\n\$version = '30.0.01';\n"]);

        $files = $this->createDownloader(new DownloaderFilesystem())->extract($zipPath);

        $this->assertSame(['version.php'], $files);
        $this->assertFileExists($outside.'/keep.txt');
        $this->assertFalse(is_link($this->fixturePath.'/backups/fileUpdate/staging/leftover'));
    }

    public function testADevelopmentWithoutCuttingEdgeGetsNoChecksumExemption(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturnCallback(fn (string $key) => $key === 'absolutePath' ? $this->fixturePath : ($key === 'cuttingEdgeCode' ? 'N' : null));
        $downloader = new Downloader($session, $this->createMock(VersionCatalog::class), new Filesystem());
        $zipPath = $this->createZip(['version.php' => "<?php\n\$version = '31.0.00';\n"]);

        try {
            $downloader->verify($zipPath, '', 'Development');
            $this->fail('A Development build without a checksum is only exempt on cutting edge installs.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame(Downloader::ERROR_VERIFY, $error->getMessage());
        }
    }

    /**
     * @dataProvider developmentStatusProvider
     */
    public function testADevelopmentBuildWithoutAChecksumIsCheckedByItsAddressOnly(string $status): void
    {
        $requests = [];
        $downloader = null;

        $files = $this->runReleased([
            'status' => $status,
            'checksum' => null,
            'filePath' => 'https://github.com/GibbonEdu/core/archive/refs/heads/v31.0.00.zip',
        ], [], $requests, $downloader);

        $this->assertSame(['version.php'], $files);
        $this->assertSame([], $requests, 'GitHub is not asked for a digest of a development build.');
        $this->assertSame(1, $downloader->downloads);
    }

    public static function developmentStatusProvider(): array
    {
        // The catalog lower-cases status, so a real Development build arrives as 'development'
        return [
            'lower case, as the catalog sends it' => ['development'],
            'capitalised, as the catalog lists it' => ['Development'],
        ];
    }

    public function testAReleaseOtherThanDevelopmentStillNeedsItsChecksum(): void
    {
        $downloader = null;
        $requests = [];

        try {
            $this->runReleased([
                'status' => 'Current',
                'checksum' => '',
                'filePath' => 'https://gibbonedu.org/download/gibbon-30.0.03.zip',
            ], [], $requests, $downloader);
            $this->fail('A released package without a checksum must not be installed.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame(Downloader::ERROR_VERIFY, $error->getMessage());
        }
    }

    public function testReleasedGithubFileMustHaveTheChecksumGithubReports(): void
    {
        $requests = [];
        $downloader = null;

        $files = $this->runReleased([], [$this->githubResponse('"sha256:{checksum}"')], $requests, $downloader);

        $this->assertSame(['version.php'], $files);
        $this->assertCount(1, $requests);
        $this->assertSame('https://api.github.com/repos/GibbonEdu/core/releases/tags/v30.0.03', (string) $requests[0]->getUri());
        $this->assertSame('application/vnd.github+json', $requests[0]->getHeaderLine('Accept'));
        $this->assertSame(1, $downloader->downloads);
    }

    public function testTheGithubChecksumIsComparedIgnoringCase(): void
    {
        $files = $this->runReleased([], [$this->githubResponse('strtoupper')]);

        $this->assertSame(['version.php'], $files);
    }

    /**
     * @dataProvider unverifiedGithubReleaseProvider
     */
    public function testAGithubReleaseThatCannotBeVerifiedIsNotDownloaded(array $responses, array $release = []): void
    {
        $downloader = null;

        try {
            $this->runReleased($release, $responses, $requests, $downloader);
            $this->fail('A GitHub release that does not verify must not be installed.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $error) {
            $this->assertSame(Downloader::ERROR_VERIFY, $error->getMessage());
        }

        $this->assertSame(0, $downloader->downloads, 'The package is not fetched when it cannot be verified.');
    }

    public static function unverifiedGithubReleaseProvider(): array
    {
        $notFound = new \GuzzleHttp\Psr7\Response(404, [], '{"message":"Not Found"}');

        return [
            'checksums differ' => [[self::githubResponse('"sha256:'.str_repeat('0', 64).'"')]],
            'file has no checksum on GitHub' => [[self::githubResponse('null')]],
            'file is not in the release' => [[new \GuzzleHttp\Psr7\Response(200, [], '{"assets":[{"name":"Other.zip","digest":"sha256:abc"}]}')]],
            'release has no files' => [[new \GuzzleHttp\Psr7\Response(200, [], '{"assets":[]}')]],
            'reply is not JSON' => [[new \GuzzleHttp\Psr7\Response(200, [], 'not json')]],
            'release not found' => [[$notFound]],
            'rate limited' => [[new \GuzzleHttp\Psr7\Response(403, [], '{"message":"API rate limit exceeded"}')]],
            'GitHub is down' => [[new \GuzzleHttp\Psr7\Response(503)]],
            'GitHub cannot be reached' => [[new \GuzzleHttp\Exception\ConnectException('timed out', new \GuzzleHttp\Psr7\Request('GET', 'x'))]],
            'catalog has no checksum' => [[self::githubResponse('"sha256:abc"')], ['checksum' => '']],
        ];
    }

    /**
     * @dataProvider notCheckedWithGithubProvider
     */
    public function testOtherDownloadsAreLeftToTheCatalogChecksum(array $release): void
    {
        // No API reply is queued: asking GitHub would fail the test
        $requests = [];

        $files = $this->runReleased($release, [], $requests);

        $this->assertSame(['version.php'], $files);
        $this->assertSame([], $requests);
    }

    public static function notCheckedWithGithubProvider(): array
    {
        return [
            'Development version' => [['status' => 'development']],
            'Development version in capitals' => [['status' => 'Development']],
            'download from gibbonedu.org' => [['filePath' => 'https://gibbonedu.org/download/gibbon-30.0.03.zip']],
            'branch archive on GitHub' => [['filePath' => 'https://github.com/GibbonEdu/core/archive/refs/heads/v31.0.00.zip']],
            'no download address' => [['filePath' => '']],
        ];
    }

    /**
     * A GitHub API reply for the release file, whose digest is $digest, with {checksum}
     * standing for the checksum of the test package. 'strtoupper' gives the right digest in capitals.
     */
    private static function githubResponse(string $digest): \GuzzleHttp\Psr7\Response
    {
        $body = json_encode(['assets' => [
            ['name' => 'Other.tar.gz', 'digest' => 'sha256:'.str_repeat('f', 64)],
            ['name' => 'GibbonEduCore-InstallBundle.zip', 'digest' => '{digest}'],
        ]]);

        return new \GuzzleHttp\Psr7\Response(200, [], str_replace('"{digest}"', $digest === 'strtoupper' ? '"{upper}"' : $digest, $body));
    }

    /**
     * Run a download of a released GitHub file, with the GitHub API answering from $apiResponses.
     *
     * @param array<string, mixed> $release Changes to the release the catalog would hold.
     * @param array<int, mixed> $apiResponses Replies, in order, for requests to the GitHub API.
     * @param array $requests Set to the requests made to the GitHub API.
     * @param LimitedDownloader|null $downloader Set to the downloader used.
     * @return array<int, string>
     */
    private function runReleased(array $release, array $apiResponses, ?array &$requests = [], ?LimitedDownloader &$downloader = null): array
    {
        $zipPath = $this->createZip(['version.php' => "<?php\n\$version = '30.0.03';\n"]);
        $checksum = hash_file('sha256', $zipPath);
        $release += [
            'version' => '30.0.03',
            'status' => 'current',
            'checksum' => $checksum,
            'filePath' => 'https://github.com/GibbonEdu/core/releases/download/v30.0.03/GibbonEduCore-InstallBundle.zip',
        ];

        // Fill in the checksum the test package really has
        $apiResponses = array_map(function ($response) use ($checksum) {
            if (!$response instanceof \GuzzleHttp\Psr7\Response) {
                return $response;
            }

            return $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor(str_replace(
                ['{checksum}', '{upper}'],
                [$checksum, 'sha256:'.strtoupper($checksum)],
                (string) $response->getBody()
            )));
        }, $apiResponses);

        $history = [];
        $stack = \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\MockHandler($apiResponses));
        $stack->push(\GuzzleHttp\Middleware::history($history));

        $downloader = $this->createDownloader(new DownloaderFilesystem());
        $downloader->fixtureZip = $zipPath;
        $downloader->client = new \GuzzleHttp\Client(['handler' => $stack]);

        try {
            return $downloader->run($release);
        } finally {
            $requests = array_column($history, 'request');
        }
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
        $session->method('get')->willReturnCallback(fn (string $key) => $key === 'absolutePath' ? $this->fixturePath : ($key === 'cuttingEdgeCode' ? 'Y' : null));

        $downloader = new LimitedDownloader($session, $this->createMock(VersionCatalog::class), $filesystem);
        $downloader->setBaseDirectory($this->fixturePath.'/backups/fileUpdate');
        if ($maxExtractedBytes !== null) {
            $downloader->setMaxExtractedBytes($maxExtractedBytes);
        }

        return $downloader;
    }

    /**
     * A downloader whose HTTP requests are answered by $replies, in order.
     *
     * @param array<int, mixed> $replies Responses or exceptions for the mock server.
     * @return array{0: ScriptedDownloader, 1: \GuzzleHttp\Handler\MockHandler}
     */
    private function createScriptedDownloader(array $replies): array
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturnCallback(fn (string $key) => [
            'absolutePath' => $this->fixturePath,
            'absoluteURL' => 'https://school.example',
        ][$key] ?? null);

        $mock = new \GuzzleHttp\Handler\MockHandler($replies);
        $downloader = new ScriptedDownloader($session, new VersionCatalog($session), new DownloaderFilesystem());
        $downloader->client = new \GuzzleHttp\Client(['handler' => \GuzzleHttp\HandlerStack::create($mock)]);
        $downloader->setBaseDirectory($this->fixturePath.'/backups/fileUpdate');

        return [$downloader, $mock];
    }

    /**
     * @param array<string, string> $files
     */
    private function zipBytes(array $files): string
    {
        $path = $this->fixturePath.'/source.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        return (string) file_get_contents($path);
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

    /** Use this client, with a mocked GitHub API, when set. */
    public ?\GuzzleHttp\Client $client = null;

    public int $downloads = 0;

    public function download(array $release) : string
    {
        ++$this->downloads;

        return $this->fixtureZip ?? parent::download($release);
    }

    protected function createClient(array $options) : \GuzzleHttp\Client
    {
        return $this->client ?? parent::createClient($options);
    }

    public function protectBaseDirectory() : string
    {
        return $this->ensureBaseDirectory();
    }

    public function setMaxExtractedBytes(int $bytes): void
    {
        $this->maxExtractedBytes = $bytes;
    }
}

/**
 * Sends HTTP requests through a supplied client, so tests can answer them from a script.
 */
class ScriptedDownloader extends Downloader
{
    public ?\GuzzleHttp\Client $client = null;

    protected function createClient(array $options) : \GuzzleHttp\Client
    {
        return $this->client ?? parent::createClient($options);
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
