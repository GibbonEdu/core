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
use Gibbon\Domain\QueryCriteria;
use Gibbon\Forms\FormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\ActionColumn;
use Gibbon\Tables\Renderer\RendererInterface;

/**
 * PaginatedRenderer
 *
 * @version v16
 * @since   v16
 */
class PaginatedRenderer implements RendererInterface
{
    protected $criteria;
    protected $factory;
    
    public function __construct(QueryCriteria $criteria)
    {
        $this->criteria = $criteria;
        $this->factory = FormFactory::create();
    }

    public function renderTable(DataTable $table, DataSet $dataSet)
    {
        $output = '';

        $output .= '<div class="linkTop">';
        foreach ($table->getHeaderActions() as $action) {
            $output .= $action->getOutput();
        }
        $output .= '</div>';

        $output .= '<div id="'.$table->getID().'">';
        $output .= '<div class="dataTable">';

        // Debug the AJAX $POST => Filters
        // $output .= json_encode($_POST).'<br/>';

        // Debug the criteria
        // $output .= '<code>';
        // $output .= $this->criteria->toJson();
        // $output .= '</code>';

        $output .= '<div>';
        $output .= $this->renderPageCount($dataSet);
        $output .= $this->renderPageFilters($dataSet, $table->getFilterOptions());
        $output .= '</div>';
        $output .= $this->renderSelectFilters($dataSet, $table->getFilterOptions());
        $output .= $this->renderPageSize($dataSet);
        $output .= $this->renderPagination($dataSet);

        if ($dataSet->count() > 0) {
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

                if ($this->criteria->hasSort($columnName)) {
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

            foreach ($dataSet as $data) {
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
            if ($dataSet->isSubset()) {
                $output .= '<div class="warning">';
                $output .= __('No results matched your search.');
                $output .= '</div>';
            } else {
                $output .= '<div class="error">';
                $output .= __('There are no records to display.');
                $output .= '</div>';
            }
        }

        $output .= $this->renderPageCount($dataSet);
        $output .= $this->renderPagination($dataSet);

        $output .= '</div></div><br/>';

        // Initialize the jQuery Data Table functionality
        $output .="
        <script>
        $(function(){
            $('#".$table->getID()."').gibbonDataTable('.".str_replace(' ', '%20', $table->getPath())."', ".$this->criteria->toJson().", ".$dataSet->getResultCount().");
        });
        </script>";

        return $output;
    }

    protected function renderPageCount(DataSet $dataSet)
    {
        $output = '<span class="small" style="line-height: 32px;margin-right: 10px;">';

        $output .= $this->criteria->hasSearch()? __('Search').' ' : '';
        $output .= $dataSet->isSubset()? __('Results') : __('Records');
        $output .= $dataSet->count() > 0? ' '.$dataSet->getPageFrom().'-'.$dataSet->getPageTo().' '.__('of').' ' : ': ';
        $output .= $dataSet->isSubset()? $dataSet->getResultCount() : $dataSet->getTotalCount();

        $output .= '</span>';

        return $output;
    }

    protected function renderPageFilters(DataSet $dataSet, array $filters)
    {
        if (empty($this->criteria)) return '';

        $output = '<span class="small" style="line-height: 32px;">';

        $filterBy = $this->criteria->getFilters();

        if (!empty($filterBy)) {
            $output .= __('Filtered by').' ';

            $criteriaUsed = array_filter($filters, function($name) use ($filterBy) {
                return in_array($name, $filterBy);
            }, ARRAY_FILTER_USE_KEY);

            foreach ($criteriaUsed as $value => $label) {
                $output .= '<input type="button" class="filter" value="'.$label.'" data-filter="'.$value.'"> ';
            }

            $output .= '<input type="button" class="filter clear buttonLink" value="'.__('Clear').'">';
        }

        return $output;
    }

    protected function renderSelectFilters(DataSet $dataSet, array $filters)
    {
        if (empty($this->criteria)) return '';
        if (empty($filters)) return '';
        
        return $this->factory->createSelect('filter')
            ->fromArray($filters)
            ->setClass('filters floatNone')
            ->placeholder(__('Filters'))
            ->getOutput();
    }

    protected function renderPageSize(DataSet $dataSet)
    {
        $pageSize = $dataSet->getPageSize();

        if ($pageSize <= 0) return '';

        return $this->factory->createSelect('limit')
            ->fromArray(array(10, 25, 50, 100))
            ->setClass('limit floatNone')
            ->selected($pageSize)
            ->append('<small style="line-height: 30px;margin-left:5px;">'.__('Per Page').'</small>')
            ->getOutput();
    }

    protected function renderPagination(DataSet $dataSet)
    {
        if ($dataSet->getPageCount() <= 1) return '';

        $pageNumber = $dataSet->getPage();
        $pageIndex = $pageNumber - 1;

        $output = '<div class="floatRight">';
            $output .= '<input type="button" class="paginate" data-page="'.($pageNumber - 1).'" '.($pageNumber <= 1? 'disabled' : '').' value="'.__('Prev').'">';

            $pageCount = $dataSet->getPageCount();
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