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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_attendance.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Attendance History by Activity'));

    echo '<h2>';
    echo __('Choose Activity');
    echo '</h2>';

    $gibbonActivityID = null;
    if (isset($_GET['gibbonActivityID'])) {
        $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';
    }
    $allColumns = (isset($_GET['allColumns'])) ? $_GET['allColumns'] : false;

    $form = Form::create('action', $session->get('absoluteURL').'/index.php','get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$session->get('module')."/report_attendance.php");

    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
    $sql = "SELECT gibbonActivityID AS value, name FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name, programStart";
    $row = $form->addRow();
        $row->addLabel('gibbonActivityID', __('Activity'));
        $row->addSelect('gibbonActivityID')->fromQuery($pdo, $sql, $data)->selected($gibbonActivityID)->required()->placeholder();

    $row = $form->addRow();
        $row->addLabel('allColumns', __('All Columns'))->description(__('Include empty columns with unrecorded attendance.'));
        $row->addCheckbox('allColumns')->checked($allColumns);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

    // Cancel out early if we have no gibbonActivityID
    if (empty($gibbonActivityID)) {
        return;
    }


        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonActivityID' => $gibbonActivityID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonFormGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStudent.status='Accepted' AND gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName";
        $studentResult = $connection2->prepare($sql);
        $studentResult->execute($data);


        $data = array('gibbonActivityID' => $gibbonActivityID);
        $sql = "SELECT gibbonSchoolYearTermIDList, maxParticipants, programStart, programEnd, (SELECT COUNT(*) FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.status='Waiting List' AND gibbonPerson.status='Full') AS waiting FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID";
        $activityResult = $connection2->prepare($sql);
        $activityResult->execute($data);

    if ($studentResult->rowCount() < 1 || $activityResult->rowCount() < 1) {
        echo $page->getBlankSlate();

        return;
    }


        $data = array('gibbonActivityID' => $gibbonActivityID);
        $sql = 'SELECT gibbonActivityAttendance.date, gibbonActivityAttendance.timestampTaken, gibbonActivityAttendance.attendance, gibbonPerson.preferredName, gibbonPerson.surname FROM gibbonActivityAttendance, gibbonPerson WHERE gibbonActivityAttendance.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonActivityAttendance.gibbonActivityID=:gibbonActivityID';
        $attendanceResult = $connection2->prepare($sql);
        $attendanceResult->execute($data);

    // Gather the existing attendance data (by date and not index, should the time slots change)
    $sessionAttendanceData = array();

    while ($attendance = $attendanceResult->fetch()) {
        $sessionAttendanceData[ $attendance['date'] ] = array(
            'data' => (!empty($attendance['attendance'])) ? unserialize($attendance['attendance']) : array(),
            'info' => sprintf(__('Recorded at %1$s on %2$s by %3$s.'), substr($attendance['timestampTaken'], 11), Format::date(substr($attendance['timestampTaken'], 0, 10)), Format::name('', $attendance['preferredName'], $attendance['surname'], 'Staff', false, true)),
        );
    }

    $activity = $activityResult->fetch();
    $activity['participants'] = $studentResult->rowCount();

    // Get the week days that match time slots for this activity
    $activityWeekDays = getActivityWeekdays($connection2, $gibbonActivityID);

    // Get the start and end date of the activity, depending on which dateType we're using
    $activityTimespan = getActivityTimespan($connection2, $gibbonActivityID, $activity['gibbonSchoolYearTermIDList']);

    // Use the start and end date of the activity, along with time slots, to get the activity sessions
    $activitySessions = getActivitySessions($guid, $connection2, ($allColumns) ? $activityWeekDays : array(), $activityTimespan, $sessionAttendanceData);

    echo '<h2>';
    echo __('Activity');
    echo '</h2>';

    echo "<table class='smallIntBorder' style='width: 100%;' cellspacing='0'><tbody>";
    echo '<tr>';
    echo "<td style='width: 33%; vertical-align: top'>";
    echo "<span class='infoTitle'>".__('Start Date').'</span><br>';
    if (!empty($activityTimespan['start'])) {
        echo date($session->get('i18n')['dateFormatPHP'], $activityTimespan['start']);
    }
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    echo "<span class='infoTitle'>".__('End Date').'</span><br>';
    if (!empty($activityTimespan['end'])) {
        echo date($session->get('i18n')['dateFormatPHP'], $activityTimespan['end']);
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

    if ($allColumns == false && $attendanceResult->rowCount() < 1) {
        echo $page->getBlankSlate();

        return;
    }

    if (empty($activityWeekDays) || empty($activityTimespan)) {
        echo "<div class='error'>";
        echo __('There are no time slots assigned to this activity, or the start and end dates are invalid. New attendance values cannot be entered until the time slots and dates are added.');
        echo '</div>';
    }

    if (count($activitySessions) <= 0) {
        echo $page->getBlankSlate();
    } else {
        if (isActionAccessible($guid, $connection2, '/modules/Activities/report_attendanceExport.php')) {
            echo "<div class='linkTop'>";
            echo "<a href='".$session->get('absoluteURL').'/modules/'.$session->get('module').'/report_attendanceExport.php?gibbonActivityID='.$gibbonActivityID."'>".__('Export to Excel')."<img style='margin-left: 5px' title='".__('Export to Excel')."' src='./themes/".$session->get('gibbonThemeName')."/img/download.png'/></a>";
            echo '</div>';
        }

        echo "<div id='attendance' class='block max-w-full'>";
        echo "<div class='doublescroll-wrapper'>";

        echo "<table class='mini' cellspacing='0' style='width:100%; border: 0; margin:0;'>";
        echo "<tr class='head' style='height:60px; '>";
        echo "<th style='width:190px;'>";
        echo __('Student');
        echo '</th>';
        echo '<th>';
        echo __('Attendance');
        echo '</th>';
        echo "<th class='emphasis subdued' style='text-align:right'>";
        printf(__('Sessions Recorded: %s of %s'), count($sessionAttendanceData), count($activitySessions));
        echo '</th>';
        echo '</tr>';
        echo '</table>';
        echo "<div class='doublescroll-top'><div class='doublescroll-top-tablewidth'></div></div>";

        $columnCount = ($allColumns) ? count($activitySessions) : count($sessionAttendanceData);

        echo "<div class='doublescroll-container overflow-x-scroll'>";
        echo "<table class='mini colorOddEven border-0' cellspacing='0' style='width: ".(($columnCount * 56)+175)."px'>";

        echo "<tr style='height: 55px'>";
        echo "<td style='vertical-align:top;height:55px;width:175px'>".__('Date').'</td>';

        foreach ($activitySessions as $sessionDate => $sessionTimestamp) {
            if (isset($sessionAttendanceData[$sessionDate]['data'])) {
                // Handle instances where the time slot has been deleted after creating an attendance record
                        if (!in_array(date('D', $sessionTimestamp), $activityWeekDays) || ($sessionTimestamp < $activityTimespan['start']) || ($sessionTimestamp > $activityTimespan['end'])) {
                            echo "<td style='vertical-align:top; width: 50px;  white-space: nowrap;' class='warning' title='".__('Does not match the time slots for this activity.')."'>";
                        } else {
                            echo "<td style='vertical-align:top; width: 50px;  white-space: nowrap;'>";
                        }

                printf("<span title='%s'>%s <br/> %s</span><br/>&nbsp;<br/>",
                    $sessionAttendanceData[$sessionDate]['info'],
                    Format::dayOfWeekName($sessionDate, true),
                    Format::dateReadable($sessionDate, Format::MEDIUM_NO_YEAR)
                );
            } else {
                echo "<td style='color: #bbb; vertical-align:top; width: 50px; white-space: nowrap;'>";
                echo Format::dayOfWeekName($sessionDate).' <br/> '.
                    Format::dateReadable($sessionDate, Format::MEDIUM_NO_YEAR).'<br/>&nbsp;<br/>';
            }
            echo '</td>';
        }

        echo '</tr>';

        $count = 0;
        // Build an empty array of attendance count data for each session
        $attendanceCount = array_combine(array_keys($activitySessions), array_fill(0, count($activitySessions), 0));

        while ($row = $studentResult->fetch()) {
            ++$count;
            $student = $row['gibbonPersonID'];

            echo "<tr data-student='$student'>";
            echo '<td>';
            echo $count.'. '.Format::name('', $row['preferredName'], $row['surname'], 'Student', true);
            echo '</td>';

            foreach ($activitySessions as $sessionDate => $sessionTimestamp) {
                echo "<td class='col'>";
                if (isset($sessionAttendanceData[$sessionDate]['data'])) {
                    if (isset($sessionAttendanceData[$sessionDate]['data'][$student])) {
                        echo '✓';
                        $attendanceCount[$sessionDate]++;
                    }
                }
                echo '</td>';
            }

            echo '</tr>';

            $lastPerson = $row['gibbonPersonID'];
        }

            // Output a total attendance per column
            echo '<tr>';
        echo "<td class='right'>";
        echo __('Total students:');
        echo '</td>';

        foreach ($activitySessions as $sessionDate => $sessionTimestamp) {
            echo '<td>';
            if (!empty($attendanceCount[$sessionDate])) {
                echo $attendanceCount[$sessionDate].' / '.$activity['participants'];
            }
            echo '</td>';
        }

        echo '</tr>';

        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=16>';
            echo __('There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '</div>';
        echo '</div>';
        echo '</div><br/>';
    }
}

?>
