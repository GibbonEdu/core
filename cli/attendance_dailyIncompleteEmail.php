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

use Gibbon\Comms\NotificationEvent;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;

require getcwd().'/../gibbon.php';

getSystemSettings($guid, $connection2);

setCurrentSchoolYear($guid, $connection2);

//Set up for i18n via gettext
if (isset($_SESSION[$guid]['i18n']['code'])) {
    if ($_SESSION[$guid]['i18n']['code'] != null) {
        putenv('LC_ALL='.$_SESSION[$guid]['i18n']['code']);
        setlocale(LC_ALL, $_SESSION[$guid]['i18n']['code']);
        bindtextdomain('gibbon', getcwd().'/../i18n');
        textdomain('gibbon');
    }
}

//Check for CLI, so this cannot be run through browser
if (!isCommandLineInterface()) { echo __('This script cannot be run from a browser, only via CLI.');
} else {
    $currentDate = date('Y-m-d');

    if (isSchoolOpen($guid, $currentDate, $connection2, true)) {
        $report = '';
        $reportInner = '';

        $partialFail = false;

        $userReport = array();
        $adminReport = array( 'formGroup' => array(), 'classes' => array() );

        $enabledByFormGroup = getSettingByScope($connection2, 'Attendance', 'attendanceCLINotifyByFormGroup');
        $additionalUsersList = getSettingByScope($connection2, 'Attendance', 'attendanceCLIAdditionalUsers');

        if ($enabledByFormGroup != 'Y') {
            die('Attendance CLI cancelled: Notifications not enabled in Attendance Settings.');
        }

        //Produce array of attendance data ------------------------------------------------------------------------------------------------------

        if ($enabledByFormGroup == 'Y') {
            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);

                // Looks for form groups with attendance='Y', also grabs primary tutor name
                $sql = "SELECT gibbonFormGroupID, gibbonFormGroup.name, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, gibbonPerson.preferredName, gibbonPerson.surname, (SELECT count(*) FROM gibbonStudentEnrolment WHERE gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) AS studentCount
                FROM gibbonFormGroup
                JOIN gibbonPerson ON (gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID)
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                AND attendance = 'Y'
                AND gibbonPerson.status='Full'
                ORDER BY LENGTH(gibbonFormGroup.name), gibbonFormGroup.name";

                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $partialFail = true;
            }

            // Proceed if we have attendance-able Form Groups
            if ($result->rowCount() > 0) {

                try {
                    $data = array('date' => $currentDate);
                    $sql = 'SELECT gibbonFormGroupID FROM gibbonAttendanceLogFormGroupID WHERE date=:date';
                    $resultLog = $connection2->prepare($sql);
                    $resultLog->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }

                // Gather the current Form Group logs for the day
                $log = array();
                while ($row = $resultLog->fetch()) {
                    $log[$row['gibbonFormGroupID']] = true;
                }

                while ($row = $result->fetch()) {
                    // Skip form groups with no students
                    if ($row['studentCount'] <= 0) continue;

                    // Check for a current log
                    if (isset($log[$row['gibbonFormGroupID']]) == false) {

                        $formGroupInfo = array( 'gibbonFormGroupID' => $row['gibbonFormGroupID'], 'name' => $row['name'] );

                        // Compile info for Admin report
                        $adminReport['formGroup'][] = '<b>'.$row['name'] .'</b> - '. $row['preferredName'].' '.$row['surname'];

                        // Compile info for User reports
                        if ($row['gibbonPersonIDTutor'] != '') {
                            $userReport[ $row['gibbonPersonIDTutor'] ]['formGroup'][] = $formGroupInfo;
                        }
                        if ($row['gibbonPersonIDTutor2'] != '') {
                            $userReport[ $row['gibbonPersonIDTutor2'] ]['formGroup'][] = $formGroupInfo;
                        }
                        if ($row['gibbonPersonIDTutor3'] != '') {
                            $userReport[ $row['gibbonPersonIDTutor3'] ]['formGroup'][] = $formGroupInfo;
                        }
                    }
                }

                // Use the form group counts to generate a report
                if ( isset($adminReport['formGroup']) && count($adminReport['formGroup']) > 0) {
                    $reportInner = implode('<br>', $adminReport['formGroup']);
                    $report .= '<br/><br/>';
                    $report .= sprintf(__('%1$s form groups have not been registered today  (%2$s).'), count($adminReport['formGroup']), dateConvertBack($guid, $currentDate) ).'<br/><br/>'.$reportInner;
                } else {
                    $report .= '<br/><br/>';
                    $report .= sprintf(__('All form groups have been registered today (%1$s).'), dateConvertBack($guid, $currentDate));
                }
            }
        }

        // Initialize the notification sender & gateway objects
        $notificationGateway = new NotificationGateway($pdo);
        $notificationSender = new NotificationSender($notificationGateway, $gibbon->session);

        // Raise a new notification event
        $event = new NotificationEvent('Attendance', 'Daily Attendance Summary');

        if ($event->getEventDetails($notificationGateway, 'active') == 'Y' && $partialFail == false) {
            //Notify non-completing tutors
            foreach ($userReport as $gibbonPersonID => $items ) {

                $notificationText = __('You have not taken attendance yet today. Please do so as soon as possible.');

                if ($enabledByFormGroup == 'Y') {
                    // Output the form groups the particular user is a part of
                    if ( isset($items['formGroup']) && count($items['formGroup']) > 0) {
                        $notificationText .= '<br/><br/>';
                        $notificationText .= '<b>'.__('Form Group').':</b><br/>';
                        foreach ($items['formGroup'] as $formGroup) {
                            $notificationText .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $formGroup['name'] .'<br/>';
                        }

                    }
                }

                $notificationSender->addNotification($gibbonPersonID, $notificationText, 'Attendance', '/index.php?q=/modules/Attendance/attendance.php&currentDate='.dateConvertBack($guid, date('Y-m-d')));
            }

            // Notify Additional Users
            if (!empty($additionalUsersList)) {
                $additionalUsers = explode(',', $additionalUsersList);

                if (is_array($additionalUsers) && count($additionalUsers) > 0) {
                    foreach ($additionalUsers as $gibbonPersonID) {
                        // Confirm that this user still has permission to access these reports
                        try {
                            $data = array( 'gibbonPersonID' => $gibbonPersonID, 'action1' => '%report_formGroupsNotRegistered_byDate.php%', 'action2' => '%report_courseClassesNotRegistered_byDate.php%' );
                            $sql = "SELECT gibbonAction.name FROM gibbonAction, gibbonPermission, gibbonRole, gibbonPerson WHERE (gibbonAction.URLList LIKE :action1 OR gibbonAction.URLList LIKE :action2) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) AND (gibbonPermission.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary) AND (gibbonPerson.gibbonPersonID=:gibbonPersonID) AND (gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Attendance'))";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        }  catch (PDOException $e) {}

                        if ($result->rowCount() > 0) {
                            $event->addRecipient($gibbonPersonID);
                        }
                    }
                }
            }

        } else if ($partialFail) {
            // Notify admin if there was an error in the report
            $report = __('Your request failed due to a database error.') . '<br/><br/>' . $report;
        }

        $event->setNotificationText(__('An Attendance CLI script has run.').' '.$report);
        $event->setActionLink('/index.php?q=/modules/Attendance/report_formGroupsNotRegistered_byDate.php');

        // Add admin, then push the event to the notification sender
        $event->addRecipient($_SESSION[$guid]['organisationAdministrator']);
        $event->pushNotifications($notificationGateway, $notificationSender);

        // Send all notifications
        $sendReport = $notificationSender->sendNotifications();

        // Output the result to terminal
        echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['count'], $sendReport['inserts'], $sendReport['updates'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
    }
}
