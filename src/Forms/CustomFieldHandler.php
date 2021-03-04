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

namespace Gibbon\Forms;

use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\CustomFieldGateway;

class CustomFieldHandler
{
    protected $customFieldGateway;

    protected $contexts;
    protected $types;

    public function __construct(CustomFieldGateway $customFieldGateway, FileUploader $fileUploader)
    {
        $this->customFieldGateway = $customFieldGateway;
        $this->fileUploader = $fileUploader;

        $this->contexts = [
            __('User Admin') => [
                'Person' => __('Person'),
            ],
            __('Students') => [
                'Medical Form' => __('Medical Form'),
            ],
        ];

        $this->types = [
            __('Text') => [
                'varchar'    => __('Short Text (max 255 characters)'),
                'text'       => __('Long Text'),
                'editor'     => __('Rich Text'),
            ],
            __('Options') => [
                'select'     => __('Dropdown'),
                'checkboxes' => __('Checkboxes'),
                'radio'      => __('Radio'),
            ],
            __('Dates') => [
                'date'       => __('Date'),
                'time'       => __('Time'),
            ],
            __('File') => [
                'file'       => __('File'),
                'image'      => __('Image'),
            ],
            __('Other') => [
                'yesno'      => __('Yes/No'),
                'number'     => __('Number'),
                'url'        => __('Link'),
                'color'      => __('Colour'),
            ]
        ];
    }

