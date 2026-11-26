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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Gibbon\Filesystem\VersionCatalog
 */
class VersionCatalogTest extends TestCase
{
    /**
     * @dataProvider allowedUrlProvider
     */
    public function testAllowsPackageUrlsOnTheAllowedHosts(string $url): void
    {
        $this->assertTrue($this->createCatalog()->isHostAllowed($url));
    }

    public static function allowedUrlProvider(): array
    {
        return [
            // The forms the real version catalog uses
            'gibbonedu.org download' => ['https://gibbonedu.org/download/gibbon-30.0.04.zip'],
            'GitHub release bundle' => ['https://github.com/GibbonEdu/core/releases/download/v30.0.03/GibbonEduCore-InstallBundle.zip'],
            'GitHub branch archive' => ['https://github.com/GibbonEdu/core/archive/refs/heads/v31.0.00.zip'],
            // Other forms of the same hosts
            'www.gibbonedu.org' => ['https://www.gibbonedu.org/download/gibbon-30.0.04.zip'],
            'codeload' => ['https://codeload.github.com/GibbonEdu/core/zip/refs/tags/v30.0.04'],
            'host and organisation in any case' => ['https://GitHub.com/gibbonedu/core/archive/v30.0.04.zip'],
            'explicit https port' => ['https://github.com:443/GibbonEdu/core/archive/v30.0.04.zip'],
            'with a query string' => ['https://gibbonedu.org/download/gibbon-30.0.04.zip?source=updater'],
        ];
    }

    /**
     * @dataProvider rejectedUrlProvider
     */
    public function testRejectsUrlsThatAreNotSafePackageUrls(string $url): void
    {
        $catalog = $this->createCatalog();

        $this->assertFalse($catalog->isHostAllowed($url));
        $this->assertFalse($catalog->isHostAllowed($url, true), 'A redirect must meet the same rules.');
    }

    public static function rejectedUrlProvider(): array
    {
        return [
            'plain http' => ['http://gibbonedu.org/download/gibbon-30.0.04.zip'],
            'plain http on GitHub' => ['http://github.com/GibbonEdu/core/archive/v30.0.04.zip'],
            'other protocol' => ['ftp://gibbonedu.org/download/gibbon-30.0.04.zip'],
            'no protocol' => ['//github.com/GibbonEdu/core/archive/v30.0.04.zip'],
            'empty' => [''],
            'someone else on GitHub' => ['https://github.com/attacker/core/archive/v30.0.04.zip'],
            'organisation name only as a prefix' => ['https://github.com/GibbonEdu-fake/core/archive/v30.0.04.zip'],
            'organisation with no repository path' => ['https://github.com/GibbonEdu'],
            'someone else on codeload' => ['https://codeload.github.com/attacker/core/zip/refs/heads/main'],
            'dot segments out of the organisation' => ['https://github.com/GibbonEdu/../attacker/core/archive/v30.0.04.zip'],
            'encoded dot segments' => ['https://github.com/GibbonEdu/%2e%2e/attacker/core/archive/v30.0.04.zip'],
            'encoded slash' => ['https://github.com/GibbonEdu%2f..%2fattacker/core/archive/v30.0.04.zip'],
            'backslash' => ['https://github.com/GibbonEdu\\..\\attacker/core/archive/v30.0.04.zip'],
            'host that only starts with an allowed one' => ['https://github.com.evil.example/GibbonEdu/core/archive/v30.0.04.zip'],
            'host that only ends with an allowed one' => ['https://evilgithub.com/GibbonEdu/core/archive/v30.0.04.zip'],
            'allowed host as a subdomain of another' => ['https://gibbonedu.org.evil.example/download/gibbon-30.0.04.zip'],
            'allowed host as user name' => ['https://github.com@evil.example/GibbonEdu/core/archive/v30.0.04.zip'],
            'credentials in the URL' => ['https://user:secret@github.com/GibbonEdu/core/archive/v30.0.04.zip'],
            'non-standard port' => ['https://github.com:8443/GibbonEdu/core/archive/v30.0.04.zip'],
            'unlisted host' => ['https://example.com/gibbon-30.0.04.zip'],
        ];
    }

    public function testReleaseAssetsHostIsOnlyAllowedAsARedirect(): void
    {
        $url = 'https://release-assets.githubusercontent.com/github-production-release-asset/1234/abcd?sig=xyz&response-content-type=application%2Foctet-stream';
        $catalog = $this->createCatalog();

        $this->assertFalse($catalog->isHostAllowed($url), 'Anyone can publish files on this host.');
        $this->assertTrue($catalog->isHostAllowed($url, true));
        $this->assertFalse($catalog->isHostAllowed(str_replace('https://', 'http://', $url), true));
    }

