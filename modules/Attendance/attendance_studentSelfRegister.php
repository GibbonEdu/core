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
        $inRange = false ;
        foreach (explode(',', $studentSelfRegistrationIPAddresses) as $ipAddress) {
            if (trim($ipAddress) == $realIP)
                $inRange = true ;
        }

        if (!$inRange) {
            echo "<div class='error'>";
            echo __($guid, 'It appears that you are not in school, and so cannot register yourself as present.');
            echo '</div>';
        }
        else {
            //Check if school day
            $currentDate = date('Y-m-d');
            if (isSchoolOpen($guid, $currentDate, $connection2, true) == false) {
                print "<div class='error'>" ;
					print _("School is closed on the specified date, and so attendance information cannot be recorded.") ;
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
    					print sprintf(_('Attendance has been taken for you today. Your current status is: %1$s'), "<b>".$row['type']."</b>") ;
    				print "</div>" ;
                }
                else { //If no records, give option to self register
                    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/attendance_studentSelfRegisterProcess.php');

                    $form->setFactory(DatabaseFormFactory::create($pdo));

                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                    $row = $form->addRow();
                        $row->addLabel('submit','Click the Submit button below to register yourself as Present today.');

                    $row = $form->addRow();
                        $row->addFooter(false);
                        $row->addSubmit();

                    echo $form->getOutput();
                }
            }
        }

    }
}
?>
