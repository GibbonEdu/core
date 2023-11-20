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

use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Domain\System\SettingGateway;

class Parent2Fields extends AbstractFieldGroup
{
    protected $userGateway;
    protected $uniqueEmailAddress;

    public function __construct(SettingGateway $settingGateway, UserGateway $userGateway)
    {
        $this->uniqueEmailAddress = $settingGateway->getSettingByScope('User Admin', 'uniqueEmailAddress');
        $this->userGateway = $userGateway;

        $this->fields = [
            'headingParentGuardian2' => [
                'label' => __('Parent/Guardian')." 2",
                'type'  => 'heading',
                'options' => 'familySection',
            ],
            'secondParent' => [
                'label' => __('Do not include a second parent/guardian'),
                'type'  => 'checkbox',
                'prefill'  => 'Y',
            ],
            'headingParentGuardian2PersonalData' => [
                'label' => __('Parent/Guardian')." 2 ".__('Personal Data'),
                'type'  => 'subheading',
                'options' => 'parentSection2',
            ],
            'parent2title' => [
                'label'    => __('Title'),
                'required' => 'Y',
                'prefill'  => 'Y',
                'translate' => 'Y',
            ],
            'parent2surname' => [
                'label'       => __('Surname'),
                'description' => __('Family name as shown in ID documents.'),
                'required'    => 'X',
                'prefill'     => 'Y',
            ],
            'parent2firstName' => [
                'label'       => __('First Name'),
                'description' => __('First name as shown in ID documents.'),
                'required'    => 'Y',
                'prefill'     => 'Y',
            ],
            'parent2preferredName' => [
                'label'       => __('Preferred Name'),
                'description' => __('Most common name, alias, nickname, etc.'),
                'required'    => 'X',
                'prefill'     => 'Y',
            ],
            'parent2officialName' => [
                'label'       => __('Official Name'),
                'description' => __('Full name as shown in ID documents.'),
                'required'    => 'Y',
                'prefill'     => 'Y',
            ],
            'parent2nameInCharacters' => [
                'label'    => __('Name In Characters'),
                'description' => __('Chinese or other character-based name.'),
                'prefill'  => 'Y',
            ],
            'parent2gender' => [
                'label' => __('Gender'),
                'required' => 'Y',
                'prefill'  => 'Y',
                'type'     => 'gender',
            ],
            'parent2relationship' => [
                'label'    => __('Relationship'),
                'required' => 'Y',
                'prefill'  => 'Y',
                'translate' => 'Y',
            ],
            'headingParentGuardian2PersonalBackground' => [
                'label'   => __('Parent/Guardian')." 2 ".__('Personal Background'),
                'type'    => 'subheading',
                'options' => 'parentSection2',
            ],
            'parent2languageFirst' => [
                'label' => __('First Language'),
                'description' => __('Student\'s native/first/mother language.'),
                'prefill'  => 'Y',
                'translate' => 'Y',
            ],
            'parent2languageSecond' => [
                'label' => __('Second Language'),
                'prefill'  => 'Y',
                'translate' => 'Y',
            ],
            'headingParentGuardian2Contact' => [
                'label'   => __('Parent/Guardian')." 2 ".__('Contact'),
                'type'    => 'subheading',
                'options' => 'parentSection2',
            ],
            'parent2email' => [
                'label'    => __('Email'),
                'type'     => 'email',
                'prefill'  => 'Y',
            ],
            'parent2phone' => [
                'label'       => __('Phone'),
                'description' => __('Type, country code, number.'),
                'type'        => 'phone',
                'prefill'     => 'Y',
                'acquire'     => ['parent2phone1' => 'varchar', 'parent2phone1Type' => 'varchar', 'parent2phone1CountryCode' => 'varchar','parent2phone2' => 'varchar', 'parent2phone2Type' => 'varchar', 'parent2phone2CountryCode' => 'varchar'],
            ],
            'headingParentGuardian2Employment' => [
                'label'   => __('Parent/Guardian')." 2 ".__('Employment'),
                'type'    => 'subheading',
                'options' => 'parentSection2',
            ],
            'parent2profession' => [
                'label' => __('Profession'),
                'prefill'  => 'Y',
            ],
            'parent2employer' => [
                'label' => __('Employer'),
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
        $required = $this->getRequired($formBuilder, $field);
        $default = $field['defaultValue'] ?? null;

        $row = $form->addRow()->setClass("parentSection2");

        // PARENT 2: Already logged in, record gibbonPersonID
        if ($formBuilder->hasConfig('gibbonPersonID')) {
            if ($field['fieldName'] == 'parent2surname') {
                $form->toggleVisibilityByClass('parentSection2')->onCheckbox('secondParent')->when('Yes');
            }

            $row->addClass('hidden');
            return $row;
           
        }

        switch ($field['fieldName']) {
            // PARENT 2 PERSONAL DATA
            case 'secondParent':
                $checked = $formBuilder->hasConfig('gibbonPersonID') ? 'No' : ($default == 'N' || $default == 'No' ? 'No' : 'Yes');
                $row->setClass('')->addCheckbox('secondParent')->setValue('No')->alignRight()->checked($checked)->description(__($field['label']));
                $form->toggleVisibilityByClass('parentSection2')->onCheckbox('secondParent')->whenNot('No');

                break;
            case 'parent2title':
                $row->addLabel('parent2title', __($field['label']))->description(__($field['description']));
                $row->addSelectTitle('parent2title')->required($required)->selected($default);
                break;

            case 'parent2surname':
                $row->addLabel('parent2surname', __($field['label']))->description(__($field['description']));
                $row->addTextField('parent2surname')->required($required)->setValue($default)->maxLength(60);
                break;

            case 'parent2firstName':
                $row->addLabel('parent2firstName', __($field['label']))->description(__($field['description']));
                $row->addTextField('parent2firstName')->required($required)->setValue($default)->maxLength(60);
                break;

            case 'parent2preferredName':
                $row->addLabel('parent2preferredName', __($field['label']))->description(__($field['description']));
                $row->addTextField('parent2preferredName')->required($required)->setValue($default)->maxLength(60);
                break;

            case 'parent2officialName':
                $row->addLabel('parent2officialName', __($field['label']))->description(__($field['description']));
                $row->addTextField('parent2officialName')->required($required)->setValue($default)->maxLength(150)->setTitle(__('Please enter full name as shown in ID documents'));
                break;

            case 'parent2nameInCharacters':
                $row->addLabel('parent2nameInCharacters', __($field['label']))->description(__($field['description']));
                $row->addTextField('parent2nameInCharacters')->required($required)->setValue($default)->maxLength(60);
                break;

            case 'parent2gender':
                $row->addLabel('parent2gender', __($field['label']))->description(__($field['description']));
                $row->addSelectGender('parent2gender')->required($required)->selected($default);
                break;

            case 'parent2relationship':
                $row->addLabel('parent2relationship', __($field['label']))->description(__($field['description']));
                $row->addSelectRelationship('parent2relationship')->required($required)->selected($default);
                break;

            // PARENT1 BACKGROUND
            case 'parent2languageFirst':
                $row->addLabel('parent2languageFirst', __($field['label']))->description(__($field['description']));
                $row->addSelectLanguage('parent2languageFirst')->required($required)->selected($default);
                break;
        
            case 'parent2languageSecond':
                $row->addLabel('parent2languageSecond', __($field['label']))->description(__($field['description']));
                $row->addSelectLanguage('parent2languageSecond')->placeholder('')->required($required)->selected($default);
                break;

            // PARENT1 CONTACT
            case 'parent2email':
                $row->addLabel('parent2email', __($field['label']))->description(__($field['description']));
                $email = $row->addEmail('parent2email')->required($required)->setValue($default);
                if ($this->uniqueEmailAddress == 'Y') {
                    $email->uniqueField('./publicRegistrationCheck.php', ['fieldName' => 'email']);
                }
                break;

            case 'parent2phone':
                $colGroup = $row->addColumn()->setClass('flex-col w-full justify-between items-start');
                $phoneCount = $field['options'] ?? 2;
                for ($i = 1; $i <= $phoneCount; ++$i) {
                    $col = $colGroup->addColumn()->setClass('flex flex-row justify-between');
                    $col->addLabel('parent2phone'.$i, __('Phone').' '.$i)->description(__($field['description']));
                    $col->addPhoneNumber('parent2phone'.$i)->required($required && $i == 1);
                }
                break;

            // PARENT1 EMPLOYMENT
            case 'parent2profession':
                $row->addLabel('parent2profession',__($field['label']))->description(__($field['description']));
                $row->addTextField('parent2profession')->maxLength(90)->required($required)->setValue($default);
                break;

            case 'parent2employer':
                $row->addLabel('parent2employer',__($field['label']))->description(__($field['description']));
                $row->addTextField('parent2employer')->maxLength(90)->required($required)->setValue($default);
                break;
        }

        return $row;
    }

    public function shouldValidate(FormBuilderInterface $formBuilder, array &$data, string $fieldName)
    {
        if (empty($data['secondParent']) || $data['secondParent'] == 'No') return false;
        
        return true;
    }
}
