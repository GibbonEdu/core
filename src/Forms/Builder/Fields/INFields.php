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
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;

class INFields extends AbstractFieldGroup
{
    protected $settingGateway;
    protected $customFieldGateway;
    protected $customFieldHandler;

    public function __construct(SettingGateway $settingGateway, CustomFieldGateway $customFieldGateway, CustomFieldHandler $customFieldHandler)
    {
        $this->customFieldGateway = $customFieldGateway;
        $this->customFieldHandler = $customFieldHandler;
        $this->settingGateway = $settingGateway;

        $this->fields = [
            'headingSpecialEducationalNeeds' => [
                'label'       => __('Special Educational Needs'),
                'type'        => 'heading',
            ],
            'sen' => [
                'label'       => __('Special Educational Needs (SEN)'),
                'description' => __('Are there any known or suspected SEN concerns, or previous SEN assessments?'),
                'required'    => 'X',
                'type'        => 'yesno',
            ],
            'senDetails' => [
                'label'       => __('SEN Details'),
                'description' => __('Provide any comments or information concerning your child\'s development and SEN history.'),
                'required'    => 'Y',
                'columns'     => 2,
                'conditional' => ['sen' => 'Y'],
            ],
        ];

        $params = ['applicationForm' => 1];
        $customFields = $this->customFieldGateway->selectCustomFields('Individual Needs', [])->fetchAll();

        if (!empty($customFields)) {
            $this->fields['headingIndividualNeeds'] = [
                'label'       => __('Individual Needs'),
                'type'        => 'subheading',
            ];
        }

        foreach ($customFields as $field) {
            $id = $field['gibbonCustomFieldID'];
            $this->fields[$id] = [
                'type'                => $field['type'],
                'label'               => __($field['name']),
                'description'         => __($field['description']),
                'options'             => $field['options'],
                'custom'              => 'true',
            ];
        }
        
    }

    public function getDescription() : string
    {
        return '';
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row
    {
        $required = $this->getRequired($formBuilder, $field);
        $default = $field['defaultValue'] ?? null;
        $customField = $this->fields[$field['fieldName']]['custom'] ?? false;

        $row = $form->addRow();

        if ($customField) {
            $row->addLabel($field['fieldName'], __($field['label']))->description(__($field['description']));
            $row->addCustomField($field['fieldName'], $field);

            return $row;
        }

        switch ($field['fieldName']) {
            case 'sen':
                $row->addLabel('sen', __($field['label']))->description(__($field['description']));
                $row->addYesNo('sen')->required($required)->selected($default)->placeholder();
                break;

            case 'senDetails':
                $form->toggleVisibilityByClass('senDetailsRow')->onSelect('sen')->when('Y');
                $col = $row->setClass('senDetailsRow')->addColumn();
                    $col->addLabel('senDetails', __($field['label']))->description(__($field['description']));
                    $col->addTextArea('senDetails')->setRows(5)->required($required)->setValue($default)->setClass('w-full');
                break;
        }

        return $row;
    }

    public function getFieldDataFromPOST(string $fieldName, array $field)  
    {
        $customField = $this->fields[$fieldName]['custom'] ?? false;
        
        return $customField
            ? $this->customFieldHandler->getFieldValueFromPOST($fieldName, $field['fieldType'])
            : parent::getFieldDataFromPOST($fieldName, $field);
    }
}
