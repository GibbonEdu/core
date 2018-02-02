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

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_studentSelfRegister.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {

    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Student Self Registration').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if (isset($_GET['redirect']) && $_GET['redirect'] == 'true') {
        echo '<div class=\'warning\'>';
            echo __('Please self register!');
        echo '</div>';
    }

    //Check to see if IP addresses are set
    $studentSelfRegistrationIPAddresses = getSettingByScope($connection2, 'Attendance', 'studentSelfRegistrationIPAddresses');
    $realIP = getIPAddress();
    if ($studentSelfRegistrationIPAddresses == '' || is_null($studentSelfRegistrationIPAddresses)) {
        echo "<div class='error'>";
        echo __($guid, 'You do not have access to this action.');
        echo '</div>';
    } else {
        //Check if school day
        $currentDate = date('Y-m-d');
        if (isSchoolOpen($guid, $currentDate, $connection2, true) == false) {
            print "<div class='error'>" ;
                print __("School is closed on the specified date, and so attendance information cannot be recorded.") ;
            print "</div>" ;
        }
        else {
            //Check for existence of records today
            try {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'date' => $currentDate);
                $sql = "SELECT type FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date=:date ORDER BY timestampTaken DESC";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() > 0) { //Records! Output current status
                $row = $result->fetch();
                print "<div class='message'>" ;
                    print sprintf(__('Attendance has been taken for you today. Your current status is: %1$s'), "<b>".$row['type']."</b>") ;
                print "</div>" ;
            }
            else { //If no records, give option to self register
                $inRange = false ;
                foreach (explode(',', $studentSelfRegistrationIPAddresses) as $ipAddress) {
                    if (trim($ipAddress) == $realIP)
                        $inRange = true ;
                }

                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/attendance_studentSelfRegisterProcess.php');

                $form->setFactory(DatabaseFormFactory::create($pdo));

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                if (!$inRange) { //Out of school, offer ability to register as absent
                    $form->addHiddenValue('status', 'Absent');

                    $row = $form->addRow();
                        $row->addLabel('submit',sprintf(__('It seems that you are out of school right now. Click the Submit button below to register yourself as %1$sAbsent%2$s today.'), '<span style=\'color: #CC0000; text-decoration: underline\'>', '</span>'));
                }
                else { //In school, offer ability to register as present
                    $form->addHiddenValue('status', 'Present');

                    $row = $form->addRow();
                        $row->addLabel('submit',sprintf(__('Welcome back to %1$s. Click the Submit button below to register yourself as %2$sPresent%3$s today.'), $_SESSION[$guid]['organisationNameShort'], '<span style=\'color: #390; text-decoration: underline\'>', '</span>'));
                }

                $row = $form->addRow();
                    $row->addFooter(false);
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }

    }
}
?>