    public function getContexts()
    {
        return $this->contexts;
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function getFieldDataFromPOST($context, $params = [], &$customRequireFail = false)
    {
        $customFields = $this->customFieldGateway->selectCustomFields($context, $params)->fetchAll();
        $prefix = $params['prefix'] ?? 'custom';
        $fields = [];

        foreach ($customFields as $field) {
            $fieldValue = $_POST[$prefix.$field['gibbonCustomFieldID']] ?? null;

            if ($field['type'] == 'file' || $field['type'] == 'image') {
                if ($field['type'] == 'image') {
                    $this->fileUploader->getFileExtensions('Graphics/Design');
                }
            
                // Move attached file, if there is one
                if (!empty($_FILES[$prefix.$field['gibbonCustomFieldID'].'File']['tmp_name'])) {
                    $file = $_FILES[$prefix.$field['gibbonCustomFieldID'].'File'] ?? null;
            
                    // Upload the file, return the /uploads relative path
                    $fieldValue = $this->fileUploader->uploadFromPost($file, $field['name']);
                }
            }
            
            if (!is_null($fieldValue)) {
                if ($field['type'] == 'date') {
                    $fieldValue = Format::dateConvert($fieldValue);
                } elseif ($field['type'] == 'checkboxes') {
                    $fieldValue = implode(',', $fieldValue);
                }

                $fields[$field['gibbonCustomFieldID']] = $fieldValue;
            }

            if ($field['required'] == 'Y' && (is_null($fieldValue) || $fieldValue == '')) {
                $customRequireFail = true;
            }
        }

        return json_encode($fields);
    }

    public function addCustomFieldsToForm(&$form, $context, $params = [], $fields = [])
    {
        $fields = !empty($fields) && is_string($fields)? json_decode($fields, true) : (is_array($fields) ? $fields : []);
        $customFieldsGrouped = $this->customFieldGateway->selectCustomFields($context, $params)->fetchGrouped();
        $prefix = $params['prefix'] ?? 'custom';

        if (empty($customFieldsGrouped)) {
            return;
        }

        $existingFields = [];
        foreach ($fields as $key => $value) {
            $key = str_pad($key, 4, "0", STR_PAD_LEFT);
            $existingFields[$key] = $value;
        }

        if (!empty($params['heading'])) {
            $form->addRow()->addHeading($params['heading']);
        }
        if (!empty($params['subheading'])) {
            $form->addRow()->addSubheading($params['subheading']);
        }

        foreach ($customFieldsGrouped as $heading => $customFields) {
            if (!empty($heading)) {
                $form->addRow()->addSubheading($heading);
            }

            foreach ($customFields as $field) {
                $fieldValue = $existingFields[$field['gibbonCustomFieldID']] ?? '';
                if (!empty($fieldValue) && $field['type'] == 'date') {
                    $fieldValue = Format::date($fieldValue);
                } elseif (!empty($fieldValue) && $field['type'] == 'checkboxes') {
                    $fieldValue = explode(',', $fieldValue);
                }
                
                $name = $prefix.$field['gibbonCustomFieldID'];
                $row = $field['type'] == 'editor' ? $form->addRow()->addColumn() : $form->addRow();
                    $row->addLabel($name, $field['name'])->description($field['description']);
                    $row->addCustomField($name, $field)->setValue($fieldValue);
            }
        }
    }

    public function createCustomFieldsTable($context, $params = [], $fields = [], $table = null)
    {
        $fields = !empty($fields) && is_string($fields)? json_decode($fields, true) : (is_array($fields) ? $fields : []);
        $customFields = $this->customFieldGateway->selectCustomFields($context, $params + ['hideHidden' => '1'])->fetchAll();

        $existingFields = [];
        foreach ($fields as $key => $value) {
            $key = str_pad($key, 4, "0", STR_PAD_LEFT);
            $existingFields[$key] = $value;
        }

        if (!empty($table)) {
            $table->withData([$existingFields]);
        } else {
            $table = DataTable::createDetails('customFields')->withData([$existingFields]);
        }
        
        foreach ($customFields as $field) {
            $col = $table->addColumn($field['gibbonCustomFieldID'], __($field['name']));

            switch ($field['type']) {
                case 'date':
                    $col->format(Format::using('date', $field['gibbonCustomFieldID']));
                    break;
                case 'url':
                    $col->format(Format::using('link', [$field['gibbonCustomFieldID'], $field['gibbonCustomFieldID']]));
                    break;
                case 'file':
                case 'image':
                    $col->format(function ($values) use ($field) {
                        return !empty($values[$field['gibbonCustomFieldID']])
                            ? Format::link($values[$field['gibbonCustomFieldID']], __('Attachment'), '', ['target' => '_blank'])
                            : '';
                    });
                    break;
                case 'yesno':
                    $col->format(Format::using('yesno', $field['gibbonCustomFieldID']));
                    break;
                case 'color':
                    $col->format(function ($values) use ($field) {
                        $value = $values[$field['gibbonCustomFieldID']] ?? '';
                        return "<span class='tag text-xxs w-12' title='$value' style='background-color: $value'>&nbsp;</span>";
                    });
                    break;
            }
        }

        return $table;
    }

    public function addCustomFieldsToDataUpdate(&$form, $context, $params = [], $oldValues, $newValues)
    {
        $oldFields = !empty($oldValues['fields'])? json_decode($oldValues['fields'], true) : [];
        $newFields = !empty($newValues['fields'])? json_decode($newValues['fields'], true) : [];

        $customFields = $this->customFieldGateway->selectCustomFields($context, $params)->fetchAll();

        foreach ($customFields as $field) {
            $fieldName = $field['gibbonCustomFieldID'];
            $label = __($field['name']);

            $oldValue = isset($oldFields[$fieldName])? $oldFields[$fieldName] : '';
            $newValue = isset($newFields[$fieldName])? $newFields[$fieldName] : '';

            if ($field['type'] == 'date') {
                $oldValue = Format::date($oldValue);
                $newValue = Format::date($newValue);
            }

            $isMatching = ($oldValue != $newValue);

            $row = $form->addRow();
            $row->addLabel('new'.$fieldName.'On', $label);
            $row->addContent($oldValue);
            $row->addContent($newValue)->addClass($isMatching ? 'matchHighlightText' : '');

            if ($isMatching) {
                $row->addCheckbox('newcustom'.$fieldName.'On')->checked(true)->setClass('textCenter');
                $form->addHiddenValue('newcustom'.$fieldName, $newValue);
            } else {
                $row->addContent();
            }
        }
    }

    public function getFieldDataFromDataUpdate($context, $params = [], $fields = [])
    {
        $customFields = $this->customFieldGateway->selectCustomFields($context, $params)->fetchAll();

        $fields = !empty($fields) && is_string($fields) ? json_decode($fields, true) : $fields;
        foreach ($customFields as $field) {
            if (!isset($_POST['newcustom'.$field['gibbonCustomFieldID'].'On'])) continue;
            if (!isset($_POST['newcustom'.$field['gibbonCustomFieldID']])) continue;

            $fields[$field['gibbonCustomFieldID']] = $field['type'] == 'date'
                ? Format::dateConvert($_POST['newcustom'.$field['gibbonCustomFieldID']])
                : $_POST['newcustom'.$field['gibbonCustomFieldID']];
        }

        return json_encode($fields);
    }
}
