<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Services\Localization;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Gibbon\Services\Localization\MoCatalog
 */
class MoCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        MoCatalog::clearCache();
    }

    protected function tearDown(): void
    {
        MoCatalog::clearCache();
    }

    public function testLoadsArabicCatalogWithoutSystemLocale()
    {
        $moFile = realpath(__DIR__ . '/../../../../i18n/ar_SA/LC_MESSAGES/gibbon.mo');
        if ($moFile === false) {
            $this->markTestSkipped('Arabic gibbon.mo is not installed.');
        }

        $catalog = MoCatalog::load($moFile);
        $this->assertInstanceOf(MoCatalog::class, $catalog);
        $this->assertSame('الرئيسية', $catalog->translate('Home'));
        $this->assertSame('الطلبة', $catalog->translate('Students'));
        $this->assertSame('Missing String', $catalog->translate('Missing String'));
    }

    public function testReusesCachedCatalogWithinWorker()
    {
        $moFile = realpath(__DIR__ . '/../../../../i18n/ar_SA/LC_MESSAGES/gibbon.mo');
        if ($moFile === false) {
            $this->markTestSkipped('Arabic gibbon.mo is not installed.');
        }

        $first = MoCatalog::load($moFile);
        $second = MoCatalog::load($moFile);

        $this->assertSame($first, $second);
    }

    public function testReturnsNullForMissingFile()
    {
        $this->assertNull(MoCatalog::load('/tmp/gibbon-does-not-exist.mo'));
    }
}
