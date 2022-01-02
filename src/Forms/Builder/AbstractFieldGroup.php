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

use Gibbon\Forms\Form;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Builder\Fields\FieldGroupInterface;

abstract class AbstractFieldGroup implements FieldGroupInterface
{
    protected $fields;

    public function getDescription() : string
    {
        return '';
    }
    
    public function getFields() : array 
    {
        return $this->fields;
    }

    public function getFieldOptions() : array 
    {
        $heading = '';
        return array_reduce(array_keys($this->fields), function ($group, $key) use (&$heading) {
            $field = $this->fields[$key];

            if (!empty($field['type']) && $field['type'] == 'optgroup') {
                $heading = $field['label'];
            } else if (!empty($heading)) {
                $group[$heading][$key] = $field['label'];
            } else {
                $group[$key] = $field['label'];
            }
            return $group;
        }, []);
    }

    public function getField($name) : array 
    {
        return $this->fields[$name] ?? [];
    }

    public function addFieldToForm(Form $form, array $field) : Row
    {
        $row = $form->addRow();

        if ($field['fieldType'] == 'editor') {
            $row = $row->addColumn();
        }

        $row->addLabel($field['fieldName'], __($field['label']))
            ->description(__($field['description']));
        $row->addCustomField($field['fieldName'], $field)
            ->required($field['required'] != 'N');

        return $row;
    }

    public function getFieldDataFromPOST() : array 
    {
        $data = [];

        return $data;
    }
}