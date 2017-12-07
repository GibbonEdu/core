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
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

require_once $_SESSION[$guid]['absolutePath'].'/modules/Attendance/src/attendanceView.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byRollGroup.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Take Attendance by Roll Group').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, array('warning1' => 'Your request was successful, but some data was not properly saved.', 'error3' => 'Your request failed because the specified date is not in the future, or is not a school day.'));
        }

        $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);

        $gibbonRollGroupID = '';
        if (isset($_GET['gibbonRollGroupID']) == false) {
            try {
                $data = array('gibbonPersonIDTutor1' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor3' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonRollGroup.*, firstDay, lastDay FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() > 0) {
                $row = $result->fetch();
                $gibbonRollGroupID = $row['gibbonRollGroupID'];
            }
        } else {
            $gibbonRollGroupID = $_GET['gibbonRollGroupID'];
        }

        $today = date('Y-m-d');
        $currentDate = isset($_GET['currentDate'])? dateConvert($guid, $_GET['currentDate']) : $today;

        $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'] . '/index.php', 'get');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('q', '/modules/' . $_SESSION[$guid]['module'] . '/attendance_take_byRollGroup.php');

        $form->addRow()->addHeading(__('Choose Roll Group'));

        $row = $form->addRow();
            $row->addLabel('gibbonRollGroupID', __('Roll Group'));
            $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->isRequired()->selected($gibbonRollGroupID)->placeholder();

        $row = $form->addRow();
            $row->addLabel('currentDate', __('Date'));
            $row->addDate('currentDate')->isRequired()->setValue(dateConvertBack($guid, $currentDate));

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();

        if ($gibbonRollGroupID != '') {
            if ($currentDate > $today) {
                echo "<div class='error'>";
                echo __($guid, 'The specified date is in the future: it must be today or earlier.');
                echo '</div>';
            } else {
                if (isSchoolOpen($guid, $currentDate, $connection2) == false) {
                    echo "<div class='error'>";
                    echo __($guid, 'School is closed on the specified date, and so attendance information cannot be recorded.');
                    echo '</div>';
                } else {
                    $prefillAttendanceType = getSettingByScope($connection2, 'Attendance', 'prefillRollGroup');
                    $defaultAttendanceType = getSettingByScope($connection2, 'Attendance', 'defaultRollGroupAttendanceType');

                    //Check roll group
                    try {
                        $data = array('gibbonRollGroupID' => $gibbonRollGroupID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sql = 'SELECT gibbonRollGroup.*, firstDay, lastDay FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonRollGroupID=:gibbonRollGroupID AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($result->rowCount() == 0) {
                        echo '<div class="error">';
                        echo __('There are no records to display.');
                        echo '</div>';
                        return;
                    }

                    $rollGroup = $result->fetch();

                    if ($rollGroup['attendance'] == 'N') {
                        print "<div class='error'>" ;
                            print __("Attendance taking has been disabled for this roll group.") ;
                        print "</div>" ;
                    } else {

                        //Show attendance log for the current day
                        try {
                            $dataLog = array('gibbonRollGroupID' => $gibbonRollGroupID, 'date' => $currentDate.'%');
                            $sqlLog = 'SELECT * FROM gibbonAttendanceLogRollGroup, gibbonPerson WHERE gibbonAttendanceLogRollGroup.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonRollGroupID=:gibbonRollGroupID AND date LIKE :date ORDER BY timestampTaken';
                            $resultLog = $connection2->prepare($sqlLog);
                            $resultLog->execute($dataLog);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultLog->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'Attendance has not been taken for this group yet for the specified date. The entries below are a best-guess based on defaults and information put into the system in advance, not actual data.');
                            echo '</div>';
                        } else {
                            echo "<div class='success'>";
                            echo __($guid, 'Attendance has been taken at the following times for the specified date for this group:');
                            echo '<ul>';
                            while ($rowLog = $resultLog->fetch()) {
                                echo '<li>'.sprintf(__($guid, 'Recorded at %1$s on %2$s by %3$s.'), substr($rowLog['timestampTaken'], 11), dateConvertBack($guid, substr($rowLog['timestampTaken'], 0, 10)), formatName('', $rowLog['preferredName'], $rowLog['surname'], 'Staff', false, true)).'</li>';
                            }
                            echo '</ul>';
                            echo '</div>';
                        }

                        //Show roll group grid
                        try {
                            $dataRollGroup = array('gibbonRollGroupID' => $gibbonRollGroupID, 'date' => $currentDate);
                            $sqlRollGroup = "SELECT gibbonPerson.image_240, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonRollGroupID=:gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL  OR dateEnd>=:date) ORDER BY rollOrder, surname, preferredName";
                            $resultRollGroup = $connection2->prepare($sqlRollGroup);
                            $resultRollGroup->execute($dataRollGroup);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultRollGroup->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'There are no records to display.');
                            echo '</div>';
                        } else {
                            $count = 0;
                            $countPresent = 0;
                            $columns = 4;

                            $defaults = array('type' => $defaultAttendanceType, 'reason' => '', 'comment' => '', 'context' => '');
                            $students = $resultRollGroup->fetchAll();

                            // Build the attendance log data per student
                            foreach ($students as $key => $student) {
                                $data = array('gibbonPersonID' => $student['gibbonPersonID'], 'date' => $currentDate.'%');
                                $sql = "SELECT type, reason, comment, context, timestampTaken FROM gibbonAttendanceLogPerson
                                        JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                        WHERE gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID 
                                        AND date LIKE :date";

                                if ($prefillAttendanceType == 'N') {
                                    $sql .= " AND context='Roll Group'";
                                }
                                $sql .= " ORDER BY timestampTaken DESC";
                                $result = $pdo->executeQuery($data, $sql);

                                $log = ($result->rowCount() > 0)? $result->fetch() : $defaults;

                                $students[$key]['cellHighlight'] = '';
                                if ($attendance->isTypeAbsent($log['type'])) {
                                    $students[$key]['cellHighlight'] = 'dayAbsent';
                                } elseif ($attendance->isTypeOffsite($log['type'])) {
                                    $students[$key]['cellHighlight'] = 'dayMessage';
                                }

                                $students[$key]['absenceCount'] = '';
                                $absenceCount = getAbsenceCount($guid, $student['gibbonPersonID'], $connection2, $rollGroup['firstDay'], $rollGroup['lastDay']);
                                if ($absenceCount !== false) {
                                    $absenceText = ($absenceCount == 1)? __('%1$s Day Absent') : __('%1$s Days Absent');
                                    $students[$key]['absenceCount'] = sprintf($absenceText, $absenceCount);
                                }

                                if ($attendance->isTypePresent($log['type']) && $attendance->isTypeOnsite($log['type'])) {
                                    $countPresent++;
                                }

                                $students[$key]['log'] = $log;
                            }

                            $form = Form::create('attendanceByRollGroup', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']. '/attendance_take_byRollGroupProcess.php');
                            $form->setAutocomplete('off');
                            $form->addClass('attendanceGrid');

                            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                            $form->addHiddenValue('gibbonRollGroupID', $gibbonRollGroupID);
                            $form->addHiddenValue('currentDate', $currentDate);
                            $form->addHiddenValue('count', count($students));
                            
                            $form->addRow()->addHeading(__('Take Attendance') . ': '. htmlPrep($rollGroup['name']));
                            
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
                                $cell->addContent($student['absenceCount'])->wrap('<span class="small emphasis">', '<span>');
                                $cell->addSelect($count.'-type')
                                     ->fromArray(array_keys($attendance->getAttendanceTypes()))
                                     ->selected($student['log']['type'])
                                     ->setClass('attendanceField floatNone shortWidth');
                                $cell->addSelect($count.'-reason')
                                     ->fromArray($attendance->getAttendanceReasons())
                                     ->selected($student['log']['reason'])
                                     ->setClass('attendanceField attendanceFieldStacked floatNone shortWidth');
                                $cell->addTextField($count.'-comment')
                                     ->maxLength(255)
                                     ->setValue($student['log']['comment'])
                                     ->setClass('attendanceField attendanceFieldStacked floatNone shortWidth');
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
}

