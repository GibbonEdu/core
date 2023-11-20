<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\Columns\Column;
use Gibbon\Tables\Renderer\RendererInterface;
use Gibbon\Forms\FormFactory;

/**
 * PaginatedRenderer
 *
 * @version v16
 * @since   v16
 */
class PaginatedRenderer extends SimpleRenderer implements RendererInterface
{
    protected $path;
    protected $criteria;
    protected $factory;
    
    /**
     * Creates a renderer that uses page info from the QueryCriteria to display a paginated data table.
     * Hooks into the DataTable functionality in core.js to load using AJAX.
     *
     * @param QueryCriteria $criteria
     * @param string $path
     */
    public function __construct(QueryCriteria $criteria, $path)
    {
        $this->path = $path;
        $this->criteria = $criteria;
        $this->factory = FormFactory::create();
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

        $output .= '<div id="'.$table->getID().'">';
        $output .= '<div class="dataTable" data-results="'.$dataSet->getResultCount().'">';

        $output .= parent::renderTable($table, $dataSet);

        $output .= '</div></div>';

        // Persist the bulk actions outside the AJAX-reloaded data table div
        $output .= $this->renderBulkActions($table);

        $postData = $table->getMetaData('post');
        $jsonData = !empty($postData) 
            ? json_encode(array_replace($postData, $this->criteria->toArray()))
            : $this->criteria->toJson();
        
        // Initialize the jQuery Data Table functionality
        $output .="
        <script>
        $(function(){
            $('#".$table->getID()."').gibbonDataTable('.".str_replace(' ', '%20', $this->path)."', ".$jsonData.", '".$this->criteria->getIdentifier()."');
        });
        </script>";

        return $output;
    }

    /**
     * Adds the pagination and filter controls to the pre-table header.
     *
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return string
     */
    protected function renderHeader(DataTable $table, DataSet $dataSet) 
    {
        if ($table->getMetaData('hidePagination') == true) {
            return parent::renderHeader($table, $dataSet);
        }
        
        $filterOptions = $table->getMetaData('filterOptions', []);

        $output = '<div class="flexRow">';
            $output .= '<div>';
                $output .= $this->renderPageCount($dataSet);
                $output .= $this->renderPageFilters($dataSet, $filterOptions);
            $output .= '</div>';

            $output .= parent::renderHeader($table, $dataSet);
        $output .= '</div>';

        $output .= $this->renderFilterOptions($dataSet, $filterOptions);
        $output .= $this->renderPageSize($dataSet);
        $output .= $this->renderPagination($dataSet);

        return $output;
    }

