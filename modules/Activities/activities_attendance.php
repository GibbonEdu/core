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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_attendance.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Enter Activity Attendance').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h2>';
    echo __($guid, 'Choose Activity');
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
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/activities_attendance.php");

    $row = $form->addRow();
        $row->addLabel('gibbonActivityID', __('Activity'));
        $row->addSelect('gibbonActivityID')->fromQuery($pdo, $sql, $data)->selected($gibbonActivityID)->isRequired()->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    // Cancel out early if we have no gibbonActivityID
    if (empty($gibbonActivityID)) {
        return;
    }

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStudent.status='Accepted' AND gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName";
        $studentResult = $connection2->prepare($sql);
        $studentResult->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    try {
        $data = array('gibbonActivityID' => $gibbonActivityID);
        $sql = "SELECT gibbonSchoolYearTermIDList, maxParticipants, programStart, programEnd, (SELECT COUNT(*) FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.status='Waiting List' AND gibbonPerson.status='Full') AS waiting FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID";
        $activityResult = $connection2->prepare($sql);
        $activityResult->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($studentResult->rowCount() < 1 || $activityResult->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';

        return;
    }


    $students = $studentResult->fetchAll();

    try {
        $data = array('gibbonActivityID' => $gibbonActivityID);
        $sql = 'SELECT gibbonActivityAttendance.date, gibbonActivityAttendance.timestampTaken, gibbonActivityAttendance.attendance, gibbonPerson.preferredName, gibbonPerson.surname FROM gibbonActivityAttendance, gibbonPerson WHERE gibbonActivityAttendance.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonActivityAttendance.gibbonActivityID=:gibbonActivityID';
        $attendanceResult = $connection2->prepare($sql);
        $attendanceResult->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    // Gather the existing attendance data (by date and not index, should the time slots change)
    $sessionAttendanceData = array();

    while ($attendance = $attendanceResult->fetch()) {
        $sessionAttendanceData[ $attendance['date'] ] = array(
            'data' => (!empty($attendance['attendance'])) ? unserialize($attendance['attendance']) : array(),
            'info' => sprintf(__($guid, 'Recorded at %1$s on %2$s by %3$s.'), substr($attendance['timestampTaken'], 11), dateConvertBack($guid, substr($attendance['timestampTaken'], 0, 10)), formatName('', $attendance['preferredName'], $attendance['surname'], 'Staff', false, true)),
        );
    }

    $activity = $activityResult->fetch();
    $activity['participants'] = $studentResult->rowCount();

    // Get the week days that match time slots for this activity
    $activityWeekDays = getActivityWeekdays($connection2, $gibbonActivityID);

    // Get the start and end date of the activity, depending on which dateType we're using
    $activityTimespan = getActivityTimespan($connection2, $gibbonActivityID, $activity['gibbonSchoolYearTermIDList']);

    // Use the start and end date of the activity, along with time slots, to get the activity sessions
    $activitySessions = getActivitySessions($activityWeekDays, $activityTimespan, $sessionAttendanceData);

    echo '<h2>';
    echo __($guid, 'Activity');
    echo '</h2>';

    echo "<table class='smallIntBorder' style='width: 100%' cellspacing='0'><tbody>";
    echo '<tr>';
    echo "<td style='width: 33%; vertical-align: top'>";
    echo "<span class='infoTitle'>".__($guid, 'Start Date').'</span><br>';
    if (!empty($activityTimespan['start'])) {
        echo date($_SESSION[$guid]['i18n']['dateFormatPHP'], $activityTimespan['start']);
    }
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    echo "<span class='infoTitle'>".__($guid, 'End Date').'</span><br>';
    if (!empty($activityTimespan['end'])) {
        echo date($_SESSION[$guid]['i18n']['dateFormatPHP'], $activityTimespan['end']);
    }
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    printf("<span class='infoTitle' title=''>%s</span><br>%s", __($guid, 'Number of Sessions'), count($activitySessions));
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo "<td style='width: 33%; vertical-align: top'>";
    printf("<span class='infoTitle'>%s</span><br>%s", __($guid, 'Participants'), $activity['participants']);
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    printf("<span class='infoTitle'>%s</span><br>%s", __($guid, 'Maximum Participants'), $activity['maxParticipants']);
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    printf("<span class='infoTitle' title=''>%s</span><br>%s", __($guid, 'Waiting'), $activity['waiting']);
    echo '</td>';
    echo '</tr>';
    echo '</tbody></table>';

    echo '<h2>';
    echo __($guid, 'Attendance');
    echo '</h2>';

    // Handle activities with no time slots or start/end, but don't return because there can still be previous records
    if (empty($activityWeekDays) || empty($activityTimespan)) {
        echo "<div class='error'>";
        echo __($guid, 'There are no time slots assigned to this activity, or the start and end dates are invalid. New attendance values cannot be entered until the time slots and dates are added.');
        echo '</div>';
    }

    if (count($activitySessions) <= 0) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if (isActionAccessible($guid, $connection2, '/modules/Activities/report_attendanceExport.php')) {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/report_attendanceExport.php?gibbonActivityID='.$gibbonActivityID."'>".__($guid, 'Export to Excel')."<img style='margin-left: 5px' title='".__($guid, 'Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
            echo '</div>';
        }

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_attendanceProcess.php?gibbonActivityID='.$gibbonActivityID)->setClass('');
        $form->getRenderer()->setWrapper('form', 'div')->setWrapper('row', 'div')->setWrapper('cell', 'div');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('gibbonPersonID', $_SESSION[$guid]['gibbonPersonID']);

        $row = $form->addRow()->addClass('doublescroll-wrapper');

        // Headings as a separate table (for double-scroll)
        $table = $row->addTable()->setClass('mini fullWidth noMargin noBorder');
        $header = $table->addHeaderRow();
            $header->addContent(__('Student'))->addClass('attendanceRowHeader');
            $header->addContent(__('Attendance'));
            $header->addContent(sprintf(__('Sessions Recorded: %s of %s'), count($sessionAttendanceData), count($activitySessions)))
                ->addClass('emphasis subdued right');

        $row->addContent("<div class='doublescroll-top'><div class='doublescroll-top-tablewidth'></div></div>");

        // Wrap the attendance table in a double-scroll container
        $table = $row->addColumn()->addClass('doublescroll-container')
            ->addTable()->setClass('mini colorOddEven fullWidth noMargin noBorder');

        $row = $table->addRow();
            $row->addContent(__('Date'))->addClass('attendanceDateHeader attendanceRowHeader');

        $icon = '<img style="margin-top: 3px" title="%1$s" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/%2$s"/>';

        // Display the date and action buttons for each session
        $i = 0;
        foreach ($activitySessions as $sessionDate => $sessionTimestamp) {
            $col = $row->addColumn()->addClass('attendanceDateHeader');
            $dateLabel = $col->addContent(date('D<\b\r>M j', $sessionTimestamp))->addClass('attendanceDate');

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

            $row->addWebLink(formatName('', $student['preferredName'], $student['surname'], 'Student', true))
                ->setURl($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php')
                ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                ->setClass('colName')
                ->prepend(($index+1).') ');

            $i = 0;
            foreach ($activitySessions as $sessionDate => $sessionTimestamp) {
                $content = '';
                if (isset($sessionAttendanceData[$sessionDate]['data'][$student['gibbonPersonID']])) {
                    $content = '✓';
                    $attendanceCount[$sessionDate]++;
                }
                $row->addContent($content)->setClass("col$i");
                ++$i;
            }
        }

        // Total students per date
        $row = $table->addRow();
        $row->addContent(__('Total students:'))->addClass('right');

        foreach ($activitySessions as $sessionDate => $sessionTimestamp) {
            $row->addContent(!empty($attendanceCount[$sessionDate])
                ? $attendanceCount[$sessionDate].' / '.$activity['participants']
                : ''
            );
        }

        $row = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth')->addRow();
            $row->addContent(__('All highlighted columns will be updated when you press submit.'))
                ->wrap('<span class="small emphasis">', '</span>');
            $row->addSubmit();

        echo $form->getOutput();

        echo '<br/>';
    }
}
