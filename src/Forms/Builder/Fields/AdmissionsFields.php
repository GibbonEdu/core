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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\LanguageGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;

class AdmissionsFields extends AbstractFieldGroup
{
    protected $settingGateway;
    protected $schoolYearGateway;
    protected $yearGroupGateway;
    protected $languageGateway;

    public function __construct(SettingGateway $settingGateway, SchoolYearGateway $schoolYearGateway, YearGroupGateway $yearGroupGateway, LanguageGateway $languageGateway)
    {
        $this->settingGateway = $settingGateway;
        $this->schoolYearGateway = $schoolYearGateway;
        $this->yearGroupGateway = $yearGroupGateway;
        $this->languageGateway = $languageGateway;

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
            ],
            'referenceEmail' => [
                'label'       => __('Current School Reference Email'),
                'description' => __('An email address for a referee at the applicant\'s current school.'),
                'required'    => 'Y',
            ],
            'previousSchools' => [
                'label'       => __('Previous Schools'),
                'description' => __('Please give information on the last two schools attended by the applicant.'),
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('');
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row
    {
        $required = $field['required'] != 'N';
        
        $row = $form->addRow();

        switch ($field['fieldName']) {
            case 'agreement':
                $row->addLabel($field['fieldName'], __($field['label']))->description(__($field['description']));
                $row->addCheckbox($field['fieldName'])->description(__('Yes'))->setValue('on')->required($required);
                break;

            case 'gibbonSchoolYearIDEntry':
                $years = $formBuilder->getConfig('enableLimitedYearsOfEntry') == 'Y' && $formBuilder->hasConfig('availableYearsOfEntry')
                    ? $this->schoolYearGateway->getSchoolYearsFromList($formBuilder->getConfig('availableYearsOfEntry'))
                    : $this->schoolYearGateway->getSchoolYearList(true);

                $row->addLabel('gibbonSchoolYearIDEntry', __($field['label']))->description(__($field['description']));
                $row->addSelect('gibbonSchoolYearIDEntry')->fromArray($years)->required($required)->placeholder(__('Please select...'));
                break;

            case 'dateStart':
                $row->addLabel('dateStart', __($field['label']))->description(__($field['description']));
                $row->addDate('dateStart')->required($required);
                break;

            case 'gibbonYearGroupIDEntry':
                $yearGroups = $this->yearGroupGateway->selectYearGroupsByIDs($formBuilder->getDetail('gibbonYearGroupIDList'))->fetchKeyPair();
                $row->addLabel('gibbonYearGroupIDEntry', __($field['label']))->description(__($field['description']));
                $yearGroups = $row->addSelect('gibbonYearGroupIDEntry')->fromArray($yearGroups)->required($required)->placeholder(__('Please select...'));
                break;
                
            case 'gibbonFormGroupIDEntry':
                $row->addLabel('gibbonFormGroupIDEntry', __($field['label']))->description(__($field['description']));
                $row->addSelectFormGroup('gibbonFormGroupIDEntry', $formBuilder->getConfig('gibbonSchoolYearID', ''))->required($required)->placeholder($required ? __('Please select...') : '');
                break;

            case 'dayType':
                $dayTypeOptions = $this->settingGateway->getSettingByScope('User Admin', 'dayTypeOptions');
                if (!empty($dayTypeOptions)) {
                    $row->addLabel('dayType', __($field['label']))->description(__($field['description']));
                    $row->addSelect('dayType')->fromString($dayTypeOptions)->required($required);
                }
                break;

            case 'referenceEmail':
                $row->addLabel('referenceEmail', __($field['label']))->description(__($field['description']));
                $row->addEmail('referenceEmail')->required($required);
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
        }

        return $row;
    }
}
