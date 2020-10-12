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
include '../../gibbon.php';

include "./moduleFunctions.php";

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/";

if (isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manage.php')) {
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
                if(isset($_GET["report"])) {
                    $report = $_GET["report"];
                    if($report == "medical" || $report == "emergency") {
                        $students = array();
                        $studentsInTrip = getPeopleInTrip($connection2, array($tripPlannerRequestID), "Student");
                        while ($student = $studentsInTrip->fetch()) {
                            $students[] = $student['gibbonPersonID'];
                        }
                        $_SESSION[$guid]['report_student_' . $report . 'Summary.php_choices'] = $students;
                        $URL = $_SESSION[$guid]['absoluteURL']."/report.php?q=/modules/Students/report_student_" . $report . 'Summary.php&format=print';
                        header("Location: {$URL}");
                    } else {
                        $URL .= "trips_manage.php&return=error1";
                        header("Location: {$URL}");
                    }
                } else {
                    $URL .= "trips_manage.php&return=error1";
                    header("Location: {$URL}");
                }
            } else {
                $URL .= "trips_manage.php&return=error0";
                header("Location: {$URL}");
            }
        } else {
            $URL .= "trips_manage.php&return=error1";
            header("Location: {$URL}");
        }
    } else {
        $URL .= "trips_manage.php&return=error0";
        header("Location: {$URL}");
        }
} else {
    $URL .= "trips_manage.php&return=error0";
    header("Location: {$URL}");
}
