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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

@session_start();

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php";

require_once $_SESSION[$guid]['absolutePath'].'/modules/Attendance/src/attendanceView.php';

if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php") == false) {
    //Acess denied
    echo "<div class='error'>";
        echo __("You do not have access to this action.");
    echo "</div>";
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Take Attendance by Class') . "</div>";
    echo "</div>";

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array( 'error3' => __($guid, 'Your request failed because the specified date is not in the future, or is not a school day.')));
    }

    $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);

    $gibbonCourseClassID = isset($_GET['gibbonCourseClassID'])? $_GET['gibbonCourseClassID'] : '';
    if (empty($gibbonCourseClassID)) {
        try {
            $data=array('gibbonPersonID'=>$_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID'=>$_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonSchoolYear.firstDay, gibbonSchoolYear.lastDay
                    FROM gibbonCourse
                    JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                    JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                    JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                    WHERE gibbonPersonID=:gibbonPersonID
                    AND gibbonCourseClass.attendance='Y'
                    AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID";
            $result=$connection2->prepare($sql);
            $result->execute($data);
        } catch(PDOException $e) {
            echo "<div class='error'>" . $e->getMessage() . "</div>";
        }
        
        if ($result->rowCount() > 0) {
            $gibbonCourseClassID = $result->fetchColumn(0);
        }
    }

    $today = date('Y-m-d');
    $currentDate = isset($_GET['currentDate'])? dateConvert($guid, $_GET['currentDate']) : $today;

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'] . '/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/' . $_SESSION[$guid]['module'] . '/attendance_take_byCourseClass.php');

    $form->addRow()->addHeading(__('Choose Class'));

    $row = $form->addRow();
        $row->addLabel('gibbonCourseClassID', __('Class'));
        $row->addSelectClass('gibbonCourseClassID', $_SESSION[$guid]['gibbonSchoolYearID'], $_SESSION[$guid]['gibbonPersonID'])->isRequired()->selected($gibbonCourseClassID)->placeholder();

    $row = $form->addRow();
        $row->addLabel('currentDate', __('Date'));
        $row->addDate('currentDate')->isRequired()->setValue(dateConvertBack($guid, $currentDate));

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if (!empty($gibbonCourseClassID)) {
        if ($currentDate > $today) {
            echo "<div class='error'>";
                echo __("The specified date is in the future: it must be today or earlier.");
            echo "</div>";
        } else {
            if (isSchoolOpen($guid, $currentDate, $connection2) == false) {
                echo "<div class='error'>";
                    echo __("School is closed on the specified date, and so attendance information cannot be recorded.");
                echo "</div>";
            } else {
                $prefillAttendanceType = getSettingByScope($connection2, 'Attendance', 'prefillClass');
                $defaultAttendanceType = getSettingByScope($connection2, 'Attendance', 'defaultClassAttendanceType');

                // Check class
                try {
                    $data = array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]);
                    $sql = "SELECT gibbonCourseClass.*, gibbonCourse.gibbonSchoolYearID,firstDay, lastDay,
                    gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse
                    JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                    JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                    WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID";

                    $result=$connection2->prepare($sql);
                    $result->execute($data);
                } catch(PDOException $e) {
                    echo "<div class='error'>" . $e->getMessage() . "</div>";
                }

                if ($result->rowCount() == 0) {
                    echo '<div class="error">';
                    echo __('There are no records to display.');
                    echo '</div>';
                    return;
                }

                $class = $result->fetch();

                if ($class["attendance"] == 'N') {
                    echo '<div class="error">';
                    echo __('Attendance taking has been disabled for this class.');
                    echo '</div>';
                } else {
                    // Check if the class is a timetabled course AND if it's timetabled on the current day
                    try {
                        $dataTT = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $currentDate);
                        $sqlTT = "SELECT MIN(gibbonTTDayDateID) as currentlyTimetabled, COUNT(*) AS totalTimetableCount
                        FROM gibbonTTDayRowClass
                        LEFT JOIN gibbonTTDayDate ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID AND gibbonTTDayDate.date=:date)
                        WHERE gibbonTTDayRowClass.gibbonCourseClassID=:gibbonCourseClassID
                        GROUP BY gibbonTTDayRowClass.gibbonCourseClassID";
                        $resultTT=$connection2->prepare($sqlTT);
                        $resultTT->execute($dataTT);
                    } catch(PDOException $e) {
                        echo "<div class='error'>" . $e->getMessage() . "</div>";
                    }

                    if ($resultTT && $resultTT->rowCount() > 0) {
                        $ttCheck = $resultTT->fetch();
                        if ($ttCheck['totalTimetableCount'] > 0 && empty($ttCheck['currentlyTimetabled'])) {
                            echo "<div class='warning'>";
                                echo __('This class is not timetabled to run on the specified date. Attendance may still be taken for this group however it currently falls outside the regular schedule for this class.');
                            echo "</div>";
                        }
                    }

                    //Show attendance log for the current day
                    try {
                        $dataLog=array("gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$currentDate . "%");
                        $sqlLog="SELECT * FROM gibbonAttendanceLogCourseClass, gibbonPerson WHERE gibbonAttendanceLogCourseClass.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND date LIKE :date ORDER BY timestampTaken";
                        $resultLog=$connection2->prepare($sqlLog);
                        $resultLog->execute($dataLog);
                    }
                    catch(PDOException $e) {
                        echo "<div class='error'>" . $e->getMessage() . "</div>";
                    }
                    if ($resultLog->rowCount()<1) {
                        echo "<div class='error'>";
                            echo __("Attendance has not been taken for this group yet for the specified date. The entries below are a best-guess based on defaults and information put into the system in advance, not actual data.");
                        echo "</div>";
                    }
                    else {
                        echo "<div class='success'>";
                            echo __("Attendance has been taken at the following times for the specified date for this group:");
                            echo "<ul>";
                            while ($rowLog=$resultLog->fetch()) {
                                echo "<li>" . sprintf(__('Recorded at %1$s on %2$s by %3$s.'), substr($rowLog["timestampTaken"],11), dateConvertBack($guid, substr($rowLog["timestampTaken"],0,10)), formatName("", $rowLog["preferredName"], $rowLog["surname"], "Staff", false, true)) ."</li>";
                            }
                            echo "</ul>";
                        echo "</div>";
                    }

                    //Show roll group grid
                    try {
                        $dataCourseClass=array("gibbonCourseClassID"=>$gibbonCourseClassID, 'date' => $currentDate);
                        $sqlCourseClass="SELECT gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.gibbonPersonID, gibbonPerson.image_240 FROM gibbonCourseClassPerson
                            INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID
                            LEFT JOIN (SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayRowClass.gibbonTTDayRowClassID FROM gibbonTTDayDate JOIN gibbonTTDayRowClass ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) WHERE gibbonTTDayDate.date=:date) AS gibbonTTDayRowClassSubset ON (gibbonTTDayRowClassSubset.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                            LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClassSubset.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                            WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID
                            AND status='Full' AND role='Student'
                            AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL OR dateEnd>=:date)
                            GROUP BY gibbonCourseClassPerson.gibbonPersonID
                            HAVING COUNT(gibbonTTDayRowClassExceptionID) = 0
                            ORDER BY surname, preferredName";
                        $resultCourseClass=$connection2->prepare($sqlCourseClass);
                        $resultCourseClass->execute($dataCourseClass);
                    }
                    catch(PDOException $e) {
                        echo "<div class='error'>" . $e->getMessage() . "</div>";
                    }

                    if ($resultCourseClass->rowCount()<1) {
                        echo "<div class='error'>";
                            echo __("There are no records to display.");
                        echo "</div>";
                    } else {
                        $count = 0;
                        $countPresent = 0;
                        $columns = 4;

                        $defaults = array('type' => $defaultAttendanceType, 'reason' => '', 'comment' => '', 'context' => '');                        
                        $students = $resultCourseClass->fetchAll();

                        // Build the attendance log data per student
                        foreach ($students as &$student) {
                            $data = array('gibbonPersonID' => $student['gibbonPersonID'], 'date' => $currentDate.'%');
                            $sql = "SELECT type, reason, comment, context, timestampTaken FROM gibbonAttendanceLogPerson
                                    JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                    WHERE gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID 
                                    AND date LIKE :date";

                            if ($prefillAttendanceType == 'N') {
                                $data['gibbonCourseClassID'] = $gibbonCourseClassID;
                                $sql .= " AND context='Class' AND gibbonCourseClassID=:gibbonCourseClassID";
                            }
                            $sql .= " ORDER BY timestampTaken DESC";
                            $result = $pdo->executeQuery($data, $sql);

                            $student['log'] = ($result->rowCount() > 0)? $result->fetch() : $defaults;

                            $student['cellHighlight'] = '';
                            if ($attendance->isTypeAbsent($student['log']['type'])) {
                                $student['cellHighlight'] = ($student['log']['context'] == 'Class')? 'dayWarning' : 'dayAbsent';
                            } else if ($attendance->isTypeOffsite($student['log']['type'])) {
                                $student['cellHighlight'] = 'dayMessage';
                            }

                            if ($attendance->isTypePresent($student['log']['type']) && $attendance->isTypeOnsite($student['log']['type'])) {
                                $countPresent++;
                            }
                        }

                        $form = Form::create('attendanceByClass', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']. '/attendance_take_byCourseClassProcess.php');
                        $form->setAutocomplete('off');
                        $form->setClass('attendanceGrid fullWidth');

                        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                        $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
                        $form->addHiddenValue('currentDate', $currentDate);
                        $form->addHiddenValue('count', count($students));
                        
                        $form->addRow()->addHeading(__('Take Attendance') . ': '. htmlPrep($class['course']) . '.' . htmlPrep($class['class']));
                        
                        $grid = $form->addRow()->addGrid('attendance')->setColumns(4);

                        foreach ($students as $student) {
                            $form->addHiddenValue($count . '-gibbonPersonID', $student['gibbonPersonID']);

                            $cell = $grid->addCell()->addClass('textCenter stacked')->addClass($student['cellHighlight']);
                                $cell->addContent(getUserPhoto($guid, $student['image_240'], 75));
                                $cell->addWebLink(formatName('', htmlPrep($student['preferredName']), htmlPrep($student['surname']), 'Student', false))
                                    ->setURL('index.php?q=/modules/Students/student_view_details.php')
                                    ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                                    ->addParam('subpage', 'School Attendance')
                                    ->wrap('<b>', '</b>');
                                $cell->addSelect($count.'-type')
                                    ->fromArray(array_keys($attendance->getAttendanceTypes()))
                                    ->selected($student['log']['type'])
                                    ->setClass('attendanceField');
                                $cell->addSelect($count.'-reason')
                                    ->fromArray($attendance->getAttendanceReasons())
                                    ->selected($student['log']['reason'])
                                    ->setClass('attendanceField');
                                $cell->addTextField($count.'-comment')
                                    ->maxLength(255)
                                    ->setValue($student['log']['comment'])
                                    ->setClass('attendanceField');
                                $cell->addContent($attendance->renderMiniHistory($student['gibbonPersonID']));

                            $count++;
                        }
                        
                        $form->addRow()->addAlert(__('Total students:').' '. $count, 'success')->setClass('right')
                            ->append('<br/><span title="'.__('e.g. Present or Present - Late').'">'.__('Total students present in room:').' '. $countPresent.'</span>')
                            ->append('<br/><span title="'.__('e.g. not Present and not Present - Late').'">'.__('Total students absent from room:').' '. ($count-$countPresent).'</span>')
                            ->wrap('<b>', '</b>');
                        
                        $row = $form->addRow();
                            // Drop-downs to change the whole group at once
                            $col = $row->addColumn()->addClass('inline');
                                $col->addSelect('set-all-type')->fromArray(array_keys($attendance->getAttendanceTypes()))->setClass('attendanceField');
                                $col->addSelect('set-all-reason')->fromArray($attendance->getAttendanceReasons())->setClass('attendanceField');
                                $col->addTextField('set-all-comment')->maxLength(255)->setClass('attendanceField');
                                $col->addButton(__('Change All'))->setID('set-all');
                            $row->addSubmit();
                        
                        echo $form->getOutput();
                    }
                }
            }
        }
    }
}
?>
