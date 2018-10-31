<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Domain\System;

use PHPUnit\Framework\TestCase;
use Gibbon\Domain\System\Module;

/**
 * @covers Module
 */
class ModuleTest extends TestCase
{
    protected $module;

    public function setUp()
    {
        $this->module = $this->getMockBuilder(Module::class)
            ->setConstructorArgs([])
            ->getMockForAbstractClass();
    }

    public function testCanAddStylesheets()
    {
        $this->module->stylesheets->add('foo', 'bar/baz');

        $this->assertArrayHasKey('foo',  $this->module->stylesheets->getAssets());
    }

    public function testCanAddScripts()
    {
        $this->module->scripts->add('fiz', 'bar/baz');

        $this->assertArrayHasKey('fiz',  $this->module->scripts->getAssets());
    }

    public function testGetAutoloadFilepath()
    {
        $this->assertEquals(
            realpath(__DIR__ . '/../../../../modules') . '/Hello World/src/MyClass.php',
            Module::getAutoloadFilepath('Gibbon\\Module\\HelloWorld\\MyClass'),
            'Suggested filepath to module HelloWorld does not match expectation.'
        );
        $this->assertEquals(
            realpath(__DIR__ . '/../../../../modules') . '/Hello World/src/SomeDomain/MyClass.php',
            Module::getAutoloadFilepath('Gibbon\\Module\\HelloWorld\\SomeDomain\\MyClass'),
            'Suggested filepath to module HelloWorld does not match expectation.'
        );
        $this->assertNull(
            Module::getAutoloadFilepath('Gibbon\\NotModule\\HelloWorld\\SomeDomain\\MyClass'),
            'Should not suggest any filepath if the pattern is not a module class.'
        );
        $this->assertNull(
            Module::getAutoloadFilepath('Something\\Else\\HelloWorld\\SomeDomain\\MyClass'),
            'Should not suggest any filepath if the pattern is not a Gibbon class.'
        );
    }
}
