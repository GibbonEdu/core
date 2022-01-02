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

namespace Gibbon\Forms\Builder;

use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\Storage\FormStorageInterface;

abstract class AbstractFormStorage implements FormStorageInterface, FormDataInterface
{
    /**
     * @var array
     */
    private $data = [];

    abstract public function load(string $identifier) : bool;

    abstract public function save(string $identifier) : bool;

    public function exists($fieldName) : bool
    {
        return isset($this->data[$fieldName]);
    }
    
    public function has($fieldName) : bool
    {
        return !empty($this->data[$fieldName]);
    }

    public function get($fieldName)
    {
        return $this->data[$fieldName] ?? null;
    }

    public function set($fieldName, $value)
    {
        $this->data[$fieldName] = $value;
    }

    public function getData() : array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function addData(array $data)
    {
        $this->data = array_merge($this->data, $data);
    }
}
