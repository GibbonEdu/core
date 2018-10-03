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
 * @covers Theme
 */
class ThemeTest extends TestCase
{
    public function testCanAddStylesheets()
    {
        $theme = new Theme('Test');
        $theme->stylesheets()->add('foo', 'bar/baz', 'screen');

        $this->assertArrayHasKey('foo',  $theme->stylesheets()->getAssets());
    }

    public function testCanAddScripts()
    {
        $theme = new Theme('Test');
        $theme->scripts()->add('fiz', 'bar/baz', 'head');

        $this->assertArrayHasKey('fiz',  $theme->scripts()->getAssets());
    }
}
