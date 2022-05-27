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
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Services\Format;

class FamilyFields extends AbstractFieldGroup
{
    protected $familyGateway;

    public function __construct(FamilyGateway $familyGateway)
    {
        $this->familyGateway = $familyGateway;

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
                'prefill'     => 'Y',
            ],
            'homeAddress' => [
                'label'       => __('Home Address'),
                'description' => __('Unit, Building, Street'),
                'required'    => 'X',
                'prefill'     => 'Y',
            ],
            'homeAddressDistrict' => [
                'label'       => __('Home Address (District)'),
                'description' => __('County, State, District'),
                'required'    => 'Y',
                'prefill'     => 'Y',
            ],
            'homeAddressCountry' => [
                'label'       => __('Home Address (Country)'),
                'required'    => 'Y',
                'prefill'     => 'Y',
            ],
            'headingFamilyDetails' => [
                'label'       => __('Family Details'),
                'type'        => 'heading'
            ],
            'languageHomePrimary' => [
                'label'       => __('Home Language - Primary'),
                'description' => __('The primary language used in the student\'s home.'),
                'required'    => 'Y',
                'prefill'     => 'Y',
            ],
            'languageHomeSecondary' => [
                'label'       => __('Home Language - Secondary'),
                'prefill'     => 'Y',
            ],
            'familyStatus' => [
                'label'       => __('Marital Status'),
                'prefill'     => 'Y',
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('Family fields enable the creation of a family record in Gibbon once an application has been accepted. Students and parents will be automatically attached to the new family.');
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row
    {
        $required = $field['required'] != 'N';

        $row = $form->addRow();

        // FAMILY: Already logged in, record gibbonFamilyID choice
        if ($formBuilder->hasConfig('gibbonPersonID')) {
            $gibbonPersonIDParent = str_pad($formBuilder->getConfig('gibbonPersonID'), 10, '0', STR_PAD_LEFT);
            $families = $this->familyGateway->selectFamiliesByAdult($gibbonPersonIDParent)->fetchAll();
        }

        if (!empty($families) || $formBuilder->hasConfig('gibbonFamilyID')) {
        
            if ($field['fieldName'] == 'homeAddress') {
                $col = $row->addColumn();

                $col->addLabel('gibbonFamilyID', __('Family'))->description(__('Choose the family you wish to associate this application with.').'<br/><br/>');

                $table = $col->addTable()->addClass('colorOddEven');

                $header = $table->addHeaderRow();
                $header->addContent(__('Family Name'));
                $header->addContent(__('Selected'));
                // $header->addContent(__('Relationships'));

                $firstFamily = current($families);
                $checked = $formBuilder->getConfig('gibbonFamilyID') ?? $firstFamily['gibbonFamilyID'] ?? '';
                foreach ($families as $family) {

                    $row = $table->addRow()->setClass('break');
                    $row->addContent($family['name'])->wrap('<strong>','</strong>')->addClass('shortWidth');
                    $row->addRadio('gibbonFamilyID')->fromArray(array($family['gibbonFamilyID'] => ''))->checked($checked)->required($required);

                    // if ($relationships = $this->familyGateway->selectAdultsByFamily($family['gibbonFamilyID'])->fetchAll()) {
                    //     $subTable = $row->addTable()->setClass('blank');
                    //     foreach ($relationships as $relationship) {
                    //         $selected = ($relationship['gender'] == 'F')? 'Mother' : (($relationship['gender'] == 'M')? 'Father' : '');

                    //         $subTableRow = $subTable->addRow()->addClass('right');
                    //         $subTableRow->addContent(Format::name($relationship['title'], $relationship['preferredName'], $relationship['surname'], 'Parent'))->setClass('mediumWidth');
                    //         $subTableRow->addSelectRelationship($family['gibbonFamilyID'].'-relationships[]')->selected($selected)->setClass('mediumWidth');
                    //         $form->addHiddenValue($family['gibbonFamilyID'].'-relationshipsGibbonPersonID[]', $relationship['gibbonPersonID']);
                    //     }
                    // }
                }
                
            } else {
                $row->addClass('hidden');
            }
            
            return $row;
        }

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

    public function shouldValidate(FormBuilderInterface $formBuilder, array &$data, string $fieldName)
    {
        if ($formBuilder->hasConfig('gibbonPersonID')) return false;

        return true;
    }
}
