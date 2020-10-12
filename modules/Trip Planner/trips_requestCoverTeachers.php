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
                if(isset($_GET["date"]) && isset($_GET["timeStart"]) && isset($_GET["timeEnd"])) {
                    $date = $_GET["date"];
                    $timeStart = $_GET["timeStart"];
                    $timeEnd = $_GET["timeEnd"];
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
                            print "Possible Cover Teachers";
                        print "</h3>";

                        try {
                            $sql = "SELECT gibbonPersonID, preferredName, surname, title, gibbonRole.category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary = gibbonRole.gibbonRoleID) WHERE gibbonRole.category='Staff' AND gibbonPerson.status='Full' ORDER BY gibbonPerson.surname, gibbonPerson.preferredName ASC";
                            $result = $connection2->prepare($sql);
                            $result->execute();

                            $teachers = $result->fetchAll();
                        } catch(PDOException $e) {
                        }

                        //TODO: don't use these functions
                        $overlap = getPlannerOverlaps($connection2, null, array($date), array(), array($timeStart), array($timeEnd), array_column($teachers, "gibbonPersonID"));

                        $keys = array();

                        while($row = $overlap->fetch()) {
                            $classTeachers = getTeachersOfClass($connection2, $row["gibbonCourseClassID"]);
                            while ($teacher = $classTeachers->fetch()) {
                                if(($key = array_search($teacher['gibbonPersonID'], array_column($teachers, "gibbonPersonID"))) !== false) {
                                    $keys[] = $key;
                                }
                            }
                        }

                        foreach ($keys as $key) {
                            unset($teachers[$key]);
                        }

                        print "<ul>";
                        foreach ($teachers as $teacher) {
                            print "<li>";
                                print formatName($teacher['title'], $teacher["preferredName"], $teacher["surname"], $teacher["category"], true, true);
                            print "</li>";
                        }
                        print "</ul>";
                    } else {
                        print "<div class='error'>";
                            print "You do not have access to this action.";
                        print "</div>";
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
