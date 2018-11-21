<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon;

use Gibbon\Contracts\Database\Connection;
use Gibbon\Locale;
use League\Container\Container;
use PHPUnit\Framework\TestCase;

/**
 * @covers Locale
 *
 * Test against PO file generated LocaleTest.sh in
 * the folder containing this file.
 */
class TranslateTest extends TestCase
{
    private $mockPDO;
    private $mockSession;

    private $locale;
    private $gibbonToRestore;

    public function setUp()
    {

        // Setup the composer autoloader
        $autoloader = require_once __DIR__.'/../../../../vendor/autoload.php';

        // Require the system-wide functions
        require_once __DIR__.'/../../../../functions.php';

        // Create a stub for the Gibbon\session class
        $this->mockSession = $this->createMock(session::class);
        $this->mockSession
            ->method('get')
            ->willReturn(null); // always return null

        // mocked locale object
        $i18ncode = 'zh_TW';
        $locale = new Locale(__DIR__ . '/mock', $this->mockSession);
        $locale->setLocale($i18ncode);
        $locale->setSystemTextDomain(__DIR__ . '/mock');

        // mocked global gibbon object
        global $gibbon;
        $this->gibbonToRestore = isset($gibbon) ? $gibbon : null;
        $gibbon = (object) [
            'locale' => $locale,
        ];
    }

    public function tearDown()
    {
        global $gibbon;
        unset($gibbon);
        if (isset($this->gibbonToRestore)) {
            $gibbon = $this->gibbonToRestore; // restore gibbon before test
        }
    }

    public function testTranslate()
    {
        global $gibbon;

        $this->assertEquals(
            'Not translated',
            # L10N: Untranslated plain text.
            $gibbon->locale->translate('Not translated'),
            'Untranslated string stays untranslated'
        );

        $this->assertEquals(
            'Untranslated hello world',
            # L10N: Untranslated string for string replacement.
            $gibbon->locale->translate('Untranslated {action} {name}', [
                'action' => 'hello',
                'name' => 'world',
            ]),
            'Named string replacement works on untranslated strings'
        );

        $this->assertEquals(
            '你好世界',
            # L10N: Translated plain text.
            $gibbon->locale->translate('Hello world'),
            'Translated plain text'
        );

        $this->assertEquals(
            '你好，來自 earth 的 stranger',
            # L10N: Translated string for string replacement.
            $gibbon->locale->translate('Hello {name} from {planet}', [
                'name' => 'stranger',
                'planet' => 'earth',
            ]),
            'Translated string with named string replacement'
        );

        $this->assertEquals(
            '你好，來自 earth 的 stranger',
            # L10N: Translated string for numerical replacement.
            $gibbon->locale->translate('Hello {0} from {1}', [
                'stranger',
                'earth',
            ]),
            'Translated string with named string replacement'
        );

        $this->assertEquals(
            '你好，來自 earth 的 stranger。今天是 monday。',
            # L10N: Translated string with mixed numerical and string placeholder.
            $gibbon->locale->translate('Hello {0} from {1}. Today is {dayOfWeek}.', [
                'stranger',
                'dayOfWeek' => 'monday',
                'earth',
            ]),
            'Translated string with mixed numerical and string placeholder'
        );
    }

    public function testShortcut()
    {
        $this->assertEquals(
            'Not translated',
            # L10N: Untranslated plain text.
            __('Not translated'),
            'Untranslated string stays untranslated'
        );

        $this->assertEquals(
            'Untranslated hello world',
            # L10N: Untranslated string for string replacement.
            __('Untranslated {action} {name}', [
                'action' => 'hello',
                'name' => 'world',
            ]),
            'Named string replacement works on untranslated strings'
        );

        $this->assertEquals(
            '你好世界',
            # L10N: Translated plain text.
            __('Hello world'),
            'Translated plain text'
        );

        $this->assertEquals(
            '你好，來自 earth 的 stranger',
            # L10N: Translated string for string replacement.
            __('Hello {name} from {planet}', [
                'name' => 'stranger',
                'planet' => 'earth',
            ]),
            'Translated string with named string replacement'
        );

        $this->assertEquals(
            '你好，來自 earth 的 stranger',
            # L10N: Translated string for numerical placeholder replacement.
            __('Hello {0} from {1}', [
                'stranger',
                'earth',
            ]),
            'Translated string with numerical placeholder replacement'
        );

        $this->assertEquals(
            '你好，來自 earth 的 stranger。今天是 monday。',
            # L10N: Translated string with mixed numerical and string placeholder.
            __('Hello {0} from {1}. Today is {dayOfWeek}.', [
                'stranger',
                'dayOfWeek' => 'monday',
                'earth',
            ]),
            'Translated string with mixed numerical and string placeholder'
        );
    }
}
