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

use Gibbon\View\View;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Layout\Element;
use Gibbon\Tables\Columns\Column;
use Gibbon\Forms\Layout\TableCell;
use Gibbon\Tables\Renderer\RendererInterface;

/**
 * TableView
 *
 * @version v18
 * @since   v18
 */
class DataTableView extends View implements RendererInterface
{
    /**
     * Render the table to HTML.
     *
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return string
     */
    public function renderTable(DataTable $table, DataSet $dataSet)
    {
        $this->addData('table', $table);

        if ($dataSet->count() > 0) {
            $this->addData('headers', $this->getTableHeaders($table));
            $this->addData('columns', $table->getColumns());
            $this->addData('rows', $this->getTableRows($table, $dataSet));
        }

        return $this->render('components/dataTable.twig.html');
    }

    protected function getTableHeaders(DataTable $table)
    {
        $headers = [];

        $totalColumnDepth = $table->getTotalColumnDepth();

        for ($i = 0; $i < $totalColumnDepth; $i++) {
            foreach ($table->getColumns($i) as $columnIndex => $column) {
                $th = $this->createTableHeader($column);

                if (!$th) continue; // Can be removed by tableHeader logic
                if ($column->getDepth() < $i) continue;

                // Calculate colspan and rowspan to handle nested column headers
                $th->colSpan($column->getTotalSpan());
                $th->rowSpan($column->getTotalDepth() > 1 ? 1 : ($totalColumnDepth - $column->getDepth()));

                $headers[$i][$columnIndex] = $th;
            }
        }
        
        return $headers;
    }

    protected function getTableRows(DataTable $table, DataSet $dataSet)
    {
        $rows = [];

        foreach ($dataSet as $index => $data) {
            $row = $this->createTableRow($data, $table);
            if (!$row) continue; // Can be removed by rowLogic
            
            $row->addClass($index % 2 == 0? 'odd' : 'even');

            $cells = [];

            // CELLS
            foreach ($table->getColumns() as $columnIndex => $column) {
                $cell = $this->createTableCell($data, $table, $column);
                if (!$cell) continue; // Can be removed by cellLogic

                $cells[$columnIndex] = $cell;
            }

            $rows[$index] = ['data' => $data, 'row' => $row, 'cells' => $cells];
        }

        return $rows;
    }

    /**
     * Creates the HTML object for the <th> tag.
     * 
     * @param Column $column
     * @return Element
     */
    protected function createTableHeader(Column $column)
    {
        $th = new TableCell($column->getLabel());

        $th->setTitle($column->getTitle())
           ->setClass('column')
           ->addClass($column->getClass())
           ->addData('description', $column->getDescription());

        return $th;
    }

    /**
     * Creates the HTML object for the <tr> tag, applies optional rowLogic callable to modify the output.
     * 
     * @param DataTable $table
     * @return Element
     */
    protected function createTableRow(array $data, DataTable $table)
    {
        $row = new Element();

        foreach ($table->getRowModifiers() as $callable) {
            $row = $callable($data, $row, $table->getColumnCount());
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
    protected function createTableCell(array $data, DataTable $table, Column $column)
    {
        $cell = new Element();

        foreach ($column->getCellModifiers() as $callable) {
            $cell = $callable($data, $cell, $table->getColumnCount());
        }

        $cell->addClass($column->getClass());

        return $cell;
    }
}
