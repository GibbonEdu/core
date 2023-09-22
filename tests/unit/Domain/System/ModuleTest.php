<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

    public function setUp(): void
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
