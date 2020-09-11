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
        $adminReport = array( 'rollGroup' => array(), 'classes' => array() );

        $enabledByClass = getSettingByScope($connection2, 'Attendance', 'attendanceCLINotifyByClass');
        $additionalUsersList = getSettingByScope($connection2, 'Attendance', 'attendanceCLIAdditionalUsers');

        if ($enabledByClass != 'Y') {
            die('Attendance CLI cancelled: Notifications not enabled in Attendance Settings.');
        }

        //Produce array of attendance data for Classes ------------------------------------------------------------------------------------------------------
        if ($enabledByClass == 'Y') {
            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => $currentDate, 'time' => date("H:i:s"));

                // Looks for only courses that are scheduled on the current day and have attendance='Y', also grabs tutor name
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name as class, gibbonCourse.name as course, gibbonCourse.nameShort as courseShort,  gibbonCourseClassPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname, (SELECT count(*) FROM gibbonCourseClassPerson JOIN gibbonPerson AS student ON (gibbonCourseClassPerson.gibbonPersonID=student.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND student.status='Full' AND (student.dateStart IS NULL OR student.dateStart<=:date) AND (student.dateEnd IS NULL OR student.dateEnd>=:date)) AS studentCount
                FROM gibbonCourseClass
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
                LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonTTDayDate.date=:date
                AND gibbonTTColumnRow.timeStart<=:time
                AND gibbonCourseClassPerson.role='Teacher'
                AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonCourseClass.attendance='Y'
                AND gibbonTTDayRowClassException.gibbonTTDayRowClassExceptionID IS NULL
                AND gibbonPerson.status='Full'
                ORDER BY gibbonPerson.surname, gibbonCourse.nameShort, gibbonCourseClass.nameShort";

                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $partialFail = true;
            }

            // Proceed if we have attendance-able Classes
            if ($result->rowCount() > 0) {

                try {
                    $data = array('date' => $currentDate);
                    $sql = 'SELECT gibbonCourseClassID FROM gibbonAttendanceLogCourseClass WHERE date=:date';
                    $resultLog = $connection2->prepare($sql);
                    $resultLog->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }

                // Gather the current Class logs for the day
                $log = array();
                while ($row = $resultLog->fetch()) {
                    $log[$row['gibbonCourseClassID']] = true;
                }

                while ($row = $result->fetch()) {
                    // Skip classes with no students
                    if ($row['studentCount'] <= 0) continue;

                    // Check for a current log
                    if (isset($log[$row['gibbonCourseClassID']]) == false) {

                        $className = $row['course'].' ('.$row['courseShort'].'.'.$row['class'].')';
                        $classInfo = array( 'gibbonCourseClassID' => $row['gibbonCourseClassID'], 'name' => $className );

                        // Compile info for Admin report
                        $adminReport['classes'][ $row['preferredName'].' '.$row['surname'] ][] = $className;

                        // Compile info for User reports
                        if ($row['gibbonPersonID'] != '') {
                            $userReport[ $row['gibbonPersonID'] ]['classes'][] = $classInfo;
                        }
                    }
                }

                // Use the class counts to generate reports
                if ( isset($adminReport['classes']) && count($adminReport['classes']) > 0) {
                    $reportInner = '';

                    // Output the reports grouped by teacher
                    foreach ($adminReport['classes'] as $teacherName => $classes) {
                        $reportInner .= '<b>' . $teacherName;
                        $reportInner .= (count($classes) > 1)? ' ('.count($classes).')</b><br/>' : '</b><br/>';
                        foreach ($classes as $className) {
                            $reportInner .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $className .'<br/>';
                        }
                        $reportInner .= '<br>';
                    }

                    $report .= '<br/><br/>';
                    $report .= sprintf(__('%1$s classes have not been registered today (%2$s).'), count($adminReport['classes']), dateConvertBack($guid, $currentDate)).'<br/><br/>'.$reportInner;
                } else {
                    $report .= '<br/><br/>';
                    $report .= sprintf(__('All classes have been registered today (%1$s).'), dateConvertBack($guid, $currentDate));
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

                if ($enabledByClass == 'Y') {
                    // Output the classes the particular user is a part of
                    if ( isset($items['classes']) && count($items['classes']) > 0) {
                        $notificationText .= '<br/><br/>';
                        $notificationText .= '<b>'.__('Classes').':</b><br/>';
                        foreach ($items['classes'] as $class) {
                            $notificationText .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $class['name'] .'<br/>';
                        }
                        $notificationText .= '<br/>';
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
                            $data = array( 'gibbonPersonID' => $gibbonPersonID, 'action1' => '%report_rollGroupsNotRegistered_byDate.php%', 'action2' => '%report_courseClassesNotRegistered_byDate.php%' );
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
        $event->setActionLink('/index.php?q=/modules/Attendance/report_courseClassesNotRegistered_byDate.php');

        // Add admin, then push the event to the notification sender
        $event->addRecipient($_SESSION[$guid]['organisationAdministrator']);
        $event->pushNotifications($notificationGateway, $notificationSender);

        // Send all notifications
        $sendReport = $notificationSender->sendNotifications();

        // Output the result to terminal
        echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['count'], $sendReport['inserts'], $sendReport['updates'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
    }
}
