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

use Gibbon\Domain\QueryResult;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\ActionColumn;
use Gibbon\Tables\Renderer\RendererInterface;
/**
 * SimpleRenderer
 *
 * @version v16
 * @since   v16
 */
class SimpleRenderer implements RendererInterface
{
    public function renderTable(DataTable $table, QueryResult $queryResult)
    {
        $output = '';

        if ($queryResult->count() > 0) {
            $output .= '<table class="fullWidth colorOddEven" cellspacing="0">';

            // HEADING
            $output .= '<thead>';
            $output .= '<tr class="head">';
            foreach ($table->getColumns() as $columnName => $column) {
                $classes = array('column');
                $style = array('width:' . $column->getWidth());

                if ($column->getSortable()) {
                    $classes[] = 'sortable';
                }

                if (isset($criteria->sortBy[$columnName])) {
                    $classes[] = 'sorting sort'.$criteria->sortBy[$columnName];
                }

                if ($column instanceOf ActionColumn) {
                    $style[] = 'min-width: '.$column->getWidth();
                }
                $output .= '<th style="'.implode('; ', $style).'" class="'.implode(' ', $classes).'" data-column="'.$columnName.'">';
                $output .=  $column->getLabel();
                $output .= '</th>';
            }
            $output .= '</tr>';
            $output .= '</thead>';

            // ROWS
            $output .= '<tbody>';

            foreach ($queryResult as $data) {
                $output .= '<tr>';

                foreach ($table->getColumns() as $columnName => $column) {
                    $output .= '<td>';
                    $output .= $column->getOutput($data);
                    $output .= '</td>';
                }

                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
        } else {
            if ($queryResult->isSubset()) {
                $output .= '<div class="warning">';
                $output .= __('No results matched your search.');
                $output .= '</div>';
            } else {
                $output .= '<div class="error">';
                $output .= __('There are no records to display.');
                $output .= '</div>';
            }
        }

        return $output;
    }
}