    public function testRedirectsFollowTheSamePathRulesAsTheFirstUrl(): void
    {
        $catalog = $this->createCatalog();

        $this->assertTrue($catalog->isHostAllowed('https://codeload.github.com/GibbonEdu/core/zip/refs/tags/v30.0.04', true));
        $this->assertFalse($catalog->isHostAllowed('https://codeload.github.com/attacker/core/zip/refs/tags/v30.0.04', true));
    }

    public function testNextReleaseIsTheLowestVersionAboveTheCurrentOne(): void
    {
        $catalog = $this->createStubbedCatalog([
            '30.0.02' => $this->release('30.0.02', 'Current', 'b1', $this->gibbonPackage('30.0.02')),
            '30.0.01' => $this->release('30.0.01', 'Current', 'a1', $this->gibbonPackage('30.0.01')),
            '30.0.00' => $this->release('30.0.00', 'Current', 'c0', $this->gibbonPackage('30.0.00')),
        ]);

        $this->assertSame('30.0.01', $catalog->getNextRelease('30.0.00')['version']);
    }

    public function testVersionsAreComparedAsNumbers(): void
    {
        $catalog = $this->createStubbedCatalog([
            '30.0.9' => $this->release('30.0.9', 'Current', 'a0', $this->gibbonPackage('30.0.9')),
            '30.0.10' => $this->release('30.0.10', 'Current', 'a1', $this->gibbonPackage('30.0.10')),
        ]);

        // As text, "30.0.10" would sort before "30.0.9"
        $this->assertSame('30.0.10', $catalog->getNextRelease('30.0.9')['version']);
    }

    public function testDevelopmentReleasesAreOnlyOfferedToCuttingEdge(): void
    {
        $catalog = $this->createStubbedCatalog([
            '30.0.01' => $this->release('30.0.01', 'Development', null, 'https://github.com/GibbonEdu/core/archive/refs/heads/v30.0.01.zip'),
            '30.0.02' => $this->release('30.0.02', 'Current', 'b1', $this->gibbonPackage('30.0.02')),
        ]);

        $this->assertSame('30.0.02', $catalog->getNextRelease('30.0.00', false)['version']);
        $this->assertSame('30.0.01', $catalog->getNextRelease('30.0.00', true)['version']);
    }

    public function testNextReleaseStopsAtAVersionWithoutAPackage(): void
    {
        $catalog = $this->createStubbedCatalog([
            '30.0.01' => $this->release('30.0.01', 'Current', '', $this->gibbonPackage('30.0.01')),
            '30.0.02' => $this->release('30.0.02', 'Current', 'b1', $this->gibbonPackage('30.0.02')),
        ]);

        $this->assertNull($catalog->getNextRelease('30.0.00'));
    }

    public function testNothingIsOfferedWhenNoNewerReleaseExists(): void
    {
        $catalog = $this->createStubbedCatalog([
            '30.0.00' => $this->release('30.0.00', 'Current', 'a0', $this->gibbonPackage('30.0.00')),
        ]);

        $this->assertNull($catalog->getNextRelease('30.0.00'));
    }

    public function testAReleaseFromAnUnlistedHostIsNotOffered(): void
    {
        $catalog = $this->createStubbedCatalog([
            '30.0.01' => $this->release('30.0.01', 'Current', 'a1', 'https://example.com/gibbon-30.0.01.zip'),
        ]);

        $this->assertNull($catalog->getNextRelease('30.0.00'));
    }

    public function testGetReleaseReturnsAReleaseThatCanBeVerified(): void
    {
        $catalog = $this->createStubbedCatalog([
            '30.0.01' => $this->release('30.0.01', 'Current', ' A1 ', $this->gibbonPackage('30.0.01')),
        ]);

        $release = $catalog->getRelease('30.0.01');

        $this->assertSame('30.0.01', $release['version']);
        $this->assertSame('current', $release['status'], 'The status is normalised to lower case.');
        $this->assertSame('A1', $release['checksum'], 'The checksum is trimmed.');
        $this->assertSame($this->gibbonPackage('30.0.01'), $release['filePath']);
    }

    /**
     * @dataProvider unusableReleaseProvider
     */
    public function testGetReleaseRefusesAReleaseThatCannotBeInstalled(string $requestedVersion, array $entry): void
    {
        $catalog = $this->createStubbedCatalog(['30.0.01' => $entry]);

        $this->assertNull($catalog->getRelease($requestedVersion));
    }

