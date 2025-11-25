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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;

class StudentFields extends AbstractFieldGroup
{
    protected $uniqueEmailAddress;

    public function __construct(SettingGateway $settingGateway)
    {
        $this->uniqueEmailAddress = $settingGateway->getSettingByScope('User Admin', 'uniqueEmailAddress');

        $this->fields = [
            'headingStudent' => [
                'label' => __('Student'),
                'type'  => 'heading',
            ],
            'headingStudentPersonalData' => [
                'label' => __('Student Personal Data'),
                'type' => 'subheading',
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
                'type'     => 'gender',
            ],
            'dob' => [
                'label' => __('Date of Birth'),
                'required' => 'Y',
                'type' => 'date',
            ],
            'headingStudentBackground' => [
                'label' => __('Student Background'),
                'type' => 'subheading',
            ],
            'languageFirst' => [
                'label' => __('First Language'),
                'description' => __('Student\'s native/first/mother language.'),
                'required' => 'Y',
                'translate' => 'Y',
            ],
            'languageSecond' => [
                'label' => __('Second Language'),
                'translate' => 'Y',
            ],
            'languageThird' => [
                'label' => __('Third Language'),
                'translate' => 'Y',
            ],
            'countryOfBirth' => [
                'label' => __('Country of Birth'),
                'required' => 'Y',
                'translate' => 'Y',
            ],
            'headingStudentContact' => [
                'label' => __('Student Contact'),
                'type' => 'subheading',
            ],
            'email' => [
                'label' => __('Email'),
            ],
            'phone' => [
                'label'       => __('Phone'),
                'description' => __('Type, country code, number.'),
                'type'        => 'phone',
                'acquire'     => ['phone1' => 'varchar', 'phone1Type' => 'varchar', 'phone1CountryCode' => 'varchar','phone2' => 'varchar', 'phone2Type' => 'varchar', 'phone2CountryCode' => 'varchar'],
            ],
            'headingEmergencyContacts' => [
                'label' => __('Emergency Contacts'),
                'type' => 'subheading',
                'description' => __('These details are used when immediate family members (e.g. parent, spouse) cannot be reached first. Please try to avoid listing immediate family members.'),
            ],
            'emergency1Name' => [
                'label' => __('Contact 1 Name'),
            ],
            'emergency1Relationship' => [
                'label' => __('Contact 1 Relationship'),
            ],
            'emergency1Number1' => [
                'label' => __('Contact 1 Number 1'),
            ],
            'emergency1Number2' => [
                'label' => __('Contact 1 Number 2'),
            ],
            'emergency2Name' => [
                'label' => __('Contact 2 Name'),
            ],
            'emergency2Relationship' => [
                'label' => __('Contact 2 Relationship'),
            ],
            'emergency2Number1' => [
                'label' => __('Contact 2 Number 1'),
            ],
            'emergency2Number2' => [
                'label' => __('Contact 2 Number 2'),
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('Student fields are attached to a student\'s user data once an application has been accepted.');
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row
    {
        $row = $form->addRow();

        $required = $this->getRequired($formBuilder, $field);
        $default = $field['defaultValue'] ?? null;

        switch ($field['fieldName']) {
            // STUDENT PERSONAL DATA
            case 'surname':
                $row->addLabel('surname', __($field['label']))->description(__($field['description']));
                $row->addTextField('surname')->required($required)->setValue($default)->maxLength(60);
                break;

            case 'firstName':
                $row->addLabel('firstName', __($field['label']))->description(__($field['description']));
                $row->addTextField('firstName')->required($required)->setValue($default)->maxLength(60);
                break;

            case 'preferredName':
                $row->addLabel('preferredName', __($field['label']))->description(__($field['description']));
                $row->addTextField('preferredName')->required($required)->setValue($default)->maxLength(60);
                break;

            case 'officialName':
                $row->addLabel('officialName', __($field['label']))->description(__($field['description']));
                $row->addTextField('officialName')->required($required)->setValue($default)->maxLength(150)->setTitle(__('Please enter full name as shown in ID documents'));
                break;

            case 'nameInCharacters':
                $row->addLabel('nameInCharacters', __($field['label']))->description(__($field['description']));
                $row->addTextField('nameInCharacters')->required($required)->setValue($default)->maxLength(60);
                break;

            case 'gender':
                $row->addLabel('gender', __($field['label']))->description(__($field['description']));
                $row->addSelectGender('gender')->required($required)->selected($default);
                break;

            case 'dob':
                $row->addLabel('dob', __($field['label']))->description(__($field['description']));
                $row->addDate('dob')->required($required)->setValue($default);
                break;

            // STUDENT BACKGROUND
            case 'languageHomePrimary':
                $row->addLabel('languageHomePrimary', __($field['label']))->description(__($field['description']));
                $row->addSelectLanguage('languageHomePrimary')->required($required)->selected($default);
                break;
        
            case 'languageHomeSecondary':
                $row->addLabel('languageHomeSecondary', __($field['label']))->description(__($field['description']));
                $row->addSelectLanguage('languageHomeSecondary')->placeholder('')->required($required)->selected($default);
                break;
        
            case 'languageFirst':
                $row->addLabel('languageFirst', __($field['label']))->description(__($field['description']));
                $row->addSelectLanguage('languageFirst')->required($required)->selected($default);
                break;
        
            case 'languageSecond':
                $row->addLabel('languageSecond', __($field['label']))->description(__($field['description']));
                $row->addSelectLanguage('languageSecond')->placeholder('')->required($required)->selected($default);
                break;
        
            case 'languageThird':
                $row->addLabel('languageThird', __($field['label']))->description(__($field['description']));
                $row->addSelectLanguage('languageThird')->placeholder('')->required($required)->selected($default);
                break;
        
            case 'countryOfBirth':
                $row->addLabel('countryOfBirth', __($field['label']))->description(__($field['description']));
                $row->addSelectCountry('countryOfBirth')->required($required)->selected($default);
                break;

            // STUDENT CONTACT
            case 'email':
                $row->addLabel('email', __($field['label']))->description(__($field['description']));
                $email = $row->addEmail('email')->required($required)->setValue($default);
                if ($this->uniqueEmailAddress == 'Y') {
                    $email->uniqueField('./publicRegistrationCheck.php');
                }
                break;

            case 'phone':
                $colGroup = $row->addColumn()->setClass('flex-col w-full justify-between items-start gap-2');
                $phoneCount = $field['options'] ?? 2;
                for ($i = 1; $i <= $phoneCount; ++$i) {
                    $col = $colGroup->addColumn()->setClass('flex flex-col sm:flex-row content-center p-0 gap-2 sm:gap-4 justify-between sm:items-start');
                    $col->addLabel('phone'.$i, __('Phone').' '.$i)->description(__($field['description']))->addClass('sm:w-2/5');
                    $col->addPhoneNumber('phone'.$i)->required($required && $i == 1)->addClass('flex-1');
                }
                break;

            // EMERGENCY CONTACTS
            case 'emergency1Name':
                $row->addLabel('emergency1Name', __('Contact 1 Name'));
                $row->addTextField('emergency1Name')->maxLength(90);
                break;
            case 'emergency1Relationship':
                $row->addLabel('emergency1Relationship', __('Contact 1 Relationship'));
                $row->addSelectEmergencyRelationship('emergency1Relationship');
                break;
            case 'emergency1Number1':
                $row->addLabel('emergency1Number1', __('Contact 1 Number 1'));
                $row->addTextField('emergency1Number1')->maxLength(30);
                break;
            case 'emergency1Number2':
                $row->addLabel('emergency1Number2', __('Contact 1 Number 2'));
                $row->addTextField('emergency1Number2')->maxLength(30);
                break;
            case 'emergency2Name':
                $row->addLabel('emergency2Name', __('Contact 2 Name'));
                $row->addTextField('emergency2Name')->maxLength(90);
                break;
            case 'emergency2Relationship':
                $row->addLabel('emergency2Relationship', __('Contact 2 Relationship'));
                $row->addSelectEmergencyRelationship('emergency2Relationship');
                break;
            case 'emergency2Number1':
                $row->addLabel('emergency2Number1', __('Contact 2 Number 1'));
                $row->addTextField('emergency2Number1')->maxLength(30);
                break;
            case 'emergency2Number2':
                $row->addLabel('emergency2Number2', __('Contact 2 Number 2'));
                $row->addTextField('emergency2Number2')->maxLength(30);
                break;
        }

        return $row;
    }
    
}
