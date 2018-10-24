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
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\Renderer\SimpleRenderer;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

// data table definition
$attendance_table = ''; // TODO: should remove this in long run
$errors = [];

$currentDate = (isset($_GET["currentDate"])==false) ? date("Y-m-d") : dateConvert($guid, $_GET["currentDate"]);
$today = date("Y-m-d");
$lastNSchoolDays = getLastNSchoolDays($guid, $connection2, $currentDate, 10, true);
$accessNotRegistered = isActionAccessible($guid, $connection2, "/modules/Attendance/report_rollGroupsNotRegistered_byDate.php")
    && isActionAccessible($guid, $connection2, "/modules/Attendance/report_courseClassesNotRegistered_byDate.php");
$gibbonPersonID = ($accessNotRegistered && isset($_GET['gibbonPersonID'])) ?
    $_GET['gibbonPersonID'] : $_SESSION[$guid]["gibbonPersonID"];

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance.php') && isset($_SESSION[$guid]["username"])) {

    $attendanceByRollTable = DataTable::create(
        'attendance-by-roll',
        (new SimpleRenderer)
            ->setID('tableAttendanceByRollGroup')
            ->addClass('mini')
    );

    if ($currentDate>$today) {
        $errors[] = __("The specified date is in the future: it must be today or earlier.");
    } elseif (isSchoolOpen($guid, $currentDate, $connection2)==false) {
        $errors[] = __("School is closed on the specified date, and so attendance information cannot be recorded.");
    } else {
        if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php")) {
            // Show My Form Groups
            try {
                $data=array("gibbonPersonIDTutor1"=>$gibbonPersonID, "gibbonPersonIDTutor2"=>$gibbonPersonID, "gibbonPersonIDTutor3"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]);
                $sql="SELECT gibbonRollGroupID, gibbonRollGroup.nameShort as name, firstDay, lastDay FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroup.attendance = 'Y'";
                $result=$connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $errors[] = $e->getMessage();
            }

            if ($result->rowCount()>0) {
                $attendance_table .= "<h2 style='margin-bottom: 10px'  class='sidebar'>";
                $attendance_table .= __($guid, "My Roll Group");
                $attendance_table .= "</h2>";

                $attendanceByRollTable->addColumn('group', __('Group'))
                    ->width('80px');
                $attendance_table .= "<table class='mini' cellspacing='0' style='width: 100%;table-layout: fixed;'>";
                $attendance_table .= "<tr class='head'>";
                $attendance_table .= "<th style='width: 80px;font-size: 85%;text-transform: uppercase'>";
                $attendance_table .= __("Group");
                $attendance_table .= "</th>";

                $attendanceByRollTable->addColumn('recent-history', __('Recent History'))
                    ->width('342px');
                $attendance_table .= "<th style='width: 342px;font-size: 60%;text-align: center;text-transform: uppercase'>";
                $attendance_table .= __("Recent History");
                $attendance_table .= "</th>";

                $attendanceByRollTable->addColumn('today', __('Today'))
                    ->width('40px');
                $attendance_table .= "<th style='width: 40px;font-size: 60%;text-align: center;text-transform: uppercase'>";
                $attendance_table .= __("Today");
                $attendance_table .= "</th>";

                $attendanceByRollTable->addColumn('in', __('In'))
                    ->width('40px');
                $attendance_table .= "<th style='width: 40px;font-size: 60%;text-align: center;text-transform: uppercase'>";
                $attendance_table .= __("In");
                $attendance_table .= "</th>";

                $attendanceByRollTable->addColumn('out', __('Out'))
                    ->width('40px');
                $attendance_table .= "<th style='width: 40px;font-size: 60%;text-align: center;text-transform: uppercase'>";
                $attendance_table .= __("Out");
                $attendance_table .= "</th>";

                if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php")) {
                    $attendanceByRollTable->addColumn('actions', __('Actions'))
                        ->width('50px');
                    $attendance_table .= "<th style='width: 50px;font-size: 60%;text-align: center;text-transform: uppercase'>";
                    $attendance_table .= __("Actions");
                    $attendance_table .= "</th>";
                }

                $attendance_table .= "</tr>";

                $attendanceByRollGroup = [];
                while ($row=$result->fetch()) {
                    $attendanceByRollGroupRow = [];

                    //Produce array of attendance data
                    try {
                        $dataAttendance = array("gibbonRollGroupID" => $row["gibbonRollGroupID"], 'dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0] );
                        $sqlAttendance = 'SELECT date, gibbonRollGroupID, UNIX_TIMESTAMP(timestampTaken) FROM gibbonAttendanceLogRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID AND date>=:dateStart AND date<=:dateEnd ORDER BY date';
                        $resultAttendance = $connection2->prepare($sqlAttendance);
                        $resultAttendance->execute($dataAttendance);
                    } catch (PDOException $e) {
                        $errors[] = $e->getMessage();
                    }
                    $logHistory = array();
                    while ($rowAttendance = $resultAttendance->fetch()) {
                        $logHistory[$rowAttendance['date']] = true;
                    }

                    //Grab attendance log for the group & current day
                    try {
                        $dataLog=array("gibbonRollGroupID"=>$row["gibbonRollGroupID"], "date"=>$currentDate . "%");

                        $sqlLog = "SELECT DISTINCT gibbonAttendanceLogRollGroupID, gibbonAttendanceLogRollGroup.timestampTaken as timestamp,
                        COUNT(DISTINCT gibbonAttendanceLogPerson.gibbonPersonID) AS total,
                        COUNT(DISTINCT CASE WHEN gibbonAttendanceLogPerson.direction = 'Out' THEN gibbonAttendanceLogPerson.gibbonPersonID END) AS absent
                        FROM gibbonAttendanceLogPerson
                        JOIN gibbonAttendanceLogRollGroup ON (gibbonAttendanceLogRollGroup.date = gibbonAttendanceLogPerson.date)
                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonAttendanceLogPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonAttendanceLogRollGroup.gibbonRollGroupID)
                        WHERE gibbonAttendanceLogRollGroup.gibbonRollGroupID=:gibbonRollGroupID
                        AND gibbonAttendanceLogPerson.date LIKE :date
                        AND gibbonAttendanceLogPerson.context = 'Roll Group'
                        GROUP BY gibbonAttendanceLogRollGroup.gibbonAttendanceLogRollGroupID
                        ORDER BY gibbonAttendanceLogPerson.timestampTaken";

                        $resultLog=$connection2->prepare($sqlLog);
                        $resultLog->execute($dataLog);
                    } catch (PDOException $e) {
                        $errors[] = $e->getMessage();
                    }

                    $log=$resultLog->fetch();

                    $attendance_table .= "<tr>";

                    $attendanceByRollGroupRow = [];

                    $attendanceByRollGroupRow[] = "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "'>" . $row["name"] . "</a>";
                    $attendance_table .= "<td style='word-wrap: break-word'>";
                    $attendance_table .= "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "'>" . $row["name"] . "</a>";
                    $attendance_table .= "</td>";

                    $dayTable = '';
                    $dayTable .= "<table cellspacing='0' class='historyCalendarMini' style='width:160px;margin:0;' >";
                    $dayTable .= '<tr>';
                    $historyCount = 0;

                    for ($i = count($lastNSchoolDays)-1; $i >= 0; --$i) {
                        $link = '';
                        if ($i > (count($lastNSchoolDays) - 1)) {
                            $dayTable .= "<td class='highlightNoData'>";
                            $dayTable .= '<i>'.__($guid, 'NA').'</i>';
                            $dayTable .= '</td>';
                        } else {
                            $currentDayTimestamp = dateConvertToTimestamp($lastNSchoolDays[$i]);

                            $link = './index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID='.$row['gibbonRollGroupID'].'&currentDate='.dateConvertBack($guid, $lastNSchoolDays[$i]);

                            if (isset($logHistory[$lastNSchoolDays[$i]]) == false) {
                                //$class = 'highlightNoData';
                                $class = 'highlightAbsent';
                            } else {
                                $class = 'highlightPresent';
                            }

                            $dayTable .= "<td class='$class' style='padding: 12px !important;'>";
                            $dayTable .= "<a href='$link'>";
                            $dayTable .= date('d', $currentDayTimestamp).'<br/>';
                            $dayTable .= "<span>".date('M', $currentDayTimestamp).'</span>';
                            $dayTable .= '</a>';
                            $dayTable .= '</td>';
                        }

                        // Wrap to a new line every 10 dates
                        if (($historyCount+1) % 10 == 0) {
                            $dayTable .= '</tr><tr>';
                        }
                        $historyCount++;
                    }

                    $dayTable .= '</tr>';
                    $dayTable .= '</table>';

                    $attendanceByRollGroupRow[] = $dayTable;
                    $attendance_table .= "<td style='text-align: center'>" . $dayTable . "</td>";

                    // Attendance not taken
                    $attendance_image = ($resultLog->rowCount()<1) ?
                        '<img src="./themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/iconCross.png"/>' :
                        '<img src="./themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/iconTick.png"/>';

                    $attendanceByRollGroupRow[] = $attendance_image;
                    $attendance_table .= "<td style='text-align: center'>";
                    $attendance_table .= $attendance_image;
                    $attendance_table .= "</td>";

                    $attendanceByRollGroupRow[] = ($resultLog->rowCount()<1)? "" : ($log["total"] - $log["absent"]);
                    $attendance_table .= "<td style='text-align: center'>";
                    $attendance_table .= ($resultLog->rowCount()<1)? "" : ($log["total"] - $log["absent"]);
                    $attendance_table .= "</td>";

                    $attendanceByRollGroupRow[] = $log["absent"];
                    $attendance_table .= "<td style='text-align: center'>";
                    $attendance_table .= $log["absent"];
                    $attendance_table .= "</td>";

                    if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php")) {
                        $attendanceByRollGroupRow[] = "<a href='index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "&currentDate=" . dateConvertBack($guid, $currentDate) . "'><img title='" . __('Take Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>";
                        $attendance_table .= "<td style='text-align: center'>";
                        $attendance_table .= "<a href='index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "&currentDate=" . dateConvertBack($guid, $currentDate) . "'><img title='" . __('Take Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>";
                        $attendance_table .= "</td>";
                    }

                    $attendance_table .= "</tr>";
                    $attendanceByRollGroup[] = $attendanceByRollGroupRow;
                }

                $attendance_table .= "</table><br/>";
            }
        }

        if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {

            // Produce array of attendance data
            try {
                $data = array('dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0]);
                $sql = "SELECT date, gibbonCourseClassID FROM gibbonAttendanceLogCourseClass WHERE date>=:dateStart AND date<=:dateEnd ORDER BY date";

                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $errors[] = $e->getMessage();
            }
            $logHistory = array();
            while ($row = $result->fetch()) {
                $logHistory[$row['gibbonCourseClassID']][$row['date']] = true;
            }

            // Produce an array of scheduled classes
            try {
                $data = array('dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0] );
                $sql = "SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayDate.date FROM gibbonTTDayRowClass JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) WHERE gibbonCourseClass.attendance = 'Y' AND gibbonTTDayDate.date>=:dateStart AND gibbonTTDayDate.date<=:dateEnd ORDER BY gibbonTTDayDate.date";

                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $errors[] = $e->getMessage();
            }
            $ttHistory = array();
            while ($row = $result->fetch()) {
                $ttHistory[$row['gibbonCourseClassID']][$row['date']] = true;
            }

            //Show My Classes
            try {
                $data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=> $gibbonPersonID);

                $sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID,
                (SELECT count(*) FROM gibbonCourseClassPerson WHERE role='Student' AND gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) as studentCount
                FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID
                AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID
                AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left%'
                AND gibbonCourseClass.attendance = 'Y'
                ORDER BY course, class";

                $result=$connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                //
            }

            if ($result->rowCount()>0) {
                $count=0;

                $attendanceByCourseClass = [];
                while ($row=$result->fetch()) {
                    $dataRow = [];

                    // Skip unscheduled courses
                    //if ( isset($ttHistory[$row['gibbonCourseClassID']]) == false || count($ttHistory[$row['gibbonCourseClassID']]) == 0) continue;

                    // Skip classes with no students
                    if ($row['studentCount'] <= 0) {
                        continue;
                    }

                    $count++;

                    //Grab attendance log for the class & current day
                    try {
                        $dataLog=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "date"=>$currentDate . "%");

                        $sqlLog="SELECT gibbonAttendanceLogCourseClass.timestampTaken as timestamp,
                        COUNT(gibbonAttendanceLogPerson.gibbonPersonID) AS total, SUM(gibbonAttendanceLogPerson.direction = 'Out') AS absent
                        FROM gibbonAttendanceLogCourseClass
                        JOIN gibbonAttendanceLogPerson ON gibbonAttendanceLogPerson.gibbonCourseClassID = gibbonAttendanceLogCourseClass.gibbonCourseClassID
                        WHERE gibbonAttendanceLogCourseClass.gibbonCourseClassID=:gibbonCourseClassID
                        AND gibbonAttendanceLogPerson.context='Class'
                        AND gibbonAttendanceLogCourseClass.date LIKE :date AND gibbonAttendanceLogPerson.date LIKE :date
                        GROUP BY gibbonAttendanceLogCourseClass.gibbonAttendanceLogCourseClassID
                        ORDER BY gibbonAttendanceLogCourseClass.timestampTaken";

                        $resultLog=$connection2->prepare($sqlLog);
                        $resultLog->execute($dataLog);
                    } catch (PDOException $e) {
                        $errors[] = $e->getMessage();
                    }

                    $log=$resultLog->fetch();

                    $dataRow['group'] = "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'>" . $row["course"] . "." . $row["class"] . "</a>";

                    // day table
                    $dayTable = "<table cellspacing='0' class='historyCalendarMini' style='width:160px;margin:0;' >";
                    $dayTable .= '<tr>';

                    $historyCount = 0;
                    for ($i = count($lastNSchoolDays)-1; $i >= 0; --$i) {
                        $link = '';
                        if ($i > ( count($lastNSchoolDays) - 1)) {
                            $dayTable .= "<td class='highlightNoData'>";
                            $dayTable .= '<i>'.__($guid, 'NA').'</i>';
                            $dayTable .= '</td>';
                        } else {
                            $currentDayTimestamp = dateConvertToTimestamp($lastNSchoolDays[$i]);

                            $link = './index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID='.$row['gibbonCourseClassID'].'&currentDate='.dateConvertBack($guid, $lastNSchoolDays[$i]);

                            if (isset($logHistory[$row['gibbonCourseClassID']][$lastNSchoolDays[$i]]) == true) {
                                $class = 'highlightPresent';
                            } else {
                                if (isset($ttHistory[$row['gibbonCourseClassID']][$lastNSchoolDays[$i]]) == true) {
                                    $class = 'highlightAbsent';
                                } else {
                                    $class = 'highlightNoData';
                                    $link = '';
                                }
                            }

                            $dayTable .= "<td class='$class' style='padding: 12px !important;'>";
                            if ($link != '') {
                                $dayTable .= "<a href='$link'>";
                                $dayTable .= date('d', $currentDayTimestamp).'<br/>';
                                $dayTable .= "<span>".date('M', $currentDayTimestamp).'</span>';
                                $dayTable .= '</a>';
                            } else {
                                $dayTable .= date('d', $currentDayTimestamp).'<br/>';
                                $dayTable .= "<span>".date('M', $currentDayTimestamp).'</span>';
                            }
                            $dayTable .= '</td>';

                            // Wrap to a new line every 10 dates
                            if (($historyCount+1) % 10 == 0) {
                                $dayTable .= '</tr><tr>';
                            }

                            $historyCount++;
                        }
                    }
                    $dayTable .= '</tr>';
                    $dayTable .= '</table>';

                    $dataRow['recent-history'] = $dayTable;

                    // Attendance not taken, timetabled
                    $attendance_image = '';
                    if (isset($ttHistory[$row['gibbonCourseClassID']][$currentDate]) == true) {
                        $attendance_image = ($resultLog->rowCount()<1) ?
                            '<img src="./themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/iconCross.png"/>' :
                            '<img src="./themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/iconTick.png"/>';
                    } elseif (isset($logHistory[$row['gibbonCourseClassID']][$currentDate])) {
                        $attendance_image = '<span title="'.__('This class is not timetabled to run on the specified date. Attendance may still be taken for this group however it currently falls outside the regular schedule for this class.').'">';
                        $attendance_image .= __('N/A');
                        $attendance_image .= '</span>';
                    }
                    $dataRow['today'] = $attendance_image;

                    $attendance_count = ($resultLog->rowCount()<1)? "" : ($log["total"] - $log["absent"]);
                    $dataRow['in'] = $attendance_count;

                    $dataRow['out'] = $log["absent"];

                    if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
                        $attendance_action = "<a href='index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&currentDate=" . dateConvertBack($guid, $currentDate) . "'><img title='" . __('Take Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>";
                        $dataRow['actions'] = $attendance_action;
                    }

                    $attendanceByCourseClass[] = $dataRow;
                }

                // define DataTable
                $attendanceByCourseClassTable = DataTable::create(
                    'attendance-by-course-class',
                    (new SimpleRenderer)
                        ->setID('tableAttendanceByCourseClass')
                        ->addClass('mini')
                );
                $attendanceByCourseClassTable->addColumn('group', __('Group'))
                    ->width('80px');
                $attendanceByCourseClassTable->addColumn('recent-history', __('Recent History'))
                    ->width('342px');
                $attendanceByCourseClassTable->addColumn('today', __('Today'))
                    ->width('40px');
                $attendanceByCourseClassTable->addColumn('in', __('In'))
                    ->width('40px');
                $attendanceByCourseClassTable->addColumn('out', __('Out'))
                    ->width('40px');
                if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
                    $attendanceByCourseClassTable->addColumn('actions', __('Actions'))
                        ->width('50px');
                }

                // assign data set to the DataTable
                $attendanceByCourseClassTable->withData(new DataSet($attendanceByCourseClass));
            }
        }
    }
}

