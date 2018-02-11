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
 * Table
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

    protected $formatter;

    public function __construct($name, $label = '')
    {
        $this->name = $name;
        $this->setLabel($label);
    }

    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function description($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function format(callable $formatter) 
    {
        $this->formatter = $formatter;
    }

    public function getContents(&$data)
    {
        if (!empty($this->formatter) && is_callable($this->formatter)) {
            return call_user_func($this->formatter, $data);
        } else {
            return isset($data[$this->name])? $data[$this->name] : '';
        }
    }
}