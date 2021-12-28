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

use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Forms\Builder\Storage\FormSessionStorage;

class FormData
{
    /**
     * @var FormStorageInterface
     */
    protected $storage;

    /**
     * @var FormGateway
     */
    protected $formGateway;

    /**
     * @var array[]
     */
    protected $fields = [];

    /**
     * @var array[]
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Constructor
     *
     * @param FormGateway $formGateway
     * @param FormSessionStorage $storage
     */
    public function __construct(FormGateway $formGateway, FormSessionStorage $storage)
    {
        $this->formGateway = $formGateway;
        $this->storage = $storage;
    }

    public function populate(string $gibbonFormID, string $identifier)
    {
        $this->gibbonFormID = $gibbonFormID;
        $this->identifier = $identifier;

        $form = $this->formGateway->getByID($this->gibbonFormID);

        $this->fields = $this->formGateway->selectFieldsByForm($this->gibbonFormID)->fetchGroupedUnique();
        $this->config = $form['config'] ?? [];
        $this->data = $this->storage->loadData($this->identifier);
    }

    public function validate(array $data)
    {
        $validated = true;
        foreach ($this->fields as $fieldName => $field) {
            if (!isset($data[$fieldName])) continue;

            if ($field['required'] != 'N' && empty($data[$fieldName])) {
                $validated = false;
            }
        }

        return $validated;
    }

    public function save(array $data = [])
    {
        $this->addData($data);
        $this->storage->saveData($this->identifier, $this->data);
    }

    public function has($fieldName)
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

    public function hasField($fieldName)
    {
        return !empty($this->fields[$fieldName]);
    }

    public function getField($fieldName)
    {
        return $this->fields[$fieldName] ?? [];
    }

    public function getData()
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

    public function hasConfig($name)
    {
        return !empty($this->config[$name]);
    }

    public function getConfig($name, $default = null)
    {
        return $this->config[$name] ?? $default;
    }
}
