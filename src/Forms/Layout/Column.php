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
use Gibbon\Forms\ValidatableInterface;
use Gibbon\Forms\FormFactoryInterface;

/**
 * Holds a collection of form elements to be output vertically.
 *
 * @version v14
 * @since   v14
 */
class Column extends Row implements OutputableInterface, ValidatableInterface
{
    protected $class = 'column';

    /**
     * Construct a column with access to a specific factory.
     * @param  FormFactoryInterface  $factory
     * @param  string                $id
     */
    public function __construct(FormFactoryInterface $factory, $id = '')
    {
        $this->setClass('column');
        parent::__construct($factory, $id);
    }

    /**
     * Gets the required attribute of the internal element matching the column's ID.
     * @return  bool
     */
    public function getRequired()
    {
        $primaryElement = $this->getElement($this->getID());
        return (!empty($primaryElement))? $primaryElement->getRequired() : false;
    }

    public function getLabelContext($label)
    {
        $primaryElement = $this->getElement($this->getID());
        return (!empty($primaryElement) && !empty($label) && method_exists($primaryElement, 'getLabelContext'))? $primaryElement->getLabelContext($label) : false;
    }

    /**
     * Iterate over each element in the collection and concatenate the output.
     * @return  string
     */
    public function getOutput()
    {
        $output = '';

        foreach ($this->getElements() as $element) {
            $output .= '<div class="'.$this->getContainerClass($element).'">';
            $output .= $element->getOutput();
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Dead-end stub for interface: columns cannot validate.
     * @param   string  $name
     * @return  self
     */
    public function addValidation($name)
    {
        return $this;
    }

    /**
     * Iterate over each element in the collection and get the combined validation output.
     * @return  string
     */
    public function getValidationOutput()
    {
        $output = '';

        foreach ($this->getElements() as $element) {
            if ($element instanceof ValidatableInterface) {
                $output .= $element->getValidationOutput();
            }
        }

        return $output;
    }

    /**
     * Gets the classname for the div container inside the column.
     * @param Element $element
     * @return string
     */
    protected function getContainerClass($element)
    {
        return str_replace('standardWidth', '', $element->getClass());
    }
}
