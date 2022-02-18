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

class FamilyFields extends AbstractFieldGroup
{
    public function __construct()
    {
        $this->fields = [
            'headingHomeAddress' => [
                'label'       => __('Home Address'),
                'description' => __('This address will be used for all members of the family. If an individual within the family needs a different address, this can be set through Data Updater after admission.'),
                'type'        => 'heading'
            ],
            'nameAddress' => [
                'label'       => __('Address Name'),
                'description' => __('Formal name to address parents with.'),
                'required'    => 'Y',
            ],
            'homeAddress' => [
                'label'       => __('Home Address'),
                'description' => __('Unit, Building, Street'),
                'required'    => 'Y',
            ],
            'homeAddressDistrict' => [
                'label'       => __('Home Address (District)'),
                'description' => __('County, State, District'),
                'required'    => 'Y',
            ],
            'homeAddressCountry' => [
                'label'       => __('Home Address (Country)'),
                'required'    => 'Y',
            ],
            'headingFamilyDetails' => [
                'label'       => __('Family Details'),
                'type'        => 'heading'
            ],
            'languageHomePrimary' => [
                'label'       => __('Home Language - Primary'),
                'description' => __('The primary language used in the student\'s home.'),
                'required'    => 'Y',
            ],
            'languageHomeSecondary' => [
                'label'       => __('Home Language - Secondary'),
            ],
            'familyStatus' => [
                'label'       => __('Marital Status'),
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('Family fields enable the creation of a family record in Gibbon once an application has been accepted. Students and parents will be automatically attached to the new family.');
    }

    public function addFieldToForm(Form $form, array $field) : Row
    {
        $required = $field['required'] != 'N';

        $row = $form->addRow();

        switch ($field['fieldName']) {
            case 'nameAddress':
                $row->addLabel('nameAddress', __($field['label']))->description(__($field['description']));
                $row->addTextField('nameAddress')->required($required);
                break;

            case 'homeAddress':
                $row->addLabel('homeAddress', __($field['label']))->description(__($field['description']));
                $row->addTextArea('homeAddress')->required($required)->maxLength(255)->setRows(2);
                break;

            case 'homeAddressDistrict':
                $row->addLabel('homeAddressDistrict', __($field['label']))->description(__($field['description']));
                $row->addTextFieldDistrict('homeAddressDistrict')->required($required);
                break;

            case 'homeAddressCountry':
                $row->addLabel('homeAddressCountry', __($field['label']))->description(__($field['description']));
                $row->addSelectCountry('homeAddressCountry')->required($required);
                break;

            case 'languageHomePrimary':
                $row->addLabel('languageHomePrimary', __($field['label']))->description(__($field['description']));
                $row->addSelectLanguage('languageHomePrimary')->required($required)->placeholder();
                break;
        
            case 'languageHomeSecondary':
                $row->addLabel('languageHomeSecondary', __($field['label']))->description(__($field['description']));
                $row->addSelectLanguage('languageHomeSecondary')->required($required)->placeholder();
                break;

            case 'familyStatus':
                $row->addLabel('familyStatus', __($field['label']))->description(__($field['description']));
                $row->addSelectMaritalStatus('familyStatus')->required($required);
                break;
        }

        return $row;
    }
}
