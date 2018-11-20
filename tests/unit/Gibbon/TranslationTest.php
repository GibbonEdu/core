<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon;

use PHPUnit\Framework\TestCase;

/**
 * @covers __ function
 */
class TranslationTest extends TestCase
{
    protected $guid;

    public function setUp()
    {
        global $guid, $gibbon;

        $gibbon->locale->setLocale('es_ES');
        $gibbon->locale->setSystemTextDomain(realpath(__DIR__.'/../../..'));

        $this->guid = $guid;
    }

    public function tearDown()
    {
        global $gibbon;

        $gibbon->locale->setLocale('en_GB');
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
