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

namespace Gibbon\Forms\Builder\Fields;

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Forms\Builder\AbstractFieldGroup;

class CustomFields extends AbstractFieldGroup
{
    protected $customFieldGateway;
    protected $customFieldHandler;

    public function __construct(CustomFieldGateway $customFieldGateway, CustomFieldHandler $customFieldHandler)
    {
        $this->customFieldGateway = $customFieldGateway;
        $this->customFieldHandler = $customFieldHandler;

        $params = ['applicationForm' => 1];
        $customFields = $this->customFieldGateway->selectCustomFields('User', $params)->fetchAll();

        foreach ($customFields as $field) {
            $id = $field['gibbonCustomFieldID'];
            $this->fields[$id] = [
                'type'                => $field['type'],
                'label'               => __($field['name']),
                'description'         => __($field['description']),
                'options'             => $field['options'],
                'activePersonStudent' => $field['activePersonStudent'],
                'activePersonParent'  => $field['activePersonParent'],
                'activePersonStaff'   => $field['activePersonStaff'],
                'activePersonOther'   => $field['activePersonOther'],
            ];
        }
    }

    public function getDescription() : string
    {
        return __('Custom fields are attached to a specific type of record in Gibbon and can be managed in {link}.', ['link' => Format::link('./index.php?q=/modules/System Admin/customFields.php', __('System Admin').' > '.__('Custom Fields'))]);
    }

    public function getFieldOptions() : array 
    {
        $fields = array_reduce(array_keys($this->fields), function ($group, $key) {
            $field = $this->fields[$key];
            if ($field['activePersonStudent']) {
                $group[__('Student')][$key] = $field['label'];
            }
            if ($field['activePersonParent']) {
                $group[__('Parent')][$key] = $field['label'];
            }
            if ($field['activePersonStaff']) {
                $group[__('Staff')][$key] = $field['label'];
            }
            if ($field['activePersonOther']) {
                $group[__('Other')][$key] = $field['label'];
            }
            return $group;
        }, [__('Student') => [], __('Parent') => [], __('Staff') => [], __('Other') => []]);

        return array_filter($fields);
    }

    public function addFieldToForm(Form $form, array $field): Row
    {
        $row = $form->addRow();

        if ($field['fieldType'] == 'editor') {
            $row = $row->addColumn();
        }

        $row->addLabel($field['fieldName'], __($field['label']))->description(__($field['description']));
        $row->addCustomField($field['fieldName'], $field);

        return $row;
    }

    public function getFieldDataFromPOST(string $fieldName, string $fieldType) 
    {
        return $this->customFieldHandler->getFieldValueFromPOST($fieldName, $fieldType);
    }
}
