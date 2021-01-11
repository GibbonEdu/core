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
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_attendance.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Enter Activity Attendance'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h2>';
    echo __('Choose Activity');
    echo '</h2>';

    $highestAction = getHighestGroupedAction($guid, '/modules/Activities/activities_attendance.php', $connection2);
    $gibbonActivityID = null;
    if (isset($_GET['gibbonActivityID'])) {
        $gibbonActivityID = $_GET['gibbonActivityID'];
    }

    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);

    $sql = "";
    if($highestAction == "Enter Activity Attendance") {
        $sql = "SELECT gibbonActivity.gibbonActivityID AS value, name, programStart  FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name, programStart";
    } elseif($highestAction == "Enter Activity Attendance_leader") {
        $data["gibbonPersonID"] = $_SESSION[$guid]["gibbonPersonID"];
        $sql = "SELECT gibbonActivity.gibbonActivityID AS value, name, programStart FROM gibbonActivityStaff JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID = gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND gibbonActivityStaff.gibbonPersonID=:gibbonPersonID AND (gibbonActivityStaff.role='Organiser' OR gibbonActivityStaff.role='Assistant' OR gibbonActivityStaff.role='Coach') ORDER BY name, programStart";
    }

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/activities_attendance.php");

    $row = $form->addRow();
        $row->addLabel('gibbonActivityID', __('Activity'));
        $row->addSelect('gibbonActivityID')->fromQuery($pdo, $sql, $data)->selected($gibbonActivityID)->required()->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    // Cancel out early if we have no gibbonActivityID
    if (empty($gibbonActivityID)) {
        return;
    }

    
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStudent.status='Accepted' AND gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName";
        $studentResult = $connection2->prepare($sql);
        $studentResult->execute($data);

    
        $data = array('gibbonActivityID' => $gibbonActivityID);
        $sql = "SELECT gibbonSchoolYearTermIDList, maxParticipants, programStart, programEnd, (SELECT COUNT(*) FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.status='Waiting List' AND gibbonPerson.status='Full') AS waiting FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID";
        $activityResult = $connection2->prepare($sql);
        $activityResult->execute($data);

    if ($studentResult->rowCount() < 1 || $activityResult->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';

        return;
    }


    $students = $studentResult->fetchAll();

    
        $data = array('gibbonActivityID' => $gibbonActivityID);
        $sql = 'SELECT gibbonActivityAttendance.date, gibbonActivityAttendance.timestampTaken, gibbonActivityAttendance.attendance, gibbonPerson.preferredName, gibbonPerson.surname FROM gibbonActivityAttendance, gibbonPerson WHERE gibbonActivityAttendance.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonActivityAttendance.gibbonActivityID=:gibbonActivityID';
        $attendanceResult = $connection2->prepare($sql);
        $attendanceResult->execute($data);
    // Gather the existing attendance data (by date and not index, should the time slots change)
    $sessionAttendanceData = array();

    while ($attendance = $attendanceResult->fetch()) {
        $sessionAttendanceData[ $attendance['date'] ] = array(
            'data' => (!empty($attendance['attendance'])) ? unserialize($attendance['attendance']) : array(),
            'info' => sprintf(__('Recorded at %1$s on %2$s by %3$s.'), substr($attendance['timestampTaken'], 11), dateConvertBack($guid, substr($attendance['timestampTaken'], 0, 10)), Format::name('', $attendance['preferredName'], $attendance['surname'], 'Staff', false, true)),
        );
    }

    $activity = $activityResult->fetch();
    $activity['participants'] = $studentResult->rowCount();

    // Get the week days that match time slots for this activity
    $activityWeekDays = getActivityWeekdays($connection2, $gibbonActivityID);

    // Get the start and end date of the activity, depending on which dateType we're using
    $activityTimespan = getActivityTimespan($connection2, $gibbonActivityID, $activity['gibbonSchoolYearTermIDList']);

    // Use the start and end date of the activity, along with time slots, to get the activity sessions
    $activitySessions = getActivitySessions($guid, $connection2, $activityWeekDays, $activityTimespan, $sessionAttendanceData);

    echo '<h2>';
    echo __('Activity');
    echo '</h2>';

    echo "<table class='smallIntBorder' style='width: 100%' cellspacing='0'><tbody>";
    echo '<tr>';
    echo "<td style='width: 33%; vertical-align: top'>";
    echo "<span class='infoTitle'>".__('Start Date').'</span><br>';
    if (!empty($activityTimespan['start'])) {
        echo date($_SESSION[$guid]['i18n']['dateFormatPHP'], $activityTimespan['start']);
    }
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    echo "<span class='infoTitle'>".__('End Date').'</span><br>';
    if (!empty($activityTimespan['end'])) {
        echo date($_SESSION[$guid]['i18n']['dateFormatPHP'], $activityTimespan['end']);
    }
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    printf("<span class='infoTitle' title=''>%s</span><br>%s", __('Number of Sessions'), count($activitySessions));
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo "<td style='width: 33%; vertical-align: top'>";
    printf("<span class='infoTitle'>%s</span><br>%s", __('Participants'), $activity['participants']);
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    printf("<span class='infoTitle'>%s</span><br>%s", __('Maximum Participants'), $activity['maxParticipants']);
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    printf("<span class='infoTitle' title=''>%s</span><br>%s", __('Waiting'), $activity['waiting']);
    echo '</td>';
    echo '</tr>';
    echo '</tbody></table>';

    echo '<h2>';
    echo __('Attendance');
    echo '</h2>';

    // Handle activities with no time slots or start/end, but don't return because there can still be previous records
    if (empty($activityWeekDays) || empty($activityTimespan)) {
        echo "<div class='error'>";
        echo __('There are no time slots assigned to this activity, or the start and end dates are invalid. New attendance values cannot be entered until the time slots and dates are added.');
        echo '</div>';
    }

    if (count($activitySessions) <= 0) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        if (isActionAccessible($guid, $connection2, '/modules/Activities/report_attendanceExport.php')) {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/report_attendanceExport.php?gibbonActivityID='.$gibbonActivityID."'>".__('Export to Excel')."<img style='margin-left: 5px' title='".__('Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
            echo '</div>';
        }

        $form = Form::create('attendance', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_attendanceProcess.php?gibbonActivityID='.$gibbonActivityID);
        $form->setClass('blank block max-w-full');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('gibbonPersonID', $_SESSION[$guid]['gibbonPersonID']);

        $row = $form->addRow('doublescroll-wrapper')->setClass('block doublescroll-wrapper smallIntBorder w-full max-w-full')->addColumn();

        // Headings as a separate table
        $table = $row->addTable()->setClass('mini w-full m-0 border-0');
        $header = $table->addHeaderRow();
            $header->addContent(__('Student'))->addClass('w-48 py-8');
            $header->addContent(__('Attendance'));
            $header->addContent(sprintf(__('Sessions Recorded: %s of %s'), count($sessionAttendanceData), count($activitySessions)))
                ->addClass('emphasis subdued right');

        $table = $row->addClass('doublescroll-container block ')->addColumn()->setClass('ml-48 border-l-2 border-gray-600 -mt-1')
            ->addTable()->setClass('mini colorOddEven w-full m-0 border-0 overflow-x-scroll rowHighlight');

        $row = $table->addRow();
            $row->addContent(__('Date'))->addClass('w-48 h-24 absolute left-0 ml-px flex items-center');

        $icon = '<img class="mt-1 inline" title="%1$s" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/%2$s"/>';

        // Display the date and action buttons for each session
        $i = 0;
        foreach ($activitySessions as $sessionDate => $sessionTimestamp) {
            $col = $row->addColumn()->addClass('h-24 px-2 text-center');
            $dateLabel = $col->addContent(Format::dateReadable($sessionDate, '%a<br>%b %e'))->addClass('w-10 mx-auto whitespace-nowrap');

            if (isset($sessionAttendanceData[$sessionDate]['data'])) {
                $col->addWebLink(sprintf($icon, __('Edit'), 'config.png'))
                    ->setURL('')
                    ->addClass('editColumn')
                    ->addData('checked', '')
                    ->addData('column', strval($i))
                    ->addData('date', $sessionTimestamp);
            } else {
                $col->addWebLink(sprintf($icon, __('Add'), 'page_new.png'))
                    ->setURL('')
                    ->addClass('editColumn')
                    ->addData('checked', 'checked')
                    ->addData('column', strval($i))
                    ->addData('date', $sessionTimestamp);
                $dateLabel->addClass('subdued');
            }

            $col->addWebLink(sprintf($icon, __('Clear'), 'garbage.png'))
                ->setURL('')
                ->addClass('clearColumn hidden')
                ->addData('column', strval($i));

            $i++;
        }

        // Build an empty array of attendance count data for each session
        $attendanceCount = array_combine(array_keys($activitySessions), array_fill(0, count($activitySessions), 0));

        // Display student attendance data per session
        foreach ($students as $index => $student) {
            $row = $table->addRow()->addData('student', $student['gibbonPersonID']);

            $col = $row->addColumn()->addClass('w-48 h-8 absolute left-0 ml-px text-left');

            $col->addWebLink(Format::name('', $student['preferredName'], $student['surname'], 'Student', true))
                ->setURl($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php')
                ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                ->setClass('')
                ->prepend(($index+1).') ');

            $i = 0;
            foreach ($activitySessions as $sessionDate => $sessionTimestamp) {
                $content = '';
                if (isset($sessionAttendanceData[$sessionDate]['data'][$student['gibbonPersonID']])) {
                    $content = '✓';
                    $attendanceCount[$sessionDate]++;
                }
                $row->addContent($content)->setClass("col$i h-8 text-center");
                ++$i;
            }
        }

        // Total students per date
        $row = $table->addRow();
        $row->addContent(__('Total students:'))->addClass('text-right w-48 h-8 absolute left-0 ml-px');

        foreach ($activitySessions as $sessionDate => $sessionTimestamp) {
            $row->setClass('h-8')->addContent(!empty($attendanceCount[$sessionDate])
                ? $attendanceCount[$sessionDate].' / '.$activity['participants']
                : '');
        }

        $row = $form->addRow()->addClass('flex w-full')->addTable()->setClass('smallIntBorder w-full')->addRow();
            $row->addContent(__('All highlighted columns will be updated when you press submit.'))
                ->wrap('<span class="small emphasis">', '</span>');
            $row->addSubmit();

        echo $form->getOutput();

        echo '<br/>';
    }
}
