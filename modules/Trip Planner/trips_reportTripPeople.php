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

//Module includes
include './modules/Trip Planner/moduleFunctions.php';

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manage.php')) {
    //Acess denied
    echo "<div class='error'>";
    echo __m('You do not have access to this action.');
    echo '</div>';
} else {    
    $highestAction = getHighestGroupedAction($guid, '/modules/Trip Planner/trips_manage.php', $connection2);
    if ($highestAction != false) {
        if (isset($_GET["tripPlannerRequestID"])) {
            $tripPlannerRequestID = $_GET["tripPlannerRequestID"];

            $gibbonPersonID = $_SESSION[$guid]["gibbonPersonID"];
            $departments = getHOD($connection2, $gibbonPersonID);
            $departments2 = getDepartments($connection2, getOwner($connection2, $tripPlannerRequestID));
            $isHOD = false;

            foreach ($departments as $department) {
                if (in_array($department["gibbonDepartmentID"], $departments2)) {
                    $isHOD = true;
                    break;
                }
            }

            if (isApprover($connection2, $gibbonPersonID) || isOwner($connection2, $tripPlannerRequestID, $gibbonPersonID) || isInvolved($connection2, $tripPlannerRequestID, $gibbonPersonID) || $isHOD || $highestAction == "Manage Trips_full") {
                ?>
                <table class='noIntBorder' cellspacing='0' style='width:100%;'>
                    <tr>
                        <?php
                            echo '<h2>';
                                echo __m('Students in Trip');
                            echo '</h2>';
                            echo "<div class='linkTop'>";
                                echo "<a href='javascript:window.print()'>".__m('Print')."<img style='margin-left: 5px' title='".__m('Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
                            echo '</div>';
                            $students = array();
                            $peopleInTrip = getPeopleInTrip($connection2, array($tripPlannerRequestID), "Student");
                            while ($people = $peopleInTrip->fetch()) {
                                $students[] = $people['gibbonPersonID'];
                            }
                            $numPerRow = 5;
                            $studentCount = count($students);
                            $studentCount += $numPerRow - ($studentCount % $numPerRow);
                            for ($i = 0; $i < $studentCount; $i++) {
                                if ($i % $numPerRow == 0) {
                                    print "</tr>";
                                    print "<tr>";
                                } 
                                if (isset($students[$i])) {
                                    getPersonBlock($guid, $connection2, $students[$i], "Student", $numPerRow);
                                } else {
                                    print "<td>";
                                    print "</td>";
                                }
                            } 
                        ?>
                    </tr>
                </table>
                <?php
            } else {
                print "<div class='error'>";
                    print "You do not have access to this action.";
                print "</div>";
            }
        } else {    
            print "<div class='error'>";
                print "No request selected.";
            print "</div>";
        }
    }
}
