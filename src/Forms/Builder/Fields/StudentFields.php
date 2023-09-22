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
                $colGroup = $row->addColumn()->setClass('flex-col w-full justify-between items-start');
                $phoneCount = $field['options'] ?? 2;
                for ($i = 1; $i <= $phoneCount; ++$i) {
                    $col = $colGroup->addColumn()->setClass('flex flex-row justify-between');
                    $col->addLabel('phone'.$i, __('Phone').' '.$i)->description(__($field['description']));
                    $col->addPhoneNumber('phone'.$i)->required($required && $i == 1);
                }
                break;
        }

        return $row;
    }
}
