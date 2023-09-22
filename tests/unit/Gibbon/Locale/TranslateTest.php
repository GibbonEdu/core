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
require_once __DIR__ . '/MockGuidTrait.php';

/**
 * @covers \Gibbon\Locale::translate
 * @covers __ function
 *
 * Test against PO file generated LocaleTest.sh in
 * the folder containing this file.
 */
class TranslateTest extends TestCase
{

    use MockGibbonTrait;
    use MockGuidTrait;

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
        $i18ncode = 'zh_TW';
        $locale = new Locale(__DIR__ . '/mock', $mockSession);
        $locale->setLocale($i18ncode);
        $locale->setSystemTextDomain(__DIR__ . '/mock');

        $this->locale = $locale;
    }

    /**
     * @covers \Gibbon\Locale::translate
     */
    public function testTranslate()
    {
        $this->assertEquals(
            'Not translated',
            # L10N: Untranslated plain text.
            $this->locale->translate('Not translated'),
            'Untranslated string stays untranslated'
        );

        $this->assertEquals(
            'Untranslated hello world',
            # L10N: Untranslated string for string replacement.
            $this->locale->translate('Untranslated {action} {name}', [
                'action' => 'hello',
                'name' => 'world',
            ]),
            'Named string replacement works on untranslated strings'
        );

        $this->assertEquals(
            '你好世界',
            # L10N: Translated plain text.
            $this->locale->translate('Hello world'),
            'Translated plain text'
        );

        $this->assertEquals(
            '你好，來自 earth 的 stranger',
            # L10N: Translated string for string replacement.
            $this->locale->translate('Hello {name} from {planet}', [
                'name' => 'stranger',
                'planet' => 'earth',
            ]),
            'Translated string with named string replacement'
        );

        $this->assertEquals(
            '你好，來自 earth 的 stranger',
            # L10N: Translated string for numerical replacement.
            $this->locale->translate('Hello {0} from {1}', [
                'stranger',
                'earth',
            ]),
            'Translated string with named string replacement'
        );

        $this->assertEquals(
            '你好，來自 earth 的 stranger。今天是 monday。',
            # L10N: Translated string with mixed numerical and string placeholder.
            $this->locale->translate('Hello {0} from {1}. Today is {dayOfWeek}.', [
                'stranger',
                'dayOfWeek' => 'monday',
                'earth',
            ]),
            'Translated string with mixed numerical and string placeholder'
        );
    }

    /**
     * @covers __(string $text)
     */
    public function testShortcutBasic()
    {
        $localeObserver = $this->createMock(Locale::class);
        $localeObserver->expects($this->once())
                ->method('translate')
                ->with(
                     $this->equalTo('Some text to translate'),
                     $this->equalTo([]),
                     $this->equalTo([])
                )
                ->willReturn('Some translation result');
        $restoreGibbon = $this->mockGlobalGibbon((object) [
            'locale' => $localeObserver,
        ]);

        $this->assertEquals(
            'Some translation result',
            __('Some text to translate'),
            '__() calls $gibbon->locale->translate()'
        );
        $restoreGibbon();
    }

    /**
     * @covers __(string $text, array $params)
     */
    public function testShortcutTextWithParams()
    {
        $localeObserver = $this->createMock(Locale::class);
        $localeObserver->expects($this->once())
                ->method('translate')
                ->with(
                     $this->equalTo('Some text to translate'),
                     $this->equalTo(['some', 'param']),
                     $this->equalTo([])
                )
                ->willReturn('Some translation result');
        $restoreGibbon = $this->mockGlobalGibbon((object) [
            'locale' => $localeObserver,
        ]);

        $this->assertEquals(
            'Some translation result',
            __('Some text to translate', ['some', 'param']),
            '__() calls $gibbon->locale->translate()'
        );
        $restoreGibbon();
    }

    /**
     * @covers __(string $text, array $params, array $options)
     */
    public function testShortcutWithParamsAndOptions()
    {
        $localeObserver = $this->createMock(Locale::class);
        $localeObserver->expects($this->once())
                ->method('translate')
                ->with(
                     $this->equalTo('Some text to translate'),
                     $this->equalTo(['some', 'param']),
                     $this->equalTo(['some', 'options'])
                )
                ->willReturn('Some translation result');
        $restoreGibbon = $this->mockGlobalGibbon((object) [
            'locale' => $localeObserver,
        ]);

        $this->assertEquals(
            'Some translation result',
            __('Some text to translate', ['some', 'param'], ['some', 'options']),
            '__() calls $gibbon->locale->translate()'
        );
        $restoreGibbon();
    }

    /**
     * @covers __(string $guid, string $text)
     */
    public function testShortcutUsingGuid()
    {
        $localeObserver = $this->createMock(Locale::class);
        $localeObserver->expects($this->once())
                ->method('translate')
                ->with(
                     $this->equalTo('Some text to translate'),
                     $this->equalTo([]),
                     $this->equalTo([])
                )
                ->willReturn('Some translation result');
        list($guid, $restoreGuid) = $this->mockGlobalGuid();
        $restoreGibbon = $this->mockGlobalGibbon((object) [
            'locale' => $localeObserver,
        ]);

        $this->assertEquals(
            'Some translation result',
            __($guid, 'Some text to translate'),
            '__() with $guid calls $gibbon->locale->translate() in backward compatible way'
        );

        $restoreGibbon();
        $restoreGuid();
    }

    /**
     * @covers __(string $guid, string $text, string $domain)
     */
    public function testShortcutUsingGuidWithDomainString()
    {
        $localeObserver = $this->createMock(Locale::class);
        $localeObserver->expects($this->once())
                ->method('translate')
                ->with(
                     $this->equalTo('Some text to translate'),
                     $this->equalTo([]),
                     $this->equalTo(['domain' => 'bogus_domain'])
                )
                ->willReturn('Some translation result');
        list($guid, $restoreGuid) = $this->mockGlobalGuid();
        $restoreGibbon = $this->mockGlobalGibbon((object) [
            'locale' => $localeObserver,
        ]);

        $this->assertEquals(
            'Some translation result',
            __($guid, 'Some text to translate', 'bogus_domain'),
            '__() with $guid calls $gibbon->locale->translate() in backward compatible way and returns the result'
        );

        $restoreGibbon();
        $restoreGuid();
    }
}
