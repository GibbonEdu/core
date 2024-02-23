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

use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync_run.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Allows for a single value or a csv list of gibbonYearGroupID
    $gibbonYearGroupIDList = $_GET['gibbonYearGroupIDList'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';

    $page->breadcrumbs
        ->add(__('Sync Course Enrolment'), 'courseEnrolment_sync.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Sync Now'));

    if (empty($gibbonYearGroupIDList) || empty($gibbonSchoolYearID)) {
        $page->addError(__('Your request failed because your inputs were invalid.'));
        return;
    }

    if ($gibbonYearGroupIDList == 'all') {
        // All class mappings
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonCourseClassMap.*, gibbonYearGroup.name as gibbonYearGroupName
                FROM gibbonCourseClassMap
                JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonCourseClassMap.gibbonFormGroupID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonCourseClassMap.gibbonYearGroupID)
                WHERE gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonCourseClassMap.gibbonYearGroupID";
    } else {
        // Pull up the class mapping for this year group
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonYearGroupID' => $gibbonYearGroupIDList);
        $sql = "SELECT gibbonCourseClassMap.*, gibbonYearGroup.name as gibbonYearGroupName
                FROM gibbonCourseClassMap
                JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonCourseClassMap.gibbonFormGroupID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonCourseClassMap.gibbonYearGroupID)
                WHERE FIND_IN_SET(gibbonCourseClassMap.gibbonYearGroupID, :gibbonYearGroupID)
                AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonCourseClassMap.gibbonYearGroupID";
    }

    $result = $pdo->executeQuery($data, $sql);

    if ($result->rowCount() == 0) {
        $page->addError(__('Your request failed because your inputs were invalid.'));
        return;
    }

    $form = Form::create('courseEnrolmentSyncRun', $session->get('absoluteURL').'/modules/'.$session->get('module').'/courseEnrolment_sync_runProcess.php');
    $form->setClass('w-full blank');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonYearGroupIDList', $gibbonYearGroupIDList);
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    // Checkall options
    $row = $form->addRow()->addContent('<h4>'.__('Options').'</h4>');
    $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');

    $row = $table->addRow();
        $row->addLabel('includeStudents', __('Include Students'));
        $row->addCheckbox('includeStudents')->checked(true);
    $row = $table->addRow();
        $row->addLabel('includeTeachers', __('Include Teachers'));
        $row->addCheckbox('includeTeachers')->checked(true);

    $enrolableCount = 0;

    while ($classMap = $result->fetch()) {
        $form->addRow()->addHeading($classMap['gibbonYearGroupName']);

        $data = array(
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonYearGroupID' => $classMap['gibbonYearGroupID'],
            'date' => date('Y-m-d'),
        );

        // Grab mapped classes for all teachers & students grouped by year group, excluding those already enrolled
        $sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonFormGroup.gibbonFormGroupID, gibbonFormGroup.name as gibbonFormGroupName, GROUP_CONCAT(CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort SEPARATOR ', ') AS courseList, 'Teacher' as role
                FROM gibbonCourseClassMap
                JOIN gibbonFormGroup ON (gibbonCourseClassMap.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                JOIN gibbonPerson ON (gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID || gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID || gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID AND gibbonCourseClassPerson.role = 'Teacher')
                WHERE gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonCourseClassMap.gibbonYearGroupID=:gibbonYearGroupID
                AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date OR gibbonPerson.status='Expected')
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)
                AND gibbonCourseClassPerson.gibbonCourseClassPersonID IS NULL
                GROUP BY gibbonPerson.gibbonPersonID
            ) UNION ALL (
                SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonFormGroup.gibbonFormGroupID, gibbonFormGroup.name as gibbonFormGroupName, GROUP_CONCAT(CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort SEPARATOR ', ') AS courseList, 'Student' as role
                FROM gibbonCourseClassMap
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonCourseClassMap.gibbonYearGroupID AND gibbonStudentEnrolment.gibbonFormGroupID=gibbonCourseClassMap.gibbonFormGroupID)
                JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonFormGroup ON (gibbonCourseClassMap.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID  AND gibbonCourseClassPerson.role = 'Student')
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonCourseClassMap.gibbonYearGroupID=:gibbonYearGroupID
                AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date OR gibbonPerson.status='Expected')
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)
                AND gibbonCourseClassPerson.gibbonCourseClassPersonID IS NULL
                GROUP BY gibbonPerson.gibbonPersonID
            ) ORDER BY role DESC, surname, preferredName";

        $enrolmentResult = $pdo->executeQuery($data, $sql);

        if ($enrolmentResult->rowCount() == 0) {
            $form->addRow()->addAlert(__('Course enrolments are already synced. No changes will be made.'), 'success');
        } else {
            $enrolableCount += $enrolmentResult->rowCount();

            $table = $form->addRow()->addTable()->setClass('smallIntBorder colorOddEven fullWidth standardForm');
            $header = $table->addHeaderRow();
                $header->addCheckbox('checkall'.$classMap['gibbonYearGroupID'])->checked(true);
                $header->addContent(__('Name'));
                $header->addContent(__('Role'));
                $header->addContent(__('Form Group'));
                $header->addContent(__('Enrolment by Class'));

            while ($person = $enrolmentResult->fetch()) {
                $row = $table->addRow();
                    $row->addCheckbox('syncData['.$person['gibbonFormGroupID'].']['.$person['gibbonPersonID'].']')
                        ->setValue($person['role'])
                        ->checked($person['role'])
                        ->setClass($classMap['gibbonYearGroupID'])
                        ->addClass(strtolower($person['role']))
                        ->description('&nbsp;&nbsp;');
                    $row->addLabel('syncData['.$person['gibbonFormGroupID'].']['.$person['gibbonPersonID'].']', Format::name('', $person['preferredName'], $person['surname'], 'Student', true))->addClass('mediumWidth');
                    $row->addContent($person['role']);
                    $row->addContent($person['gibbonFormGroupName']);
                    $row->addContent($person['courseList']);
            }

            // Checkall by Year Group
            echo '<script type="text/javascript">';
            echo '$(function () {';
                echo "$('#checkall".$classMap['gibbonYearGroupID']."').click(function () {";
                echo "$('.".$classMap['gibbonYearGroupID']."').find(':checkbox').attr('checked', this.checked);";
                echo '});';
            echo '});';
            echo '</script>';
        }
    }

    // Only display a submit button if a sync is required
    if ($enrolableCount > 0) {
        $table = $form->addRow()->addTable()->setClass('smallIntBorder colorOddEven fullWidth standardForm');
        $table->addRow()->addSubmit(__('Proceed'));
    }

    echo $form->getOutput();

    // Checkall by Student/Teacher
    echo '<script type="text/javascript">';
    echo '$(function () {';
        echo "$('#includeStudents').click(function () {";
        echo "$('.student').find(':checkbox').attr('checked', this.checked);";
        echo '});';

        echo "$('#includeTeachers').click(function () {";
        echo "$('.teacher').find(':checkbox').attr('checked', this.checked);";
        echo '});';
    echo '});';
    echo '</script>';
}
