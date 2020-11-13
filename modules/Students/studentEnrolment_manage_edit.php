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
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Students/studentEnrolment_manage_edit.php') == false) {
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

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    if ($gibbonStudentEnrolmentID == '' or $gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID);
            $sql = 'SELECT gibbonRollGroup.gibbonRollGroupID, gibbonYearGroup.gibbonYearGroupID,gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd, gibbonPerson.gibbonPersonID, rollOrder FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID ORDER BY surname, preferredName';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/studentEnrolment_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__('Back to Search Results').'</a>';
                echo '</div>';
            }

            $form = Form::create('studentEnrolmentAdd', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/studentEnrolment_manage_editProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonStudentEnrolmentID', $gibbonStudentEnrolmentID);
            $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);
            $form->addHiddenValue('gibbonRollGroupIDOriginal', $values['gibbonRollGroupID']);

            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $pdo->executeQuery($data, $sql);

            $schoolYearName = ($result->rowCount() == 1)? $result->fetchColumn(0) : $_SESSION[$guid]['gibbonSchoolYearName'];

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
                $row->addLabel('gibbonRollGroupID', __('Roll Group'));
                $row->addSelectRollGroup('gibbonRollGroupID', $gibbonSchoolYearID)->required();

            $row = $form->addRow();
                $row->addLabel('rollOrder', __('Roll Order'));
                $row->addNumber('rollOrder')->maxLength(2);

            // Check to see if any class mappings exists -- otherwise this feature is inactive, hide it
            $sql = "SELECT COUNT(*) FROM gibbonCourseClassMap";
            $resultClassMap = $pdo->executeQuery(array(), $sql);
            $classMapCount = ($resultClassMap->rowCount() > 0)? $resultClassMap->fetchColumn(0) : 0;

            if ($classMapCount > 0) {
                $autoEnrolDefault = getSettingByScope($connection2, 'Timetable Admin', 'autoEnrolCourses');
                $row = $form->addRow();
                    $row->addLabel('autoEnrolStudent', __('Auto-Enrol Courses?'))
                        ->description(__('Should this student be automatically enrolled in courses for their Roll Group?'))
                        ->description(__('This will replace any auto-enrolled courses if the student Roll Group has changed.'));
                    $row->addYesNo('autoEnrolStudent')->selected($autoEnrolDefault);
            }

            $schoolHistory = '';

            if ($values['dateStart'] != '') {
                $schoolHistory .= '<li><u>'.__('Start Date').'</u>: '.dateConvertBack($guid, $values['dateStart']).'</li>';
            }

            $dataSelect = array('gibbonPersonID' => $values['gibbonPersonID']);
            $sqlSelect = 'SELECT gibbonRollGroup.name AS rollGroup, gibbonSchoolYear.name AS schoolYear FROM gibbonStudentEnrolment JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonStudentEnrolment.gibbonSchoolYearID';
            $resultSelect = $pdo->executeQuery($dataSelect, $sqlSelect);

            while ($resultSelect && $rowSelect = $resultSelect->fetch()) {
                $schoolHistory .= '<li><u>'.$rowSelect['schoolYear'].'</u>: '.$rowSelect['rollGroup'].'</li>';
            }

            if ($values['dateEnd'] != '') {
                $schoolHistory .= '<li><u>'.__('End Date').'</u>: '.dateConvertBack($guid, $values['dateEnd']).'</li>';
            }

            $row = $form->addRow();
                $row->addLabel('schoolHistory', __('School History'));
                $row->addContent('<ul class="list-none w-full sm:max-w-xs text-xs m-0">'.$schoolHistory.'</ul>');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
