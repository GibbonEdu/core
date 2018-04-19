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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings_manage_delete.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/attendanceSettings.php'>".__($guid, 'Attendance Settings')."</a> > </div><div class='trailEnd'>".__($guid, 'Delete Attendance Code').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonAttendanceCodeID = (isset($_GET['gibbonAttendanceCodeID']))? $_GET['gibbonAttendanceCodeID'] : NULL;
    if ($gibbonAttendanceCodeID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonAttendanceCodeID' => $gibbonAttendanceCodeID);
            $sql = 'SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE gibbonAttendanceCodeID=:gibbonAttendanceCodeID';
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
            $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/attendanceSettings_manage_deleteProcess.php?gibbonAttendanceCodeID=$gibbonAttendanceCodeID", false, false);
            $form->addRow()->addContent(__('These codes should not be changed during an active school year. Removing an attendace code after attendance has been recorded can result in lost information.'));
            $form->addRow()->addConfirmSubmit();

            echo $form->getOutput();
        }
    }
}
?>
