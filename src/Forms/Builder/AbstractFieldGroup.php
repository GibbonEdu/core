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
use Gibbon\Services\Format;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Builder\Fields\FieldGroupInterface;
use Gibbon\Forms\Builder\Fields\LayoutHeadings;
use Gibbon\Forms\Builder\FormBuilderInterface;

abstract class AbstractFieldGroup implements FieldGroupInterface
{
    protected $fields = [];

    public function getName() : string
    {
        $className = str_replace([__NAMESPACE__ . '\\Fields\\', 'Layout'], '', get_called_class());
        return ucwords(preg_replace('/(?<=[a-z])(?=[A-Z])/', ' $0', $className));
    }

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

            if (!empty($field['type']) && ($field['type'] == 'heading' || $field['type'] == 'subheading') && !($this instanceof LayoutHeadings)) {
                $heading = $field['label'];
            } else if (!empty($heading)) {
                $group[$heading][$key] = $field['label'];
            } else {
                $group[$key] = $field['label'];
            }
            return $group;
        }, []);
    }

    public function getField($fieldName) : array 
    {
        return $this->fields[$fieldName] ?? [];
    }

    /**
     * Handle whether fields should validate based on the presence of other fields.
     *
     * @param FormBuilderInterface $formBuilder
     * @param array $data
     * @param string $fieldName
     * @return bool
     */
    public function shouldValidate(FormBuilderInterface $formBuilder, array &$data, string $fieldName)
    {
        $field = $this->getField($fieldName);

        if (empty($field['conditional']) || !is_array($field['conditional'])) return true;

        foreach ($field['conditional'] as $key => $value) {
            if (empty($data[$key]) || $data[$key] != $value) return false;
        }

        return true;
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row
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

    public function getFieldDataFromPOST(string $fieldName, string $fieldType) 
    {
        $field = $this->getField($fieldName);
        $fieldValue = $_POST[$fieldName] ?? $field['default'] ?? null;

        switch ($fieldType) {
            case 'date': 
                $fieldValue = !empty($fieldValue) ? Format::dateConvert($fieldValue) : null;
                break;
            case 'checkboxes': 
                $fieldValue = !empty($fieldValue) ? implode(',', $fieldValue) : ($field['default'] ?? '');
                break;
        }

        return $fieldValue;
    }
}
