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
 * @covers \Gibbon\Services\Localization\TranslationCatalog
 */
class TranslationCatalogTest extends TestCase
{
    protected function setUp() : void
    {
        TranslationCatalog::clearCache();
    }

    protected function tearDown() : void
    {
        TranslationCatalog::clearCache();

        $filePath = realpath(__DIR__.'/../../../../i18n/ar_SA/LC_MESSAGES/gibbon.mo');
        if ($filePath !== false) {
            @unlink(sys_get_temp_dir().'/gibbon-mo/'.md5($filePath).'.ser');
        }
    }

    public function testLoadsArabicCatalogWithoutSystemLocale()
    {
        $filePath = realpath(__DIR__.'/../../../../i18n/ar_SA/LC_MESSAGES/gibbon.mo');
        if ($filePath === false) {
            $this->markTestSkipped('Arabic gibbon.mo is not installed.');
        }

        $catalog = TranslationCatalog::load($filePath);
        $this->assertInstanceOf(TranslationCatalog::class, $catalog);
        $this->assertSame('الرئيسية', $catalog->translate('Home'));
        $this->assertSame('الطلبة', $catalog->translate('Students'));
        $this->assertSame('Missing String', $catalog->translate('Missing String'));
    }

    public function testReusesCachedCatalogWithinWorker()
    {
        $filePath = realpath(__DIR__.'/../../../../i18n/ar_SA/LC_MESSAGES/gibbon.mo');
        if ($filePath === false) {
            $this->markTestSkipped('Arabic gibbon.mo is not installed.');
        }

        $first = TranslationCatalog::load($filePath);
        $second = TranslationCatalog::load($filePath);

        $this->assertSame($first, $second);
    }

    public function testCacheIsUsedOnTheNextLoad()
    {
        $filePath = realpath(__DIR__.'/../../../../i18n/ar_SA/LC_MESSAGES/gibbon.mo');
        if ($filePath === false) {
            $this->markTestSkipped('Arabic gibbon.mo is not installed.');
        }

        $cacheFilePath = sys_get_temp_dir().'/gibbon-mo/'.md5($filePath).'.ser';
        @unlink($cacheFilePath);

        $parsed = TranslationCatalog::load($filePath);
        $this->assertInstanceOf(TranslationCatalog::class, $parsed);
        $this->assertFileExists($cacheFilePath);

        // Replace the compiled catalog with one known string
        $cacheData = serialize([
            'mtime' => filemtime($filePath),
            'entries' => ['Home' => 'CACHE_HIT'],
            'nplurals' => 2,
            'plural' => null,
        ]);
        file_put_contents($cacheFilePath, $cacheData);

        TranslationCatalog::clearCache();
        $cached = TranslationCatalog::load($filePath);

        $this->assertInstanceOf(TranslationCatalog::class, $cached);
        $this->assertSame('CACHE_HIT', $cached->translate('Home'));

        @unlink($cacheFilePath);
        TranslationCatalog::clearCache();
    }

    public function testArabicPluralFormsDoNotFatalOnPhp8()
    {
        $filePath = realpath(__DIR__.'/../../../../i18n/ar_SA/LC_MESSAGES/gibbon.mo');
        if ($filePath === false) {
            $this->markTestSkipped('Arabic gibbon.mo is not installed.');
        }

        $catalog = TranslationCatalog::load($filePath);
        $this->assertInstanceOf(TranslationCatalog::class, $catalog);

        // Arabic uses 6 plural forms; nested ternaries must be PHP 8 safe.
        foreach ([0, 1, 2, 3, 11, 100] as $n) {
            $translated = $catalog->translateN(
                '%1$s Day Absent',
                '%1$s Days Absent',
                $n
            );
            $this->assertIsString($translated);
            $this->assertNotSame('', $translated);
        }
    }

    public function testRussianPluralFormsDoNotFatalOnPhp8()
    {
        $filePath = realpath(__DIR__.'/../../../../i18n/ru_RU/LC_MESSAGES/gibbon.mo');
        if ($filePath === false) {
            $this->markTestSkipped('Russian gibbon.mo is not installed.');
        }

        $catalog = TranslationCatalog::load($filePath);
        $this->assertInstanceOf(TranslationCatalog::class, $catalog);

        foreach ([1, 2, 5] as $n) {
            $translated = $catalog->translateN(
                '%1$s Day Absent',
                '%1$s Days Absent',
                $n
            );
            $this->assertIsString($translated);
        }
    }

    public function testRussianPluralFormsMatchGettextRules()
    {
        $filePath = realpath(__DIR__.'/../../../../i18n/ru_RU/LC_MESSAGES/gibbon.mo');
        if ($filePath === false) {
            $this->markTestSkipped('Russian gibbon.mo is not installed.');
        }

        $catalog = TranslationCatalog::load($filePath);
        $this->assertInstanceOf(TranslationCatalog::class, $catalog);

        // Same forms setlocale()/ngettext return for ru_RU.
        $this->assertSame('Учитель', $catalog->translateN('Teacher', 'Teachers', 1));
        $this->assertSame('Учитель', $catalog->translateN('Teacher', 'Teachers', 21));
        $this->assertSame('Учителя', $catalog->translateN('Teacher', 'Teachers', 2));
        $this->assertSame('Учителя', $catalog->translateN('Teacher', 'Teachers', 4));
        $this->assertSame('Учителей', $catalog->translateN('Teacher', 'Teachers', 0));
        $this->assertSame('Учителей', $catalog->translateN('Teacher', 'Teachers', 5));
        $this->assertSame('Учителей', $catalog->translateN('Teacher', 'Teachers', 11));
        $this->assertSame('Учителей', $catalog->translateN('Teacher', 'Teachers', 111));
    }

    public function testReturnsNullForMissingFile()
    {
        $this->assertNull(TranslationCatalog::load('/tmp/gibbon-does-not-exist.mo'));
    }
}
