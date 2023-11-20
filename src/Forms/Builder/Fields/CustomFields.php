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

namespace Gibbon\Forms\Builder\Fields;

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;

class CustomFields extends AbstractFieldGroup
{
    protected $customFieldGateway;
    protected $customFieldHandler;

    public function __construct(CustomFieldGateway $customFieldGateway, CustomFieldHandler $customFieldHandler)
    {
        $this->customFieldGateway = $customFieldGateway;
        $this->customFieldHandler = $customFieldHandler;

        $contexts = ['User', 'Medical Form', 'Individual Needs'];
        $params = ['applicationForm' => 1];
        $customFields = $this->customFieldGateway->selectCustomFields($contexts, [])->fetchAll();

        foreach ($customFields as $field) {
            
            $id = 'custom'.$field['gibbonCustomFieldID'];
            $this->fields[$id] = [
                'context'             => $field['context'],
                'type'                => $field['type'],
                'label'               => __($field['name']),
                'description'         => __($field['description']),
                'options'             => $field['options'],
                'activePersonStudent' => $field['context'] != 'User' ? 1 : $field['activePersonStudent'],
                'activePersonParent'  => $field['activePersonParent'],
                'activePersonStaff'   => $field['activePersonStaff'],
                'activePersonOther'   => $field['activePersonOther'],
                'hidden'              => $field['hidden'],
                'required'            => $field['required'],
            ];
        }
    }

    public function getDescription() : string
    {
        return __('Custom fields are attached to a specific type of record in Gibbon and can be managed in {link}.', ['link' => Format::link('./index.php?q=/modules/System Admin/customFields.php', __('System Admin').' > '.__('Custom Fields'))]);
    }

    public function getFieldOptions() : array 
    {
        $defaults = [__('Student') => [], __('Parent') => [], __('Staff') => [], __('Other') => []];
        $fields = array_reduce(array_keys($this->fields), function ($group, $key) {
            $field = $this->fields[$key];
            if ($field['context'] == 'User' && $field['activePersonStudent']) {
                $group[__('Student')][$key] = $field['label'];
            }
            if ($field['context'] == 'User' && $field['activePersonParent']) {
                $group[__('Parent')][$key] = $field['label'];
            }
            if ($field['context'] == 'User' && $field['activePersonStaff']) {
                $group[__('Staff')][$key] = $field['label'];
            }
            if ($field['context'] == 'User' && $field['activePersonOther']) {
                $group[__('Other')][$key] = $field['label'];
            }
            if ($field['context'] != 'User') {
                if (!isset($group[__($field['context'])])) $group[__($field['context'])] = [];
                
                $group[__($field['context'])][$key] = $field['label'];
            }
            return $group;
        }, $defaults);

        return array_filter($fields);
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field): Row
    {
        $required = $this->getRequired($formBuilder, $field);
        $default = $field['defaultValue'] ?? null;
        $row = $form->addRow();

        if ($field['fieldType'] == 'editor') {
            $row = $row->addColumn();
        }

        $row->addLabel($field['fieldName'], __($field['label']))->description(__($field['description']));
        $row->addCustomField($field['fieldName'], $field)->required($required)->setValue($default);

        return $row;
    }

    public function getFieldDataFromPOST(string $fieldName, array $field)  
    {
        return $this->customFieldHandler->getFieldValueFromPOST($fieldName, $field['fieldType']);
    }
}
