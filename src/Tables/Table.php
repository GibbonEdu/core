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

namespace Gibbon\Tables;

use Gibbon\Tables\TableColumn;
use Gibbon\Tables\TableAction;

/**
 * Table
 *
 * @version v16
 * @since   v16
 */
class Table
{
    protected $columns = array();
    protected $actions = array();

    public function __construct()
    {

    }

    public static function create()
    {
        $table = new Table();

        return $table;
    }

    public function addColumn($name, $label = '')
    {
        $column = new TableColumn($name, $label);
        $this->columns[$name] = $column;

        return $column;
    }

    public function addAction($name, $label = '')
    {
        $action = new TableAction($name, $label);
        $this->actions[$name] = $action;

        return $action;
    }

    public function getOutput($dataSet)
    {
        $output = '';

        if (count($dataSet) == 0) {
            $output .= '<div class="error">';
            $output .= __('There are no records to display.');
            $output .= '</div>';
            return $output;
        }

        $output .= '<table cellspacing="0" class="fullWidth colorOddEven">';

        // HEADING
        $output .= '<tr class="head">';
        foreach ($this->columns as $columnName => $column) {
            $output .= '<th>'.$column->getLabel().'</th>';
        }
        if (!empty($this->actions)) {
            $output .= '<th>'.__('Actions').'</th>';
        }
        $output .= '</tr>';

        // ROWS
        foreach ($dataSet as $data) {
            $output .= '<tr>';

            if (!empty($this->columns)) {
                foreach ($this->columns as $columnName => $column) {
                    $output .= '<td style="width:'.$column->getWidth().'">'.$column->getContents($data).'</td>';
                }
            }

            if (!empty($this->actions)) {
                $output .= '<td style="width:'.(count($this->actions) * 45).'px;">';
                foreach ($this->actions as $actionName => $action) {
                    $output .= $action->getContents($data);
                }
                $output .= '</td>';
            }

            $output .= '</tr>';
        }

        $output .= '</table><br/>';

        return $output;
    }
}