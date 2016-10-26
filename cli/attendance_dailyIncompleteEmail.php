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

        $userReport = array();
        $adminReport = array();

        //Produce array of attendance data ------------------------------------------------------------------------------------------------------
        try {
            $data = array('date' => $currentDate);
            $sql = 'SELECT gibbonRollGroupID FROM gibbonAttendanceLogRollGroup WHERE date=:date';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $report .= __($guid, 'Your request failed due to a database error.');
        }

        $log = array();
        while ($row = $result->fetch()) {
            $log[$row['gibbonRollGroupID']] = true;
        }

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonRollGroupID, gibbonRollGroup.name, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, gibbonPerson.preferredName, gibbonPerson.surname FROM gibbonRollGroup JOIN gibbonPerson ON (gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND attendance = 'Y' ORDER BY LENGTH(gibbonRollGroup.name), gibbonRollGroup.name";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $report .= __($guid, 'Your request failed due to a database error.');
        }

        if ($result->rowCount() < 1) {
            $report .= __($guid, 'There are no records to display.');
        } else {
            $countRollGroups = 0;

            while ($row = $result->fetch()) {
                if (isset($log[$row['gibbonRollGroupID']]) == false) {
                    ++$countRollGroups;
                    $rollGroupInfo = array( 'gibbonRollGroupID' => $row['gibbonRollGroupID'], 'name' => $row['name'] );

                    $reportInner .= $row['name'].'<br/>';
                    $adminReport['rollGroup'][] = '<b>'.$row['name'] .'</b> - '. $row['preferredName'].' '.$row['surname'];

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
        }

        if ( $countRollGroups > 0) {
            $reportInner = implode('<br>', $adminReport['rollGroup']);
            $report .= '<br/><br/>';
            $report .= sprintf(__($guid, '%1$s form groups have not been registered today  (%2$s).'), $countRollGroups, dateConvertBack($guid, $currentDate)).'<br/><br/>'.$reportInner;
        } else {
            $report .= '<br/><br/>';
            $report .= sprintf(__($guid, 'All form groups have been registered today (%1$s).'), dateConvertBack($guid, $currentDate));
        }


        //Produce array of attendance data for Classes ------------------------------------------------------------------------------------------------------
        try {
            $data = array('date' => $currentDate);
            $sql = 'SELECT gibbonCourseClassID FROM gibbonAttendanceLogCourseClass WHERE date=:date';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $report .= __($guid, 'Your request failed due to a database error.');
        }

        $log = array();
        while ($row = $result->fetch()) {
            $log[$row['gibbonCourseClassID']] = true;
        }

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => $currentDate);
            $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name as class, gibbonCourse.name as course, gibbonCourse.nameShort as courseShort,  gibbonCourseClassPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname, (SELECT count(*) FROM gibbonCourseClassPerson WHERE role='Student' AND gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) as studentCount FROM gibbonCourseClass JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) WHERE gibbonTTDayDate.date =:date AND gibbonCourseClassPerson.role = 'Teacher' AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.attendance = 'Y' ORDER BY gibbonPerson.surname, gibbonCourse.nameShort, gibbonCourseClass.nameShort";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $report .= __($guid, 'Your request failed due to a database error.');
        }

        if ($result->rowCount() < 1) {
            $report .= __($guid, 'There are no records to display.');
        } else {
            $countClasses = 0;
            while ($row = $result->fetch()) {
                if ($row['studentCount'] <= 0) continue;
                if (isset($log[$row['gibbonCourseClassID']]) == false) {
                    ++$countClasses;
                    $className = $row['course'].' ('.$row['courseShort'].'.'.$row['class'].')';
                    //$className .= ' - ' . $row['preferredName'].' '.$row['surname'];
                    $classInfo = array( 'gibbonCourseClassID' => $row['gibbonCourseClassID'], 'name' => $className );

                    $reportInner .= $className.'<br/>';
                    $adminReport['classes'][ $row['preferredName'].' '.$row['surname'] ][] = $className;

                    if ($row['gibbonPersonID'] != '') {
                        $userReport[ $row['gibbonPersonID'] ]['classes'][] = $classInfo;
                        ++$countInner;
                    }
                }
            }
        }


        if ( $countClasses > 0) {
            $reportInner = '';
            foreach ($adminReport['classes'] as $teacherName => $classes) {
                $reportInner .= '<b>' . $teacherName;
                $reportInner .= (count($classes) > 1)? ' ('.count($classes).')</b><br/>' : '</b><br/>';
                foreach ($classes as $className) {
                    $reportInner .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $className .'<br/>';
                }
                $reportInner .= '<br>';
            }
            
            $report .= '<br/><br/>';
            $report .= sprintf(__($guid, '%1$s classes have not been registered today  (%2$s).'), $countClasses, dateConvertBack($guid, $currentDate)).'<br/><br/>'.$reportInner;
        } else {
            $report .= '<br/><br/>';
            $report .= sprintf(__($guid, 'All classes have been registered today (%1$s).'), dateConvertBack($guid, $currentDate));
        }


        echo $report."\n";

        //Notify non-completing tutors
        foreach ($userReport as $gibbonPersonID => $items ) {

            $notificationText = __($guid, 'You have not taken attendance yet today. Please do so as soon as possible.');
            $id = 0;

            $notificationText .= '<br/><br/>';
            if ( isset($items['rollGroup']) && count($items['rollGroup']) > 0) {
                $notificationText .= '<b>'.__($guid, 'Homeroom').':</b><br/>';
                foreach ($items['rollGroup'] as $rollGroup) {
                    $id = $rollGroup['gibbonRollGroupID'];
                    $notificationText .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $rollGroup['name'] .'<br/>';
                }
                $notificationText .= '<br/><br/>';
            }
            
            if ( isset($items['classes']) && count($items['classes']) > 0) {
                $notificationText .= '<b>'.__($guid, 'Classes').':</b><br/>';
                foreach ($items['classes'] as $class) {
                    $notificationText .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $class['name'] .'<br/>'; 
                }
                $notificationText .= '<br/>';
            }
            
            setNotification($connection2, $guid,  $gibbonPersonID, $notificationText, 'Attendance', '/index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID='.$id.'&currentDate='.dateConvertBack($guid, date('Y-m-d')));

        }

        //Notify admin {
        $notificationText = __($guid, 'An Attendance CLI script has run.').' '.$report;
        setNotification($connection2, $guid, $_SESSION[$guid]['organisationAdministrator'], $notificationText, 'Attendance', '/index.php?q=/modules/Attendance/report_rollGroupsNotRegistered_byDate.php');
    }
}
