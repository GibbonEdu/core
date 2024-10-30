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

use Gibbon\Domain\DataSet;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

// get session object
$session = $container->get('session');

$page->breadcrumbs->add(__('View Daily Attendance'));

// show access denied message, if needed
if (!isActionAccessible($guid, $connection2, '/modules/Attendance/attendance.php')) {
    $page->addError(__("You do not have access to this action."));
    return;
}

// rendering parameters
$currentDate = isset($_GET['currentDate']) ? Format::dateConvert($_GET['currentDate']) : date('Y-m-d');
$today = date("Y-m-d");
$lastNSchoolDays = getLastNSchoolDays($guid, $connection2, $currentDate, 10, true);
$accessNotRegistered = isActionAccessible($guid, $connection2, "/modules/Attendance/report_formGroupsNotRegistered_byDate.php")
    && isActionAccessible($guid, $connection2, "/modules/Attendance/report_courseClassesNotRegistered_byDate.php");
$gibbonPersonID = ($accessNotRegistered && isset($_GET['gibbonPersonID'])) ?
    $_GET['gibbonPersonID'] : $session->get('gibbonPersonID');

// define attendance filter form, if user is permit to view it
$form = Form::create('action', $session->get('absoluteURL') . '/index.php', 'get');

$form->setTitle(__('View Daily Attendance'));
$form->setFactory(DatabaseFormFactory::create($pdo));
$form->setClass('noIntBorder fullWidth');

$form->addHiddenValue('q', '/modules/' . $session->get('module') . '/attendance.php');

$row = $form->addRow();
$row->addLabel('currentDate', __('Date'));
$row->addDate('currentDate')->setValue(Format::date($currentDate))->required();

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_formGroupsNotRegistered_byDate.php')) {
    $row = $form->addRow();
    $row->addLabel('gibbonPersonID', __('Staff'));
    $row->addSelectStaff('gibbonPersonID')->selected($gibbonPersonID)->placeholder()->required();
} else {
    $form->addHiddenValue('gibbonPersonID', $session->get('gibbonPersonID'));
}

$row = $form->addRow();
$row->addFooter();
$row->addSearchSubmit($session);

$page->write($form->getOutput());

