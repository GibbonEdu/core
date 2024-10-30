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
use Gibbon\Domain\System\LanguageGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Domain\FormGroups\FormGroupGateway;

class AdmissionsFields extends AbstractFieldGroup
{
    protected $settingGateway;
    protected $schoolYearGateway;
    protected $yearGroupGateway;
    protected $formGroupGateway;
    protected $languageGateway;

    public function __construct(SettingGateway $settingGateway, SchoolYearGateway $schoolYearGateway, YearGroupGateway $yearGroupGateway,  FormGroupGateway $formGroupGateway, LanguageGateway $languageGateway)
    {
        $this->settingGateway = $settingGateway;
        $this->schoolYearGateway = $schoolYearGateway;
        $this->yearGroupGateway = $yearGroupGateway;
        $this->formGroupGateway = $formGroupGateway;
        $this->languageGateway = $languageGateway;
        
        $dayTypeText = $this->settingGateway->getSettingByScope('User Admin', 'dayTypeText');

        $this->fields = [
            'headingStudentEducation' => [
                'label'       => __('Student Education'),
                'type'        => 'heading',
            ],
            'gibbonSchoolYearIDEntry' => [
                'label'       => __('Anticipated Year of Entry'),
                'description' => __('What school year will the student join in?'),
                'required'    => 'X',
            ],
            'dateStart' => [
                'label'       => __('Intended Start Date'),
                'description' => __('Student\'s intended first day at school.'),
                'required'    => 'X',
                'type'        => 'date',
            ],
            'gibbonYearGroupIDEntry' => [
                'label'       => __('Year Group at Entry'),
                'description' => __('Which year level will student enter.'),
                'required'    => 'X',
            ],
            'gibbonFormGroupIDEntry' => [
                'label'       => __('Form Group at Entry'),
                'description' => __('If set, the student will automatically be enrolled on Accept.'),
                'hidden'      => 'Y',
            ],
            'dayType' => [
                'label'       => __('Day Type'),
                'description' => $dayTypeText,
                'required'    => 'Y',
            ],
            'referenceEmail' => [
                'label'       => __('Current School Reference Email'),
                'description' => __('An email address for a referee at the applicant\'s current school.'),
                'required'    => 'Y',
            ],
            'previousSchools' => [
                'label'       => __('Previous Schools'),
                'description' => __('Please give information on the last two schools attended by the applicant.'),
                'acquire'     => ['schoolName1' => 'varchar', 'schoolAddress1' => 'varchar', 'schoolGrades1' => 'varchar', 'schoolLanguage1' => 'varchar', 'schoolDate1' => 'date','schoolName2' => 'varchar', 'schoolAddress2' => 'varchar', 'schoolGrades2' => 'varchar', 'schoolLanguage2' => 'varchar', 'schoolDate2' => 'date'],
            ],
            'howDidYouHear' => [
                'label'       => __('How Did You Hear About Us?'),
                'prefill'     => 'Y',
                'acquire'     => ['howDidYouHearMore' => 'varchar'],
                'translate' => 'Y',
            ],
        ];
    }

