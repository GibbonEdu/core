<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon;

use Gibbon\Locale;
use Gibbon\Contracts\Services\Session;
use PHPUnit\Framework\TestCase;

// Require the system-wide functions.
require_once __DIR__.'/../../../../functions.php';

// Require trait for testing.
require_once __DIR__ . '/MockGibbonTrait.php';

/**
 * @covers \Gibbon\Locale::translateN
 * @covers __n function
 *
 * Test against PO file generated LocaleTest.sh in
 * the folder containing this file.
 */
class TranslateNTest extends TestCase
{
    use MockGibbonTrait;

    /**
     * Locale object to test with.
     *
     * @var \Gibbon\Locale
     */
    private $locale;

    public function setUp(): void
    {
        // Create a stub for the Gibbon\Contracts\Services\Session interface
        $mockSession = $this->createMock(Session::class);
        $mockSession
            ->method('get')
            ->willReturn(null); // always return null

        // mocked locale object
        $i18ncode = 'es_ES';
        $locale = new Locale(__DIR__ . '/mock', $mockSession);
        $locale->setLocale($i18ncode);
        $locale->setSystemTextDomain(__DIR__ . '/mock');
        $this->locale = $locale;
    }

    /**
     * @covers \Gibbon\Locale::translateN
     */
    public function testTranslateN()
    {
        $this->assertEquals(
            'I have an orange',
            # L10N: Untranslated plural string with string placeholder
            $this->locale->translateN('I have an orange', 'I have {num} oranges', 1, [
                'num' => 1,
            ]),
            'Untranslated plural string with string placeholder, with n=1'
        );

        $this->assertEquals(
            'I have 3 oranges',
            # L10N: Untranslated plural string with string placeholder
            $this->locale->translateN('I have an orange', 'I have {num} oranges', 3, [
                'num' => 3,
            ]),
            'Untranslated plural string with string placeholder, with n=3'
        );

        $this->assertEquals(
            'Yo quiero una manzana',
            # L10N: Translated plural string with string placeholder
            $this->locale->translateN('I have an apple', 'I have {num} apples', 1, [
                'num' => 1,
            ]),
            'Translated plural string with string placeholder, with n=1'
        );

        $this->assertEquals(
            'Yo quiero 3 manzanas',
            # L10N: Translated plural string with string placeholder
            $this->locale->translateN('I have an apple', 'I have {num} apples', 3, [
                'num' => 3,
            ]),
            'Translated plural string with string placeholder, with n=3'
        );
    }

    /**
     * @covers __n(string $singular, string $plural, int $n)
     */
    public function testShortcutBasic()
    {
        $n = rand(1, 4096);
        $localeObserver = $this->createMock(Locale::class);
        $localeObserver->expects($this->once())
                ->method('translateN')
                ->with(
                     $this->equalTo('Some text to translate'),
                     $this->equalTo('Some text to translate for plural'),
                     $this->equalTo($n),
                     $this->equalTo([]),
                     $this->equalTo([])
                )
                ->willReturn('Some translation result');
        $restoreGibbon = $this->mockGlobalGibbon((object) [
            'locale' => $localeObserver,
        ]);

        $this->assertEquals(
            'Some translation result',
            __n('Some text to translate', 'Some text to translate for plural', $n),
            '__n() calls $ibbon->locale->translateN()'
        );
        $restoreGibbon();
    }

    /**
     * @covers __n(string $singular, string $plural, int $n, array $params)
     */
    public function testShortcutTextWithParams()
    {
        $n = rand(1, 4096);
        $localeObserver = $this->createMock(Locale::class);
        $localeObserver->expects($this->once())
                ->method('translateN')
                ->with(
                    $this->equalTo('Some text to translate'),
                    $this->equalTo('Some text to translate for plural'),
                    $this->equalTo($n),
                    $this->equalTo(['some', 'param']),
                    $this->equalTo([])
                )
                ->willReturn('Some translation result');
        $restoreGibbon = $this->mockGlobalGibbon((object) [
            'locale' => $localeObserver,
        ]);

        $this->assertEquals(
            'Some translation result',
            __n('Some text to translate', 'Some text to translate for plural', $n, ['some', 'param']),
            '__n() calls $ibbon->locale->translateN()'
        );
        $restoreGibbon();
    }

    /**
     * @covers __n(string $singular, string $plural, int $n, array $params, array $options)
     */
    public function testShortcutWithParamsAndOptions()
    {
        $n = rand(1, 4096);
        $localeObserver = $this->createMock(Locale::class);
        $localeObserver->expects($this->once())
                ->method('translateN')
                ->with(
                    $this->equalTo('Some text to translate'),
                    $this->equalTo('Some text to translate for plural'),
                    $this->equalTo($n),
                    $this->equalTo(['some', 'param']),
                    $this->equalTo(['some', 'options'])
                )
                ->willReturn('Some translation result');
        $restoreGibbon = $this->mockGlobalGibbon((object) [
            'locale' => $localeObserver,
        ]);

        $this->assertEquals(
            'Some translation result',
            __n('Some text to translate', 'Some text to translate for plural', $n, ['some', 'param'], ['some', 'options']),
            '__n() calls $ibbon->locale->translateN()'
        );
        $restoreGibbon();
    }

}
