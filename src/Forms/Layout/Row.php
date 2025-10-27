<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Forms\Traits\FormFieldsTrait;

/**
 * Holds a collection of form elements to be output horizontally.
 *
 * @version v30
 * @since   v14
 * 
 * {@inheritDoc}
 */
class Row
{
    use BasicAttributesTrait;
    use FormFieldsTrait;

    protected $factory;
    protected $heading;

    protected $elements = [];

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

    public function getCurrentRow()
    {
        return $this;
    }

    public function getHeading()
    {
        return $this->heading;
    }

    public function setHeading($heading)
    {
        if (empty($heading) && !empty($this->heading)) return $this;

        $this->heading = $heading;

        return $this;
    }

    public function setLabel(string $id, string $label, string $description = '')
    {
        $label = $this->factory->createLabel($id, $label)->description($description);
        $label->setRow($this);
        array_unshift($this->elements, $label);
        
        return $this;
    }

    /**
     * Allows a conditional to be chained into the form row elements, rather than wrapping the whole section in an if statement.
     * @param bool $conditional
     * @return object OutputableInterface
     */
    public function onlyIf($conditional)
    {
        return $conditional? $this : new NullElement();
    }

    public function advancedOptions()
    {
        return $this
            ->setAttribute('x-cloak')
            ->setAttribute('x-show', 'advancedOptions')
            ->setAttribute('x-transition.duration.200ms');
    }


    /**
     * Adds an outputtable element to the row's internal collection.
     * @param  OutputableInterface  $element
     */
    public function addElement(OutputableInterface $element)
    {
        $id = $this->getUniqueIdentifier($element);

        if ($element instanceof RowDependancyInterface) {
            $element->setRow($this); 
        }

        $this->elements[$id] = $element;
        return $element;
    }

    /**
     * Get a row element by ID
     * @param   string  $id
     * @return  object Element
     */
    public function getElement($id = '')
    {
        if (empty($this->elements) || count($this->elements) == 0) {
            return null;
        }
        return (isset($this->elements[$id]))? $this->elements[$id] : null;
    }

    /**
     * Get an array of all row elements.
     * @return  array
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Count the elements array.
     * @return  int
     */
    public function getElementCount()
    {
        return count($this->elements);
    }

    /**
     * Determine of the supplied Eelement object is the last element in the collection.
     * @param   object  $element
     * @return  bool
     */
    public function isLastElement($element)
    {
        return (end($this->elements) === $element);
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
    public function loadState($method, $data, $extract = true)
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
     * @return string
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
