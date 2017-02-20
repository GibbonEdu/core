<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Forms\Layout;

use PHPUnit\Framework\TestCase;

/**
 * @covers Row
 */
class RowTest extends TestCase
{
    public function testCanAddElement()
    {
        $factory = $this->createMock('Gibbon\Forms\FormFactoryInterface');
        $row = new Row($factory, 'testID');

        $element = $this->createMock('Gibbon\Forms\OutputableInterface');
        $row->addElement($element);

        $this->assertTrue(count($row->getElements()) > 0);
        $this->assertSame($element, $row->getElement());
    }
}
