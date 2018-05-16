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

/**
 * Column
 *
 * @version v16
 * @since   v16
 */
class Column
{
    protected $name;
    protected $label;
    protected $title;
    protected $description;
    protected $width = 'auto';
    protected $sortable = false;
    protected $formatter;

    public function __construct($name, $label = '')
    {
        $this->name = $name;
        $this->label = $label;
    }

    /**
     * Allows read-only access to the column properties.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : '';
    }

    /**
     * Allows read-only isset checking for the column properties.
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->$name);
    }

    /**
     * Sets the column name, used when accessing row data by array key.
     *
     * @param string $name
     * @return self
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the column label, often displayed in the table's header. Should be already translated.
     *
     * @param string $label
     * @return self
     */
    public function label($label)
    {
        $this->label = $label;

        return $this;
    }
    
    /**
     * Sets the column title text, often displayed on hover. Should be already translated.
     *
     * @param string $title
     * @return self
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
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
     * Sets the names of table columns to sort on. Blank defaults to the column name, false disables sorting.
     *
     * @param array|bool $value
     * @return self
     */
    public function sortable($value = null) 
    {
        $this->sortable = is_null($value) ? array($this->name) : $value;

        return $this;
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
            return isset($data[$this->name])? $data[$this->name] : '';
        }
    }
}
