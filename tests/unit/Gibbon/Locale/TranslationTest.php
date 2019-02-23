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
 * @covers __ function
 */
final class TranslationTest extends TestCase
{
    private $mockPDO;
    private $mockSession;

    private $guid;
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
        global $guid, $gibbon;
        $this->guid = $guid;
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

    /**
     * @covers setLocale/getLocale.
     */
    public function testLocale()
    {
        global $gibbon;

        $this->assertEquals('es_ES', $gibbon->locale->getLocale());
    }

    /**
     * @covers __(string $guid, string $text)
     */
    public function testTranslateUsingGuid()
    {
        $this->assertEquals('Bienvenido', __($this->guid, 'Welcome'));
    }

    /**
     * @covers __(string $guid, string $text, string $domain)
     */
    public function testTranslateUsingGuidWithDomainString()
    {
        $this->assertEquals('Bienvenido', __($this->guid, 'Welcome', 'gibbon'));
        $this->assertEquals('Welcome', __($this->guid, 'Welcome', 'bogus_domain'));
    }

    /**
     * @covers __(string $text)
     */
    public function testTranslateNoGuid()
    {
        $this->assertEquals('Bienvenido', __('Welcome'));
    }

    /**
     * @covers __(string $text, string $domain)
     */
    public function testTranslateNoGuidWithDomainString()
    {
        $this->assertEquals('Bienvenido', __('Welcome', 'gibbon'));
        $this->assertEquals('Welcome', __('Welcome', 'bogus_domain'));
    }

    /**
     * @covers __(string $text, array $args = [], array $options = [])
     */
    public function testTranslateUsingEmptyParameters()
    {
        $this->assertEquals('Bienvenido', __('Welcome', [], []));
    }

    /**
     * @covers __(string $text, array $args = [], array $options = [])
     */
    public function testTranslateUsingNamedParameters()
    {
        $this->assertEquals('Foo Baz Bar', __('Foo {test} Bar', ['test' => 'Baz']));
    }

    /**
     * @covers __(string $text)
     */
    public function testTranslateUsingPrintfParameters()
    {
        $this->assertEquals('Bienvenido a Foo en Bar', sprintf(__('Welcome to %1$s at %2$s'), 'Foo', 'Bar') );
    }

    /**
     * @covers __(string $text, array $args = [], array $options = [])
     */
    public function testTranslateUsingOptions()
    {
        $this->assertEquals('Bienvenido', __('Welcome', [], ['domain' => 'gibbon']));
        $this->assertEquals('Welcome', __('Welcome', [], ['domain' => 'bogus_domain']));
    }
}
