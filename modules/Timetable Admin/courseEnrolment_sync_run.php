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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync_run.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {

    // Allows for a single value or a csv list of gibbonYearGroupID
    $gibbonYearGroupIDList = (isset($_GET['gibbonYearGroupIDList']))? $_GET['gibbonYearGroupIDList'] : null;
    $gibbonSchoolYearID = (isset($_GET['gibbonSchoolYearID']))? $_GET['gibbonSchoolYearID'] : null;

    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/courseEnrolment_sync.php&gibbonSchoolYearID=".$gibbonSchoolYearID."'>".__($guid, 'Sync Course Enrolment')."</a> > </div><div class='trailEnd'>".__($guid, 'Sync Now').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if (empty($gibbonYearGroupIDList) || empty($gibbonSchoolYearID)) {
        echo "<div class='error'>";
        echo __($guid, 'Your request failed because your inputs were invalid.');
        echo '</div>';
        return;
    }

    if ($gibbonYearGroupIDList == 'all') {
        // All class mappings
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonCourseClassMap.*, gibbonYearGroup.name as gibbonYearGroupName
                FROM gibbonCourseClassMap
                JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonCourseClassMap.gibbonRollGroupID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonCourseClassMap.gibbonYearGroupID)
                WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonCourseClassMap.gibbonYearGroupID";
    } else {
        // Pull up the class mapping for this year group
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonYearGroupID' => $gibbonYearGroupIDList);
        $sql = "SELECT gibbonCourseClassMap.*, gibbonYearGroup.name as gibbonYearGroupName
                FROM gibbonCourseClassMap
                JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonCourseClassMap.gibbonRollGroupID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonCourseClassMap.gibbonYearGroupID)
                WHERE FIND_IN_SET(gibbonCourseClassMap.gibbonYearGroupID, :gibbonYearGroupID)
                AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonCourseClassMap.gibbonYearGroupID";
    }

    $result = $pdo->executeQuery($data, $sql);

    if ($result->rowCount() == 0) {
        echo "<div class='error'>";
        echo __($guid, 'Your request failed because your inputs were invalid.');
        echo '</div>';
        return;
    }

    $form = Form::create('courseEnrolmentSyncRun', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_sync_runProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    // To render the form as multiple tables
    $form->getRenderer()->setWrapper('form', 'div');
    $form->getRenderer()->setWrapper('row', 'div');
    $form->getRenderer()->setWrapper('cell', 'div');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
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
        $sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonRollGroup.gibbonRollGroupID, gibbonRollGroup.name as gibbonRollGroupName, GROUP_CONCAT(CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort SEPARATOR ', ') AS courseList, 'Teacher' as role
                FROM gibbonCourseClassMap
                JOIN gibbonRollGroup ON (gibbonCourseClassMap.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                JOIN gibbonPerson ON (gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID || gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID || gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID AND gibbonCourseClassPerson.role = 'Teacher')
                WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonCourseClassMap.gibbonYearGroupID=:gibbonYearGroupID
                AND gibbonPerson.status='Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)
                AND gibbonCourseClassPerson.gibbonCourseClassPersonID IS NULL
                GROUP BY gibbonPerson.gibbonPersonID
            ) UNION ALL (
                SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonRollGroup.gibbonRollGroupID, gibbonRollGroup.name as gibbonRollGroupName, GROUP_CONCAT(CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort SEPARATOR ', ') AS courseList, 'Student' as role
                FROM gibbonCourseClassMap
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonCourseClassMap.gibbonYearGroupID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonCourseClassMap.gibbonRollGroupID)
                JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonRollGroup ON (gibbonCourseClassMap.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID  AND gibbonCourseClassPerson.role = 'Student')
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonCourseClassMap.gibbonYearGroupID=:gibbonYearGroupID
                AND gibbonPerson.status='Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)
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
                $header->addContent(__('Roll Group'));
                $header->addContent(__('Enrolment by Class'));

            while ($person = $enrolmentResult->fetch()) {
                $row = $table->addRow();
                    $row->addCheckbox('syncData['.$person['gibbonRollGroupID'].']['.$person['gibbonPersonID'].']')
                        ->setValue($person['role'])
                        ->checked($person['role'])
                        ->setClass($classMap['gibbonYearGroupID'])
                        ->addClass(strtolower($person['role']))
                        ->description('&nbsp;&nbsp;');
                    $row->addLabel('syncData['.$person['gibbonRollGroupID'].']['.$person['gibbonPersonID'].']', formatName('', $person['preferredName'], $person['surname'], 'Student', true))->addClass('mediumWidth');
                    $row->addContent($person['role']);
                    $row->addContent($person['gibbonRollGroupName']);
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
