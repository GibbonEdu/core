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
    private $result = [];
    private $status = '';
    private $readOnly = false;

    abstract public function load(string $identifier) : bool;

    abstract public function save(string $identifier) : bool;

    public function isReadOnly()
    {
        return $this->readOnly;
    }

    public function setReadOnly($readOnly = true)
    {
        $this->readOnly = $readOnly;
    }

    public function getStatus() : string
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    public function exists($fieldName) : bool
    {
        return isset($this->result[$fieldName]) || isset($this->data[$fieldName]);
    }
    
    public function has($fieldName) : bool
    {
        if ($this->readOnly) return !empty($this->result[$fieldName]) || !empty($this->data[$fieldName]);

        return !empty($this->data[$fieldName]);
    }

    public function get($fieldName, $default = null)
    {
        if ($this->readOnly) return $this->result[$fieldName] ?? $this->data[$fieldName] ?? $default;

        return $this->data[$fieldName] ?? $default;
    }

    public function getOrNull($fieldName)
    {
        if ($this->readOnly && !empty($this->result[$fieldName])) {
            return $this->result[$fieldName];
        }

        return !empty($this->data[$fieldName]) ? $this->data[$fieldName] : null;
    }

    public function set($fieldName, $value)
    {
        if ($this->readOnly) return $this->setResult($fieldName, $value);
        
        $this->data[$fieldName] = $value;
    }

    public function getData() : array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        if ($this->readOnly) return;

        $this->data = $data;
    }

    public function addData(array $data)
    {
        if ($this->readOnly) return $this->addResults($data);
        
        $this->data = array_merge($this->data, $data);
    }

    public function hasResult($fieldName) : bool
    {
        return !empty($this->result[$fieldName]);
    }

    public function getResult($fieldName, $default = null)
    {
        return $this->result[$fieldName] ?? $default;
    }

    public function setResult($fieldName, $value)
    {
        $this->result[$fieldName] = $value;
    }

    public function getResults() : array
    {
        return $this->result;
    }

    public function setResults(array $result)
    {
        $this->result = $result;
    }

    public function addResults(array $result)
    {
        $this->result = array_merge($this->result, $result);
    }
}
