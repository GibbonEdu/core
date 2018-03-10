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

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Take Attendance by Person').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('error3' => 'Your request failed because the specified date is not in the future, or is not a school day.'));
    }

    $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);

    $today = date('Y-m-d');
    $currentDate = isset($_GET['currentDate'])? dateConvert($guid, $_GET['currentDate']) : $today;
    $gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : null;

    echo '<h2>'.__('Choose Student')."</h2>";

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/attendance_take_byPerson.php');

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'])->isRequired()->selected($gibbonPersonID)->placeholder();

    $row = $form->addRow();
        $row->addLabel('currentDate', __('Date'));
        $row->addDate('currentDate')->isRequired()->setValue(dateConvertBack($guid, $currentDate));

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if ($gibbonPersonID != '') {
        if ($currentDate > $today) {
            echo "<div class='error'>";
            echo __($guid, 'The specified date is in the future: it must be today or earlier.');
            echo '</div>';
        } else {
            if (isSchoolOpen($guid, $currentDate, $connection2) == false) {
                echo "<div class='error'>";
                echo 'School is closed on the specified date, and so attendance information cannot be recorded.';
                echo '</div>';
            } else {
                $prefillAttendanceType = getSettingByScope($connection2, 'Attendance', 'prefillPerson');

                //Get last 5 school days from currentDate within the last 100
                $timestamp = dateConvertToTimestamp($currentDate);

                $lastType = '';
                $lastReason = '';
                $lastComment = '';

                //Show attendance log for the current day
                try {
                    $dataLog = array('gibbonPersonID' => $gibbonPersonID, 'date' => "$currentDate%");
                    $sqlLog = 'SELECT gibbonAttendanceLogPersonID, direction, type, reason, context, comment, timestampTaken, gibbonAttendanceLogPerson.gibbonCourseClassID, preferredName, surname, gibbonCourseClass.nameShort as className, gibbonCourse.nameShort as courseName FROM gibbonAttendanceLogPerson JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonCourseClass ON (gibbonAttendanceLogPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE  gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY gibbonAttendanceLogPersonID';
                    $resultLog = $connection2->prepare($sqlLog);
                    $resultLog->execute($dataLog);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultLog->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There is currently no attendance data today for the selected student.');
                    echo '</div>';
                } else {
                    echo '<h4>';
                        echo __($guid, 'Attendance Log');
                    echo '</h4>';

                    echo "<p><span class='emphasis small'>";
                        echo __($guid, 'The following attendance log has been recorded for the selected student today:');
                    echo '</span></p>';

                    echo '<table class="mini smallIntBorder fullWidth colorOddEven" cellspacing=0>';
                    echo '<tr class="head">';
                        echo '<th>'.__($guid, 'Time').'</th>';
                        echo '<th>'.__($guid, 'Attendance').'</th>';
                        echo '<th>'.__($guid, 'Where').'</th>';
                        echo '<th>'.__($guid, 'Recorded By').'</th>';

                        if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson_edit.php') == true) {
                            echo '<th style="width: 60px;">'.__($guid, 'Actions').'</th>';
                        }
                    echo '</tr>';
                    while ($rowLog = $resultLog->fetch()) {
                        $logTimestamp = strtotime($rowLog['timestampTaken']);

                        echo '<tr class="'.( $rowLog['direction'] == 'Out'? 'error' : 'current').'">';

                        if (  $currentDate != substr($rowLog['timestampTaken'], 0, 10) ) {
                            echo '<td>'.date("g:i a, M j", $logTimestamp ).'</td>';
                        } else {
                            echo '<td>'.date("g:i a", $logTimestamp ).'</td>';
                        }

                        echo '<td>';
                        echo '<b>'.$rowLog['direction'].'</b> ('.$rowLog['type']. ( !empty($rowLog['reason'])? ', '.$rowLog['reason'] : '') .')';

                        if ( !empty($rowLog['comment']) ) {
                            echo '&nbsp;<img title="'.$rowLog['comment'].'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/messageWall.png" width=16 height=16/>';
                        }
                        echo '</td>';


                        if ($rowLog['context'] != '') {
                            if ($rowLog['context'] == 'Class' && $rowLog['gibbonCourseClassID'] > 0)
                                echo '<td>'.__($guid, $rowLog['context']).' ('.$rowLog['courseName'].'.'.$rowLog['className'].')</td>';
                            else
                                echo '<td>'.__($guid, $rowLog['context']).'</td>';
                        } else {
                            echo '<td>'.__($guid, 'Roll Group').'</td>';
                        }

                        echo '<td>';
                            echo formatName('', $rowLog['preferredName'], $rowLog['surname'], 'Staff', false, true);
                        echo '</td>';

                        if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson_edit.php') == true) {
                            echo '<td>';
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/attendance_take_byPerson_edit.php&gibbonAttendanceLogPersonID='.$rowLog['gibbonAttendanceLogPersonID']."&gibbonPersonID=$gibbonPersonID&currentDate=$currentDate'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL']. '/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/attendance_take_byPerson_delete.php&gibbonAttendanceLogPersonID='.$rowLog['gibbonAttendanceLogPersonID']."&gibbonPersonID=$gibbonPersonID&currentDate=$currentDate&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']. "/img/garbage.png'/></a> ";
                            echo '</td>';
                        }


                        $lastType = ($prefillAttendanceType == 'Y')? $rowLog['type'] : '';
                        $lastReason = ($prefillAttendanceType == 'Y')? $rowLog['reason'] : '';
                        $lastComment = ($prefillAttendanceType == 'Y')? $rowLog['comment'] : '';
                        echo '</tr>';
                    }
                    echo '</table><br/>';
                }

                //Show student form
                echo "<script type='text/javascript'>
                    function dateCheck() {
                        var date = new Date();
                        if ('".$currentDate."'<getDate()) {
                            return confirm(\"".__($guid, 'The selected date for attendance is in the past. Are you sure you want to continue?').'")
                        }
                    }
                </script>';

                $form = Form::create('attendanceByPerson', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']. '/attendance_take_byPersonProcess.php?gibbonPersonID='.$gibbonPersonID);
                $form->setAutocomplete('off');

                if ($currentDate < $today) {
                    $form->addConfirmation('The selected date for attendance is in the past. Are you sure you want to continue?');
                }

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('currentDate', $currentDate);

                $form->addRow()->addHeading(__('Take Attendance'));

                $row = $form->addRow();
                    $row->addLabel('summary', __('Recent Attendance Summary'));
                    $row->addContent($attendance->renderMiniHistory($gibbonPersonID, 'floatRight'));

                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addSelect('type')->fromArray(array_keys($attendance->getAttendanceTypes()))->selected($lastType);

                $row = $form->addRow();
                    $row->addLabel('reason', __('Reason'));
                    $row->addSelect('reason')->fromArray($attendance->getAttendanceReasons())->selected($lastReason);

                $row = $form->addRow();
                    $row->addLabel('comment', __('Comment'))->description(__('255 character limit'));
                    $row->addTextArea('comment')->setRows(3)->maxLength(255)->setValue($lastComment);

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }
}
