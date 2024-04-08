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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';
require_once __DIR__ . '/src/AttendanceView.php';

// set page breadcrumb
$page->breadcrumbs->add(__('Take Attendance by Form Group'));

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byFormGroup.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $page->return->addReturns(['error3' => __('Your request failed because the specified date is in the future, or is not a school day.')]);

        $settingGateway = $container->get(SettingGateway::class);

        $attendance = new AttendanceView($gibbon, $pdo, $settingGateway);

        $gibbonFormGroupID = '';
        if (isset($_GET['gibbonFormGroupID']) == false) {
                $data = array('gibbonPersonIDTutor1' => $session->get('gibbonPersonID'), 'gibbonPersonIDTutor2' => $session->get('gibbonPersonID'), 'gibbonPersonIDTutor3' => $session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                $sql = "SELECT gibbonFormGroup.*, firstDay, lastDay FROM gibbonFormGroup JOIN gibbonSchoolYear ON (gibbonFormGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            if ($result->rowCount() > 0) {
                $row = $result->fetch();
                $gibbonFormGroupID = $row['gibbonFormGroupID'];
            }
        } else {
            $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        }

        $today = date('Y-m-d');
        $currentDate = isset($_GET['currentDate'])? Format::dateConvert($_GET['currentDate']) : $today;

        echo '<h2>'.__('Choose Form Group')."</h2>";

        $form = Form::create('filter', $session->get('absoluteURL') . '/index.php', 'get');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/Attendance/attendance_take_byFormGroup.php');

        $row = $form->addRow();
            $row->addLabel('gibbonFormGroupID', __('Form Group'));
            $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'))->required()->selected($gibbonFormGroupID)->placeholder();

        $row = $form->addRow();
            $row->addLabel('currentDate', __('Date'));
            $row->addDate('currentDate')->required()->setValue(Format::date($currentDate));

        $row = $form->addRow();
            $row->addSearchSubmit($session);

        echo $form->getOutput();

        if ($gibbonFormGroupID != '') {
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
                    $countClassAsSchool = $settingGateway->getSettingByScope('Attendance', 'countClassAsSchool');
                    $defaultAttendanceType = $settingGateway->getSettingByScope('Attendance', 'defaultFormGroupAttendanceType');

                    //Check form group

                        $data = array('gibbonFormGroupID' => $gibbonFormGroupID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                        $sql = 'SELECT gibbonFormGroup.*, firstDay, lastDay FROM gibbonFormGroup JOIN gibbonSchoolYear ON (gibbonFormGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonFormGroupID=:gibbonFormGroupID AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);

                    if ($result->rowCount() == 0) {
                        echo $page->getBlankSlate();
                        return;
                    }

                    $formGroup = $result->fetch();

                    if ($formGroup['attendance'] == 'N') {
                        print "<div class='error'>" ;
                            print __("Attendance taking has been disabled for this form group.") ;
                        print "</div>" ;
                    } else {

                        //Show attendance log for the current day
                            $dataLog = array('gibbonFormGroupID' => $gibbonFormGroupID, 'date' => $currentDate.'%');
                            $sqlLog = 'SELECT * FROM gibbonAttendanceLogFormGroup, gibbonPerson WHERE gibbonAttendanceLogFormGroup.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonFormGroupID=:gibbonFormGroupID AND date LIKE :date ORDER BY timestampTaken';
                            $resultLog = $connection2->prepare($sqlLog);
                            $resultLog->execute($dataLog);

                        if ($resultLog->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __('Attendance has not been taken for this group yet for the specified date. The entries below are a best-guess based on defaults and information put into the system in advance, not actual data.');
                            echo '</div>';
                        } else {
                            echo "<div class='success'>";
                            echo __('Attendance has been taken at the following times for the specified date for this group:');
                            echo '<ul>';
                            while ($rowLog = $resultLog->fetch()) {
                                echo '<li>'.sprintf(__('Recorded at %1$s on %2$s by %3$s.'), substr($rowLog['timestampTaken'], 11), Format::date(substr($rowLog['timestampTaken'], 0, 10)), Format::name('', $rowLog['preferredName'], $rowLog['surname'], 'Staff', false, true)).'</li>';
                            }
                            echo '</ul>';
                            echo '</div>';
                        }

                        //Show form group grid

                            $dataFormGroup = array('gibbonFormGroupID' => $gibbonFormGroupID, 'date' => $currentDate);
                            $sqlFormGroup = "SELECT gibbonPerson.image_240, gibbonPerson.dob, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonFormGroupID=:gibbonFormGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL  OR dateEnd>=:date) ORDER BY rollOrder, surname, preferredName";
                            $resultFormGroup = $connection2->prepare($sqlFormGroup);
                            $resultFormGroup->execute($dataFormGroup);

                        if ($resultFormGroup->rowCount() < 1) {
                            echo $page->getBlankSlate();
                        } else {
                            $count = 0;
                            $countPresent = 0;
                            $columns = 4;

                            $defaults = array('type' => $defaultAttendanceType, 'reason' => '', 'comment' => '', 'context' => '', 'direction' => '', 'prefill' => 'Y', 'gibbonFormGroupID' => 0);
                            $students = $resultFormGroup->fetchAll();

                            // Build the attendance log data per student
                            foreach ($students as $key => $student) {
                                $data = array('gibbonPersonID' => $student['gibbonPersonID'], 'date' => $currentDate);
                                $sql = "SELECT gibbonAttendanceLogPerson.type, reason, comment, gibbonAttendanceLogPerson.direction, context, timestampTaken, gibbonAttendanceCode.prefill, gibbonAttendanceLogPerson.gibbonFormGroupID
                                        FROM gibbonAttendanceLogPerson
                                        JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                        JOIN gibbonAttendanceCode ON (gibbonAttendanceCode.gibbonAttendanceCodeID=gibbonAttendanceLogPerson.gibbonAttendanceCodeID)
                                        WHERE gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID
                                        AND date LIKE :date";

                                if ($countClassAsSchool == 'N') {
                                    $sql .= " AND NOT context='Class'";
                                }
                                $sql .= " ORDER BY timestampTaken DESC";
                                $result = $pdo->executeQuery($data, $sql);

                                $log = ($result->rowCount() > 0)? $result->fetch() : $defaults;

                                if ($log['prefill'] == 'N' && (($log['context'] == 'Form Group' && $log['gibbonFormGroupID'] != $gibbonFormGroupID) || $log['context'] == 'Class') ) {
                                    $log = $defaults;
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
                                $absenceCount = getAbsenceCount($guid, $student['gibbonPersonID'], $connection2, $formGroup['firstDay'], $formGroup['lastDay']);
                                if ($absenceCount !== false) {
                                    $absenceText = ($absenceCount == 1)? __('%1$s Day Absent') : __('%1$s Days Absent');
                                    $students[$key]['absenceCount'] = sprintf($absenceText, $absenceCount);
                                }

                                if ($attendance->isTypePresent($log['type']) && $attendance->isTypeOnsite($log['type'])) {
                                    $countPresent++;
                                }

                                $students[$key]['log'] = $log;
                            }

                            $form = Form::create('attendanceByFormGroup', $session->get('absoluteURL').'/modules/'.$session->get('module'). '/attendance_take_byFormGroupProcess.php');
                            $form->setAutocomplete('off');

                            $form->addHiddenValue('address', $session->get('address'));
                            $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
                            $form->addHiddenValue('currentDate', $currentDate);
                            $form->addHiddenValue('count', count($students));

                            $form->addRow()->addHeading(__('Take Attendance') . ': '. htmlPrep($formGroup['name']));

                            $grid = $form->addRow()->addGrid('attendance')->setBreakpoints('w-1/2 sm:w-1/4 md:w-1/5 lg:w-1/4');

                            foreach ($students as $student) {
                                $form->addHiddenValue($count . '-gibbonPersonID', $student['gibbonPersonID']);

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
                                $cell->addSelect($count.'-type')
                                     ->fromArray($attendance->getAttendanceTypes($restricted))
                                     ->selected($student['log']['type'])
                                     ->setClass('mx-auto float-none w-32 m-0 mb-px')
                                     ->readOnly($restricted);
                                $cell->addSelect($count.'-reason')
                                     ->fromArray($attendance->getAttendanceReasons())
                                     ->selected($student['log']['reason'])
                                     ->setClass('mx-auto float-none w-32 m-0 mb-px');
                                $cell->addTextField($count.'-comment')
                                     ->maxLength(255)
                                     ->setValue($student['log']['comment'])
                                     ->setClass('mx-auto float-none w-32 m-0 mb-2');
                                $cell->addContent($attendance->renderMiniHistory($student['gibbonPersonID'], 'Form Group'));

                                $count++;
                            }

                            $form->addRow()->addAlert(__('Total students:').' '. $count, 'success')->setClass('right')
                                ->append('<br/><span title="'.__('e.g. Present or Present - Late').'">'.__('Total students present in room:').' '. $countPresent.'</span>')
                                ->append('<br/><span title="'.__('e.g. not Present and not Present - Late').'">'.__('Total students absent from room:').' '. ($count-$countPresent).'</span>')
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
}
