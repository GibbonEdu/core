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
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Builder\AbstractFieldGroup;

class StudentFields extends AbstractFieldGroup
{
    public function __construct()
    {
        $this->fields = [
            'heading1' => [
                'label' => __('Student Personal Data'),
                'type' => 'optgroup',
            ],
            'surname' => [
                'label' => __('Surname'),
                'description' => __('Family name as shown in ID documents.'),
                'required' => 'X',
            ],
            'firstName' => [
                'label' => __('First Name'),
                'description' => __('First name as shown in ID documents.'),
                'required' => 'X',
            ],
            'preferredName' => [
                'label' => __('Preferred Name'),
                'description' => __('Most common name, alias, nickname, etc.'),
                'required' => 'Y',
            ],
            'officialName' => [
                'label' => __('Official Name'),
                'description' => __('Full name as shown in ID documents.'),
                'required' => 'Y',
            ],
            'nameInCharacters' => [
                'label' => __('Name In Characters'),
                'description' => __('Chinese or other character-based name.'),
            ],
            'gender' => [
                'label' => __('Gender'),
                'required' => 'Y',
            ],
            'dob' => [
                'label' => __('Date of Birth'),
                'required' => 'Y',
            ],
            'heading2' => [
                'label' => __('Student Background'),
                'type' => 'optgroup',
            ],
            'languageHomePrimary' => [
                'label' => __('Home Language - Primary'),
                'description' => __('The primary language used in the student\'s home.'),
                'required' => 'Y',
            ],
            'languageHomeSecondary' => [
                'label' => __('Home Language - Secondary'),
            ],
            'heading3' => [
                'label' => __('Student Contact'),
                'type' => 'optgroup',
            ],
            'email' => [
                'label' => __('Email'),
                'required' => 'N',
            ],
            // 'thing' => [
            //     'label' => __('Label'),
            //     'description' => __('Description'),
            //     'required' => 'Y',
            // ],
        ];
    }

    public function getDescription() : string
    {
        return __('Student fields are attached to a student\'s user data once an application has been accepted.');
    }

    public function addFieldToForm(Form $form, array $field) : Row
    {
        $row = $form->addRow();

        $required = $field['required'] != 'N';

        switch ($field['fieldName']) {
            // STUDENT PERSONAL DATA
            case 'surname':
                $row->addLabel('surname', __($field['label']))->description(__($field['description']));
                $row->addTextField('surname')->required($required)->maxLength(60);
                break;

            case 'firstName':
                $row->addLabel('firstName', __($field['label']))->description(__($field['description']));
                $row->addTextField('firstName')->required($required)->maxLength(60);
                break;

            case 'preferredName':
                $row->addLabel('preferredName', __($field['label']))->description(__($field['description']));
                $row->addTextField('preferredName')->required($required)->maxLength(60);
                break;

            case 'officialName':
                $row->addLabel('officialName', __($field['label']))->description(__($field['description']));
                $row->addTextField('officialName')->required($required)->maxLength(150)->setTitle(__('Please enter full name as shown in ID documents'));

            case 'nameInCharacters':
                $row->addLabel('nameInCharacters', __($field['label']))->description(__($field['description']));
                $row->addTextField('nameInCharacters')->maxLength(60);

            case 'gender':
                $row->addLabel('gender', __($field['label']))->description(__($field['description']));
                $row->addSelectGender('gender')->required($required);
                break;

            case 'dob':
                $row->addLabel('dob', __('Date of Birth'));
                $row->addDate('dob')->required($required);
                break;

            // STUDENT BACKGROUND
            case 'languageHomePrimary':
                $row->addLabel('languageHomePrimary', __('Home Language - Primary'))->description(__('The primary language used in the student\'s home.'));
                $row->addSelectLanguage('languageHomePrimary')->required($required)->placeholder();
                break;
        
            case 'languageHomeSecondary':
                $row->addLabel('languageHomeSecondary', __('Home Language - Secondary'));
                $row->addSelectLanguage('languageHomeSecondary')->required($required)->placeholder();
                break;

            // STUDENT CONTACT
            case 'email':
                $row->addLabel('email', __('Email'));
                $email = $row->addEmail('email')->required($required);
                break;
        }

        return $row;
    }
}
