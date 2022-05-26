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

use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;

class ParentFields extends AbstractFieldGroup
{
    protected $userGateway;

    public function __construct(UserGateway $userGateway)
    {
        $this->userGateway = $userGateway;

        $this->fields = [
            'headingParent1PersonalData' => [
                'label' => __('Parent 1 Personal Data'),
                'type'  => 'subheading',
            ],
            'parent1surname' => [
                'label'    => __('Parent 1 Surname'),
                'type'     => 'varchar',
                'required' => 'Y',
                'prefill'  => 'Y',
            ],
            'parent1preferredName' => [
                'label'    => __('Parent 1 Preferred Name'),
                'type'     => 'varchar',
                'required' => 'Y',
                'prefill'  => 'Y',
            ],
            'parent1relationship' => [
                'label'    => __('Parent 1 Relationship'),
                'type'     => 'varchar',
                'required' => 'Y',
                'prefill'  => 'Y',
            ],
            'parent1email' => [
                'label'    => __('Parent 1 Email'),
                'type'     => 'email',
                'required' => 'Y',
                'prefill'  => 'Y',
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('Parent fields enable the creation of parent users once an application has been accepted.');
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row
    {
        $required = $field['required'] != 'N';

        $row = $form->addRow();

        // PARENT 1: Already logged in, record gibbonPersonID
        if ($formBuilder->hasConfig('gibbonPersonID') && $field['fieldName'] != 'parent1relationship') {

            if ($field['fieldName'] == 'parent1surname') {
                $parent = $this->userGateway->getByID($formBuilder->getConfig('gibbonPersonID'));

                $form->addHiddenValue('parent1email', $parent['email'] ?? '');
                $form->addHiddenValue('parent1gibbonPersonID', $parent['gibbonPersonID'] ?? '');

                $row->addLabel('parent1username', __('Username'))->description(__('System login ID.'));
                $row->addTextField('parent1username')->setValue($parent['username'] ?? '')->maxLength(30)->readOnly();

                $row = $form->addRow();
                $row->addLabel('parent1surname', __('Surname'))->description(__('Family name as shown in ID documents.'));
                $row->addTextField('parent1surname')->setValue($parent['surname'] ?? '')->maxLength(30)->readOnly();

                $row = $form->addRow();
                $row->addLabel('parent1preferredName', __('Preferred Name'))->description(__('Most common name, alias, nickname, etc.'));
                $row->addTextField('parent1preferredName')->setValue($parent['preferredName'] ?? '')->maxLength(30)->readOnly();
                    
            } else {
                $row->addClass('hidden');
            }
            
            return $row;
        }

        switch ($field['fieldName']) {
            // PARENT 1 PERSONAL DATA
            case 'parent1surname':
                $row->addLabel('parent1surname', __($field['label']))->description(__($field['description']));
                $row->addTextField('parent1surname')->required($required)->maxLength(60);
                break;

            case 'parent1firstName':
                $row->addLabel('parent1firstName', __($field['label']))->description(__($field['description']));
                $row->addTextField('parent1firstName')->required($required)->maxLength(60);
                break;

            case 'parent1preferredName':
                $row->addLabel('parent1preferredName', __($field['label']))->description(__($field['description']));
                $row->addTextField('parent1preferredName')->required($required)->maxLength(60);
                break;

            case 'parent1officialName':
                $row->addLabel('parent1officialName', __($field['label']))->description(__($field['description']));
                $row->addTextField('parent1officialName')->required($required)->maxLength(150)->setTitle(__('Please enter full name as shown in ID documents'));
                break;


            // PARENT1 CONTACT
            case 'parent1email':
                $row->addLabel('parent1email', __($field['label']))->description(__($field['description']));
                $email = $row->addEmail('parent1email')->required($required);
                break;

            // PARENT1 OTHER
            case 'parent1relationship':
                $row->addLabel('parent1relationship', __('Relationship'));
                $row->addSelectRelationship('parent1relationship')->required();
                break;
        }

        return $row;
    }

    public function shouldValidate(FormBuilderInterface $formBuilder, $fieldName)
    {
        if ($formBuilder->hasConfig('gibbonPersonID') && $fieldName != 'parent1relationship') return false;
        
        return true;
    }
}
