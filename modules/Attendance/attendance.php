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

use Gibbon\Domain\DataSet;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\Renderer\SimpleRenderer;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

// get session object
$session = $container->get('session');

$page->breadcrumbs->add(__('View Daily Attendance'));

// rendering parameters
$currentDate = isset($_GET['currentDate']) ? Format::dateConvert($_GET['currentDate']) : date('Y-m-d');
$today = date("Y-m-d");
$lastNSchoolDays = getLastNSchoolDays($guid, $connection2, $currentDate, 10, true);
$accessNotRegistered = isActionAccessible($guid, $connection2, "/modules/Attendance/report_rollGroupsNotRegistered_byDate.php")
    && isActionAccessible($guid, $connection2, "/modules/Attendance/report_courseClassesNotRegistered_byDate.php");
$gibbonPersonID = ($accessNotRegistered && isset($_GET['gibbonPersonID'])) ?
    $_GET['gibbonPersonID'] : $session->get('gibbonPersonID');

// show access denied message, if needed
if (!isActionAccessible($guid, $connection2, '/modules/Attendance/attendance.php')) {
    $page->addError(__("You do not have access to this action."));
    return;
}

// define attendance filter form, if user is permit to view it
$form = Form::create('action', $session->get('absoluteURL') . '/index.php', 'get');

$form->setTitle(__('View Daily Attendance'));
$form->setFactory(DatabaseFormFactory::create($pdo));
$form->setClass('noIntBorder fullWidth');

$form->addHiddenValue('q', '/modules/' . $session->get('module') . '/attendance.php');

$row = $form->addRow();
$row->addLabel('currentDate', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
$row->addDate('currentDate')->setValue(Format::date($currentDate))->required();

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_rollGroupsNotRegistered_byDate.php')) {
    $row = $form->addRow();
    $row->addLabel('gibbonPersonID', __('Staff'));
    $row->addSelectStaff('gibbonPersonID')->selected($gibbonPersonID)->placeholder()->required();
} else {
    $form->addHiddenValue('gibbonPersonID', $session->get('gibbonPersonID'));
}

$row = $form->addRow();
$row->addFooter();
$row->addSearchSubmit($gibbon->session);

