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

namespace Gibbon\Domain;

/**
 * Object representing the paginated results of a Gateway query.
 */
class DataSet implements \Countable, \IteratorAggregate
{
    protected $data;

    protected $resultCount;
    protected $totalCount;

    protected $page;
    protected $pageSize;

    /**
     * Creates a new data set from an array and calculates the result counts and pagination based on array size.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;

        $this->setResultCount();
        $this->setPagination();
    }

    /**
     * Sets the result count (that this page may be a subset of), and the total count of all possible results.
     *
     * @param int $resultCount
     * @param int $totalCount
     * @return self
     */
    public function setResultCount($resultCount = null, $totalCount = null)
    {
        $this->resultCount = !is_null($resultCount) ? $resultCount : $this->count();
        $this->totalCount = !is_null($totalCount) ? $totalCount : $this->resultCount;
        $this->totalCount = max($this->totalCount, $this->resultCount);

        return $this;
    }

    /**
     * Set the page and pageSize for the data set.
     *
     * @param int $page
     * @param int $pageSize
     * @return self
     */
    public function setPagination($page = 1, $pageSize = null)
    {
        $this->page = $page;
        $this->pageSize = !empty($pageSize)? $pageSize : $this->resultCount;

        return $this;
    }

    /**
     * Implements \Countable, allowing the data set to be counted.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Implements IteratorAggregate, allowing this object to be looped over in a foreach.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Returns the internal array of row data.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * The total number of rows in the table being queried.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * The total un-paginated number of rows for this data set.
     * Will be less than the totalCount if the results have criteria applied.
     *
     * @return int
     */
    public function getResultCount()
    {
        return $this->resultCount;
    }

    /**
     * The current page number, counting from 1.
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * The number of rows per page in this result set.
     *
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * The total number of pages in this result set.
     *
     * @return int
     */
    public function getPageCount()
    {
        return $this->pageSize > 0 ? ceil($this->resultCount / $this->pageSize) : 1;
    }

    /**
     * Get the previous page number.
     *
     * @return int
     */
    public function getPrevPageNumber()
    {
        return max($this->page - 1, 1);
    }

    /**
     * Get the next page number.
     *
     * @return int
     */
    public function getNextPageNumber()
    {
        return min($this->page + 1, $this->getPageCount());
    }

    /**
     * The row number for the lower bounds of the current page.
     *
     * @return int
     */
    public function getPageFrom()
    {
        return (($this->page-1) * $this->pageSize + 1);
    }

    /**
     * The row number for the upper bounds of the current page.
     *
     * @return int
     */
    public function getPageTo()
    {
        return max(1, min( ($this->page * $this->pageSize), $this->resultCount));
    }

    /**
     * Returns a range of page numbers with sections collapsed into a placeholder string.
     * The midSize sets how many pages are displayed on either side of the current page.
     * The endSize sets how many pages are displayed on both ends of the range.
     *
     * @param string $placeholder
     * @param int $midSize
     * @param int $endSize
     * @return array
     */
    public function getPaginatedRange($placeholder = '...', $midSize = 1, $endSize = 2)
    {
        $range = $this->getPageCount() > 1 ? range(1, $this->getPageCount()) : [1];
        $countFromEnd = count($range) - $this->page;

        // Collapse the leading page numbers
        if ($this->page > ($midSize + $endSize + 2)) {
            array_splice($range, $endSize, $this->page-$midSize-$endSize-1, $placeholder);
        }

        // Collapse the trailing page numbers
        if ($countFromEnd > ($midSize + $endSize + 1)) {
            array_splice($range, ($countFromEnd - $midSize)*-1, $countFromEnd-$endSize-$midSize, $placeholder);
        }

        return $range;
    }

    /**
     * This result is a subset of the whole table if searches or filters have been applied.
     * Ignores paginated row counts and looks at the total results vs the total table size.
     *
     * @return bool
     */
    public function isSubset()
    {
        return $this->totalCount > 0 && ($this->totalCount != $this->resultCount);
    }

    /**
     * Is the current page the first one in the data set?
     *
     * @return bool
     */
    public function isFirstPage()
    {
        return $this->page == 1;
    }

    /**
     * Is the current page the last one in the data set?
     *
     * @return bool
     */
    public function isLastPage()
    {
        return $this->page >= $this->getPageCount();
    }

    /**
     * Extract a single row from the result set as an array.
     *
     * @param string $index
     * @return array
     */
    public function getRow($index)
    {
        return isset($this->data[$index])? $this->data[$index] : array();
    }

    /**
     * Extract a single column from the result set as an array.
     *
     * @param string $columnName
     * @return array
     */
    public function getColumn($columnName)
    {
        return array_column($this->data, $columnName);
    }

    /**
     * Joins a column of data to the result set based on a common key in both data.
     *
     * @param string $keyName
     * @param string $columnName
     * @param array $columnData
     */
    public function joinColumn($keyName, $columnName, &$columnData)
    {
        array_walk($this->data, function(&$item) use ($keyName, $columnName, &$columnData){
            $key = $item[$keyName];
            $item[$columnName] = isset($columnData[$key])? $columnData[$key] : array();
        });
    }

    /**
     * Transform a data set by applying a callback to each row.
     *
     * @param callable $callable
     */
    public function transform(callable $callable)
    {
        array_walk($this->data, $callable);
    }

    /**
     * Filter a data set by applying a callback to each row.
     *
     * @param callable $callable
     */
    public function filter(callable $callable)
    {
        $this->data = array_filter($this->data, $callable);
    }

    /**
     * Merge another data set into this data set by row index.
     *
     * @param DataSet $data
     */
    public function merge(DataSet $newData)
    {
        foreach ($this->data as $index => $row) {
            $rowData = $newData->getRow($index);
            $this->data[$index] = array_merge($row, $rowData);
        }
    }

    /**
     * Prepare data to be displayed in a table.
     *
     * @param callable $callable
     */
    public function htmlEncode(array $ignore = [])
    {
        array_walk($this->data, function (&$item, $key) use (&$ignore)  {
            if (isset($ignore[$key]) || in_array($key, $ignore)) return;
            if (is_string($item)) $item = strip_tags($item, '<br>');
            if (is_array($item)) array_walk($item, function (&$innerItem, $innerKey) use (&$ignore) {
                if (isset($ignore[$innerKey]) || in_array($innerKey, $ignore)) return;
                if (is_string($innerItem)) $innerItem = strip_tags($innerItem, '<br>');
            });
        });
    }
}
