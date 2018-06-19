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

    protected $factory;
    protected $elements = array();
    protected $columns; 

    /**
     * Create an element that displays a collection of elements in a flexible grid,
     * @param  FormFactoryInterface  $factory
     * @param  string                $id
     */
    public function __construct(FormFactoryInterface $factory, $id = '', $columns = 1)
    {
        $this->factory = $factory;
        $this->setColumns($columns);
        $this->setID($id);
        $this->setClass('fullWidth grid');
    }

    /**
     * Sets the number of columns wide to render the grid.
     * @param int $columns
     * @return self
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;

        return $this;
    }
    
    /**
     * Add a cell to the internal collection and return the resulting object.
     * @param  string  $id
     * @return object  Column
     */
    public function addCell($id = '')
    {
        $element = $this->factory->createColumn($id);
        $this->elements[] = $element;

        return $element;
    }

    /**
     * Get all cells in the grid.
     * @return  array
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
        $output = '<table '.$this->getAttributeString().' cellspacing="0">';
        $output .= '<tbody>';

        $cellWidth = 100 / $this->columns;
        $emptyCell = $this->factory->createColumn();
        $rows = array_chunk($this->getElements(), $this->columns);
        
        foreach ($rows as $cells) {
            $cells = array_pad($cells, $this->columns, $emptyCell);

            $output .= '<tr>';
            foreach ($cells as $cell) {
                $output .= '<td class="' . $cell->getClass() . '" style="width: '.$cellWidth.'%">';
                $output .= $cell->getOutput();
                $output .= '</td>';
            }
            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';

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
