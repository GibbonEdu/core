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

use Gibbon\Forms\PrefabFormFactory;

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/outcomes_delete.php') == false) {
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
        if ($highestAction != 'Manage Outcomes_viewEditAll' and $highestAction != 'Manage Outcomes_viewAllEditLearningArea') {
            echo "<div class='error'>";
            echo __($guid, 'You do not have access to this action.');
            echo '</div>';
        } else {
            //Proceed!
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/outcomes.php'>Manage Outcomes</a> > </div><div class='trailEnd'>".__($guid, 'Delete Outcome').'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $filter2 = '';
            if (isset($_GET['filter2'])) {
                $filter2 = $_GET['filter2'];
            }

            //Check if school year specified
            $gibbonOutcomeID = $_GET['gibbonOutcomeID'];
            if ($gibbonOutcomeID == '') {
                echo "<div class='error'>";
                echo __($guid, 'You have not specified one or more required parameters.');
                echo '</div>';
            } else {
                try {
                    if ($highestAction == 'Manage Outcomes_viewEditAll') {
                        $data = array('gibbonOutcomeID' => $gibbonOutcomeID);
                        $sql = 'SELECT * FROM gibbonOutcome WHERE gibbonOutcomeID=:gibbonOutcomeID';
                    } elseif ($highestAction == 'Manage Outcomes_viewAllEditLearningArea') {
                        $data = array('gibbonOutcomeID' => $gibbonOutcomeID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT * FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) AND NOT gibbonOutcome.gibbonDepartmentID IS NULL WHERE gibbonOutcomeID=:gibbonOutcomeID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND scope='Learning Area'";
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
                    $form = PrefabFormFactory::createDeleteForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/outcomes_deleteProcess.php?gibbonOutcomeID=$gibbonOutcomeID&filter2=".$filter2);
                    echo $form->getOutput();
                }
            }
        }
    }
}
?>
