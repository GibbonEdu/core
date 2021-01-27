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
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';
require_once __DIR__ . '/src/AttendanceView.php';

// set page breadcrumb
$page->breadcrumbs->add(__('Take Attendance by Person'));

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('error3' => __('Your request failed because the specified date is in the future, or is not a school day.')));
    }

    $attendance = new AttendanceView($gibbon, $pdo);

    $today = date('Y-m-d');
    $currentDate = isset($_GET['currentDate'])? dateConvert($guid, $_GET['currentDate']) : $today;
    $gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : null;

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');
    $form->setTitle(__('Choose Student'));

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/attendance_take_byPerson.php');

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'])->required()->selected($gibbonPersonID)->placeholder();

    $row = $form->addRow();
        $row->addLabel('currentDate', __('Date'));
        $row->addDate('currentDate')->required()->setValue(dateConvertBack($guid, $currentDate));

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if ($gibbonPersonID != '') {
        if ($currentDate > $today) {
            echo "<div class='error'>";
            echo __('The specified date is in the future: it must be today or earlier.');
            echo '</div>';
        } else {
            if (isSchoolOpen($guid, $currentDate, $connection2) == false) {
                echo "<div class='error'>";
                echo __('School is closed on the specified date, and so attendance information cannot be recorded.');
                echo '</div>';
            } else {
                $countClassAsSchool = getSettingByScope($connection2, 'Attendance', 'countClassAsSchool');

                //Get last 5 school days from currentDate within the last 100
                $timestamp = dateConvertToTimestamp($currentDate);

                // Get school-wide attendance logs
                $attendanceLogGateway = $container->get(AttendanceLogPersonGateway::class);
                $criteria = $attendanceLogGateway->newQueryCriteria()
                    ->sortBy('timestampTaken')
                    ->filterBy('notClass', $countClassAsSchool == 'N');

                $logs = $attendanceLogGateway->queryByPersonAndDate($criteria, $gibbonPersonID, $currentDate);
                $lastLog = $logs->getRow(count($logs) - 1);

                // Get class attendance logs
                $classLogCount = 0;
                if ($countClassAsSchool == 'N') {
                    $criteria = $attendanceLogGateway->newQueryCriteria()
                        ->sortBy(['timeStart', 'timeEnd', 'timestampTaken']);

                    $classLogs = $attendanceLogGateway->queryClassAttendanceByPersonAndDate($criteria, $gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID, $currentDate);
                    $classLogs->transform(function (&$log) use (&$classLogCount) {
                        if (!empty($log['gibbonAttendanceLogPersonID'])) $classLogCount++;
                    });
                }

                // DATA TABLE: Show attendance log for the current day
                $table = DataTable::create('attendanceLogs');

                $table->modifyRows(function ($log, $row) {
                    if ($log['scope'] == 'Onsite - Late' || $log['scope'] == 'Offsite - Late' || $log['scope'] == 'Offsite - Left') $row->addClass('warning');
                    elseif ($log['direction'] == 'Out') $row->addClass('error');
                    elseif (!empty($log['direction'])) $row->addClass('current');
                    return $row;
                });

                $table->addColumn('period', __('Period'))
                    ->format(function ($log) {
                        if (empty($log['period'])) return Format::small(__('N/A'));
                        return $log['period'].'<br/>'.Format::small(Format::timeRange($log['timeStart'], $log['timeEnd']));
                    });

                $table->addColumn('time', __('Time'))
                    ->format(function ($log) use ($currentDate) {
                        if (empty($log['timestampTaken'])) return Format::small(__('N/A'));

                        return $currentDate != substr($log['timestampTaken'], 0, 10)
                            ? Format::dateTimeReadable($log['timestampTaken'], '%H:%M, %b %d')
                            : Format::dateTimeReadable($log['timestampTaken'], '%H:%M');
                    });

                $table->addColumn('direction', __('Attendance'))
                    ->format(function ($log) use ($guid) {
                        if (empty($log['direction'])) return Format::small(__('Not Taken'));

                        $output = '<b>'.__($log['direction']).'</b> ('.__($log['type']). (!empty($log['reason'])? ', '.__($log['reason']) : '') .')';
                        if (!empty($log['comment'])) {
                            $output .= '&nbsp;<img title="'.$log['comment'].'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/messageWall.png" width=16 height=16/>';
                        }

                        return $output;
                    });

                $table->addColumn('where', __('Where'))
                    ->width('25%')
                    ->format(function ($log) {
                        return ($log['context'] == 'Class' && !empty($log['gibbonCourseClassID']))
                            ? __($log['context']).' ('.Format::courseClassName($log['courseName'], $log['className']).')'
                            : __($log['context']);
                    });

                $table->addColumn('timestampTaken', __('Recorded By'))
                    ->width('22%')
                    ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Staff', false, true]));

                // ACTIONS
                if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson_edit.php')) {
                    $table->addActionColumn()
                        ->addParam('gibbonAttendanceLogPersonID')
                        ->addParam('gibbonPersonID', $gibbonPersonID)
                        ->addParam('currentDate', $currentDate)
                        ->format(function ($log, $actions) {
                            if (empty($log['gibbonAttendanceLogPersonID'])) return;

                            $actions->addAction('edit', __('Edit'))
                                ->setURL('/modules/Attendance/attendance_take_byPerson_edit.php');

                            $actions->addAction('delete', __('Delete'))
                                ->setURL('/modules/Attendance/attendance_take_byPerson_delete.php');
                        });
                }

                // School-wide attendance: Roll Group, Person, Future and Self Registration
                $schoolTable = clone $table;
                $schoolTable->setTitle(__('Attendance Log'));
                $schoolTable->setDescription(count($logs) > 0 ? __('The following attendance log has been recorded for the selected student today:') : '');
                $schoolTable->removeColumn('period');

                $schoolTable->addHeaderAction('view', __('View All'))
                    ->setURL('/modules/Students/student_view_details.php')
                    ->addParam('gibbonPersonID', $gibbonPersonID)
                    ->addParam('allStudents', 'N')
                    ->addParam('search', '')
                    ->addParam('subpage', 'Attendance')
                    ->displayLabel();

                if (count($logs) + $classLogCount == 0) {
                    $schoolTable->addMetaData('blankSlate', __('There is currently no attendance data today for the selected student.'));
                }

                echo $schoolTable->render($logs);

                // Class Attendance
                if ($countClassAsSchool == 'N') {
                    if ($classLogCount > 0) {
                        $classTable = clone $table;
                        $classTable->setTitle(__('Class Attendance'));

                        echo $classTable->render($classLogs);
                    }
                }
                echo '<br/>';

                // FORM: Take Attendance by Person
                $form = Form::create('attendanceByPerson', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']. '/attendance_take_byPersonProcess.php?gibbonPersonID='.$gibbonPersonID);
                $form->setAutocomplete('off');

                if ($currentDate < $today) {
                    $form->addConfirmation(__('The selected date for attendance is in the past. Are you sure you want to continue?'));
                }

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('currentDate', $currentDate);

                $form->addRow()->addHeading(__('Take Attendance'));

                $row = $form->addRow();
                    $row->addLabel('summary', __('Recent Attendance Summary'));
                    $row->addContent($attendance->renderMiniHistory($gibbonPersonID, 'Person', null, 'floatRight'));

                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addSelect('type')->fromArray(array_keys($attendance->getAttendanceTypes()))->selected($lastLog['type'] ?? '');

                $row = $form->addRow();
                    $row->addLabel('reason', __('Reason'));
                    $row->addSelect('reason')->fromArray($attendance->getAttendanceReasons())->selected($lastLog['reason'] ?? '');

                $row = $form->addRow();
                    $row->addLabel('comment', __('Comment'))->description(__('255 character limit'));
                    $row->addTextArea('comment')->setRows(3)->maxLength(255)->setValue($lastLog['comment'] ?? '');

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }
}
