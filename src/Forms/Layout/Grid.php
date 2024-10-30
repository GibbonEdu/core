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
use Gibbon\Forms\ValidatableInterface;
use Gibbon\Forms\FormFactoryInterface;
use Gibbon\Forms\Traits\BasicAttributesTrait;

/**
 * Grid
 *
 * @version v15
 * @since   v15
 */
class Grid implements OutputableInterface, ValidatableInterface
{
    use BasicAttributesTrait;

    /**
     * @var \Gibbon\Forms\FormFactoryInterface
     */
    protected $factory;

    /**
     * @var \Gibbon\Forms\Layout\OutputableInterface[]
     */
    protected $elements = array();

    /**
     * @var string
     */
    protected $breakpoints;

    /**
     * Create an element that displays a collection of elements in a flexible grid,
     * @param  FormFactoryInterface  $factory
     * @param  string                $id
     */
    public function __construct(FormFactoryInterface $factory, $id = '', $breakpoints = 'w-1/2 sm:w-1/3')
    {
        $this->factory = $factory;
        $this->setBreakpoints($breakpoints);
        $this->setID($id);
    }

    /**
     * Sets the breakpoints in the grid with css classes, eg: w-1/2 sm:w-1/3
     * @param int $columns
     * @return self
     */
    public function setBreakpoints($breakpoints)
    {
        $this->breakpoints = $breakpoints;

        return $this;
    }

    /**
     * Add a cell to the internal collection and return the resulting object.
     * @param  string  $id
     * @return \Gibbon\Forms\Layout\Column  Column
     */
    public function addCell($id = '')
    {
        $element = $this->factory->createColumn($id);
        $this->elements[] = $element;

        return $element;
    }

    /**
     * Get all cells in the grid.
     * @return  \Gibbon\Forms\Layout\OutputableInterface[]
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Get the HTML output of the element. Iterate over elements to build a grid.
     * @return  string
     */
    public function getOutput()
    {
        $this->setClass('w-full flex flex-wrap items-stretch');

        $output = '<div '.$this->getAttributeString().'>';

        foreach ($this->getElements() as $cell) {
            $cell->addClass($this->breakpoints);

            $output .= '<div '.$cell->getAttributeString().'>';
            $output .= $cell->getOutput();
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Dead-end stub for interface: grids cannot validate.
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

        foreach ($this->getElements() as $cell) {
            foreach ($cell->getElements() as $element) {
                if ($element instanceof ValidatableInterface) {
                    $output .= $element->getValidationOutput();
                }
            }
        }

        return $output;
    }

    /**
     * Pass an array of $key => $value pairs into each element in the collection.
     * @param   array  &$data
     * @return  self
     */
    public function loadFrom(&$data)
    {
        foreach ($this->getElements() as $cell) {
            $cell->loadFrom($data);
        }

        return $this;
    }
}
