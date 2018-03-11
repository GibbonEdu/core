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
use Gibbon\Domain\ResultSet;

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

    protected $resultSet;

    public function __construct($id, ResultSet $resultSet)
    {
        $this->id = $id;
        $this->resultSet = $resultSet;
    }

    public static function create($id, ResultSet $resultSet)
    {
        return new DataTable($id, $resultSet);
    }

    public function setPath($path = '')
    {
        $this->path = $path;

        return $this;
    }

    public function addColumn($name, $label = '')
    {
        $this->columns[$name] = new Column($name, $label);

        return $this->columns[$name];
    }

    public function addActionColumn()
    {
        $this->columns['actions'] = new ActionColumn();

        return $this->columns['actions'];
    }

    public function getOutput()
    {
        $output = '';

        if ($this->resultSet->resultCount == 0) {
            $output .= '<div class="error">';
            $output .= __('There are no records to display.');
            $output .= '</div>';
            return $output;
        }

        $output .= '<div id="'.$this->id.'">';
        $output .= '<div class="dataTable">';

        $output .= $this->renderPageCount($this->resultSet);
        $output .= $this->renderPageSize($this->resultSet);
        $output .= $this->renderPagination($this->resultSet);

        $output .= '<table class="fullWidth colorOddEven" cellspacing="0">';

        // HEADING
        $output .= '<thead>';
        $output .= '<tr class="head">';
        foreach ($this->columns as $columnName => $column) {
            $classes = array('column');

            if ($column->getSortable()) {
                $classes[] = 'sortable';
            }
            if (isset($this->resultSet->filters->orderBy[$columnName])) {
                $classes[] = 'sorting sort'.$this->resultSet->filters->orderBy[$columnName];
            }
            $output .= '<th style="width:'.$column->getWidth().'" class="'.implode(' ', $classes).'" data-column="'.$columnName.'">';
            $output .=  $column->getLabel();
            $output .= '</th>';
        }
        $output .= '</tr>';
        $output .= '</thead>';

        // ROWS
        $output .= '<tbody>';
        foreach ($this->resultSet->getData() as $data) {
            $output .= '<tr>';

            if (!empty($this->columns)) {
                foreach ($this->columns as $columnName => $column) {
                    $output .= '<td >';
                    $output .= $column->getContents($data);
                    $output .= '</td>';
                }
            }

            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';

        $output .= $this->renderPageCount($this->resultSet);
        $output .= $this->renderPageSize($this->resultSet);
        $output .= $this->renderPagination($this->resultSet);

        $output .= '</div></div><br/>';

        // Initialize the jQuery Data Table functionality
        $output .="
        <script>
        $(function(){
            $('#".$this->id."').gibbonDataTable('".str_replace(' ', '%20', $this->path)."', ".$this->resultSet->filters->toJson().", ".$this->resultSet->totalCount.");
        });
        </script>";

        return $output;
    }

    protected function renderPageCount(ResultSet $resultSet)
    {
        $output = '<span class="small" style="line-height: 30px;">';
        $output .= __('Records').' '.$resultSet->rowsFrom.'-'.$resultSet->rowsTo.' '.__('of').' '.$resultSet->totalCount;
        $output .= '</span>';

        return $output;
    }

    protected function renderPageSize(ResultSet $resultSet)
    {
        $pageSize = $resultSet->filters->pageSize;
        
        $output = '<span style="padding-left:10px;"><select class="limit floatNone" style="width:50px;height:26px;margin: 2px 0;">';
            $output .= '<option value="10" '.($pageSize == 10? 'selected' : '').'>10</option>';
            $output .= '<option value="25" '.($pageSize == 25? 'selected' : '').'>25</option>';
            $output .= '<option value="50" '.($pageSize == 50? 'selected' : '').'>50</option>';
            $output .= '<option value="100" '.($pageSize == 100? 'selected' : '').'>100</option>';
        $output .= '</select>  <small style="line-height: 30px;">Per Page</small></span>';

        return $output;
    }

    protected function renderPagination(ResultSet $resultSet)
    {
        $filters = $resultSet->filters;

        if ($resultSet->pageCount <= 1) return '';

        $output = '<div class="floatRight">';
            $output .= '<input type="button" class="paginate" data-page="'.($filters->pageIndex - 1).'" '.($filters->pageIndex <= 0? 'disabled' : '').' value="'.__('Prev').'">';

            $range = range(0, $resultSet->pageCount);

            // Collapse the leading page-numbers
            if ($resultSet->pageCount > 7 && $filters->pageIndex > 5) {
                array_splice($range, 2, $filters->pageIndex - 4, '...');
            }

            // Collapse the trailing page-numbers
            if ($resultSet->pageCount > 7 && ($resultSet->pageCount - $filters->pageIndex) > 5) {
                array_splice($range, ($resultSet->pageCount - $filters->pageIndex - 2)*-1, ($resultSet->pageCount - $filters->pageIndex)-4, '...');
            }

            foreach ($range as $page) {
                if ($page === '...') {
                    $output .= '<input type="button" disabled value="...">';
                } else {
                    $class = ($page == $filters->pageIndex)? 'active paginate' : 'paginate';
                    $output .= '<input type="button" class="'.$class.'" data-page="'.$page.'" value="'.($page + 1).'">';
                }
            }

            $output .= '<input type="button" class="paginate" data-page="'.($filters->pageIndex + 1).'" '.($filters->pageIndex >= $resultSet->pageCount? 'disabled' : '').' value="'.__('Next').'">';
        $output .= '</div>';

        return $output;
    }
}