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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activityType_rollGroup.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Activity Type by Roll Group').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Roll Group');
    echo '</h2>';

    $gibbonRollGroupID = isset($_GET['gibbonRollGroupID'])? $_GET['gibbonRollGroupID'] : '';
    $status = isset($_GET['status'])? $_GET['status'] : '';

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_activityType_rollGroup.php");

    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonRollGroupID)->isRequired();

    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')->fromArray(array('Accepted' => __('Accepted'), 'Registered' => __('Registered')))->selected($status)->isRequired();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if ($gibbonRollGroupID != '') {
        $output = '';
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $data = array('gibbonRollGroupID' => $gibbonRollGroupID, 'today' => date('Y-m-d'));
            $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, name FROM gibbonPerson 
                    JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) 
                    JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) 
                    WHERE status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {

            $statusQuery = " AND NOT gibbonActivityStudent.status='Not Accepted'";
            if ($status == 'Accepted') {
                $statusQuery = " AND gibbonActivityStudent.status='Accepted'";
            }

            $data = array('gibbonRollGroupID' => $gibbonRollGroupID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonStudentEnrolment.gibbonPersonID, gibbonActivity.type, COUNT(*) as typeCount, GROUP_CONCAT(gibbonActivity.name SEPARATOR ' | ') as activityList
                    FROM gibbonStudentEnrolment
                    LEFT JOIN gibbonActivity ON (gibbonActivity.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID )
                    LEFT JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) 
                    WHERE gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID 
                    AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID 
                    $statusQuery 
                    GROUP BY gibbonStudentEnrolment.gibbonPersonID, gibbonActivity.type
                    ORDER BY type, name";
            $resultActivities = $pdo->executeQuery($data, $sql);
            $activities = ($resultActivities->rowCount() > 0)? $resultActivities->fetchAll(\PDO::FETCH_GROUP) : array(); 
            
            $activityTypes = array();
            $activities = array_reduce(array_keys($activities), function ($group, $id) use (&$activities, &$activityTypes) {
                $item = $activities[$id];
                $group[$id]['count'] = array_combine(array_column($item, 'type'), array_column($item, 'typeCount'));
                $group[$id]['activityList'] = implode(' | ', array_filter(array_column($item, 'activityList')));

                $activityTypes = array_merge($activityTypes, array_column($item, 'type'));

                return $group;
            }, array());
            $activityTypes = array_filter(array_unique($activityTypes));

            echo '<table cellspacing="0" class="colorOddEven fullWidth">';
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Roll Group');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Student');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'No Type');
            echo '</th>';
            foreach ($activityTypes as $type) {
                echo '<th>';
                echo $type;
                echo '</th>';
            }
            echo '<th>';
            echo __($guid, 'Total');
            echo '</th>';
            echo '</tr>';

            $count = 0;

            while ($row = $result->fetch()) {
                $gibbonPersonID = $row['gibbonPersonID'];
                //Set status for sql statements
                $status = " AND NOT status='Not Accepted'";
                if ($_GET['status'] == 'Accepted') {
                    $status = " AND status='Accepted'";
                }

                echo "<tr>";
                echo '<td>';
                echo $row['name'];
                echo '</td>';

                echo '<td>';
                // List activities seleted in title of student name
                echo '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonID.'&search=&search=&allStudents=&subpage=Activities">';
                $title = isset($activities[$gibbonPersonID]['activityList'])? $activities[$gibbonPersonID]['activityList'] : '';
                echo "<span title='$title'>".formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</span>';
                echo '</td>';
                
                // No type
                echo '<td>';
                echo isset($activities[$gibbonPersonID]['count'][''])? $activities[$gibbonPersonID]['count'][''] : 0;
                echo '</td>';

                // Activity counts
                foreach ($activityTypes as $type) {
                    echo '<td>';
                    echo isset($activities[$gibbonPersonID]['count'][$type])? $activities[$gibbonPersonID]['count'][$type] : 0;
                    echo '</td>';
                }
                
                // Total
                echo '<td>';
				echo isset($activities[$gibbonPersonID]['count'])? array_sum($activities[$gibbonPersonID]['count']) : 0;
                echo '</td>';

                echo '</tr>';
            }

            echo '</table>';
        }
    }
}
?>
