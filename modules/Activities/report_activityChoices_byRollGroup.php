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
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activityChoices_byRollGroup.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Activity Choices by Form Group'));

    echo '<h2>';
    echo __('Choose Form Group');
    echo '</h2>';

    $gibbonRollGroupID = $_GET['gibbonRollGroupID'] ?? '';
    $status = $_GET['status'] ?? '';

    $form = Form::create('action', $session->get('absoluteURL').'/index.php','get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_activityChoices_byRollGroup.php");

    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Form Group'));
        $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonRollGroupID)->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if ($gibbonRollGroupID != '') {
        $output = '';
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';


            $data = array('gibbonRollGroupID' => $gibbonRollGroupID, 'today' => date('Y-m-d'));
            $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, name FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {
            echo "<table cellspacing='0' class='fullWidth colorOddEven'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __('Student');
            echo '</th>';
            echo '<th>';
            echo __('Activities');
            echo '</th>';
            echo '</tr>';

            while ($row = $result->fetch()) {
                echo '<tr>';
                echo '<td>';
                echo '<b><a href="index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID'].'&subpage=Activities">'.Format::name('', $row['preferredName'], $row['surname'], 'Student', true).'</a>';
                echo '</td>';

                echo '<td>';


                    $dataActivities = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sqlActivities = "SELECT gibbonActivity.*, gibbonActivityStudent.status, GROUP_CONCAT(CONCAT(gibbonDaysOfWeek.nameShort, ' ', TIME_FORMAT(gibbonActivitySlot.timeStart, '%H:%i'), ' - ', (CASE WHEN gibbonActivitySlot.gibbonSpaceID IS NOT NULL THEN gibbonSpace.name ELSE gibbonActivitySlot.locationExternal END)) SEPARATOR '<br/>') as days
                        FROM gibbonActivity
                        JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID)
                        JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID)
                        JOIN gibbonDaysOfWeek ON (gibbonDaysOfWeek.gibbonDaysOfWeekID=gibbonActivitySlot.gibbonDaysOfWeekID)
                        LEFT JOIN gibbonSpace ON (gibbonSpace.gibbonSpaceID=gibbonActivitySlot.gibbonSpaceID)
                        WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID
                        AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID
                        GROUP BY gibbonActivity.gibbonActivityID
                        ORDER BY gibbonActivity.name";
                    $resultActivities = $connection2->prepare($sqlActivities);
                    $resultActivities->execute($dataActivities);

                if ($resultActivities->rowCount() > 0) {
                    echo '<table cellspacing="0" class="mini fullWidth">';
                    while ($activity = $resultActivities->fetch()) {
                        $timespan = getActivityTimespan($connection2, $activity['gibbonActivityID'], $activity['gibbonSchoolYearTermIDList']);
                        $timeStatus = '';
                        if (!empty($timespan)) {
                            $timeStatus = (time() < $timespan['start'])? __('Upcoming') : (time() > $timespan['end']? __('Ended') : __('Current'));
                        }
                        echo '<tr>';
                        echo '<td>';
                        echo '<a class="thickbox" title="'.__('View Details').'" href="'.$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_view_full.php&gibbonActivityID='.$activity['gibbonActivityID'].'&width=1000&height=550" style="text-decoration: none; color:inherit;">'.$activity['name'].'</a>';
                        echo '</td>';
                        echo '<td width="15%">';
                        if (!empty($timeStatus)) {
                            echo '<span class="emphasis" title="'.formatDateRange('@'.$timespan['start'], '@'.$timespan['end']).'">';
                            echo (time() < $timespan['start'])? __('Upcoming') : (time() > $timespan['end']? __('Ended') : __('Current'));
                            echo '</span>';
                        } else {
                            echo $activity['status'];
                        }
                        echo '</td>';
                        echo '<td width="30%">';
                        echo (!empty($timespan) && $timeStatus != __('Ended') && $activity['status'] == 'Accepted')? $activity['days'] : '';
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }

                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
?>