    /**
     * Optionally adds the pagination to the post-table footer.
     *
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return string
     */
    protected function renderFooter(DataTable $table, DataSet $dataSet)
    {
        $output = parent::renderFooter($table, $dataSet);

        if ($table->getMetaData('hidePagination') == true) return $output;

        if ($dataSet->getPageCount() > 1) {
            $output .= $this->renderPageCount($dataSet);
            $output .= $this->renderPagination($dataSet);
        }

        return $output;
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
            $th->addClass('sortable');
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
     * Render the record count for this page, and total record count.
     *
     * @param DataSet $dataSet
     * @return string
     */
    protected function renderPageCount(DataSet $dataSet)
    {
        $output = '<small style="margin-right: 10px;">';

        $output .= $this->criteria->hasSearchText()? __('Search').' ' : '';
        $output .= $dataSet->isSubset()? __('Results') : __('Records');
        $output .= $dataSet->count() > 0? ' '.$dataSet->getPageFrom().'-'.$dataSet->getPageTo().' '.__('of').' ' : ': ';
        $output .= $dataSet->isSubset()? $dataSet->getResultCount() : $dataSet->getTotalCount();

        $output .= '</small>';

        return $output;
    }

    /**
     * Render the currently active filters for this data set.
     *
     * @param DataSet $dataSet
     * @param array $filters
     * @return string
     */
    protected function renderPageFilters(DataSet $dataSet, array $filters)
    {
        $output = '<small>';

        if ($this->criteria->hasFilter()) {
            $output .= __('Filtered by').' ';

            $criteriaUsed = array();
            foreach ($this->criteria->getFilterBy() as $name => $value) {
                $key = $name.':'.$value;
                $criteriaUsed[$name] = isset($filters[$key]) 
                    ? $filters[$key] 
                    : __(ucwords(preg_replace('/(?<=[a-z])(?=[A-Z])/', ' $0', $name))) . ($name == 'in'? ': '.ucfirst($value) : ''); // camelCase => Title Case
            }

            foreach ($criteriaUsed as $name => $label) {
                $output .= '<input type="button" class="filter" value="'.$label.'" data-filter="'.$name.'"> ';
            }

            $output .= '<input type="button" class="filter clear buttonLink" value="'.__('Clear').'">';
        }

        $output .= '</small>';

        return $output;
    }

    /**
     * Render the available options for filtering the data set.
     *
     * @param DataSet $dataSet
     * @param array $filters
     * @return string
     */
    protected function renderFilterOptions(DataSet $dataSet, array $filters)
    {
        if (empty($filters)) return '';
        
        return $this->factory->createSelect('filter')
            ->fromArray($filters)
            ->setClass('filters floatNone')
            ->placeholder(__('Filters'))
            ->getOutput();
    }

    /**
     * Render the page size drop-down. Hidden if there's less than one page of total results.
     *
     * @param DataSet $dataSet
     * @return string
     */
    protected function renderPageSize(DataSet $dataSet)
    {
        if ($dataSet->getPageSize() <= 0 || $dataSet->getResultCount() <= 25) return '';

        return $this->factory->createSelect('limit')
            ->fromArray(array(10, 25, 50, 100))
            ->fromArray(array($dataSet->getResultCount() => __('All')))
            ->setClass('limit floatNone')
            ->selected($dataSet->getPageSize())
            ->append('<small style="line-height: 30px;margin-left:5px;">'.__('Per Page').'</small>')
            ->getOutput();
    }

    /**
     * Render the set of numeric page buttons for naigating paginated data sets.
     *
     * @param DataSet $dataSet
     * @return string
     */
    protected function renderPagination(DataSet $dataSet)
    {
        if ($dataSet->getResultCount() <= 25) return '';

        $pageNumber = $dataSet->getPage();

        $output = '<div class="pagination floatRight">';
            $output .= '<input type="button" class="paginate" data-page="'.$dataSet->getPrevPageNumber().'" '.($dataSet->isFirstPage()? 'disabled' : '').' value="'.__('Prev').'">';

            foreach ($dataSet->getPaginatedRange() as $page) {
                if ($page === '...') {
                    $output .= '<input type="button" disabled value="...">';
                } else {
                    $class = ($page == $pageNumber)? 'active paginate' : 'paginate';
                    $output .= '<input type="button" class="'.$class.'" data-page="'.$page.'" value="'.$page.'">';
                }
            }

            $output .= '<input type="button" class="paginate" data-page="'.$dataSet->getNextPageNumber().'" '.($dataSet->isLastPage()? 'disabled' : '').' value="'.__('Next').'">';
        $output .= '</div>';

        return $output;
    }

    /**
     * Display the bulk action panel.
     *
     * @param OutputtableInterface $bulkActions
     * @return string
     */
    protected function renderBulkActions(DataTable $table)
    {
        $bulkActions = $table->getMetaData('bulkActions');

        if (empty($bulkActions)) return '';

        $output = '<div class="bulkActionPanel hidden absolute top-0 right-0 w-full flex items-center justify-between px-1 pt-1 bg-purple-600 rounded-t">';
        $output .= '<div class="bulkActionCount flex-grow text-white text-sm text-right pr-3"><span>0</span> '.__('Selected').'</div>';
        $output .= $bulkActions->getOutput();
        $output .= '<script>';
        $output .= $bulkActions->getValidationOutput();
        $output .= '</script>';
        $output .= '</div>';

        return $output;
    }
}
