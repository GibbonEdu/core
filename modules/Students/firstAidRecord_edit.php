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

@session_start();

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/firstAidRecord.php'>".__($guid, 'Manage First Aid Records')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonFirstAidID = $_GET['gibbonFirstAidID'];
    if ($gibbonFirstAidID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonFirstAidID' => $gibbonFirstAidID);
            $sql = "SELECT gibbonFirstAid.*, patient.gibbonPersonID AS gibbonPersonIDPatient, patient.surname AS surnamePatient, patient.preferredName AS preferredNamePatient, firstAider.title, firstAider.surname AS surnameFirstAider, firstAider.preferredName AS preferredNameFirstAider
                FROM gibbonFirstAid
                    JOIN gibbonPerson AS patient ON (gibbonFirstAid.gibbonPersonIDPatient=patient.gibbonPersonID)
                    JOIN gibbonPerson AS firstAider ON (gibbonFirstAid.gibbonPersonIDFirstAider=firstAider.gibbonPersonID)
                    JOIN gibbonStudentEnrolment ON (patient.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                    JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFirstAidID=:gibbonFirstAidID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/firstAidRecord_editProcess.php?gibbonFirstAidID=$gibbonFirstAidID&gibbonRollGroupID=".$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID']);

            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonIDPatient']);
            $row = $form->addRow();
                $row->addLabel('patient', __('Patient'));
                $row->addTextField('patient')->setValue(formatName('', $values['preferredNamePatient'], $values['surnamePatient'], 'Student'))->isRequired()->readonly();

            $row = $form->addRow();
                $row->addLabel('name', __('First Aider'));
                $row->addTextField('name')->setValue(formatName('', $_SESSION[$guid]['preferredName'], $_SESSION[$guid]['surname'], 'Student'))->isRequired()->readonly();

            $row = $form->addRow();
                $row->addLabel('date', __('Date'));
                $row->addDate('date')->setValue(dateConvertBack($guid, $values['date']))->isRequired()->readonly();

            $row = $form->addRow();
                $row->addLabel('timeIn', __('Time In'));
                $row->addTime('timeIn')->setValue(substr($values['timeIn'], 0, 5))->isRequired()->readonly();

            $row = $form->addRow();
                $row->addLabel('timeOut', __('Time Out'))->description("Format: hh:mm (24hr)");;
                $row->addTime('timeOut')->setValue(substr($values['timeOut'], 0, 5));

            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('description', __('Description'));
                $column->addTextArea('description')->setValue($values['description'])->setRows(8)->setClass('fullWidth')->readonly();

            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('actionTaken', __('Action Taken'));
                $column->addTextArea('actionTaken')->setValue($values['actionTaken'])->setRows(8)->setClass('fullWidth')->readonly();

            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('followUp', __('Follow Up'));
                $column->addTextArea('followUp')->setValue($values['followUp'])->setRows(8)->setClass('fullWidth');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
?>
