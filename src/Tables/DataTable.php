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

use Gibbon\Tables\Action;
use Gibbon\Tables\Column;
use Gibbon\Domain\QueryResult;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Forms\FormFactory;

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
    protected $filters = array();
    protected $actionLinks = array();

    protected $queryResult;
    protected $criteria;
    protected $factory;

    public function __construct($id, QueryResult $queryResult)
    {
        $this->id = $id;
        $this->queryResult = $queryResult;
        $this->criteria = $queryResult->getCriteria();
        $this->factory = FormFactory::create();
    }

    public static function createFromQueryResult($id, QueryResult $queryResult)
    {
        return new DataTable($id, $queryResult);
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

    public function addHeaderAction($name, $label = '')
    {
        $this->actionLinks[$name] = new Action($name, $label);

        return $this->actionLinks[$name];
    }

    public function addFilter($name, $label = '')
    {
        $this->filters[$name] = $label;

        return $this;
    }

    public function addFilters($filters)
    {
        $this->filters = array_replace($this->filters, $filters);

        return $this;
    }

    public function getOutput()
    {
        $output = '';

        $output .= '<div class="linkTop">';
        foreach ($this->actionLinks as $action) {
            $output .= $action->getOutput();
        }
        $output .= '</div>';


        $output .= '<div id="'.$this->id.'">';
        $output .= '<div class="dataTable">';

        // Debug the AJAX $POST => Filters
        // $output .= json_encode($_POST).'<br/>';
        // $output .= json_encode($this->criteria->getFilters());

        $output .= '<div>';
        $output .= $this->renderPageCount($this->queryResult);
        $output .= $this->renderPageFilters($this->queryResult);
        $output .= '</div>';
        $output .= $this->renderSelectFilters($this->queryResult);
        $output .= $this->renderPageSize($this->queryResult);
        $output .= $this->renderPagination($this->queryResult);

        if ($this->queryResult->hasResults()) {
            $output .= '<table class="fullWidth colorOddEven" cellspacing="0">';

            // HEADING
            $output .= '<thead>';
            $output .= '<tr class="head">';
            foreach ($this->columns as $columnName => $column) {
                $classes = array('column');
                $style = array('width:' . $column->getWidth());

                if ($column->getSortable()) {
                    $classes[] = 'sortable';
                }
                if (isset($this->criteria->sortBy[$columnName])) {
                    $classes[] = 'sorting sort'.$this->criteria->sortBy[$columnName];
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

            foreach ($this->queryResult as $data) {
                $output .= '<tr>';

                if (!empty($this->columns)) {
                    foreach ($this->columns as $columnName => $column) {
                        $output .= '<td>';
                        $output .= $column->getOutput($data);
                        $output .= '</td>';
                    }
                }

                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';

            $output .= $this->renderPageCount($this->queryResult);
            $output .= $this->renderPagination($this->queryResult);
        } else {
            if ($this->queryResult->isSubset()) {
                $output .= '<div class="warning">';
                $output .= __('No results matched your search.');
                $output .= '</div>';
            } else {
                $output .= '<div class="error">';
                $output .= __('There are no records to display.');
                $output .= '</div>';
            }
        }

        $output .= '</div></div><br/>';

        // Initialize the jQuery Data Table functionality
        $output .="
        <script>
        $(function(){
            $('#".$this->id."').gibbonDataTable('.".str_replace(' ', '%20', $this->path)."', ".json_encode($this->criteria).", ".$this->queryResult->getResultCount().");
        });
        </script>";

        return $output;
    }

    protected function renderPageCount(QueryResult $queryResult)
    {
        $criteria = $queryResult->getCriteria();

        $output = '<span class="small" style="line-height: 32px;margin-right: 10px;">';

        $output .= !empty($criteria['searchBy'])? __('Search').' ' : '';
        $output .= $queryResult->isSubset()? __('Results') : __('Records');
        $output .= $queryResult->hasResults()? ' '.$queryResult->getPageLowerBounds().'-'.$queryResult->getPageUpperBounds().' '.__('of').' ' : ': ';
        $output .= $queryResult->isSubset()? $queryResult->getResultCount() : $queryResult->getTotalCount();

        $output .= '</span>';

        return $output;
    }

    protected function renderPageFilters(QueryResult $queryResult)
    {
        $criteria = $queryResult->getCriteria();

        if (empty($criteria)) return '';

        $output = '<span class="small" style="line-height: 32px;">';

        if (!empty($criteria['filterBy'])) {
            $output .= __('Filtered by').' ';

            // $criteriaUsed = array_intersect_key($this->filters, array_flip($criteria['filterBy']));
            $criteriaUsed = array_filter($this->filters, function($name) use ($criteria) {
                return in_array($name, $criteria['filterBy']);
            }, ARRAY_FILTER_USE_KEY);

            foreach ($criteriaUsed as $value => $label) {
                $output .= '<input type="button" class="filter" value="'.$label.'" data-filter="'.$value.'"> ';
            }

            $output .= '<input type="button" class="filter clear buttonLink" value="'.__('Clear').'">';
        }

        return $output;
    }

    protected function renderSelectFilters(QueryResult $queryResult)
    {
        if (empty($queryResult->getCriteria())) return '';
        if (empty($this->filters)) return '';
        
        return $this->factory->createSelect('filter')
            ->fromArray($this->filters)
            ->setClass('filters floatNone')
            ->placeholder(__('Filters'))
            ->getOutput();
    }

    protected function renderPageSize(QueryResult $queryResult)
    {
        $pageSize = $queryResult->getPageSize();

        if ($pageSize <= 0) return '';

        return $this->factory->createSelect('limit')
            ->fromArray(array(10, 25, 50, 100))
            ->setClass('limit floatNone')
            ->selected($pageSize)
            ->append('<small style="line-height: 30px;margin-left:5px;">'.__('Per Page').'</small>')
            ->getOutput();
    }

    protected function renderPagination(QueryResult $queryResult)
    {
        if ($queryResult->getPageCount() <= 1) return '';

        $pageNumber = $queryResult->getPage();
        $pageIndex = $pageNumber - 1;

        $output = '<div class="floatRight">';
            $output .= '<input type="button" class="paginate" data-page="'.($pageNumber - 1).'" '.($pageNumber <= 1? 'disabled' : '').' value="'.__('Prev').'">';

            $pageCount = $queryResult->getPageCount();
            $range = range(1, $pageCount);

            // Collapse the leading page-numbers
            if ($pageCount > 7 && $pageNumber > 6) {
                array_splice($range, 2, $pageNumber - 5, '...');
            }

            // Collapse the trailing page-numbers
            if ($pageCount > 7 && ($pageCount - $pageNumber) > 5) {
                array_splice($range, ($pageCount - $pageNumber - 2)*-1, ($pageCount - $pageNumber)-4, '...');
            }

            foreach ($range as $page) {
                if ($page === '...') {
                    $output .= '<input type="button" disabled value="...">';
                } else {
                    $class = ($page == $pageNumber)? 'active paginate' : 'paginate';
                    $output .= '<input type="button" class="'.$class.'" data-page="'.$page.'" value="'.$page.'">';
                }
            }

            $output .= '<input type="button" class="paginate" data-page="'.($pageNumber + 1).'" '.($pageNumber >= $pageCount? 'disabled' : '').' value="'.__('Next').'">';
        $output .= '</div>';

        return $output;
    }
}