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
 * @covers Element
 */
class ElementTest extends TestCase
{
    public function testCanOutputContent()
    {
        $element = new Element('Testing');

        $this->assertEquals('Testing', $element->getOutput());
    }

    public function testCanAppendContent()
    {
        $element = new Element('Testing');
        $element->append('Appended');

        $this->assertEquals('TestingAppended', $element->getOutput());
    }

    public function testCanPrependContent()
    {
        $element = new Element('Testing');
        $element->prepend('Prepended');

        $this->assertEquals('PrependedTesting', $element->getOutput());
    }

    public function testCanAppendAndPrependContent()
    {
        $element = new Element('Testing');
        $element->append('Appended');
        $element->prepend('Prepended');

        $this->assertEquals('PrependedTestingAppended', $element->getOutput());
    }
}
