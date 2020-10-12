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
include "./modules/Trip Planner/moduleFunctions.php";

use Gibbon\Forms\Form;

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manage.php')) {
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $highestAction = getHighestGroupedAction($guid, '/modules/Trip Planner/trips_manage.php', $connection2);
    if ($highestAction != false) {
        if (isset($_GET["tripPlannerRequestID"])) {
            $tripPlannerRequestID = $_GET["tripPlannerRequestID"];
            if(isset($_GET["gibbonCourseID"])) {
                $gibbonCourseID = $_GET["gibbonCourseID"];
                if(isset($_GET["gibbonPersonID"])) {
                    $studentID = $_GET["gibbonPersonID"];
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
                        print "<h3>";
                            print "Student Class Information";
                        print "</h3>";

                        

                    } else {
                        print "<div class='error'>";
                            print "You do not have access to this action.";
                        print "</div>";
                    }
                }
            }
        } else {    
            print "<div class='error'>";
                print "No request selected.";
            print "</div>";
        }
    }
}   
?>
