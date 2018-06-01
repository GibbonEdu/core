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

namespace Gibbon\Tables\Columns;

use Gibbon\Forms\Traits\BasicAttributesTrait;

/**
 * Column
 *
 * @version v16
 * @since   v16
 */
class Column
{
    use BasicAttributesTrait;

    protected $label;
    protected $description;
    protected $width = 'auto';
    protected $sortable = false;
    protected $formatter;

    protected $cellModifiers = [];

    public function __construct($id, $label = '')
    {
        $this->setID($id);
        $this->label = $label;
        $this->sortable = [$id];
    }

    /**
     * Gets the column label, often displayed in the table heading.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Sets the column width.
     *
     * @param string|int $width
     * @return self
     */
    public function width($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Gets the column width.
     *
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Sets the column description, often displayed as smaller text below the label.
     *
     * @param string $description
     * @return self
     */
    public function description($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets the column description, often displayed as smaller text below the label.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the names of table columns to sort on. Blank defaults to the column name, false disables sorting.
     *
     * @param array|bool $value
     * @return self
     */
    public function sortable($value = null) 
    {
        $this->sortable = is_null($value) ? [$this->getID()] : $value;

        return $this;
    }

    /**
     * Disables sorting for this column.
     * 
     * @return self
     */
    public function notSortable() 
    {
        $this->sortable = false;

        return $this;
    }

    /**
     * Gets the name of table columns to sort on, or false if sorting is disabled.
     *
     * @return array|bool
     */
    public function getSortable()
    {
        return $this->sortable;
    }

    /**
     * Sets the formatter as a callable, which should accept a $data param of row data.
     *
     * @param callable $formatter
     * @return self
     */
    public function format(callable $formatter) 
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Does the column have a valid formatter?
     *
     * @return bool
     */
    public function hasFormatter() 
    {
        return !empty($this->formatter) && is_callable($this->formatter);
    }

    /**
     * Set a callable function that can modify each cell and/or row based on that row's data.
     *
     * @param callable $callable
     * @return self
     */
    public function modifyCells(callable $callable)
    {
        $this->cellModifiers[] = $callable;

        return $this;
    }

    /**
     * Get the array of column logic callables.
     *
     * @return callable
     */
    public function getCellModifiers()
    {
        return $this->cellModifiers;
    }

    /**
     * Renders the column by either passing the row $data to a formatter, 
     * or grabbing the row data by key based on the column name.
     *
     * @param array $data
     * @return string
     */
    public function getOutput(&$data = array())
    {
        if ($this->hasFormatter()) {
            return call_user_func($this->formatter, $data);
        } else {
            return isset($data[$this->getID()])? $data[$this->getID()] : '';
        }
    }
}
