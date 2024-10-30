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

use PHPUnit\Framework\TestCase;
use Gibbon\Contracts\Services\Session;

// Require the system-wide functions.
require_once __DIR__.'/../../../../functions.php';

// Require trait for testing.
require_once __DIR__ . '/MockGibbonTrait.php';
require_once __DIR__ . '/MockGuidTrait.php';

/**
 * @covers __ function
 */
class TranslationTest extends TestCase
{

    use MockGibbonTrait;
    use MockGuidTrait;

    /**
     * A callable to restore gettext locale.
     *
     * @var callable
     */
    protected $restoreLocale;

    /**
     * A callable to restore global gibbon object.
     *
     * @var callable
     */
    protected $restoreGibbon;

    /**
     * The guid string for test.
     *
     * @var string
     */
    protected $guid;

    /**
     * A callable to restore guid string.
     *
     * @var callable
     */
    protected $restoreGuid;

    public function setUp(): void
    {
        // Create a stub for the Gibbon\Contracts\Services\Session interface
        $mockSession = $this->createMock(Session::class);
        $mockSession
            ->method('get')
            ->willReturn(null); // always return null

        // Check if the locale has been installed. Skip test if not.
        $langCode = 'es_ES';
        if (!is_dir(realpath(__DIR__.'/../../../..') . '/i18n/' . $langCode)) {
            $this->markTestSkipped("Gibbon i18n files for \"{$langCode}\" are not installed. Unable to proceed.");
            return;
        }

        // mock locale object
        $locale = new Locale(realpath(__DIR__.'/../../../..'), $mockSession);
        $locale->setLocale($langCode);
        $locale->setSystemTextDomain(realpath(__DIR__.'/../../../..'));

        // remember how to restore locale
        $this->restoreLocale = function () use ($locale) {
            $locale->setLocale('en_GB');
        };

        // mock gibbon object
        $this->restoreGibbon = $this->mockGlobalGibbon((object) [
            'locale' => $locale,
        ]);

        // mock guid
        list($guid, $restoreGuid) = $this->mockGlobalGuid();
        $this->guid = $guid;
        $this->restoreGuid = $restoreGuid;
    }

    public function tearDown(): void
    {
        ($this->restoreLocale)();
        ($this->restoreGibbon)();
        ($this->restoreGuid)();
    }

    /**
     * @covers __(string $guid, string $text)
     */
    public function testTranslateUsingGuid()
    {
        $this->assertEquals('Bienvenido a Gibbon', __($this->guid, 'Welcome To Gibbon'));
    }

    /**
     * @covers __(string $guid, string $text, string $domain)
     */
    public function testTranslateUsingGuidWithDomainString()
    {
        $this->assertEquals('Bienvenido a Gibbon', __($this->guid, 'Welcome To Gibbon', 'gibbon'));
        $this->assertEquals('Welcome', __($this->guid, 'Welcome', 'bogus_domain'));
    }

    /**
     * @covers __(string $text)
     */
    public function testTranslateNoGuid()
    {
        $this->assertEquals('Bienvenido a Gibbon', __('Welcome To Gibbon'));
    }

    /**
     * @covers __(string $text, string $domain)
     */
    public function testTranslateNoGuidWithDomainString()
    {
        $this->assertEquals('Bienvenido a Gibbon', __('Welcome To Gibbon', 'gibbon'));
        $this->assertEquals('Welcome', __('Welcome', 'bogus_domain'));
    }

    /**
     * @covers __(string $text, array $args = [], array $options = [])
     */
    public function testTranslateUsingEmptyParameters()
    {
        $this->assertEquals('Bienvenido a Gibbon', __('Welcome To Gibbon', [], []));
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
        $this->assertEquals('Bienvenido a Gibbon', __('Welcome To Gibbon', [], ['domain' => 'gibbon']));
        $this->assertEquals('Welcome', __('Welcome', [], ['domain' => 'bogus_domain']));
    }
}
