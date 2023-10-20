<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

// set page breadcrumb
$page->breadcrumbs->add(__('Student Self Registration'));

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_studentSelfRegister.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    if (isset($_GET['redirect']) && $_GET['redirect'] == 'true') {
        echo '<div class=\'warning\'>';
            echo __('Please self register!');
        echo '</div>';
    }

    //Check to see if IP addresses are set
    $studentSelfRegistrationIPAddresses = $container->get(SettingGateway::class)->getSettingByScope('Attendance', 'studentSelfRegistrationIPAddresses');
    $realIP = getIPAddress();
    if ($studentSelfRegistrationIPAddresses == '' || is_null($studentSelfRegistrationIPAddresses)) {
        $page->addError(__('You do not have access to this action.'));
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

                $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'date' => $currentDate);
                $sql = "SELECT type FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date=:date ORDER BY timestampTaken DESC";
                $result = $connection2->prepare($sql);
                $result->execute($data);

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

                $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/attendance_studentSelfRegisterProcess.php');

                $form->setFactory(DatabaseFormFactory::create($pdo));

                $form->addHiddenValue('address', $session->get('address'));

                if (!$inRange) { //Out of school, offer ability to register as absent
                    $form->addHiddenValue('status', 'Absent');

                    $row = $form->addRow();
                        $row->addLabel('submit',sprintf(__('It seems that you are out of school right now. Click the Submit button below to register yourself as %1$sAbsent%2$s today.'), '<span style=\'color: #CC0000; text-decoration: underline\'>', '</span>'));
                }
                else { //In school, offer ability to register as present
                    $form->addHiddenValue('status', 'Present');

                    $row = $form->addRow();
                        $row->addLabel('submit',sprintf(__('Welcome back to %1$s. Click the Submit button below to register yourself as %2$sPresent%3$s today.'), $session->get('organisationNameShort'), '<span style=\'color: #390; text-decoration: underline\'>', '</span>'));
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
