<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Domain\System;

use PHPUnit\Framework\TestCase;

/**
 * @covers Module
 */
class ModuleTest extends TestCase
{
    public function testCanAddStylesheets()
    {
        $module = new Module('Test');
        $module->stylesheets()->add('foo', 'bar/baz', 'screen');

        $this->assertArrayHasKey('foo',  $module->stylesheets()->getAssets());
    }

    public function testCanAddScripts()
    {
        $module = new Module('Test');
        $module->scripts()->add('fiz', 'bar/baz', 'head');

        $this->assertArrayHasKey('fiz',  $module->scripts()->getAssets());
    }
}
