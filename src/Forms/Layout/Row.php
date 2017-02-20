<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Forms\Layout;

use Gibbon\Forms\OutputableInterface;
use Gibbon\Forms\FormFactoryInterface;
use Gibbon\Forms\Traits\BasicAttributesTrait;

/**
 * Row
 *
 * @version v14
 * @since   v14
 */
class Row
{
    use BasicAttributesTrait;

    protected $factory;
    protected $formElements = array();

    public function __construct(FormFactoryInterface $factory, $id = '')
    {
        $this->factory = $factory;
        $this->setID($id);
    }

    public function __call($function, $args)
    {
        if (substr($function, 0, 3) != 'add') {
            return;
        }

        try {
            $function = substr_replace($function, 'create', 0, 3);

            $reflectionMethod = new \ReflectionMethod($this->factory, $function);
            $element = $reflectionMethod->invokeArgs($this->factory, $args);

            if ($element instanceof RowDependancyInterface) {
                $element->setRow($this);
            }
        } catch (\ReflectionException $e) {
            $element = $this->factory->createContent(sprintf('Cannot %1$s. This form element does not exist in the current FormFactory', $function).': '.$e->getMessage());
        } catch (\Exception $e) {
            $element = $this->factory->createContent(sprintf('Cannot %1$s. Error creating form element.', $function).': '.$e->getMessage());
        } finally {
            $this->addElement($element);
        }

        return $element;
    }

    public function addElement(OutputableInterface $element)
    {
        if (method_exists($element, 'getName')) {
            $id = $element->getName();
        } else {
            $id = 'element-'.count($this->formElements);
        }

        $this->formElements[$id] = $element;
        return $element;
    }

    public function getElement($id = '')
    {
        if (empty($this->formElements) || count($this->formElements) == 1) {
            return null;
        }
        return (isset($this->formElements[$id]))? $this->formElements[$id] : end($this->formElements);
    }

    public function getElements()
    {
        return $this->formElements;
    }

    public function getElementCount()
    {
        return count($this->formElements);
    }

    public function isLastElement($element)
    {
        return (end($this->formElements) == $element);
    }
}

interface RowDependancyInterface
{
    public function setRow(Row $row);
}
