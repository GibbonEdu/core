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

namespace Gibbon\Tables\Renderer;

use Gibbon\Tables\Column;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\Renderer\RendererInterface;
use Gibbon\Forms\Layout\Element;

/**
 * SimpleRenderer
 *
 * @version v16
 * @since   v16
 */
class SimpleRenderer implements RendererInterface
{
    /**
     * So simple ...
     */
    public function __construct()
    {
    }

    /**
     * Render the table to HTML. TODO: replace with Twig.
     *
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return string
     */
    public function renderTable(DataTable $table, DataSet $dataSet)
    {
        $output = '';

        if ($dataSet->count() == 0) {
            if ($dataSet->isSubset()) {
                $output .= '<div class="warning">';
                $output .= __('No results matched your search.');
                $output .= '</div>';
            } else {
                $output .= '<div class="error">';
                $output .= __('There are no records to display.');
                $output .= '</div>';
            }
        } else {
            $output .= '<table class="fullWidth colorOddEven" cellspacing="0">';

            // HEADER
            $output .= '<thead>';
            $output .= '<tr class="head">';
            foreach ($table->getColumns() as $columnName => $column) {
                $th = $this->createTableHeader($column);

                $output .= '<th '.$th->getAttributeString().' style="width:'.$column->getWidth().'">';
                $output .= $th->getOutput();
                $output .= '</th>';
            }
            $output .= '</tr>';
            $output .= '</thead>';

            // ROWS
            $output .= '<tbody>';

            $rowLogic = $table->getRowLogic();
            $cellLogic = $table->getCellLogic();

            foreach ($dataSet as $data) {
                $row = $this->createTableRow($table, $data);

                if (!$row) continue; // Can be removed by rowLogic

                $output .= '<tr '.$row->getAttributeString().'>';
                $output .= $row->getPrepended();

                // CELLS
                foreach ($table->getColumns() as $columnName => $column) {
                    $cell = $this->createTableCell($table, $data);

                    if (!$cell) continue; // Can be removed by cellLogic

                    $output .= '<td '.$cell->getAttributeString().'>';
                    $output .= $cell->getPrepended();
                    $output .= $column->getOutput($data);
                    $output .= $cell->getAppended();
                    $output .= '</td>';
                }

                $output .= $row->getAppended();
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
        }

        return $output;
    }

    /**
     * Creates the HTML object for the <th> tag.
     * 
     * @param Column $column
     * @return Element
     */
    protected function createTableHeader(Column $column)
    {
        $th = new Element($column->getLabel());

        $th->setTitle($column->getTitle())
           ->setClass('column');

        if ($description = $column->getDescription()) {
            $th->append('<br/><small><i>'.$column->getDescription().'</i></small>');
        }

        return $th;
    }

    /**
     * Creates the HTML object for the <tr> tag, applies optional rowLogic callable to modify the output.
     * 
     * @param DataTable $table
     * @return Element
     */
    protected function createTableRow(DataTable $table, array $data)
    {
        $row = new Element();
        if ($rowLogic = $table->getRowLogic()) {
            $row = $rowLogic($data, $row);
        }

        return $row;
    }

    /**
     * Creates the HTML object for the <td> tag, applies optional cellLogic callable to modify the output.
     * 
     * @param DataTable $table
     * @param array $data
     * @return Element
     */
    protected function createTableCell(DataTable $table, array $data)
    {
        $cell = new Element();
        if ($cellLogic = $table->getCellLogic()) {
            $cell = $cellLogic($data, $cell);
        }

        return $cell;
    }
}
