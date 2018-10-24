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
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, '/modules/Individual Needs/in_edit.php', $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'];

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.name AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd, image_240 FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $student = $result->fetch();

            $search = isset($_GET['search'])? $_GET['search'] : null;
            $source = isset($_GET['source'])? $_GET['source'] : null;
            $gibbonINDescriptorID = isset($_GET['gibbonINDescriptorID'])? $_GET['gibbonINDescriptorID'] : null;
            $gibbonAlertLevelID = isset($_GET['gibbonAlertLevelID'])? $_GET['gibbonAlertLevelID'] : null;
            $gibbonRollGroupID = isset($_GET['gibbonRollGroupID'])? $_GET['gibbonRollGroupID'] : null;
            $gibbonYearGroupID = isset($_GET['gibbonYearGroupID'])? $_GET['gibbonYearGroupID'] : null;

            // Grab educational assistant data
            $data = array('gibbonPersonIDStudent' => $gibbonPersonID);
            $sql = "SELECT gibbonPersonIDAssistant, preferredName, surname, comment FROM gibbonINAssistant JOIN gibbonPerson ON (gibbonINAssistant.gibbonPersonIDAssistant=gibbonPerson.gibbonPersonID) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
            $result = $pdo->executeQuery($data, $sql);
            $educationalAssistants = ($result->rowCount() > 0)? $result->fetchAll() : array();

            // Grab IEP data
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID";
            $result = $pdo->executeQuery($data, $sql);
            $IEP = ($result->rowCount() > 0)? $result->fetch() : array();
            
            // DISPLAY STUDENT DATA
            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%;margin-top:40px;'>";
            echo '<tr>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
            echo formatName('', $student['preferredName'], $student['surname'], 'Student');
            echo '</td>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Year Group').'</span><br/>';
            echo '<i>'.__($guid, $student['yearGroup']).'</i>';
            echo '</td>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Roll Group').'</span><br/>';
            echo '<i>'.$student['rollGroup'].'</i>';
            echo '</td>';
            echo '</tr>';
            echo '</table><br/>';

            // LIST EDUCATIONAL ASSISTANTS
            if (!empty($educationalAssistants)) {
                echo '<h3>'.__('Educational Assistants').'</h3>';

                echo '<ul>';
                foreach ($educationalAssistants as $ea) {
                    echo '<li>'.formatName('', $ea['preferredName'], $ea['surname'], 'Staff', true, true).'</li>';
                }
                echo '</ul><br/>';
            }

            // DISPLAY  IEP
            if (!empty($IEP)) {
                echo '<h3>'.__('Individual Education Plan').'</h3>';

                // ARCHIVED IEP
                if (!empty($IEP['targets'])) {
                    echo '<strong style="font-size: 110%;">'.__('Targets').'</strong><br/>';
                    echo '<p>'.$IEP['targets'].'</p><br/>';
                }

                if (!empty($IEP['strategies'])) {
                    echo '<strong style="font-size: 110%;">'.__('Teaching Strategies').'</strong><br/>';
                    echo '<p>'.$IEP['strategies'].'</p><br/>';
                }

                if (!empty($IEP['notes'])) {
                    echo '<strong style="font-size: 110%;">'.__('Notes & Review').'</strong><br/>';
                    echo '<p>'.$IEP['notes'].'</p><br/>';
                }
            }
        }
    }
}
