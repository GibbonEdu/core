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

use Gibbon\Forms\Prefab\DeleteForm;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {

	$gibbonAttendanceLogPersonID = isset($_GET['gibbonAttendanceLogPersonID'])? $_GET['gibbonAttendanceLogPersonID'] : '';
	$gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : '';
	$currentDate = isset($_GET['currentDate']) ? $_GET['currentDate'] : '';

	if ( empty($gibbonAttendanceLogPersonID) || empty($gibbonPersonID) || empty($currentDate) ) {
		$page->addError(__('You have not specified one or more required parameters.'));
	} else {
	    //Proceed!
	    
			$dataPerson = array('gibbonPersonID' => $gibbonPersonID, 'gibbonAttendanceLogPersonID' => $gibbonAttendanceLogPersonID );
			$sqlPerson = "SELECT gibbonAttendanceLogPersonID FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonAttendanceLogPersonID=:gibbonAttendanceLogPersonID ";
			$resultPerson = $connection2->prepare($sqlPerson);
			$resultPerson->execute($dataPerson);

	    if ($resultPerson->rowCount() != 1) {
	    	echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
	    } else {
			$form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']. '/attendance_take_byPerson_deleteProcess.php?gibbonAttendanceLogPersonID='.$gibbonAttendanceLogPersonID.'&gibbonPersonID='.$gibbonPersonID.'&currentDate='.$currentDate);
			echo $form->getOutput();
	    }
	}
}
