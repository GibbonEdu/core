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

if (isActionAccessible($guid, $connection2, '/modules/Students/studentEnrolment_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/studentEnrolment_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Student Enrolment')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Student Enrolment').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/studentEnrolment_manage_edit.php&gibbonStudentEnrolmentID='.$_GET['editID'].'&search='.$_GET['search'].'&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $search = $_GET['search'];
    if ($gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        if ($search != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/studentEnrolment_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__($guid, 'Back to Search Results').'</a>';
            echo '</div>';
        }

        $form = Form::create('studentEnrolmentAdd', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/studentEnrolment_manage_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

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

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}

