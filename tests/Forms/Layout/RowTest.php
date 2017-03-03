<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Forms\Layout;

use PHPUnit\Framework\TestCase;
use Gibbon\Forms\FormFactory;

/**
 * @covers Row
 */
class RowTest extends TestCase
{
    private $mockFactory;
    private $mockElement;

    public function setUp()
    {
        $this->mockFactory = $this->createMock('Gibbon\Forms\FormFactoryInterface');
        $this->mockElement = $this->createMock('Gibbon\Forms\OutputableInterface');
    }

    public function testCanAddElement()
    {
        $row = new Row($this->mockFactory, 'testID');
        $row->addElement($this->mockElement);

        $this->assertTrue(count($row->getElements()) > 0);
    }

    public function testCanAddContent()
    {
        $factory = FormFactory::create();
        $row = new Row($factory, 'testID');
        $row->addContent('Testing');

        $this->assertEquals($row->getElement()->getOutput(), 'Testing');
    }

    public function testCanHandleUnknownElements()
    {
        $factory = FormFactory::create();
        $row = new Row($factory, 'testID');

        $element = $row->addCompletelyBogusElement();

        $this->assertTrue(count($row->getElements()) > 0);
    }

    public function testCanGetElement()
    {
        $row = new Row($this->mockFactory, 'testID');
        $row->addElement($this->mockElement);

        $this->assertSame($this->mockElement, $row->getElement());
    }

    public function testCanGetElements()
    {
        $row = new Row($this->mockFactory, 'testID');

        $element1 = $row->addElement($this->mockElement);
        $element2 = $row->addElement($this->mockElement);

        $elements = $row->getElements();

        $this->assertEquals(count($elements), 2);

        $this->assertSame(reset($elements), $element1);
        $this->assertSame(next($elements), $element2);
    }

    public function testCanCheckIfObjectIsLastElement()
    {
        $row = new Row($this->mockFactory, 'testID');

        $element1 = $row->addElement(new Element('Foo'));
        $element2 = $row->addElement(new Element('Bar'));

        $this->assertNotTrue($row->isLastElement($element1));
        $this->assertTrue($row->isLastElement($element2));
    }
}
