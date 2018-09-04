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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_archive.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Archive Records').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.'));
	}
	
	$form = Form::create('courseEdit', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/in_archiveProcess.php');
                
	$form->addHiddenValue('address', $_SESSION[$guid]['address']);

	$row = $form->addRow();
		$row->addLabel('deleteCurrentPlans', __('Delete Current Plans?'))->description(__('Deletes Individual Education Plan fields only, not Individual Needs Status fields.'));
		$row->addYesNo('deleteCurrentPlans')->isRequired()->selected('N');

	$row = $form->addRow();
		$row->addLabel('title', __('Archive Title'));
		$row->addTextField('title')->isRequired()->maxLength(50);

	$data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
	$sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort as rollGroup
			FROM gibbonPerson 
			JOIN gibbonIN ON (gibbonIN.gibbonPersonID=gibbonPerson.gibbonPersonID) 
			JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
			JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID)
			WHERE status='Full' ORDER BY surname, preferredName";
	$result = $pdo->executeQuery($data, $sql);

	$students = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();
	$students = array_map(function($item) {
		return formatName('', $item['preferredName'], $item['surname'], 'Student', true).' ('.$item['rollGroup'].')';
	}, $students);
						
	$row = $form->addRow();
		$row->addLabel('gibbonPersonID', __('Students'));
		$row->addCheckbox('gibbonPersonID')->fromArray($students)->addCheckAllNone();

	$row = $form->addRow();
		$row->addSubmit();

	echo $form->getOutput();
}
