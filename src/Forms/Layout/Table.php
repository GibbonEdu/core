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
 * Column
 *
 * @version v14
 * @since   v14
 */
class Table implements OutputableInterface
{
    use BasicAttributesTrait;

    protected $factory;

    protected $headers = array();
    protected $rows = array();

    public function __construct(FormFactoryInterface $factory, $id = '')
    {
        $this->factory = $factory;
        $this->setID($id);
        $this->setClass('fullWidth formTable');
    }

    public function addHeaderRow($id = '')
    {
        $row = $this->factory->createRow($id);
        $this->headers[] = $row;

        return $row;
    }

    public function addRow($id = '')
    {
        $row = $this->factory->createRow($id);
        $this->rows[] = $row;

        return $row;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getRows()
    {
        return $this->rows;
    }

    public function getOutput()
    {
        $output = '';

        $totalColumns = $this->getColumnCount();

        $output .= '<table '.$this->getAttributeString().' cellspacing="0">';

        // Output table headers
        foreach ($this->getHeaders() as $row) {
            $output .= '<tr '.$row->getAttributeString().'>';

            // Output each element inside the row
            foreach ($row->getElements() as $element) {
                $output .= '<th>';
                    $output .= $element->getOutput();
                $output .= '</th>';
            }
            $output .= '</tr>';
        }

        // Output table rows
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

        $output .= '</table>';

        return $output;
    }

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
}
