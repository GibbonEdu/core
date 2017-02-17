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

namespace Gibbon\Forms\Traits;

/**
 * Basic HTML Attributes (id, class)
 *
 * @version v14
 * @since   v14
 */
trait BasicAttributesTrait
{
    protected $id = '';
    protected $class = '';

    public function setID($id = '')
    {
        $this->id = $id;
        return $this;
    }

    public function getID()
    {
        return $this->id;
    }

    public function setClass($class = '')
    {
        $this->class = $class;
        return $this;
    }

    public function addClass($class = '')
    {
        $this->class = (empty($this->class))? $class : $this->class.' '.$class;
        return $this;
    }

    public function getClass()
    {
        return $this->class;
    }
}
