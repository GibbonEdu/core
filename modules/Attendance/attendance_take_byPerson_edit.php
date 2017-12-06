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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

require_once $_SESSION[$guid]['absolutePath'].'/modules/Attendance/src/attendanceView.php';


if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {

	$gibbonAttendanceLogPersonID = isset($_GET['gibbonAttendanceLogPersonID'])? $_GET['gibbonAttendanceLogPersonID'] : '';
	$gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : '';

	if ( empty($gibbonAttendanceLogPersonID) || empty($gibbonPersonID) ) {
		echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
	} else {
	    //Proceed!
	    echo "<div class='trail'>";
	    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Attendance by Person').'</div>';
	    echo '</div>';

	    if (isset($_GET['return'])) {
	        returnProcess($guid, $_GET['return'], null, array('error3' => 'Your request failed because the specified date is not in the future, or is not a school day.'));
	    }

	    $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);

	    try {
			$dataPerson = array('gibbonPersonID' => $gibbonPersonID, 'gibbonAttendanceLogPersonID' => $gibbonAttendanceLogPersonID );
			$sqlPerson = "SELECT p.preferredName, p.surname, type, reason, comment, date, context, timestampTaken, gibbonAttendanceLogPerson.gibbonCourseClassID, t.preferredName as teacherPreferredName, t.surname as teacherSurname, gibbonCourseClass.nameShort as className, gibbonCourse.nameShort as courseName FROM gibbonAttendanceLogPerson JOIN gibbonPerson p ON (gibbonAttendanceLogPerson.gibbonPersonID=p.gibbonPersonID) JOIN gibbonPerson t ON (gibbonAttendanceLogPerson.gibbonPersonIDTaker=t.gibbonPersonID) LEFT JOIN gibbonCourseClass ON (gibbonAttendanceLogPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND gibbonAttendanceLogPersonID=:gibbonAttendanceLogPersonID ";
			$resultPerson = $connection2->prepare($sqlPerson);
			$resultPerson->execute($dataPerson);
		} catch (PDOException $e) {
			echo "<div class='error'>".$e->getMessage().'</div>';
		}

	    if ($resultPerson->rowCount() != 1) {
	    	echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
	    } else {
	    	$values = $resultPerson->fetch();
	    	$currentDate = dateConvert($guid, $values['date']);

			$form = Form::create('attendanceEdit', $_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/attendance_take_byPerson_editProcess.php');
			$form->setAutocomplete('off');

			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			$form->addHiddenValue('gibbonAttendanceLogPersonID', $gibbonAttendanceLogPersonID);
			$form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
			$form->addHiddenValue('currentDate', $currentDate);

			$form->addRow()->addHeading(__('Edit Attendance'));

			$row = $form->addRow();
				$row->addLabel('student', __('Student'));
				$row->addTextField('student')->readonly()->setValue(formatName('', htmlPrep($values['preferredName']), htmlPrep($values['surname']), 'Student', true));

			$row = $form->addRow();
				$row->addLabel('date', __('Date'));
				$row->addDate('date')->readonly()->setValue(dateConvertBack($guid, $currentDate));

			$row = $form->addRow();
				$row->addLabel('recordedBy', __('Recorded By'));
				$row->addTextField('recordedBy')->readonly()->setValue(formatName('', htmlPrep($values['teacherPreferredName']), htmlPrep($values['teacherSurname']), 'Staff', false, true));

			$row = $form->addRow();
				$row->addLabel('time', __('Time'));
				$row->addTextField('time')->readonly()->setValue(substr($values['timestampTaken'], 11) . ' ' . dateConvertBack($guid, substr($values['timestampTaken'], 0, 10)));
				
			$row = $form->addRow();
				$row->addLabel('where', __('Where'));
				$row->addTextField('where')->readonly()->setValue(__($values['context']));

			$row = $form->addRow();
				$row->addLabel('type', __('Type'));
				$row->addSelect('type')->fromArray(array_keys($attendance->getAttendanceTypes()));

			$row = $form->addRow();
				$row->addLabel('reason', __('Reason'));
				$row->addSelect('reason')->fromArray($attendance->getAttendanceReasons());

			$row = $form->addRow();
				$row->addLabel('comment', __('Comment'))->description(__('255 character limit'));
				$row->addTextArea('comment')->setRows(3)->maxLength(255);

			$row = $form->addRow();
				$row->addFooter();
				$row->addSubmit();

			$form->loadAllValuesFrom($values);

			echo $form->getOutput();
	        
	    }
	}
}