// define attendance tables, if user is permit to view them
if ($session->has('username')) {
    // generator of basic attendance table
    $getDailyAttendanceTable = function ($guid, $connection2, $currentDate, $rowID, $takeAttendanceURL) use ($session) {

        // proto attendance table with columns for both
        // form group and course class
        $dailyAttendanceTable = DataTable::create('dailyAttendanceTable');

        // column definitions
        $dailyAttendanceTable->addColumn('group', __('Group'))
            ->context('primary')
            ->format(function ($row) use ($session, $rowID) {
                return Format::link(
                    $session->get('absoluteURL') . '/index.php?' .
                        http_build_query(['q' => $row['groupQuery'], $rowID => $row[$rowID]]),
                    $row['groupName']
                );
            });
        $dailyAttendanceTable->addColumn('recent-history', __('Recent History'))
            ->width('40%')
            ->format(function ($row) use ($takeAttendanceURL, $rowID, $session) {
                $dayTable = "<table class='historyCalendarMini rounded-sm overflow-hidden' cellspacing='0'>";

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
                                '<div class="day text-xs">' . Format::date($day['currentDate'], 'd') . '</div>' .
                                '<div class="month text-xxs mt-px">' . Format::monthName($day['currentDate'], true) . '</div>';
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
            ->context('primary')
            ->width('6%')
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
            ->context('primary')
            ->width('6%');

        $dailyAttendanceTable->addColumn('out', __('Out'))
            ->context('primary')
            ->width('6%');

        // action column, if user has the permission, and if this is a school day.
        if (isActionAccessible($guid, $connection2, $takeAttendanceURL) && isSchoolOpen($guid, $currentDate, $connection2)) {
            $dailyAttendanceTable->addActionColumn()
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
        $page->write(Format::alert(__("The specified date is in the future: it must be today or earlier.")));
        return;
    } elseif (isSchoolOpen($guid, $currentDate, $connection2)==false) {
        $page->write(Format::alert(__("School is closed on the specified date, and so attendance information cannot be recorded.")));
        return;
    }

    if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byFormGroup.php")) {
        // Show My Form Groups
        try {
            $result = $connection2->prepare("SELECT gibbonFormGroupID, gibbonFormGroup.nameShort as name, firstDay, lastDay FROM gibbonFormGroup JOIN gibbonSchoolYear ON (gibbonFormGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFormGroup.attendance = 'Y'");
            $result->execute([
                'gibbonPersonIDTutor1' => $gibbonPersonID,
                'gibbonPersonIDTutor2' => $gibbonPersonID,
                'gibbonPersonIDTutor3' => $gibbonPersonID,
                'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'),
            ]);
        } catch (PDOException $e) {
        }

        if ($result->rowCount() > 0) {
            $attendanceByFormGroup = [];
            while ($row = $result->fetch()) {
                //Produce array of attendance data
                try {
                    $resultAttendance = $connection2->prepare('SELECT date, gibbonFormGroupID, UNIX_TIMESTAMP(timestampTaken) FROM gibbonAttendanceLogFormGroup WHERE gibbonFormGroupID=:gibbonFormGroupID AND date>=:dateStart AND date<=:dateEnd ORDER BY date');
                    $resultAttendance->execute([
                        'gibbonFormGroupID' => $row["gibbonFormGroupID"],
                        'dateStart' => $lastNSchoolDays[count($lastNSchoolDays) - 1],
                        'dateEnd' => $lastNSchoolDays[0],
                    ]);
                } catch (PDOException $e) {
                }
                $logHistory = array();
                while ($rowAttendance = $resultAttendance->fetch()) {
                    $logHistory[$rowAttendance['date']] = true;
                }

                //Grab attendance log for the group & current day
                try {
                    $resultLog = $connection2->prepare("SELECT DISTINCT gibbonAttendanceLogFormGroupID, gibbonAttendanceLogFormGroup.timestampTaken as timestamp,
                        COUNT(DISTINCT gibbonAttendanceLogPerson.gibbonPersonID) AS total,
                        COUNT(DISTINCT CASE WHEN gibbonAttendanceLogPerson.direction = 'Out' THEN gibbonAttendanceLogPerson.gibbonPersonID END) AS absent
                        FROM gibbonAttendanceLogPerson
                        JOIN gibbonAttendanceLogFormGroup ON (gibbonAttendanceLogFormGroup.date = gibbonAttendanceLogPerson.date)
                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonAttendanceLogPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonFormGroupID=gibbonAttendanceLogFormGroup.gibbonFormGroupID)
                        WHERE gibbonAttendanceLogFormGroup.gibbonFormGroupID=:gibbonFormGroupID
                        AND gibbonAttendanceLogPerson.date LIKE :date
                        AND gibbonAttendanceLogPerson.context = 'Form Group'
                        GROUP BY gibbonAttendanceLogFormGroup.gibbonAttendanceLogFormGroupID
                        ORDER BY gibbonAttendanceLogPerson.timestampTaken");
                    $resultLog->execute([
                        'gibbonFormGroupID' => $row['gibbonFormGroupID'],
                        'date' => $currentDate . '%'
                    ]);
                } catch (PDOException $e) {
                }

                $log = $resultLog->fetch();

                // general row variables
                $row['currentDate'] = Format::date($currentDate);

                // render group link variables
                $row['groupQuery'] = '/modules/Form Groups/formGroups_details.php';
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
                $row['out'] = $log["absent"] ?? '';

                $attendanceByFormGroup[] = $row;
            }

            // define DataTable
            $takeAttendanceURL = '/modules/Attendance/attendance_take_byFormGroup.php';
            $attendanceByFormGroupTable = $getDailyAttendanceTable(
                $guid,
                $connection2,
                $currentDate,
                'gibbonFormGroupID',
                $takeAttendanceURL
            );
            $attendanceByFormGroupTable->setTitle(__('My Form Group'));
            $attendanceByFormGroupTable->withData(new DataSet($attendanceByFormGroup));
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
                $row['out'] = $log["absent"] ?? '';

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
if (isset($attendanceByFormGroupTable)) {
    $page->write($attendanceByFormGroupTable->getOutput());
}
if (isset($attendanceByCourseClassTable)) {
    $page->write($attendanceByCourseClassTable->getOutput());
}

if (empty($attendanceByFormGroupTable) && empty($attendanceByCourseClassTable)) {
    echo DataTable::create('blank')->setDescription('<br/>')->withData([])->getOutput();
}
