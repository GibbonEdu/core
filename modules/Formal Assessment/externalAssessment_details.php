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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_details.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Get action with highest precendence
        $gibbonPersonID = $_GET['gibbonPersonID'];
        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }
        $allStudents = '';
        if (isset($_GET['allStudents'])) {
            $allStudents = $_GET['allStudents'];
        }

        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/externalAssessment.php'>".__($guid, 'View All Assessments')."</a> > </div><div class='trailEnd'>".__($guid, 'Student Details').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.'));
        }

        try {
            if ($allStudents != 'on') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolmentID, surname, preferredName, title, image_240, gibbonYearGroup.name AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
            } else {
                $data = array('gibbonPersonID' => $gibbonPersonID);
                $sql = 'SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, title, image_240, NULL AS yearGroup, NULL AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
            }
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
            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Formal Assessment/externalAssessment.php&search=$search&allStudents=$allStudents'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            $row = $result->fetch();

            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
            echo formatName('', $row['preferredName'], $row['surname'], 'Student');
            echo '</td>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Year Group').'</span><br/>';
            if ($row['yearGroup'] != '') {
                echo __($guid, $row['yearGroup']);
            }
            echo '</td>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Roll Group').'</span><br/>';
            echo $row['rollGroup'];
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            if ($highestAction == 'External Assessment Data_manage') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/externalAssessment_manage_details_add.php&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                echo '</div>';
            }

            //Print assessments
            $manage = false;
            if ($highestAction == 'External Assessment Data_manage') {
                $manage = true;
            }
            externalAssessmentDetails($guid, $gibbonPersonID, $connection2, '', $manage, $search, $allStudents);

            //Set sidebar
            $_SESSION[$guid]['sidebarExtra'] = getUserPhoto($guid, $row['image_240'], 240);
        }
    }
}
