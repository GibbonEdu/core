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

require getcwd().'/../config.php';
require getcwd().'/../functions.php';
require getcwd().'/../lib/PHPMailer/PHPMailerAutoload.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

//Check for CLI, so this cannot be run through browser
if (php_sapi_name() != 'cli') { echo __($guid, 'This script cannot be run from a browser, only via CLI.');
} else {
    $currentDate = date('Y-m-d');

    if (isSchoolOpen($guid, $currentDate, $connection2, true)) {
        $report = '';
        $reportInner = '';

        $partialFail = false;

        $userReport = array();
        $adminReport = array( 'rollGroup' => array(), 'classes' => array() );

        $enabledByRollGroup = getSettingByScope($connection2, 'Attendance', 'attendanceCLINotifyByRollGroup');
        $enabledByClass = getSettingByScope($connection2, 'Attendance', 'attendanceCLINotifyByClass');
        $additionalUsersList = getSettingByScope($connection2, 'Attendance', 'attendanceCLIAdditionalUsers');

        if ($enabledByRollGroup == 'N' && $enabledByClass == 'N') {
            die('Attendance CLI cancelled: Notifications not enabled in Attendance Settings.');
        }

        //Produce array of attendance data ------------------------------------------------------------------------------------------------------
        
        if ($enabledByRollGroup == 'Y') {
            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);

                // Looks for roll groups with attendance='Y', also grabs primary tutor name
                $sql = "SELECT gibbonRollGroupID, gibbonRollGroup.name, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, gibbonPerson.preferredName, gibbonPerson.surname, (SELECT count(*) FROM gibbonStudentEnrolment WHERE gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AS studentCount 
                FROM gibbonRollGroup 
                JOIN gibbonPerson ON (gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID) 
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID 
                AND attendance = 'Y' 
                ORDER BY LENGTH(gibbonRollGroup.name), gibbonRollGroup.name";

                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $partialFail = true;
            }

            // Proceed if we have attendance-able Roll Groups
            if ($result->rowCount() > 0) {

                try {
                    $data = array('date' => $currentDate);
                    $sql = 'SELECT gibbonRollGroupID FROM gibbonAttendanceLogRollGroup WHERE date=:date';
                    $resultLog = $connection2->prepare($sql);
                    $resultLog->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }

                // Gather the current Roll Group logs for the day
                $log = array();
                while ($row = $resultLog->fetch()) {
                    $log[$row['gibbonRollGroupID']] = true;
                }

                while ($row = $result->fetch()) {
                    // Skip roll groups with no students
                    if ($row['studentCount'] <= 0) continue;

                    // Check for a current log
                    if (isset($log[$row['gibbonRollGroupID']]) == false) {

                        $rollGroupInfo = array( 'gibbonRollGroupID' => $row['gibbonRollGroupID'], 'name' => $row['name'] );

                        // Compile info for Admin report
                        $adminReport['rollGroup'][] = '<b>'.$row['name'] .'</b> - '. $row['preferredName'].' '.$row['surname'];

                        // Compile info for User reports
                        if ($row['gibbonPersonIDTutor'] != '') {
                            $userReport[ $row['gibbonPersonIDTutor'] ]['rollGroup'][] = $rollGroupInfo;
                        }
                        if ($row['gibbonPersonIDTutor2'] != '') {
                            $userReport[ $row['gibbonPersonIDTutor2'] ]['rollGroup'][] = $rollGroupInfo;
                        }
                        if ($row['gibbonPersonIDTutor3'] != '') {
                            $userReport[ $row['gibbonPersonIDTutor3'] ]['rollGroup'][] = $rollGroupInfo;
                        }
                    }
                }

                // Use the roll group counts to generate a report
                if ( isset($adminReport['rollGroup']) && count($adminReport['rollGroup']) > 0) {
                    $reportInner = implode('<br>', $adminReport['rollGroup']);
                    $report .= '<br/><br/>';
                    $report .= sprintf(__($guid, '%1$s form groups have not been registered today  (%2$s).'), count($adminReport['rollGroup']), dateConvertBack($guid, $currentDate) ).'<br/><br/>'.$reportInner;
                } else {
                    $report .= '<br/><br/>';
                    $report .= sprintf(__($guid, 'All form groups have been registered today (%1$s).'), dateConvertBack($guid, $currentDate));
                }
            }
        }


        //Produce array of attendance data for Classes ------------------------------------------------------------------------------------------------------
        
        if ($enabledByClass == 'Y') {
            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => $currentDate);

                // Looks for only courses that are scheduled on the current day and have attendance='Y', also grabs tutor name
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name as class, gibbonCourse.name as course, gibbonCourse.nameShort as courseShort,  gibbonCourseClassPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname, (SELECT count(*) FROM gibbonCourseClassPerson WHERE role='Student' AND gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) AS studentCount 
                FROM gibbonCourseClass 
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
                JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) 
                JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) 
                JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) 
                WHERE gibbonTTDayDate.date =:date 
                AND gibbonCourseClassPerson.role = 'Teacher' 
                AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID 
                AND gibbonCourseClass.attendance = 'Y' 
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
                    $report .= sprintf(__($guid, '%1$s classes have not been registered today (%2$s).'), count($adminReport['classes']), dateConvertBack($guid, $currentDate)).'<br/><br/>'.$reportInner;
                } else {
                    $report .= '<br/><br/>';
                    $report .= sprintf(__($guid, 'All classes have been registered today (%1$s).'), dateConvertBack($guid, $currentDate));
                }
            }
        }


        if ($partialFail == false) {
            //Notify non-completing tutors
            foreach ($userReport as $gibbonPersonID => $items ) {

                $notificationText = __($guid, 'You have not taken attendance yet today. Please do so as soon as possible.');
                $id = 0;

                if ($enabledByRollGroup == 'Y') {
                    // Output the roll groups the particular user is a part of
                    if ( isset($items['rollGroup']) && count($items['rollGroup']) > 0) {
                        $notificationText .= '<br/><br/>';
                        $notificationText .= '<b>'.__($guid, 'Roll Group').':</b><br/>';
                        foreach ($items['rollGroup'] as $rollGroup) {
                            $id = $rollGroup['gibbonRollGroupID'];
                            $notificationText .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $rollGroup['name'] .'<br/>';
                        }
                        
                    }
                }

                if ($enabledByClass == 'Y') {
                    // Output the classes the particular user is a part of
                    if ( isset($items['classes']) && count($items['classes']) > 0) {
                        $notificationText .= '<br/><br/>';
                        $notificationText .= '<b>'.__($guid, 'Classes').':</b><br/>';
                        foreach ($items['classes'] as $class) {
                            $notificationText .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $class['name'] .'<br/>'; 
                        }
                        $notificationText .= '<br/>';
                    }
                }
                
                setNotification($connection2, $guid,  $gibbonPersonID, $notificationText, 'Attendance', '/index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID='.$id.'&currentDate='.dateConvertBack($guid, date('Y-m-d')));
            }

        } else {
            // Notify admin if there was an error in the report
            $report = __($guid, 'Your request failed due to a database error.') . '<br/><br/>' . $report;
        }

        // Notify admin
        $notificationText = __($guid, 'An Attendance CLI script has run.').' '.$report;
        setNotification($connection2, $guid, $_SESSION[$guid]['organisationAdministrator'], $notificationText, 'Attendance', '/index.php?q=/modules/Attendance/report_rollGroupsNotRegistered_byDate.php');

        // Notify Additional Users
        if (!empty($additionalUsersList)) {
            $additionalUsers = explode(',', $additionalUsersList );

            if (is_array($additionalUsers) && count($additionalUsers) > 0) {
                foreach ($additionalUsers as $gibbonPersonID) {

                    // Skip duplicates if the Admin is in the Additional Users list
                    if ($gibbonPersonID == $_SESSION[$guid]['organisationAdministrator']) continue;

                    // Confirm that this user still has permission to access these reports
                    try {
                        $data = array( 'gibbonPersonID' => $gibbonPersonID, 'action1' => '%report_rollGroupsNotRegistered_byDate.php%', 'action2' => '%report_courseClassesNotRegistered_byDate.php%' );
                        $sql = "SELECT gibbonAction.name FROM gibbonAction, gibbonPermission, gibbonRole, gibbonPerson WHERE (gibbonAction.URLList LIKE :action1 OR gibbonAction.URLList LIKE :action2) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) AND (gibbonPermission.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary) AND (gibbonPerson.gibbonPersonID=:gibbonPersonID) AND (gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Attendance'))";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    }  catch (PDOException $e) {}

                    if ($result->rowCount() > 0) {
                        setNotification($connection2, $guid, $gibbonPersonID, $notificationText, 'Attendance', '/index.php?q=/modules/Attendance/report_rollGroupsNotRegistered_byDate.php');
                    }
                }
            }
        }

        // Output the result to terminal
        echo $report."\n";
    }
}
