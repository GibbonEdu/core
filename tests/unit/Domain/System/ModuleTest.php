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
}
