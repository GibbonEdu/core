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
class TranslateNTest extends TestCase
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
        $i18ncode = 'es_ES';
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

    public function testTranslateN()
    {
        global $gibbon;

        $this->assertEquals(
            'I have an orange',
            # L10N: Untranslated plural string with string placeholder
            $gibbon->locale->translateN('I have an orange', 'I have {num} oranges', 1, [
                'num' => 1,
            ]),
            'Untranslated plural string with string placeholder, with n=1'
        );

        $this->assertEquals(
            'I have 3 oranges',
            # L10N: Untranslated plural string with string placeholder
            $gibbon->locale->translateN('I have an orange', 'I have {num} oranges', 3, [
                'num' => 3,
            ]),
            'Untranslated plural string with string placeholder, with n=3'
        );

        $this->assertEquals(
            'Yo quiero una manzana',
            # L10N: Translated plural string with string placeholder
            $gibbon->locale->translateN('I have an apple', 'I have {num} apples', 1, [
                'num' => 1,
            ]),
            'Translated plural string with string placeholder, with n=1'
        );

        $this->assertEquals(
            'Yo quiero 3 manzanas',
            # L10N: Translated plural string with string placeholder
            $gibbon->locale->translateN('I have an apple', 'I have {num} apples', 3, [
                'num' => 3,
            ]),
            'Translated plural string with string placeholder, with n=3'
        );
    }

    public function testShortcut()
    {
        $this->assertEquals(
            'I have an orange',
            # L10N: Untranslated plural string with string placeholder
            __n('I have an orange', 'I have {num} oranges', 1, [
                'num' => 1,
            ]),
            'Untranslated plural string with string placeholder, with n=1'
        );

        $this->assertEquals(
            'I have 3 oranges',
            # L10N: Untranslated plural string with string placeholder
            __n('I have an orange', 'I have {num} oranges', 3, [
                'num' => 3,
            ]),
            'Untranslated plural string with string placeholder, with n=3'
        );

        $this->assertEquals(
            'Yo quiero una manzana',
            # L10N: Translated plural string with string placeholder
            __n('I have an apple', 'I have {num} apples', 1, [
                'num' => 1,
            ]),
            'Translated plural string with string placeholder, with n=1'
        );

        $this->assertEquals(
            'Yo quiero 3 manzanas',
            # L10N: Translated plural string with string placeholder
            __n('I have an apple', 'I have {num} apples', 3, [
                'num' => 3,
            ]),
            'Translated plural string with string placeholder, with n=3'
        );
    }
}
