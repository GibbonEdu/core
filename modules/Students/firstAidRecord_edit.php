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
use Gibbon\Domain\Students\FirstAidGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    //Proceed!
    $page->breadcrumbs
        ->add(__('First Aid Records'), 'firstAidRecord.php')
        ->add(__('Edit'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonFirstAidID = $_GET['gibbonFirstAidID'] ?? '';
    $gibbonRollGroupID = $_GET['gibbonRollGroupID'] ?? '';
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

    if ($gibbonFirstAidID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
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

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/firstAidRecord_editProcess.php?gibbonFirstAidID=$gibbonFirstAidID&gibbonRollGroupID=".$gibbonRollGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID);

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonIDPatient']);

            $row = $form->addRow()->addHeading(__('Basic Information'));

            $row = $form->addRow();
                $row->addLabel('patient', __('Patient'));
                $row->addTextField('patient')->setValue(Format::name('', $values['preferredNamePatient'], $values['surnamePatient'], 'Student'))->required()->readonly();

            $row = $form->addRow();
                $row->addLabel('name', __('First Aider'));
                $row->addTextField('name')->setValue(Format::name('', $values['preferredNameFirstAider'], $values['surnameFirstAider'], 'Staff', false, true))->required()->readonly();

            $row = $form->addRow();
                $row->addLabel('date', __('Date'));
                $row->addDate('date')->setValue(dateConvertBack($guid, $values['date']))->required()->readonly();

            $row = $form->addRow();
                $row->addLabel('timeIn', __('Time In'));
                $row->addTime('timeIn')->setValue(substr($values['timeIn'], 0, 5))->required()->readonly();

            $row = $form->addRow();
                $row->addLabel('timeOut', __('Time Out'));
                $row->addTime('timeOut')->setValue(substr($values['timeOut'], 0, 5))->chainedTo('timeIn');

            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('description', __('Description'));
                $column->addTextArea('description')->setValue($values['description'])->setRows(8)->setClass('fullWidth')->readonly();

            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('actionTaken', __('Action Taken'));
                $column->addTextArea('actionTaken')->setValue($values['actionTaken'])->setRows(8)->setClass('fullWidth')->readonly();

            $row = $form->addRow()->addHeading(__('Follow Up'));

            //Print old-style followup as first log entry
            if (!empty($values['followUp'])) {
                $row = $form->addRow();
                    $column = $row->addColumn();
                    $column->addLabel('followUp0', __("Follow Up by {name} at {date}", ['name' => Format::name('', $values['preferredNameFirstAider'], $values['surnameFirstAider']), 'date' => Format::dateTimeReadable($values['timestamp'], '%H:%M, %b %d %Y')]));
                    $column->addContent($values['followUp'])->setClass('fullWidth');
            }

            //Print new-style followup as log
            $firstAidGateway = $container->get(FirstAidGateway::class);
            $resultLog = $firstAidGateway->queryFollowUpByFirstAidID($gibbonFirstAidID);
            $count = 0;
            foreach ($resultLog AS $rowLog) {
                $row = $form->addRow();
                    $column = $row->addColumn();
                    $column->addLabel('followUp'.$count, __("Follow Up by {name} at {date}", ['name' => Format::name('', $rowLog['preferredName'], $rowLog['surname']), 'date' => Format::dateTimeReadable($rowLog['timestamp'], '%H:%M, %b %d %Y')]));
                    $column->addContent($rowLog['followUp'])->setClass('fullWidth');
                $count++;
            }

            //Allow entry of fresh followup
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('followUp', __('Further Follow Up'));
                $column->addTextArea('followUp')->setRows(8)->setClass('fullWidth');


            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