$output = '';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance.php')==false) {
    //Acess denied
    $errors[] = __("You do not have access to this action.");
} else {
    //Proceed!
    $output .= "<div class='trail'>";
    $output .= "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('View Daily Attendance') . "</div>";
    $output .= "</div>";

    $output .= "<h2>";
    $output .= __("View Daily Attendance");
    $output .= "</h2>";

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/attendance.php");

    $row = $form->addRow();
    $row->addLabel('currentDate', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
    $row->addDate('currentDate')->setValue(dateConvertBack($guid, $currentDate))->isRequired();

    if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_rollGroupsNotRegistered_byDate.php')) {
        $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Staff'));
        $row->addSelectStaff('gibbonPersonID')->selected($gibbonPersonID)->placeholder()->isRequired();
    } else {
        $form->addHiddenValue('gibbonPersonID', $_SESSION[$guid]['gibbonPersonID']);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    $output .= $form->getOutput();
}

// show errors
echo array_map(function ($error) {
    return "<div class='error'>{$error}</div>\n";
}, $errors);

// show form output
echo $output;

// show attendance table
if (!empty($attendance_table)) {
    echo $attendance_table;
}
if (isset($attendanceByCourseClassTable)) {
    echo "<h2 style='margin-bottom: 10px'  class='sidebar'>";
    echo __("My Classes");
    echo "</h2>";
    echo $attendanceByCourseClassTable->getOutput();
}
