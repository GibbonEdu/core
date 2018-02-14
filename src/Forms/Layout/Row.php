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
use Gibbon\Forms\RowDependancyInterface;
use Gibbon\Forms\Traits\BasicAttributesTrait;

/**
 * Holds a collection of form elements to be output horizontally.
 *
 * @version v14
 * @since   v14
 */
class Row
{
    use BasicAttributesTrait;

    protected $factory;
    protected $formElements = array();

    /**
     * Construct a row with access to a specific factory.
     * @param  FormFactoryInterface  $factory
     * @param  string                $id
     */
    public function __construct(FormFactoryInterface $factory, $id = '')
    {
        $this->factory = $factory;
        $this->setID($id);
    }

    /**
     * Invoke factory method for creating elements when an "add" method is called on this row.
     * @param   string  $function
     * @param   array   $args
     * @return  object  Element
     */
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

    public function if($conditional)
    {
        return $conditional? $this : new NullElement();
    }

    /**
     * Adds an outputtable element to the row's internal collection.
     * @param  OutputableInterface  $element
     */
    public function addElement(OutputableInterface $element)
    {
        $id = $this->getUniqueIdentifier($element);

        $this->formElements[$id] = $element;
        return $element;
    }

    /**
     * Get a row element by ID
     * @param   string  $id
     * @return  object Element
     */
    public function getElement($id = '')
    {
        if (empty($this->formElements) || count($this->formElements) == 0) {
            return null;
        }
        return (isset($this->formElements[$id]))? $this->formElements[$id] : null;
    }

    /**
     * Get an array of all row elements.
     * @return  array
     */
    public function getElements()
    {
        return $this->formElements;
    }

    /**
     * Count the elements array.
     * @return  int
     */
    public function getElementCount()
    {
        return count($this->formElements);
    }

    /**
     * Determine of the supplied Eelement object is the last element in the collection.
     * @param   object  $element
     * @return  bool
     */
    public function isLastElement($element)
    {
        return (end($this->formElements) == $element);
    }

    /**
     * Pass an array of $key => $value pairs into each element in the collection.
     * @param   array  &$data
     * @return  self
     */
    public function loadFrom(&$data)
    {
        foreach ($this->getElements() as $element) {
            if (method_exists($element, 'loadFrom')) {
                $element->loadFrom($data);
            }
        }

        return $this;
    }

    /**
     * Load the state of several fields at once by calling $method on each element present in $data by key, passing in the value of $data.
     * @param string $method
     * @param array $data
     * @return self
     */
    public function loadState($method, &$data, $extract = true)
    {
        foreach ($this->getElements() as $element) {
            $name = $this->getUniqueIdentifier($element);

            if (isset($data[$name]) && method_exists($element, $method)) {
                $element->$method($data[$name]);
            }
        }

        return $this;
    }

    /**
     * Gets the string identifier for an element that can be used as an array key.
     * @param object $element
     * @return void
     */
    protected function getUniqueIdentifier($element)
    {
        if (method_exists($element, 'getID') && !empty($element->getID())) {
            return $element->getID();
        }
        
        if (method_exists($element, 'getName') && !empty($element->getName())) {
            return $element->getName();
        }
        
        return 'element-'.$this->getElementCount();
    }
}
