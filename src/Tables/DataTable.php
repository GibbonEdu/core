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

use Gibbon\Tables\Column;
use Gibbon\Tables\Action;
use Gibbon\Tables\DataFilters;

/**
 * DataTable
 *
 * @version v16
 * @since   v16
 */
class DataTable
{
    protected $id;
    protected $columns = array();
    protected $actions = array();
    protected $filters;

    protected $dataSet;

    public function __construct($id, $path)
    {
        $this->id = $id;
        $this->path = $path;
    }

    public static function create($id, $path = '')
    {
        $table = new DataTable($id, $path);

        return $table;
    }

    public function withFilters(DataFilters $filters)
    {
        $this->filters = $filters;

        return $this;
    }

    public function fromDataSet(DataSet $dataSet)
    {
        $this->dataSet = $dataSet;

        return $this;
    }


    public function addColumn($name, $label = '')
    {
        $column = new Column($name, $label);
        $this->columns[$name] = $column;

        return $column;
    }

    public function addAction($name, $label = '')
    {
        $action = new Action($name, $label);
        $this->actions[$name] = $action;

        return $action;
    }

    public function getOutput()
    {
        $output = '';

        if ($this->dataSet->count() == 0) {
            $output .= '<div class="error">';
            $output .= __('There are no records to display.');
            $output .= '</div>';
            return $output;
        }

        $filters = $this->dataSet->getFilters();

        $output .= '<div id="'.$this->id.'">';
        $output .= '<div class="dataTable">';

        $output .= $this->getPageCount($filters);
        $output .= $this->getPagination($filters);

        $output .= '<table class="fullWidth colorOddEven" cellspacing="0">';

        // HEADING
        $output .= '<thead>';
        $output .= '<tr class="head">';
        foreach ($this->columns as $columnName => $column) {
            $classes = array('column');

            if ($filters->sort == $columnName) {
                $classes[] = 'sorting sort'.$filters->direction;
            }
            $output .= '<th class="'.implode(' ', $classes).'" data-column="'.$columnName.'">'.$column->getLabel().'</th>';
        }
        if (!empty($this->actions)) {
            $output .= '<th style="width:'.(count($this->actions) * 36).'px;">'.__('Actions').'</th>';
        }
        $output .= '</tr>';
        $output .= '</thead>';

        // ROWS
        $output .= '<tbody>';
        foreach ($this->dataSet->getData() as $data) {
            $output .= '<tr>';

            if (!empty($this->columns)) {
                foreach ($this->columns as $columnName => $column) {
                    $output .= '<td style="width:'.$column->getWidth().'">'.$column->getContents($data).'</td>';
                }
            }

            if (!empty($this->actions)) {
                $output .= '<td>';
                foreach ($this->actions as $actionName => $action) {
                    $output .= $action->getContents($data);
                }
                $output .= '</td>';
            }

            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';

        $output .= $this->getPagination($filters);
        $output .= $this->getPageLimit($filters);

        $output .= '</div></div><br/>';

        // Initialize the jQuery Data Table functionality
        $output .="
        <script>
        $(function(){
            $('#".$this->id."').gibbonDataTable('".str_replace(' ', '%20', $this->path)."', ".$filters->toJson().");
        });
        </script>";

        return $output;
    }

    protected function getPageCount($filters)
    {
        $from = max(1, $filters->page * $filters->limit);
        $to = max(1, min( (($filters->page + 1) * $filters->limit), $filters->totalRows));

        $output = '<span class="small" style="line-height: 30px;">';
        $output .= __('Records').' '.$from.'-'.$to.' '.__('of').' '.$filters->totalRows;
        $output .= '</span>';
        return $output;
    }

    protected function getPageLimit($filters)
    {
        $output = '<div><select class="limit floatNone noMargin" style="width:50px;">';
            $output .= '<option value="10" '.($filters->limit == 10? 'selected' : '').'>10</option>';
            $output .= '<option value="25" '.($filters->limit == 25? 'selected' : '').'>25</option>';
            $output .= '<option value="50" '.($filters->limit == 50? 'selected' : '').'>50</option>';
            $output .= '<option value="100" '.($filters->limit == 100? 'selected' : '').'>100</option>';
        $output .= '</select>  <small style="line-height: 30px;">Per Page</small></div>';

        return $output;
    }

    protected function getPagination($filters)
    {
        if ($filters->pageMax == 0) return '';

        $output = '<div class="floatRight">';
            // $output .= '<input type="button" class="paginate" data-page="0" value="'.__('First').'">';
            $output .= '<input type="button" class="paginate" data-page="'.($filters->page - 1).'" '.($filters->page <= 0? 'disabled' : '').' value="'.__('Prev').'">';

            $range = range(0, $filters->pageMax);

            if ($filters->pageMax > 7 && $filters->page > 5) {
                array_splice($range, 2, $filters->page - 4, '...');
            }

            if ($filters->pageMax > 7 && ($filters->pageMax - $filters->page) > 5) {
                array_splice($range, ($filters->pageMax - $filters->page - 2)*-1, ($filters->pageMax - $filters->page)-4, '...');
            }

            foreach ($range as $page) {
                if ($page === '...') {
                    $output .= '<input type="button" disabled value="...">';
                } else {
                    $class = ($page == $filters->page)? 'active paginate' : 'paginate';
                    $output .= '<input type="button" class="'.$class.'" data-page="'.$page.'" value="'.($page + 1).'">';
                }
            }

            $output .= '<input type="button" class="paginate" data-page="'.($filters->page + 1).'" '.($filters->page >= $filters->pageMax? 'disabled' : '').' value="'.__('Next').'">';
            // $output .= '<input type="button" class="paginate" data-page="'.($filters->pageMax-1).'" value="'.__('Last').'">';
        $output .= '</div>';

        return $output;
    }
}