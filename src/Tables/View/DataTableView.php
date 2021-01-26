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

namespace Gibbon\Tables\View;

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
        $this->addData('blankSlate', $table->getMetaData('blankSlate'));
        $this->addData('draggable', $table->getMetaData('draggable'));

        if ($dataSet->count() > 0) {
            $this->preProcessTable($table);
        }

        $this->addData([
            'headers'    => $this->getTableHeaders($table),
            'columns'    => $table->getColumns(),
            'rows'       => $this->getTableRows($table, $dataSet),
        ]);

        return $this->render('components/dataTable.twig.html');
    }

    /**
     * If a table doesn't have pre-defined context, apply some initial contexts.
     * In most cases, the first few columns in a table represent the primary data.
     *
     * @param DataTable $table
     */
    protected function preProcessTable(DataTable $table)
    {
        $contextColumns = array_filter($table->getColumns(), function ($column) {
            return $column->hasContext('primary');
        });

        if (count($contextColumns) == 0) {
            for ($i = 0; $i <= 2; $i++) {
                if ($column = $table->getColumnByIndex($i)) {
                    if ($column->hasContext('action')) continue;

                    $column->context($i < 2 ? 'primary' : 'secondary');
                }
            }
        }
    }

    /**
     * Returns an array of header objects, accounting for nested columns.
     *
     * @param DataTable $table
     * @return array
     */
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

    /**
     * Returns an array of row objects for the data in this <table class=""></table>
     *
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return array
     */
    protected function getTableRows(DataTable $table, DataSet $dataSet)
    {
        $rows = [];
        $count = 0;

        foreach ($dataSet as $data) {
            $row = $this->createTableRow($data, $table);
            if (!$row) continue; // Can be removed by rowLogic
            
            $row->addClass($count % 2 == 0? 'odd' : 'even');

            $cells = [];

            // CELLS
            foreach ($table->getColumns() as $columnIndex => $column) {
                $cell = $this->createTableCell($data, $table, $column);
                if (!$cell) continue; // Can be removed by cellLogic

                $cells[$columnIndex] = $cell;
            }

            $rows[$count] = ['data' => $data, 'row' => $row, 'cells' => $cells];
            $count++;
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
           ->setClass('column sticky top-0 z-10 '.$column->getClass())
           ->addData('description', $column->getDescription());

        $this->applyContexts($column, $th);

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
            if (!$row) break;
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
        $cell->addClass('p-2 sm:p-3 '.$column->getClass());
        $this->applyContexts($column, $cell);

        foreach ($column->getCellModifiers() as $callable) {
            $cell = $callable($data, $cell, $table->getColumnCount());
        }
        
        return $cell;
    }

    /**
     * Adds classes to a table element based on it's column's context.
     * 
     * @param Column $column
     * @param Element $element
     */
    protected function applyContexts(Column $column, Element &$element)
    {
        if ($column->hasContext('secondary')) {
            $element->addClass('hidden sm:table-cell');
        } elseif (!$column->hasContext('primary') && !$column->hasContext('action')) {
            $element->addClass('hidden lg:table-cell');
        }
    }
}
