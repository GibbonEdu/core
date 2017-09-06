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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Students/studentEnrolment_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/studentEnrolment_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Student Enrolment')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Student Enrolment').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $gibbonStudentEnrolmentID = $_GET['gibbonStudentEnrolmentID'];
    $search = $_GET['search'];
    if ($gibbonStudentEnrolmentID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID);
            $sql = 'SELECT gibbonRollGroup.gibbonRollGroupID, gibbonYearGroup.gibbonYearGroupID,gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd, gibbonPerson.gibbonPersonID, rollOrder FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID ORDER BY surname, preferredName';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/studentEnrolment_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            $form = Form::create('studentEnrolmentAdd', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/studentEnrolment_manage_editProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonStudentEnrolmentID', $gibbonStudentEnrolmentID);

            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $pdo->executeQuery($data, $sql);

            $schoolYearName = ($result->rowCount() == 1)? $result->fetchColumn(0) : $_SESSION[$guid]['gibbonSchoolYearName'];

            $row = $form->addRow();
                $row->addLabel('yearName', __('School Year'));
                $row->addTextField('yearName')->readOnly()->maxLength(20)->setValue($schoolYearName);

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __('Student'));
                $row->addSelectStudent('gibbonPersonID', $gibbonSchoolYearID)->isRequired()->placeholder();

            $row = $form->addRow();
                $row->addLabel('gibbonYearGroupID', __('Year Group'));
                $row->addSelectYearGroup('gibbonYearGroupID')->isRequired();

            $row = $form->addRow();
                $row->addLabel('gibbonRollGroupID', __('Roll Group'));
                $row->addSelectRollGroup('gibbonRollGroupID', $gibbonSchoolYearID)->isRequired();

            $row = $form->addRow();
                $row->addLabel('rollOrder', __('Roll Order'));
                $row->addNumber('rollOrder')->maxLength(2);

            $schoolHistory = '';

            if ($values['dateStart'] != '') {
                $schoolHistory .= '<u>'.__($guid, 'Start Date').'</u>: '.dateConvertBack($guid, $values['dateStart']).'</br>';
            }

            $dataSelect = array('gibbonPersonID' => $values['gibbonPersonID']);
            $sqlSelect = 'SELECT gibbonRollGroup.name AS rollGroup, gibbonSchoolYear.name AS schoolYear FROM gibbonStudentEnrolment JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonStudentEnrolment.gibbonSchoolYearID';
            $resultSelect = $pdo->executeQuery($dataSelect, $sqlSelect);

            while ($resultSelect && $rowSelect = $resultSelect->fetch()) {
                $schoolHistory .= '<u>'.$rowSelect['schoolYear'].'</u>: '.$rowSelect['rollGroup'].'<br/>';
            }

            if ($values['dateEnd'] != '') {
                $schoolHistory .= '<u>'.__($guid, 'End Date').'</u>: '.dateConvertBack($guid, $values['dateEnd']).'</br>';
            }

            $row = $form->addRow();
                $row->addLabel('schoolHistory', __('School History'));
                $row->addContent($schoolHistory)->setClass('right');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
