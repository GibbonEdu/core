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
 * Column
 *
 * @version v14
 * @since   v14
 */
class Table implements OutputableInterface, ValidatableInterface
{
    use BasicAttributesTrait;

    /**
     * @var \Gibbon\Forms\FormFactoryInterface
     */
    protected $factory;

    /**
     * @var \Gibbon\Forms\Layout\Row[]
     */
    protected $headers = array();

    /**
     * @var \Gibbon\Forms\Layout\Row[]
     */
    protected $rows = array();

    /**
     * @var int
     */
    protected $totalColumns;

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
    public function getElements()
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

        $this->totalColumns = $this->getColumnCount();

        $output .= '<table '.$this->getAttributeString().' cellspacing="0">';

        // Output table headers
        $output .= '<thead>';
        foreach ($this->getHeaders() as $row) {
            $output .= '<tr '.$row->getAttributeString().'>';

            // Output each element inside the row
            foreach ($row->getElements() as $element) {
                $element->addClass('text-xxs sm:text-xs p-2 sm:py-3');
                $output .= '<th '.$element->getAttributeString('class,title,rowspan,colspan,data').' '.$this->getColspan($row, $element).'>';
                    $output .= $element->getOutput();
                $output .= '</th>';
            }
            $output .= '</tr>';
        }
        $output .= '</thead>';

        // Output table rows
        $output .= '<tbody>';
        foreach ($this->getElements() as $row) {
            $output .= '<tr '.$row->getAttributeString().'>';

            // Output each element inside the row
            foreach ($row->getElements() as $element) {
                $element->removeClass('standardWidth');

                $output .= '<td '.$element->getAttributeString('class,title,rowspan,colspan,data').' '.$this->getColspan($row, $element).'>';
                    if (stripos($this->getClass(), 'formTable') !== false) {
                        $element->setClass('w-full '.$element->getClass());
                    }
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
        foreach ($this->getHeaders() as $row) {
            $count = max($count, $row->getElementCount());
        }

        foreach ($this->getElements() as $row) {
            $count = max($count, $row->getElementCount());
        }

        return $count;
    }

    protected function getColspan($row, $element)
    {
        return $row->isLastElement($element) && $row->getElementCount() < $this->totalColumns
            ? 'colspan="'.($this->totalColumns + 1 - $row->getElementCount()).'"'
            : '';
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

        foreach ($this->getElements() as $row) {
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
        foreach ($this->getElements() as $row) {
            $row->loadFrom($data);
        }

        return $this;
    }
}