    public function getDescription() : string
    {
        return '';
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row
    {
        $required = $this->getRequired($formBuilder, $field);
        $default = $field['defaultValue'] ?? null;
        $accepted = $formBuilder->getConfig('status') == 'Accepted';
        
        if ($field['fieldName'] == 'howDidYouHear' && ($formBuilder->hasConfig('gibbonPersonID') || $formBuilder->hasConfig('gibbonFamilyID'))) {
            return new Row($form->getFactory(), 'howDidYouHear');
        }

        $row = $form->addRow();

        switch ($field['fieldName']) {
            case 'agreement':
                $row->addLabel($field['fieldName'], __($field['label']))->description(__($field['description']));
                $row->addCheckbox($field['fieldName'])->description(__('Yes'))->setValue('on')->required($required)->checked($default);
                break;

            case 'dateStart':
                $row->addLabel('dateStart', __($field['label']))->description(__($field['description']));
                $row->addDate('dateStart')->required($required)->readonly($accepted)->setValue($default);
                break;

            case 'gibbonSchoolYearIDEntry':
                $years = $formBuilder->getConfig('enableLimitedYearsOfEntry') == 'Y' && $formBuilder->hasConfig('availableYearsOfEntry') && $formBuilder->getConfig('mode') != 'edit' && $formBuilder->getConfig('mode') != 'office'
                    ? $this->schoolYearGateway->getSchoolYearsFromList($formBuilder->getConfig('availableYearsOfEntry'))
                    : $this->schoolYearGateway->getSchoolYearList(true);

                $row->addLabel('gibbonSchoolYearIDEntry', __($field['label']))->description(__($field['description']));
                $row->addSelect('gibbonSchoolYearIDEntry')->fromArray($years)->required($required)->placeholder()->readonly($accepted)->selected($default);
                break;

            case 'gibbonYearGroupIDEntry':
                $yearGroups = $this->yearGroupGateway->selectYearGroupsByIDs($formBuilder->getDetail('gibbonYearGroupIDList'))->fetchKeyPair();
                $row->addLabel('gibbonYearGroupIDEntry', __($field['label']))->description(__($field['description']));
                $yearGroups = $row->addSelect('gibbonYearGroupIDEntry')->fromArray($yearGroups)->required($required)->placeholder()->readonly($accepted)->selected($default);
                break;
                
            case 'gibbonFormGroupIDEntry':

                $row->addLabel('gibbonFormGroupIDEntry', __($field['label']))->description(__($field['description']));

                if ($formBuilder->hasConfig('gibbonSchoolYearID')) {
                    // Handle form group selection within the Office Only, school year is provided
                    $row->addSelectFormGroup('gibbonFormGroupIDEntry', $formBuilder->getConfig('gibbonSchoolYearID', ''))
                        ->required($required)
                        ->placeholder($required ?  : '')
                        ->readonly($accepted)
                        ->selected($default);

                } else {
                    // Handle form group select in a regular application form
                    $formGroups = $this->formGroupGateway->selectFormGroups()->fetchAll();
                    $formGroupsChained = array_combine(array_column($formGroups, 'value'), array_column($formGroups, 'gibbonSchoolYearID'));
                    $formGroupsOptions = array_combine(array_column($formGroups, 'value'), array_column($formGroups, 'name'));

                    $row->addSelect('gibbonFormGroupIDEntry')
                        ->fromArray($formGroupsOptions)
                        ->chainedTo('gibbonSchoolYearIDEntry', $formGroupsChained)
                        ->required($required)
                        ->placeholder($required ?  : '')
                        ->readonly($accepted)
                        ->selected($default);
                }
                break;

            case 'dayType':
                $dayTypeOptions = $this->settingGateway->getSettingByScope('User Admin', 'dayTypeOptions');
                $row->addLabel('dayType', __($field['label']))->description(__($field['description']));
                $row->addSelect('dayType')->fromString($dayTypeOptions)->required($required)->readonly($accepted)->selected($default);

                break;

            case 'referenceEmail':
                $row->addLabel('referenceEmail', __($field['label']))->description(__($field['description']));
                $row->addEmail('referenceEmail')->required($required)->setValue($default);
                break;

            case 'previousSchools':
                $col = $row->addColumn();
                $col->addLabel('', __($field['label']))->description(__($field['description']));

                $languages = $this->languageGateway->selectLanguages()->fetchAll(\PDO::FETCH_COLUMN);
                $table = $col->addTable()->addClass('colorOddEven mt-4');

                $header = $table->addHeaderRow();
                $header->addContent(__('School Name'));
                $header->addContent(__('Address'));
                $header->addContent(sprintf(__('Grades%1$sAttended'), '<br/>'));
                $header->addContent(sprintf(__('Language of%1$sInstruction'), '<br/>'));
                $header->addContent(__('Joining Date'));

                for ($i = 1; $i < 3; ++$i) {
                    $tableRow = $table->addRow();
                    $tableRow->addTextField('schoolName'.$i)->maxLength(50)->setSize(18)->required($required);
                    $tableRow->addTextField('schoolAddress'.$i)->maxLength(255)->setSize(20);
                    $tableRow->addTextField('schoolGrades'.$i)->maxLength(20)->setSize(8);
                    $tableRow->addTextField('schoolLanguage'.$i)->autocomplete($languages)->setSize(10);
                    $tableRow->addDate('schoolDate'.$i)->setSize(10);
                }
                break;
            
            case 'howDidYouHear':
                $howDidYouHear = $this->settingGateway->getSettingByScope('Application Form', 'howDidYouHear');
                $howDidYouHearList = array_map('trim', explode(',', $howDidYouHear));

                $colGroup = $row->addColumn()->setClass('flex-col w-full justify-between items-start');

                $col = $colGroup->addColumn()->setClass('flex flex-row justify-between items-center');
                $col->addLabel('howDidYouHear', __('How Did You Hear About Us?'));

                if (empty($howDidYouHear)) {
                    $col->addTextField('howDidYouHear')->required()->maxLength(30);
                } else {
                    $col->addSelect('howDidYouHear')->fromArray($howDidYouHearList)->required()->placeholder()->selected($default);

                    $form->toggleVisibilityByClass('tellUsMore')->onSelect('howDidYouHear')->whenNot('Please select...');

                    $col = $colGroup->addColumn()->setClass('tellUsMore flex flex-row justify-between items-center');
                        $col->addLabel('howDidYouHearMore', __('Tell Us More'))->description(__('The name of a person or link to a website, etc.'));
                        $col->addTextField('howDidYouHearMore')->maxLength(255)->setClass('w-64');
                }
                
        }

        return $row;
    }

    public function displayFieldValue(FormBuilderInterface $formBuilder, string $fieldName, array $field, &$data = [])
    {
        $fieldValue = $data[$fieldName] ?? null;

        if ($fieldName == 'gibbonSchoolYearIDEntry' && !empty($fieldValue)) {
            if ($schoolYear = $this->schoolYearGateway->getByID($fieldValue, ['name'])) {
                return $schoolYear['name'];
            }
        }

        if ($fieldName == 'gibbonYearGroupIDEntry' && !empty($fieldValue)) {
            if ($yearGroup = $this->yearGroupGateway->getByID($fieldValue, ['name'])) {
                return $yearGroup['name'];
            }
        }

        if ($fieldName == 'gibbonFormGroupIDEntry' && !empty($fieldValue)) {
            if ($formGroup = $this->formGroupGateway->getByID($fieldValue, ['name'])) {
                return $formGroup['name'];
            }
        }

        return parent::displayFieldValue($formBuilder, $fieldName, $field, $data);
    }
}
