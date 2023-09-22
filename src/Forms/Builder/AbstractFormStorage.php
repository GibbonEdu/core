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
    private $status = 'Incomplete';
    private $readOnly = false;

    abstract public function identify(string $identifier) : int;

    abstract public function load(string $identifier) : bool;

    abstract public function save(string $identifier) : bool;

    public function isReadOnly()
    {
        return $this->readOnly;
    }

    public function setReadOnly(bool $readOnly = true)
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
        if (isset($this->data['status'])) $this->data['status'] = $this->status;
    }

    public function exists(string $fieldName) : bool
    {
        return isset($this->result[$fieldName]) || isset($this->data[$fieldName]);
    }
    
    public function has(string $fieldName) : bool
    {
        if ($this->readOnly) return !empty($this->result[$fieldName]) || !empty($this->data[$fieldName]);

        return !empty($this->data[$fieldName]);
    }

    public function hasAll(array $fieldNames) : bool
    {
        foreach ($fieldNames as $fieldName) {
            if (!$this->has($fieldName)) return false;
        }

        return true;
    }

    public function hasAny(array $fieldNames) : bool
    {
        foreach ($fieldNames as $fieldName) {
            if ($this->has($fieldName)) return true;
        }

        return false;
    }

    public function hasData(string $fieldName) : bool
    {
        return !empty($this->data[$fieldName]);
    }

    public function get(string $fieldName, $default = null)
    {
        if ($this->readOnly) return $this->result[$fieldName] ?? $this->data[$fieldName] ?? $default;

        return $this->data[$fieldName] ?? $default;
    }

    public function getOrNull(string $fieldName)
    {
        if ($this->readOnly && !empty($this->result[$fieldName])) {
            return $this->result[$fieldName];
        }

        return !empty($this->data[$fieldName]) ? $this->data[$fieldName] : null;
    }

    public function getAny(string $fieldName, $default = null)
    {
        return $this->result[$fieldName] ?? $this->data[$fieldName] ?? $default;
    }

    public function set(string $fieldName, $value)
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

    public function hasResult(string $fieldName) : bool
    {
        return !empty($this->result[$fieldName]);
    }

    public function getResult(string $fieldName, $default = null)
    {
        return $this->result[$fieldName] ?? $default;
    }

    public function setResult(string $fieldName, $value)
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
