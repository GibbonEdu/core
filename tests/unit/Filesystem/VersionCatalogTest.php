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

    private function createCatalog(): VersionCatalog
    {
        return new VersionCatalog($this->createMock(Session::class));
    }
}
