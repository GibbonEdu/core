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

use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\Renderer\RendererInterface;
use Gibbon\Tables\View\DataTableView;
use Gibbon\Forms\FormFactory;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Tables\Columns\Column;

/**
 * Paginated View
 *
 * @version v18
 * @since   v18
 */
class PaginatedView extends DataTableView implements RendererInterface
{
    protected $criteria;
    protected $factory;

    public function setCriteria(QueryCriteria $criteria)
    {
        $this->criteria = $criteria;
        $this->factory = FormFactory::create();

        return $this;
    }

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

        $this->preProcessTable($table);
        
        $filters = $table->getMetaData('filterOptions', []);

        $this->addData([
            'dataSet'    => $dataSet,

            'headers'    => $this->getTableHeaders($table),
            'columns'    => $table->getColumns(),
            'rows'       => $this->getTableRows($table, $dataSet),
            'path'       => './fullscreen.php?'.http_build_query($_GET),
            'identifier' => $this->criteria->getIdentifier(),

            'blankSlate'     => $table->getMetaData('blankSlate'),
            'searchText'     => $this->criteria->getSearchText(),
            'pageSize'       => $this->getSelectPageSize($dataSet, $filters),
            'filterOptions'  => $this->getSelectFilterOptions($dataSet, $filters),
            'filterCriteria' => $this->getFilterCriteria($filters),
            'bulkActions'    => $table->getMetaData('bulkActions'),
            'isFiltered'     => $dataSet->getTotalCount() > 0 && ($this->criteria->hasSearchText() || $this->criteria->hasFilter()),
        ]);

        $postData = $table->getMetaData('post');
        $this->addData('jsonData', !empty($postData)
            ? json_encode(array_replace($postData, $this->criteria->toArray()))
            : $this->criteria->toJson());

        return $this->render('components/paginatedTable.twig.html');
    }

    /**
     * Overrides the SimpleRenderer header to add sortable column classes & data attribute.
     * @param Column $column
     * @return Element
     */
    protected function createTableHeader(Column $column)
    {
        $th = parent::createTableHeader($column);

        if ($sortBy = $column->getSortable()) {
            $sortBy = !is_array($sortBy)? array($sortBy) : $sortBy;
            $th->addClass('sortable relative pr-4 cursor-pointer');
            $th->addData('sort', implode(',', $sortBy));

            foreach ($sortBy as $sortColumn) {
                if ($this->criteria->hasSort($sortColumn)) {
                    $th->addClass('sorting sort'.$this->criteria->getSortBy($sortColumn));
                }
            }
        }

        return $th;
    }
    
    /**
     * Get the currently active filters for this criteria.
     *
     * @param array $filters
     * @return string
     */
    protected function getFilterCriteria(array $filters)
    {
        $criteriaUsed = [];
        foreach ($this->criteria->getFilterBy() as $name => $value) {
            $key = $name.':'.$value;
            $criteriaUsed[$name] = isset($filters[$key]) 
                ? $filters[$key] 
                : __(ucwords(preg_replace('/(?<=[a-z])(?=[A-Z])/', ' $0', $name))) . ($name == 'in'? ': '.ucfirst($value) : ''); // camelCase => Title Case
        }

        return $criteriaUsed;
    }
    
    /**
     * Render the available options for filtering the data set.
     *
     * @param DataSet $dataSet
     * @param array $filters
     * @return string
     */
    protected function getSelectFilterOptions(DataSet $dataSet, array $filters)
    {
        if (empty($filters)) return '';
        
        return $this->factory->createSelect('filter')
            ->fromArray($filters)
            ->setClass('filters float-none w-24 pl-2 border leading-loose h-full sm:h-8 ')
            ->addClass($this->criteria->hasFilter() ?: 'rounded-r')
            ->placeholder(__('Filters'))
            ->getOutput();
    }

    /**
     * Render the page size drop-down. Hidden if there's less than one page of total results.
     *
     * @param DataSet $dataSet
     * @param array $filters
     * @return string
     */
    protected function getSelectPageSize(DataSet $dataSet, array $filters)
    {
        if ($dataSet->getPageSize() <= 0 || $dataSet->getTotalCount() <= 25) return '';

        $options = [__('Per Page') => [
            10 => 10,
            25 => 25,
            50 => 50,
            100 => 100,
            $dataSet->getResultCount() => __('All'),
        ]];

        return $this->factory->createSelect('limit')
            ->fromArray($options)
            ->setClass('limit float-none w-16 pl-2 rounded-l border leading-loose h-full sm:h-8 ')
            ->addClass(!empty($filters) ?: 'rounded-r')
            ->selected($dataSet->getPageSize())
            ->getOutput();
    }
}
