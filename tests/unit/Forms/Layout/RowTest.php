<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Forms\Layout;

use PHPUnit\Framework\TestCase;
use Gibbon\Forms\FormFactory;
use Gibbon\Forms\OutputableInterface;

/**
 * @covers Row
 */
class RowTest extends TestCase
{
    private $mockFactory;
    private $mockElement;

    public function setUp(): void
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
        $element = $row->addContent('Testing');

        $this->assertEquals($element->getOutput(), 'Testing');
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
        $factory = FormFactory::create();
        $row = new Row($factory, 'testID');
        $element = $row->addTextField('testElement');

        $this->assertSame($element, $row->getElement('testElement'));
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