    public static function unusableReleaseProvider(): array
    {
        $package = 'https://gibbonedu.org/download/gibbon-30.0.01.zip';

        return [
            'not in the catalog' => ['30.0.02', ['version' => '30.0.01', 'status' => 'Current', 'checksum' => 'a1', 'filePath' => $package]],
            'unknown status' => ['30.0.01', ['version' => '30.0.01', 'status' => 'Unreleased', 'checksum' => 'a1', 'filePath' => $package]],
            'no checksum' => ['30.0.01', ['version' => '30.0.01', 'status' => 'Current', 'checksum' => '', 'filePath' => $package]],
            'unlisted host' => ['30.0.01', ['version' => '30.0.01', 'status' => 'Current', 'checksum' => 'a1', 'filePath' => 'https://example.com/x.zip']],
        ];
    }

    /**
     * @dataProvider unusableCatalogReplyProvider
     */
    public function testAFailedOrMalformedCatalogGivesNoReleases(Response|\Throwable $reply): void
    {
        $catalog = $this->createStubbedCatalogFromReplies([$reply]);

        $this->assertSame([], $catalog->fetch('30.0.00'));
        $this->assertNull($catalog->getNextRelease('30.0.00'));
    }

    public static function unusableCatalogReplyProvider(): array
    {
        return [
            'server error' => [new Response(500, [], 'Error')],
            'not JSON' => [new Response(200, [], 'not json')],
            'a list instead of a version map' => [new Response(200, [], json_encode([['version' => '30.0.01']]))],
            'connection failure' => [new ConnectException('timed out', new Request('GET', VersionCatalog::CATALOG_URL))],
        ];
    }

    public function testTheCatalogIsRequestedOncePerVersion(): void
    {
        // Only one reply is queued. A second request would fail, and the catalog would come back empty.
        $catalog = $this->createStubbedCatalog([
            '30.0.01' => $this->release('30.0.01', 'Current', 'a1', $this->gibbonPackage('30.0.01')),
        ]);

        $first = $catalog->fetch('30.0.00');
        $second = $catalog->fetch('30.0.00');

        $this->assertNotEmpty($first);
        $this->assertSame($first, $second);
    }

    public function testChangelogTextIsGroupedByVersionAndCategory(): void
    {
        $text = "v30.0.03\n--------\n\n    Security\n        System: improved POST referrer check\n\n    Bug Fixes\n        Calendar: fixed attendance\n";
        $catalog = new VersionCatalog($this->createMock(Session::class));

        $parsed = $catalog->getChangeLogFromString($text);

        $this->assertSame([
            'Security' => ['System: improved POST referrer check'],
            'Bug Fixes' => ['Calendar: fixed attendance'],
        ], $parsed['30.0.03']['changelog']);
    }

    public function testMergedChangelogsAreNewestFirstAndKeepEveryEntry(): void
    {
        $catalog = new VersionCatalog($this->createMock(Session::class));

        $catalog->mergeCatalog([
            '30.0.00' => ['changelog' => ['Security' => ['A fix.']]],
            '31.0.00' => ['changelog' => []],
        ]);
        $catalog->mergeCatalog(['30.0.00' => ['status' => 'Current']]);

        $merged = $catalog->getCatalog();
        $this->assertSame(['31.0.00', '30.0.00'], array_keys($merged));
        $this->assertSame(['Security' => ['A fix.']], $merged['30.0.00']['changelog']);
        $this->assertSame('Current', $merged['30.0.00']['status']);
    }

    /**
     * @param array<string, array<string, mixed>> $entries The catalog, keyed by version.
     */
    private function createStubbedCatalog(array $entries): StubbedVersionCatalog
    {
        return $this->createStubbedCatalogFromReplies([new Response(200, [], json_encode($entries))]);
    }

    /**
     * @param array<int, Response|\Throwable> $replies
     */
    private function createStubbedCatalogFromReplies(array $replies): StubbedVersionCatalog
    {
        $catalog = new StubbedVersionCatalog($this->createMock(Session::class));
        $catalog->client = new Client(['handler' => HandlerStack::create(new MockHandler($replies))]);

        return $catalog;
    }

    private function release(string $version, string $status, ?string $checksum, string $filePath): array
    {
        return ['version' => $version, 'status' => $status, 'checksum' => $checksum, 'filePath' => $filePath, 'phpVersion' => '8.2'];
    }

    private function gibbonPackage(string $version): string
    {
        return 'https://gibbonedu.org/download/gibbon-'.$version.'.zip';
    }

    private function createCatalog(): VersionCatalog
    {
        return new VersionCatalog($this->createMock(Session::class));
    }
}

/**
 * Sends the catalog request through a supplied client, so tests can answer it from a script.
 */
class StubbedVersionCatalog extends VersionCatalog
{
    public ?Client $client = null;

    protected function createClient(array $options) : Client
    {
        return $this->client ?? parent::createClient($options);
    }
}
