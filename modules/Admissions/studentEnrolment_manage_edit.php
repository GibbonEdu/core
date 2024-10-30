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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\Timetable\CourseSyncGateway;
use Gibbon\Forms\CustomFieldHandler;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/studentEnrolment_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonStudentEnrolmentID = $_GET['gibbonStudentEnrolmentID'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Student Enrolment'), 'studentEnrolment_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Edit Student Enrolment'));

    //Check if gibbonStudentEnrolmentID and gibbonSchoolYearID specified
    if ($gibbonStudentEnrolmentID == '' or $gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

        $studentGateway = $container->get(StudentGateway::class);
        $enrollment = $studentGateway->getByID($gibbonStudentEnrolmentID);
        if (empty($enrollment)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        $values = $container->get(StudentGateway::class)->selectActiveStudentByPerson($gibbonSchoolYearID, $enrollment['gibbonPersonID'], false)->fetch();
        if (empty($values)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        if ($search != '') {
             $params = [
                "search" => $search,
                "gibbonSchoolYearID" => $gibbonSchoolYearID
            ];
            $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Admissions', 'studentEnrolment_manage.php')->withQueryParams($params));
        }

        $form = Form::create('studentEnrolmentAdd', $session->get('absoluteURL').'/modules/'.$session->get('module')."/studentEnrolment_manage_editProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonStudentEnrolmentID', $gibbonStudentEnrolmentID);
        $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);
        $form->addHiddenValue('gibbonFormGroupIDOriginal', $values['gibbonFormGroupID']);
        $form->addHiddenValue('formGroupOriginalNameShort', $values['formGroup']);

        $schoolYear = $container->get(SchoolYearGateway::class)->getByID($gibbonSchoolYearID, ['name']);
        $schoolYearName = $schoolYear['name'] ?? $session->get('gibbonSchoolYearName');

        $row = $form->addRow()->addHeading('Basic Information', __('Basic Information'));

        $row = $form->addRow();
            $row->addLabel('yearName', __('School Year'));
            $row->addTextField('yearName')->readOnly()->maxLength(20)->setValue($schoolYearName);

        $row = $form->addRow();
            $row->addLabel('studentName', __('Student'));
            $row->addTextField('studentName')->readOnly()->setValue(Format::name('', $values['preferredName'], $values['surname'], 'Student', true));

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->required();

        $row = $form->addRow();
            $row->addLabel('gibbonFormGroupID', __('Form Group'));
            $row->addSelectFormGroup('gibbonFormGroupID', $gibbonSchoolYearID)->required();

        $row = $form->addRow();
            $row->addLabel('rollOrder', __('Roll Order'));
            $row->addNumber('rollOrder')->maxLength(2)->setValue($enrollment['rollOrder']);

        // Check to see if any class mappings exists -- otherwise this feature is inactive, hide it
        $classMapCount = $container->get(CourseSyncGateway::class)->countAll();
        if ($classMapCount > 0) {
            $autoEnrolDefault = $container->get(SettingGateway::class)->getSettingByScope('Timetable Admin', 'autoEnrolCourses');
            $row = $form->addRow();
                $row->addLabel('autoEnrolStudent', __('Auto-Enrol Courses?'))
                    ->description(__('Should this student be automatically enrolled in courses for their Form Group?'))
                    ->description(__('This will replace any auto-enrolled courses if the student Form Group has changed.'));
                $row->addYesNo('autoEnrolStudent')->selected($autoEnrolDefault);
        }

        $schoolHistory = '';

        if ($values['dateStart'] != '') {
            $schoolHistory .= '<li><u>'.__('Start Date').'</u>: '.Format::date($values['dateStart']).'</li>';
        }

        $dataSelect = array('gibbonPersonID' => $values['gibbonPersonID']);
        $sqlSelect = 'SELECT gibbonFormGroup.name AS formGroup, gibbonSchoolYear.name AS schoolYear FROM gibbonStudentEnrolment JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonStudentEnrolment.gibbonSchoolYearID';
        $resultSelect = $pdo->executeQuery($dataSelect, $sqlSelect);

        while ($resultSelect && $rowSelect = $resultSelect->fetch()) {
            $schoolHistory .= '<li><u>'.$rowSelect['schoolYear'].'</u>: '.$rowSelect['formGroup'].'</li>';
        }

        if ($values['dateEnd'] != '') {
            $schoolHistory .= '<li><u>'.__('End Date').'</u>: '.Format::date($values['dateEnd']).'</li>';
        }

        $row = $form->addRow();
            $row->addLabel('schoolHistory', __('School History'));
            $row->addContent('<ul class="list-none w-full sm:max-w-xs text-xs m-0">'.$schoolHistory.'</ul>');

        // Custom Fields
        $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Student Enrolment', [], $values['fields']);
        
        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        $form->loadAllValuesFrom($values);

        echo $form->getOutput();

    }
}
