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

use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\Columns\Column;
use Gibbon\Tables\Renderer\RendererInterface;
use Gibbon\Forms\Traits\BasicAttributesTrait;
use Gibbon\Forms\Layout\Element;

/**
 * SimpleRenderer
 *
 * @version v16
 * @since   v16
 */
class SimpleRenderer implements RendererInterface
{
    use BasicAttributesTrait;

    /**
     * A callback to render string display if there is no result
     * in the DataSet.
     *
     * The callback should have the same function siguature as
     * the `SimpleRenderer::renderNoResult` method.
     *
     * @var callable|null
     */
    protected $noResultRenderer = null;

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

        if ($title = $table->getTitle()) {
            $output .= '<h2>'.$title.'</h2>';
        }

        if ($description = $table->getDescription()) {
            $output .= '<p>'.$description.'</p>';
        }

        $output .= '<header style="position:relative">';
        $output .= $this->renderHeader($table, $dataSet);
        $output .= '</header>';

        if ($dataSet->count() == 0) {
            $output .= $this->renderNoResult($table, $dataSet);
        } else {
            $this->addClass('fullWidth');

            $output .= '<table '.$this->getAttributeString().' cellspacing="0">';

            // HEADER
            $output .= '<thead>';
            
            $totalColumnDepth = $table->getTotalColumnDepth();

            for ($i = 0; $i < $totalColumnDepth; $i++) {
                $output .= '<tr class="head">';

                foreach ($table->getColumns($i) as $columnName => $column) {
                    $th = $this->createTableHeader($column);

                    if (!$th) continue; // Can be removed by tableHeader logic

                    // Calculate colspan and rowspan to handle nested column headers
                    $colspan = $column->getTotalSpan();
                    $rowspan = ($column->getTotalDepth() > 1) ? 1 : ($totalColumnDepth - $column->getDepth()) ;

                    if ($column->getDepth() < $i) continue;

                    $output .= '<th '.$th->getAttributeString().' style="width:'.$column->getWidth().'" colspan="'.$colspan.'" rowspan="'.$rowspan.'">';
                    $output .= $th->getOutput();
                    $output .= '</th>';
                }

                $output .= '</tr>';
            }
            
            $output .= '</thead>';

            // ROWS
            $output .= '<tbody>';

            foreach ($dataSet as $index => $data) {
                $row = $this->createTableRow($data, $table);
                if (!$row) continue; // Can be removed by rowLogic
                
                $row->addClass($index % 2 == 0? 'odd' : 'even');

                $output .= '<tr '.$row->getAttributeString().'>';
                $output .= $row->getPrepended();

                // CELLS
                foreach ($table->getColumns() as $columnName => $column) {
                    $cell = $this->createTableCell($data, $table, $column);

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

        $output .= '<footer>';
        $output .= $this->renderFooter($table, $dataSet);
        $output .= '</footer>';

        return $output;
    }

    /**
     * Set a renderer callback to render if there is no result.
     * The callback should have the same function siguature as
     * the `SimpleRenderer::renderNoResult` method.
     *
     * @param callable $callback
     * @return this
     */
    public function setNoResultRenderer(callable $callback)
    {
        $this->noResultRenderer = $callback;
        return $this;
    }

    /**
     * Render the no result output.
     *
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return void
     */
    protected function renderNoResult(DataTable $table, DataSet $dataSet)
    {
        if ($this->noResultRenderer !== null) {
            // use the overriding $this->noResultRenderer callback
            return call_user_func($this->noResultRenderer, $table, $dataSet);
        }

        if ($dataSet->isSubset() && $dataSet->getPageSize() > 0) {
            // if this is a page overload
            return '<div class="warning">' .
                __('No results matched your search.') .
                '</div>';
        }

        // if there is actually no result
        return '<div class="error">' .
            __('There are no records to display.') .
            '</div>';
    }

    /**
     * Render a pre-table header section. Defaults to any header actions added to the table.
     *
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return string
     */
    protected function renderHeader(DataTable $table, DataSet $dataSet)
    {
        $output = '';

        if ($headerActions = $table->getHeader()) {
            $output .= '<div class="linkTop column inline">';
            foreach ($headerActions as $header) {
                $output .= $header->getOutput();
            }
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Render a post-table footer section.
     *
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return string
     */
    protected function renderFooter(DataTable $table, DataSet $dataSet)
    {
        return '';
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

        return $cell;
    }
}