// define attendance tables, if user is permit to view them
if (isset($_SESSION[$guid]["username"])) {
    // generator of basic attendance table
    $getDailyAttendanceTable = function ($guid, $connection2, $currentDate, $rowID, $takeAttendanceURL) use ($session) {

        // proto attendance table with columns for both
        // roll group and course class
        $dailyAttendanceTable = DataTable::create(
            '',
            (new SimpleRenderer)
                ->addClass('mini')
                ->addClass('dailyAttendanceTable')
        );

        // column definitions
        $dailyAttendanceTable->addColumn('group', __('Group'))
            ->width('80px')
            ->format(function ($row) use ($session, $rowID) {
                return Format::link(
                    $session->get('absoluteURL') . '/index.php?' .
                        http_build_query(['q' => $row['groupQuery'], $rowID => $row[$rowID]]),
                    $row['groupName']
                );
            });
        $dailyAttendanceTable->addColumn('recent-history', __('Recent History'))
            ->width('342px')
            ->format(function ($row) use ($takeAttendanceURL, $rowID, $session) {
                $dayTable = "<table class='historyCalendarMini'>";

                $l = sizeof($row['recentHistory']);
                for ($i = 0; $i < $l; $i++) {
                    $dayTable .= '<tr>';
                    for ($j = 0; ($j < 10) && ($i + $j < $l); $j++) {
                        // grouping 10 days as a row
                        $day = $row['recentHistory'][$i + $j];
                        $link = '';
                        $content = '';

                        // default link and content
                        if (!empty($day['currentDate']) && !empty($day['currentDayTimestamp'])) {
                            // link and date content of a cell
                            $link = $session->get('absoluteURL') . '/index.php?' . http_build_query([
                                'q' => $takeAttendanceURL,
                                $rowID => $row[$rowID],
                                'currentDate' => $day['currentDate'],
                            ]);
                            $content =
                                '<div class="day">' . date('d', $day['currentDayTimestamp']) . '</div>' .
                                '<div class="month">' . date('M', $day['currentDayTimestamp']) . '</div>';
                        }

                        // determine how to display link and content
                        // according to status
                        switch ($day['status']) {
                            case 'na':
                                $class = 'highlightNoData';
                                $content = __('NA');
                                break;
                            case 'present':
                                $class = 'highlightPresent';
                                $content = Format::link($link, $content);
                                break;
                            case 'absent':
                                $class = 'highlightAbsent';
                                $content = Format::link($link, $content);
                                break;
                            default:
                                $class = 'highlightNoData';
                                break;
                        }

                        $dayTable .= "<td class=\"{$class}\" style=\"padding: 12px !important;\">{$content}</td>";
                    }
                    $i += $j;
                    $dayTable .= '</tr>';
                }

                $dayTable .= '</table>';
                return $dayTable;
            });
        $dailyAttendanceTable->addColumn('today', __('Today'))
            ->width('40px')
            ->format(function ($row) use ($session) {
                switch ($row['today']) {
                    case 'taken':
                        // attendance taken
                        return '<img src="./themes/' . $session->get('gibbonThemeName') . '/img/iconTick.png"/>';
                    case 'not taken':
                        // attendance not taken
                        return '<img src="./themes/' . $session->get('gibbonThemeName') . '/img/iconCross.png"/>';
                    case 'not timetabled':
                        // class not timetabled on the day
                        return '<span title="' . __('This class is not timetabled to run on the specified date. Attendance may still be taken for this group however it currently falls outside the regular schedule for this class.') . '">' .
                            __('N/A') . '</span>';
                }
            });
        $dailyAttendanceTable->addColumn('in', __('In'))
            ->width('40px');
        $dailyAttendanceTable->addColumn('out', __('Out'))
            ->width('40px');

        // action column, if user has the permission, and if this is a school day.
        if (isActionAccessible($guid, $connection2, $takeAttendanceURL) && isSchoolOpen($guid, $currentDate, $connection2)) {
            $dailyAttendanceTable->addActionColumn()
                ->width('50px')
                ->addParam($rowID)
                ->addParam('currentDate')
                ->addAction('takeAttendance')
                ->setLabel(__('Take Attendance'))
                ->setIcon('attendance')
                ->setURL($takeAttendanceURL);
        }

        return $dailyAttendanceTable;
    };

    if ($currentDate > $today) {
        $page->addError(__("The specified date is in the future: it must be today or earlier."));
    } elseif (isSchoolOpen($guid, $currentDate, $connection2)==false) {
        $page->addError(__("School is closed on the specified date, and so attendance information cannot be recorded."));
    }

    if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php")) {
        // Show My Form Groups
        try {
            $result = $connection2->prepare("SELECT gibbonRollGroupID, gibbonRollGroup.nameShort as name, firstDay, lastDay FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroup.attendance = 'Y'");
            $result->execute([
                'gibbonPersonIDTutor1' => $gibbonPersonID,
                'gibbonPersonIDTutor2' => $gibbonPersonID,
                'gibbonPersonIDTutor3' => $gibbonPersonID,
                'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'),
            ]);
        } catch (PDOException $e) {
            $page->addError($e->getMessage());
        }

        if ($result->rowCount() > 0) {
            $attendanceByRollGroup = [];
            while ($row = $result->fetch()) {
                //Produce array of attendance data
                try {
                    $resultAttendance = $connection2->prepare('SELECT date, gibbonRollGroupID, UNIX_TIMESTAMP(timestampTaken) FROM gibbonAttendanceLogRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID AND date>=:dateStart AND date<=:dateEnd ORDER BY date');
                    $resultAttendance->execute([
                        'gibbonRollGroupID' => $row["gibbonRollGroupID"],
                        'dateStart' => $lastNSchoolDays[count($lastNSchoolDays) - 1],
                        'dateEnd' => $lastNSchoolDays[0],
                    ]);
                } catch (PDOException $e) {
                    $page->addError($e->getMessage());
                }
                $logHistory = array();
                while ($rowAttendance = $resultAttendance->fetch()) {
                    $logHistory[$rowAttendance['date']] = true;
                }

                //Grab attendance log for the group & current day
                try {
                    $resultLog = $connection2->prepare("SELECT DISTINCT gibbonAttendanceLogRollGroupID, gibbonAttendanceLogRollGroup.timestampTaken as timestamp,
                        COUNT(DISTINCT gibbonAttendanceLogPerson.gibbonPersonID) AS total,
                        COUNT(DISTINCT CASE WHEN gibbonAttendanceLogPerson.direction = 'Out' THEN gibbonAttendanceLogPerson.gibbonPersonID END) AS absent
                        FROM gibbonAttendanceLogPerson
                        JOIN gibbonAttendanceLogRollGroup ON (gibbonAttendanceLogRollGroup.date = gibbonAttendanceLogPerson.date)
                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonAttendanceLogPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonAttendanceLogRollGroup.gibbonRollGroupID)
                        WHERE gibbonAttendanceLogRollGroup.gibbonRollGroupID=:gibbonRollGroupID
                        AND gibbonAttendanceLogPerson.date LIKE :date
                        AND gibbonAttendanceLogPerson.context = 'Roll Group'
                        GROUP BY gibbonAttendanceLogRollGroup.gibbonAttendanceLogRollGroupID
                        ORDER BY gibbonAttendanceLogPerson.timestampTaken");
                    $resultLog->execute([
                        'gibbonRollGroupID' => $row['gibbonRollGroupID'],
                        'date' => $currentDate . '%'
                    ]);
                } catch (PDOException $e) {
                    $page->addError($e->getMessage());
                }

                $log = $resultLog->fetch();

                // general row variables
                $row['currentDate'] = Format::date($currentDate);

                // render group link variables
                $row['groupQuery'] = '/modules/Roll Groups/rollGroups_details.php';
                $row['groupName'] = $row['name'];

                // render recentHistory into the row
                for ($i = count($lastNSchoolDays) - 1; $i >= 0; --$i) {
                    if ($i > (count($lastNSchoolDays) - 1)) {
                        $dayData = [
                            'currentDate' => null,
                            'currentDayTimestamp' => null,
                            'status' => 'na',
                        ];
                    } else {
                        $dayData = [
                            'currentDate' => Format::dateConvert($lastNSchoolDays[$i]),
                            'currentDayTimestamp' => Format::timestamp($lastNSchoolDays[$i]),
                            'status' => isset($logHistory[$lastNSchoolDays[$i]]) ? 'present' : 'absent',
                        ];
                    }
                    $row['recentHistory'][] = $dayData;
                }

                // Attendance not taken
                $row['today'] = ($resultLog->rowCount() < 1) ? 'not taken' : 'taken';
                $row['in'] = ($resultLog->rowCount() < 1) ? "" : ($log["total"] - $log["absent"]);
                $row['out'] = $log["absent"];

                $attendanceByRollGroup[] = $row;
            }

            // define DataTable
            $takeAttendanceURL = '/modules/Attendance/attendance_take_byRollGroup.php';
            $attendanceByRollGroupTable = $getDailyAttendanceTable(
                $guid,
                $connection2,
                $currentDate,
                'gibbonRollGroupID',
                $takeAttendanceURL
            );
            $attendanceByRollGroupTable->setTitle(__('My Roll Group'));
            $attendanceByRollGroupTable->withData(new DataSet($attendanceByRollGroup));
        }
    }

    if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
        // Produce array of attendance data
        try {
            $result = $connection2->prepare("SELECT date, gibbonCourseClassID FROM gibbonAttendanceLogCourseClass WHERE date>=:dateStart AND date<=:dateEnd ORDER BY date");
            $result->execute([
                'dateStart' => $lastNSchoolDays[count($lastNSchoolDays) - 1],
                'dateEnd' => $lastNSchoolDays[0],
            ]);
        } catch (PDOException $e) {
            $page->addError($e->getMessage());
        }
        $logHistory = array();
        while ($row = $result->fetch()) {
            $logHistory[$row['gibbonCourseClassID']][$row['date']] = true;
        }

        // Produce an array of scheduled classes
        try {
            $result = $connection2->prepare("SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayDate.date FROM gibbonTTDayRowClass JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) WHERE gibbonCourseClass.attendance = 'Y' AND gibbonTTDayDate.date>=:dateStart AND gibbonTTDayDate.date<=:dateEnd ORDER BY gibbonTTDayDate.date");
            $result->execute([
                'dateStart' => $lastNSchoolDays[count($lastNSchoolDays) - 1],
                'dateEnd' => $lastNSchoolDays[0],
            ]);
        } catch (PDOException $e) {
            $page->addError($e->getMessage());
        }
        $ttHistory = array();
        while ($row = $result->fetch()) {
            $ttHistory[$row['gibbonCourseClassID']][$row['date']] = true;
        }

        //Show My Classes
        try {
            $result = $connection2->prepare("SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID,
                (SELECT count(*) FROM gibbonCourseClassPerson WHERE role='Student' AND gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) as studentCount
                FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID
                AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID
                AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left%'
                AND gibbonCourseClass.attendance = 'Y'
                ORDER BY course, class");
            $result->execute([
                'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'),
                'gibbonPersonID' => $gibbonPersonID,
            ]);
        } catch (PDOException $e) {
            //
        }

        if ($result->rowCount() > 0) {
            $count = 0;

            $attendanceByCourseClass = [];
            while ($row = $result->fetch()) {
                // Skip classes with no students
                if ($row['studentCount'] <= 0) {
                    continue;
                }

                $count++;

                //Grab attendance log for the class & current day
                try {
                    $resultLog = $connection2->prepare("SELECT gibbonAttendanceLogCourseClass.timestampTaken as timestamp,
                        COUNT(gibbonAttendanceLogPerson.gibbonPersonID) AS total, SUM(gibbonAttendanceLogPerson.direction = 'Out') AS absent
                        FROM gibbonAttendanceLogCourseClass
                        JOIN gibbonAttendanceLogPerson ON gibbonAttendanceLogPerson.gibbonCourseClassID = gibbonAttendanceLogCourseClass.gibbonCourseClassID
                        WHERE gibbonAttendanceLogCourseClass.gibbonCourseClassID=:gibbonCourseClassID
                        AND gibbonAttendanceLogPerson.context='Class'
                        AND gibbonAttendanceLogCourseClass.date LIKE :date AND gibbonAttendanceLogPerson.date LIKE :date
                        GROUP BY gibbonAttendanceLogCourseClass.gibbonAttendanceLogCourseClassID
                        ORDER BY gibbonAttendanceLogCourseClass.timestampTaken");
                    $resultLog->execute([
                        'gibbonCourseClassID' => $row['gibbonCourseClassID'],
                        'date' => $currentDate . '%',
                    ]);
                } catch (PDOException $e) {
                    $page->addError($e->getMessage());
                }

                $log = $resultLog->fetch();

                // general row variables
                $row['currentDate'] = Format::date($currentDate);

                // render group link variables
                $row['groupQuery'] = '/modules/Departments/department_course_class.php';
                $row['groupName'] = $row["course"] . "." . $row["class"];

                // render recentHistory into the row
                for ($i = count($lastNSchoolDays) - 1; $i >= 0; --$i) {
                    if ($i > (count($lastNSchoolDays) - 1)) {
                        $dayData = [
                            'currentDate' => null,
                            'currentDayTimestamp' => null,
                            'status' => 'na',
                        ];
                    } else {
                        $dayData = [
                            'currentDate' => Format::dateConvert($lastNSchoolDays[$i]),
                            'currentDayTimestamp' => Format::timestamp($lastNSchoolDays[$i]),
                        ];
                        if (isset($logHistory[$row['gibbonCourseClassID']][$lastNSchoolDays[$i]]) == true) {
                            $dayData['status'] = 'present';
                        } else {
                            $dayData['status'] =
                            isset($ttHistory[$row['gibbonCourseClassID']][$lastNSchoolDays[$i]]) ?
                            $dayData['status'] = 'absent' :
                            $dayData['status'] = null;
                        }
                    }
                    $row['recentHistory'][] = $dayData;
                }

                // attendance today, if timetabled
                $row['today'] = null;
                if (isset($ttHistory[$row['gibbonCourseClassID']][$currentDate])) {
                    $row['today'] = ($resultLog->rowCount() < 1) ? 'not taken' : 'taken';
                } elseif (isset($logHistory[$row['gibbonCourseClassID']][$currentDate])) {
                    // class is not timetabled to run on the specified date
                    $row['today'] = 'not timetabled';
                }
                $row['in'] = ($resultLog->rowCount() < 1) ? "" : ($log["total"] - $log["absent"]);
                $row['out'] = $log["absent"];

                $attendanceByCourseClass[] = $row;
            }

            // define DataTable
            $takeAttendanceURL = '/modules/Attendance/attendance_take_byCourseClass.php';
            $attendanceByCourseClassTable = $getDailyAttendanceTable(
                $guid,
                $connection2,
                $currentDate,
                'gibbonCourseClassID',
                $takeAttendanceURL
            );
            $attendanceByCourseClassTable->setTitle(__('My Classes'));
            $attendanceByCourseClassTable->withData(new DataSet($attendanceByCourseClass));
        }
    }
}

//
// write page outputs
//
if (isset($form)) {
    $page->write($form->getOutput());
}
if (isset($attendanceByRollGroupTable)) {
    $page->write($attendanceByRollGroupTable->getOutput());
}
if (isset($attendanceByCourseClassTable)) {
    $page->write($attendanceByCourseClassTable->getOutput());
}
