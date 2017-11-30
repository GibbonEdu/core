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
 * Column
 *
 * @version v14
 * @since   v14
 */
class Table implements OutputableInterface, ValidatableInterface
{
    use BasicAttributesTrait;

    protected $factory;

    protected $headers = array();
    protected $rows = array();

    /**
     * Create an element that holds an internal collection of rows and optional header.
     * @param  FormFactoryInterface  $factory
     * @param  string                $id
     */
    public function __construct(FormFactoryInterface $factory, $id = '')
    {
        $this->factory = $factory;
        $this->setID($id);
        $this->setClass('fullWidth formTable');
    }

    /**
     * Add a header to the internal collection and return the resulting Row object.
     * @param  string  $id
     * @return object  Row
     */
    public function addHeaderRow($id = '')
    {
        $row = $this->factory->createRow($id);
        $this->headers[] = $row;

        return $row;
    }

    /**
     * Add a row to the internal collection and return the resulting object.
     * @param  string  $id
     * @return object  Row
     */
    public function addRow($id = '')
    {
        $row = $this->factory->createRow($id);
        $this->rows[] = $row;

        return $row;
    }

    /**
     * Get all rows defined as headers.
     * @return  array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get all rows in the table.
     * @return  array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Get the HTML output of the table element. Iterate over headers and rows to build a table.
     * @return  string
     */
    public function getOutput()
    {
        $output = '';

        $totalColumns = $this->getColumnCount();

        $output .= '<table '.$this->getAttributeString().' cellspacing="0">';

        // Output table headers
        $output .= '<thead>';
        foreach ($this->getHeaders() as $row) {
            $output .= '<tr '.$row->getAttributeString().'>';

            // Output each element inside the row
            foreach ($row->getElements() as $element) {
                $output .= '<th class="'.$element->getClass().'">';
                    $output .= $element->getOutput();
                $output .= '</th>';
            }
            $output .= '</tr>';
        }
        $output .= '</thead>';

        // Output table rows
        $output .= '<tbody>';
        foreach ($this->getRows() as $row) {
            $output .= '<tr '.$row->getAttributeString().'>';

            // Output each element inside the row
            foreach ($row->getElements() as $element) {
                $output .= '<td class="'.$element->getClass().'">';
                    $element->removeClass('standardWidth');
                    $output .= $element->getOutput();
                $output .= '</td>';
            }
            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';

        return $output;
    }

    /**
     * Get the minimum columns required to render this table.
     * @return  int
     */
    protected function getColumnCount()
    {
        $count = 0;
        foreach ($this->getRows() as $row) {
            if ($row->getElementCount() > $count) {
                $count = $row->getElementCount();
            }
        }

        return $count;
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

        foreach ($this->getRows() as $row) {
            foreach ($row->getElements() as $element) {
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
        foreach ($this->getRows() as $row) {
            $row->loadFrom($data);
        }

        return $this;
    }
}
