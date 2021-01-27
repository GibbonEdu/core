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

use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';
require_once __DIR__ . '/src/AttendanceView.php';

// set page breadcrumb
$page->breadcrumbs->add(__('Take Attendance by Class'));

if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php") == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __("You do not have access to this action.");
    echo "</div>";
} else {
    //Proceed!
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('error3' => __('Your request failed because the specified date is in the future, or is not a school day.')));
    }

    $attendance = new AttendanceView($gibbon, $pdo);

    $gibbonCourseClassID = isset($_GET['gibbonCourseClassID']) ? $_GET['gibbonCourseClassID'] : '';
    if (empty($gibbonCourseClassID)) {
        try {
            $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonSchoolYear.firstDay, gibbonSchoolYear.lastDay
                    FROM gibbonCourse
                    JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                    JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                    JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                    WHERE gibbonPersonID=:gibbonPersonID
                    AND gibbonCourseClass.attendance='Y'
                    AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>" . $e->getMessage() . "</div>";
        }

        if ($result->rowCount() > 0) {
            $gibbonCourseClassID = $result->fetchColumn(0);
        }
    }

    echo '<h2>' . __('Choose Class') . "</h2>";

    $today = date('Y-m-d');
    $currentDate = isset($_GET['currentDate']) ? dateConvert($guid, $_GET['currentDate']) : $today;

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'] . '/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/' . $_SESSION[$guid]['module'] . '/attendance_take_byCourseClass.php');

    $row = $form->addRow();
    $row->addLabel('gibbonCourseClassID', __('Class'));
    $row->addSelectClass('gibbonCourseClassID', $_SESSION[$guid]['gibbonSchoolYearID'], $_SESSION[$guid]['gibbonPersonID'], array('attendance' => 'Y'))
        ->required()
        ->selected($gibbonCourseClassID)
        ->placeholder();

    $row = $form->addRow();
    $row->addLabel('currentDate', __('Date'));
    $row->addDate('currentDate')->required()->setValue(dateConvertBack($guid, $currentDate));

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
                $defaultAttendanceType = getSettingByScope($connection2, 'Attendance', 'defaultClassAttendanceType');
                $crossFillClasses = getSettingByScope($connection2, 'Attendance', 'crossFillClasses');

                // Check class
                try {
                    $data = array("gibbonCourseClassID" => $gibbonCourseClassID, "gibbonSchoolYearID" => $_SESSION[$guid]["gibbonSchoolYearID"]);
                    $sql = "SELECT gibbonCourseClass.*, gibbonCourse.gibbonSchoolYearID,firstDay, lastDay,
                    gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse
                    JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                    JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                    WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID";

                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
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
                        $resultTT = $connection2->prepare($sqlTT);
                        $resultTT->execute($dataTT);
                    } catch (PDOException $e) {
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
                        $dataLog = array("gibbonCourseClassID" => $gibbonCourseClassID, "date" => $currentDate . "%");
                        $sqlLog = "SELECT * FROM gibbonAttendanceLogCourseClass, gibbonPerson WHERE gibbonAttendanceLogCourseClass.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND date LIKE :date ORDER BY timestampTaken";
                        $resultLog = $connection2->prepare($sqlLog);
                        $resultLog->execute($dataLog);
                    } catch (PDOException $e) {
                        echo "<div class='error'>" . $e->getMessage() . "</div>";
                    }
                    if ($resultLog->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __("Attendance has not been taken for this group yet for the specified date. The entries below are a best-guess based on defaults and information put into the system in advance, not actual data.");
                        echo "</div>";
                    } else {
                        echo "<div class='success'>";
                        echo __("Attendance has been taken at the following times for the specified date for this group:");
                        echo "<ul>";
                        while ($rowLog = $resultLog->fetch()) {
                            echo "<li>" . sprintf(__('Recorded at %1$s on %2$s by %3$s.'), substr($rowLog["timestampTaken"], 11), dateConvertBack($guid, substr($rowLog["timestampTaken"], 0, 10)), Format::name("", $rowLog["preferredName"], $rowLog["surname"], "Staff", false, true)) . "</li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                    }

                    //Show roll group grid
                    try {
                        $dataCourseClass = array("gibbonCourseClassID" => $gibbonCourseClassID, 'date' => $currentDate);
                        $sqlCourseClass = "SELECT gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.gibbonPersonID, gibbonPerson.image_240 FROM gibbonCourseClassPerson
                            INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID
                            LEFT JOIN (SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayRowClass.gibbonTTDayRowClassID FROM gibbonTTDayDate JOIN gibbonTTDayRowClass ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) WHERE gibbonTTDayDate.date=:date) AS gibbonTTDayRowClassSubset ON (gibbonTTDayRowClassSubset.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                            LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClassSubset.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                            WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID
                            AND status='Full' AND role='Student'
                            AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL OR dateEnd>=:date)
                            GROUP BY gibbonCourseClassPerson.gibbonPersonID
                            HAVING COUNT(gibbonTTDayRowClassExceptionID) = 0
                            ORDER BY surname, preferredName";
                        $resultCourseClass = $connection2->prepare($sqlCourseClass);
                        $resultCourseClass->execute($dataCourseClass);
                    } catch (PDOException $e) {
                        echo "<div class='error'>" . $e->getMessage() . "</div>";
                    }

                    if ($resultCourseClass->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __("There are no records to display.");
                        echo "</div>";
                    } else {
                        $count = 0;
                        $countPresent = 0;
                        $columns = 4;

                        $defaults = array('type' => $defaultAttendanceType, 'reason' => '', 'comment' => '', 'context' => '', 'prefill' => 'Y');
                        $students = $resultCourseClass->fetchAll();

                        // Build the attendance log data per student
                        foreach ($students as $key => $student) {
                            $data = array('gibbonPersonID' => $student['gibbonPersonID'], 'date' => $currentDate . '%', 'gibbonCourseClassID' => $gibbonCourseClassID);
                            $sql = "SELECT gibbonAttendanceLogPerson.type, reason, comment, context, timestampTaken FROM gibbonAttendanceLogPerson
                                    JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                    WHERE gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID
                                    AND date LIKE :date
                                    AND context='Class' AND gibbonCourseClassID=:gibbonCourseClassID
                                    ORDER BY timestampTaken DESC";
                            $result = $pdo->executeQuery($data, $sql);

                            $log = ($result->rowCount() > 0) ? $result->fetch() : $defaults;
                            $log['prefilled'] = $result->rowCount() > 0 ? $log['context'] : '';

                            //Check for school prefill if attendance not taken in this class
                            if ($result->rowCount() == 0) {
                                $data = array('gibbonPersonID' => $student['gibbonPersonID'], 'date' => $currentDate . '%');
                                $sql = "SELECT gibbonAttendanceLogPerson.type, reason, comment, context, timestampTaken, gibbonAttendanceCode.prefill
                                        FROM gibbonAttendanceLogPerson
                                        JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                        JOIN gibbonAttendanceCode ON (gibbonAttendanceCode.gibbonAttendanceCodeID=gibbonAttendanceLogPerson.gibbonAttendanceCodeID)
                                        WHERE gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID
                                        AND date LIKE :date";
                                if ($crossFillClasses == "N") {
                                    $sql .= " AND NOT context='Class'";
                                }
                                $sql .= " ORDER BY timestampTaken DESC";
                                $result = $pdo->executeQuery($data, $sql);

                                $log = ($result->rowCount() > 0) ? $result->fetch() : $log;
                                $log['prefilled'] = $result->rowCount() > 0 ? $log['context'] : '';

                                if ($log['prefill'] == 'N') {
                                    $log = $defaults;
                                    $log['prefilled'] = 'Person';
                                }
                            }

                            $students[$key]['cellHighlight'] = '';
                            if ($attendance->isTypeAbsent($log['type'])) {
                                $students[$key]['cellHighlight'] = 'dayAbsent';
                            } elseif ($attendance->isTypeOffsite($log['type'])) {
                                $students[$key]['cellHighlight'] = 'dayMessage';
                            } elseif ($attendance->isTypeLate($log['type'])) {
                                $students[$key]['cellHighlight'] = 'dayPartial';
                            }

                            $students[$key]['absenceCount'] = '';
                            $absenceCount = getAbsenceCount($guid, $student['gibbonPersonID'], $connection2, $class['firstDay'], $class['lastDay'], $gibbonCourseClassID);
                            if ($absenceCount !== false) {
                                $absenceText = ($absenceCount == 1) ? __('%1$s Class Absent') : __('%1$s Classes Absent');
                                $students[$key]['absenceCount'] = sprintf($absenceText, $absenceCount);
                            }

                            if ($attendance->isTypePresent($log['type']) && $attendance->isTypeOnsite($log['type'])) {
                                $countPresent++;
                            }

                            $students[$key]['log'] = $log;
                        }

                        $form = Form::create('attendanceByClass', $_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/attendance_take_byCourseClassProcess.php');
                        $form->setAutocomplete('off');

                        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                        $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
                        $form->addHiddenValue('currentDate', $currentDate);
                        $form->addHiddenValue('count', count($students));

                        $form->addRow()->addHeading(__('Take Attendance') . ': ' . htmlPrep($class['course']) . '.' . htmlPrep($class['class']));

                        $grid = $form->addRow()->addGrid('attendance')->setBreakpoints('w-1/2 sm:w-1/4 md:w-1/5 lg:w-1/4');

                        foreach ($students as $student) {
                            $form->addHiddenValue($count . '-gibbonPersonID', $student['gibbonPersonID']);
                            $form->addHiddenValue($count . '-prefilled', $student['log']['prefilled'] ?? '');

                            $cell = $grid->addCell()
                                ->setClass('text-center py-2 px-1 -mr-px -mb-px flex flex-col justify-between')
                                ->addClass($student['cellHighlight']);

                            $studentLink = './index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'].'&subpage=Attendance';
                            $cell->addContent(Format::link($studentLink, Format::userPhoto($student['image_240'], 75)));
                            $cell->addWebLink(Format::name('', htmlPrep($student['preferredName']), htmlPrep($student['surname']), 'Student', false))
                                ->setURL('index.php?q=/modules/Students/student_view_details.php')
                                ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                                ->addParam('subpage', 'Attendance')
                                ->setClass('pt-2 font-bold underline');
                            $cell->addContent($student['absenceCount'])->wrap('<div class="text-xxs italic py-2">', '</div>');
                            $cell->addSelect($count . '-type')
                                ->fromArray(array_keys($attendance->getAttendanceTypes()))
                                ->selected($student['log']['type'])
                                ->setClass('mx-auto float-none w-32 m-0 mb-px');
                            $cell->addSelect($count . '-reason')
                                ->fromArray($attendance->getAttendanceReasons())
                                ->selected($student['log']['reason'])
                                ->setClass('mx-auto float-none w-32 m-0 mb-px');
                            $cell->addTextField($count . '-comment')
                                ->maxLength(255)
                                ->setValue($student['log']['comment'])
                                ->setClass('mx-auto float-none w-32 m-0 mb-2');
                            $cell->addContent($attendance->renderMiniHistory($student['gibbonPersonID'], 'Class', $gibbonCourseClassID));

                            $count++;
                        }

                        $form->addRow()->addAlert(__('Total students:') . ' ' . $count, 'success')->setClass('right')
                            ->append('<br/><span title="' . __('e.g. Present or Present - Late') . '">' . __('Total students present in room:') . ' ' . $countPresent . '</span>')
                            ->append('<br/><span title="' . __('e.g. not Present and not Present - Late') . '">' . __('Total students absent from room:') . ' ' . ($count - $countPresent) . '</span>')
                            ->wrap('<b>', '</b>');

                        $row = $form->addRow();

                        // Drop-downs to change the whole group at once
                        $row->addButton(__('Change All').'?')->addData('toggle', '.change-all')->addClass('w-32 m-px sm:self-center');

                        $col = $row->addColumn()->setClass('change-all hidden flex flex-col sm:flex-row items-stretch sm:items-center');
                            $col->addSelect('set-all-type')->fromArray(array_keys($attendance->getAttendanceTypes()))->addClass('m-px');
                            $col->addSelect('set-all-reason')->fromArray($attendance->getAttendanceReasons())->addClass('m-px');
                            $col->addTextField('set-all-comment')->maxLength(255)->addClass('m-px');
                        $col->addButton(__('Apply'))->setID('set-all');

                        $row->addSubmit();

                        echo $form->getOutput();
                    }
                }
            }
        }
    }
}
