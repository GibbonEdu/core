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

    public function getField($fieldName) : array 
    {
        return $this->fields[$fieldName] ?? [];
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

    public function getRequired(FormBuilderInterface $formBuilder, array &$field) : bool 
    {
        return $formBuilder->getConfig('mode') != 'edit' && $field['required'] != 'N';
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

        return !$this->checkConditions($field['conditional'] ?? [], $data);
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
            ->required($this->getRequired($formBuilder, $field))
            ->setValue($field['defaultValue'] ?? '');

        return $row;
    }

    public function getFieldDataFromPOST(string $fieldName, array $field)  
    {
        $fieldInfo = $this->getField($fieldName);
        $fieldValue = $_POST[$fieldName] ?? $fieldInfo['default'] ?? null;

        switch ($field['fieldType']) {
            case 'date': 
                $fieldValue = !empty($fieldValue) ? Format::dateConvert($fieldValue) : null;
                break;
            case 'checkbox': 
                $fieldValue = isset($_POST[$fieldName]) ? $_POST[$fieldName] : ($fieldInfo['default'] ?? '');
                break;
            case 'checkboxes': 
                $fieldValue = !empty($fieldValue) ? implode(',', $fieldValue) : ($fieldInfo['default'] ?? '');
                break;
        }

        return $fieldValue;
    }

    public function displayFieldValue(FormBuilderInterface $formBuilder, string $fieldName, array $field, array &$data = [])
    {
        $fieldInfo = $this->getField($fieldName);
        $fieldValue = $data[$fieldName] ?? null;
        $fieldType = $fieldInfo['type'] ?? $field['fieldType'] ?? '';

        if (!empty($fieldInfo['conditional']) && $this->checkConditions($fieldInfo['conditional'], $data)) {
            return '';
        }

        if (is_array($fieldValue)) {
            return implode(', ', $fieldValue);
        }

        if (!empty($fieldInfo['translate'])) {
            $fieldValue = __($fieldValue);
        }

        switch ($fieldType) {
            case 'date':
                return Format::date($fieldValue);
        
            case 'gender':
                return Format::genderName($fieldValue);
                
            case 'yesno':
                return Format::yesNo($fieldValue);

            case 'radio':
                return $fieldValue == 'Y' || $fieldValue == 'N' ? Format::yesNo($fieldValue) : __($fieldValue);

            case 'checkbox':
                return $fieldValue == 'Y' || $fieldValue == 'on' ? __('Yes') : (empty($fieldValue) ? __('No') : __($fieldValue));

            case 'phone':
                $output = '';
                for ($i = 1; $i <= 4; $i++) {
                    if (empty($data["{$fieldName}{$i}"])) continue;
                    $output .= Format::phone($data["{$fieldName}{$i}"] ?? '', $data["{$fieldName}{$i}CountryCode"] ?? '', $data["{$fieldName}{$i}Type"] ?? '').'<br/>';
                }
                return $output;
        }

        return $fieldValue;
    }

    protected function checkConditions(array $conditions, array &$data) : bool
    {
        if (empty($conditions) || !is_array($conditions)) return false;

        foreach ($conditions as $key => $value) {
            if (empty($data[$key]) || $data[$key] != $value) return true;
        }

        return false;
    }
}
