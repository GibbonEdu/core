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
