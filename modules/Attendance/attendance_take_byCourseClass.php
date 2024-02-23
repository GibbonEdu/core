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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Domain\Timetable\TimetableDayDateGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';
require_once __DIR__ . '/src/AttendanceView.php';

// set page breadcrumb
$page->breadcrumbs->add(__('Take Attendance by Class'));

if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php") == false) {
    //Acess denied
    $page->addError(__("You do not have access to this action."));
} else {
    //Proceed!
    $page->return->addReturns(['error3' => __('Your request failed because the specified date is in the future, or is not a school day.')]);

    $settingGateway = $container->get(SettingGateway::class);
    $ttDayDateGateway = $container->get(TimetableDayDateGateway::class);

    $attendance = new AttendanceView($gibbon, $pdo, $settingGateway);

    $today = date('Y-m-d');
    $currentDate = isset($_GET['currentDate']) ? Format::dateConvert($_GET['currentDate']) : $today;
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $gibbonTTDayRowClassID = $_GET['gibbonTTDayRowClassID'] ?? '';

    if (empty($gibbonCourseClassID)) {
        try {
            $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
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
        }

        if ($result->rowCount() > 0) {
            $gibbonCourseClassID = $result->fetchColumn(0);
        }
    }

    $ttPeriods = $ttDayDateGateway->selectTimetabledPeriodsByClass($gibbonCourseClassID, $currentDate)->fetchAll();

    echo '<h2>' . __('Choose Class') . "</h2>";

    $form = Form::create('filter', $session->get('absoluteURL') . '/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/' . $session->get('module') . '/attendance_take_byCourseClass.php');

    $row = $form->addRow();
    $row->addLabel('gibbonCourseClassID', __('Class'));
    $row->addSelectClass('gibbonCourseClassID', $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'), array('attendance' => 'Y'))
        ->required()
        ->selected($gibbonCourseClassID)
        ->placeholder();

    $row = $form->addRow();
    $row->addLabel('currentDate', __('Date'));
    $row->addDate('currentDate')->required()->setValue(Format::date($currentDate));

    if (!empty($ttPeriods) && count($ttPeriods) > 1) {
        $row = $form->addRow()->addClass('selectPeriod');
        $row->addLabel('gibbonTTDayRowClassID', __('Period'));
        $row->addSelect('gibbonTTDayRowClassID')
            ->fromArray($ttPeriods, 'gibbonTTDayRowClassID', 'period')
            ->placeholder()
            ->required()
            ->selected($gibbonTTDayRowClassID);
    } else if (!empty($ttPeriods) && count($ttPeriods) == 1) {
        $ttPeriod = current($ttPeriods);
        $gibbonTTDayRowClassID = $ttPeriod['gibbonTTDayRowClassID'];
    }

    $row = $form->addRow();
    $row->addSearchSubmit($session);

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
                $defaultAttendanceType = $settingGateway->getSettingByScope('Attendance', 'defaultClassAttendanceType');
                $crossFillClasses = $settingGateway->getSettingByScope('Attendance', 'crossFillClasses');

                // Check class
                try {
                    $data = array("gibbonCourseClassID" => $gibbonCourseClassID, "gibbonSchoolYearID" => $session->get('gibbonSchoolYearID'));
                    $sql = "SELECT gibbonCourseClass.*, gibbonCourse.gibbonSchoolYearID,firstDay, lastDay,
                    gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse
                    JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                    JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                    WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID";

                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }

                if ($result->rowCount() == 0) {
                    echo $page->getBlankSlate();
                    return;
                }

                $class = $result->fetch();

                if ($class["attendance"] == 'N') {
                    echo '<div class="error">';
                    echo __('Attendance taking has been disabled for this class.');
                    echo '</div>';
                } elseif (!empty($ttPeriods) && count($ttPeriods) > 1 && empty($gibbonTTDayRowClassID)) {
                    echo Format::alert(__('This class has more than one timetabled lesson on the selected date. Please choose a period above to take attendance for the desired lesson.'), 'message');
                } else {
                    // Check if the class is a timetabled course AND if it's timetabled on the current day
                    if (empty($ttPeriods)) {
                        echo Format::alert(__('This class is not timetabled to run on the specified date. Attendance may still be taken for this group however it currently falls outside the regular schedule for this class.'), 'warning');
                    }

                    //Show attendance log for the current day
                    try {
                        $dataLog = array("gibbonCourseClassID" => $gibbonCourseClassID, "date" => $currentDate . "%", 'gibbonTTDayRowClassID' => $gibbonTTDayRowClassID);
                        $sqlLog = "SELECT * 
                            FROM gibbonAttendanceLogCourseClass, gibbonPerson 
                            WHERE gibbonAttendanceLogCourseClass.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID 
                            AND date LIKE :date 
                            AND (gibbonTTDayRowClassID=:gibbonTTDayRowClassID OR gibbonTTDayRowClassID IS NULL)
                            ORDER BY timestampTaken";
                        $resultLog = $connection2->prepare($sqlLog);
                        $resultLog->execute($dataLog);
                    } catch (PDOException $e) {
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
                            echo "<li>" . sprintf(__('Recorded at %1$s on %2$s by %3$s.'), substr($rowLog["timestampTaken"], 11), Format::date(substr($rowLog["timestampTaken"], 0, 10)), Format::name("", $rowLog["preferredName"], $rowLog["surname"], "Staff", false, true)) . "</li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                    }

                    //Show form group grid
                    $dataCourseClass = array("gibbonCourseClassID" => $gibbonCourseClassID, 'date' => $currentDate);
                    $sqlCourseClass = "SELECT gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.gibbonPersonID, gibbonPerson.image_240, gibbonPerson.dob FROM gibbonCourseClassPerson
                        INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID
                        LEFT JOIN (SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayRowClass.gibbonTTDayRowClassID FROM gibbonTTDayDate JOIN gibbonTTDayRowClass ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) WHERE gibbonTTDayDate.date=:date) AS gibbonTTDayRowClassSubset ";

                    if (!empty($gibbonTTDayRowClassID)) {
                        $dataCourseClass['gibbonTTDayRowClassID'] = $gibbonTTDayRowClassID;
                        $sqlCourseClass .= " ON (gibbonTTDayRowClassSubset.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonTTDayRowClassSubset.gibbonTTDayRowClassID=:gibbonTTDayRowClassID) ";
                    } else {
                        $sqlCourseClass .= " ON (gibbonTTDayRowClassSubset.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) ";
                    }
                        
                    $sqlCourseClass .= "
                        LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClassSubset.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                        WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID
                        AND status='Full' AND role='Student'
                        AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL OR dateEnd>=:date) 
                        GROUP BY gibbonCourseClassPerson.gibbonPersonID
                        HAVING COUNT(gibbonTTDayRowClassExceptionID) = 0
                        ORDER BY surname, preferredName";

                    $resultCourseClass = $pdo->select($sqlCourseClass, $dataCourseClass);

                    if ($resultCourseClass->rowCount() < 1) {
                        echo $page->getBlankSlate();
                    } else {
                        $count = 0;
                        $countPresent = 0;
                        $columns = 4;

                        $defaults = array('type' => $defaultAttendanceType, 'reason' => '', 'comment' => '', 'context' => '', 'direction' => '', 'prefill' => 'Y');
                        $students = $resultCourseClass->fetchAll();

                        // Build the attendance log data per student
                        foreach ($students as $key => $student) {
                            $data = array('gibbonPersonID' => $student['gibbonPersonID'], 'date' => $currentDate . '%', 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonTTDayRowClassID' => $gibbonTTDayRowClassID);
                            $sql = "SELECT gibbonAttendanceLogPerson.type, gibbonAttendanceLogPerson.reason, gibbonAttendanceLogPerson.comment, gibbonAttendanceLogPerson.direction, gibbonAttendanceLogPerson.context, timestampTaken FROM gibbonAttendanceLogPerson
                                    JOIN gibbonAttendanceCode ON (gibbonAttendanceCode.gibbonAttendanceCodeID=gibbonAttendanceLogPerson.gibbonAttendanceCodeID)
                                    JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                    WHERE gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID
                                    AND date LIKE :date
                                    AND gibbonAttendanceLogPerson.context='Class' AND gibbonCourseClassID=:gibbonCourseClassID";
                            if ($crossFillClasses == "N") {
                                $sql .= " AND (gibbonTTDayRowClassID=:gibbonTTDayRowClassID OR gibbonTTDayRowClassID IS NULL)";
                            } else {
                                $sql .= " AND (gibbonTTDayRowClassID=:gibbonTTDayRowClassID OR gibbonAttendanceCode.prefill='Y')";
                            }
                            $sql .= " ORDER BY timestampTaken DESC";
                            
                            $result = $pdo->executeQuery($data, $sql);

                            $log = ($result->rowCount() > 0) ? $result->fetch() : $defaults;
                            $log['prefilled'] = $result->rowCount() > 0 ? $log['context'] : '';

                            //Check for school prefill if attendance not taken in this class
                            if ($result->rowCount() == 0) {
                                $data = array('gibbonPersonID' => $student['gibbonPersonID'], 'date' => $currentDate . '%');
                                $sql = "SELECT gibbonAttendanceLogPerson.type, reason, comment, gibbonAttendanceCode.direction, context, timestampTaken, gibbonAttendanceCode.prefill
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
                            } elseif ($attendance->isTypeOffsite($log['type']) || $log['direction'] == 'Out') {
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

                        $form = Form::create('attendanceByClass', $session->get('absoluteURL') . '/modules/' . $session->get('module') . '/attendance_take_byCourseClassProcess.php');
                        $form->setAutocomplete('off');

                        $form->addHiddenValue('address', $session->get('address'));
                        $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
                        $form->addHiddenValue('gibbonTTDayRowClassID', $gibbonTTDayRowClassID);
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
                            $icon = Format::userBirthdayIcon($student['dob'], $student['preferredName']);

                            $cell->addContent(Format::link($studentLink, Format::userPhoto($student['image_240'], 75)))
                                ->setClass('relative')
                                ->append($icon ?? '');
                            $cell->addWebLink(Format::name('', htmlPrep($student['preferredName']), htmlPrep($student['surname']), 'Student', false))
                                ->setURL('index.php?q=/modules/Students/student_view_details.php')
                                ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                                ->addParam('subpage', 'Attendance')
                                ->setClass('pt-2 font-bold underline');
                            $cell->addContent($student['absenceCount'])->wrap('<div class="text-xxs italic py-2">', '</div>');
                            $restricted = $attendance->isTypeRestricted($student['log']['type']);
                            $cell->addSelect($count . '-type')
                                ->fromArray($attendance->getAttendanceTypes($restricted))
                                ->selected($student['log']['type'])
                                ->setClass('mx-auto float-none w-32 m-0 mb-px')
                                ->readOnly($restricted);
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
                            $col->addSelect('set-all-type')->fromArray($attendance->getAttendanceTypes())->addClass('m-px');
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
?>

<script type="text/javascript">
    // When changing classes, hide the period selector
    $(document).on('change', '#gibbonCourseClassID', function () {
        $('#gibbonTTDayRowClassID').val('').prop('disabled', true);
        $('.selectPeriod, .message').addClass('hidden');
    });
</script>
