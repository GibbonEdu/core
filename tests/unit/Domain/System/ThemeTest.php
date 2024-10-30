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
use Gibbon\Domain\System\Theme;

/**
 * @covers Theme
 */
class ThemeTest extends TestCase
{
    protected $theme;

    public function setUp(): void
    {
        $this->theme = $this->getMockBuilder(Theme::class)
            ->setConstructorArgs([])
            ->getMockForAbstractClass();
    }

    public function testCanAddStylesheets()
    {
        $this->theme->stylesheets->add('foo', 'bar/baz');

        $this->assertArrayHasKey('foo',  $this->theme->stylesheets->getAssets());
    }

    public function testCanAddScripts()
    {
        $this->theme->scripts->add('fiz', 'bar/baz');

        $this->assertArrayHasKey('fiz',  $this->theme->scripts->getAssets());
    }
}
