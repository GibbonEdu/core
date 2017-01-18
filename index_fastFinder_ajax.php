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

//Gibbon system-wide includes
include './functions.php';
include './config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

$themeName = 'Default';
if (isset($_SESSION[$guid]['gibbonThemeName'])) {
    $themeName = $_SESSION[$guid]['gibbonThemeName'];
}

if (isset($_SESSION[$guid]) == false or isset($_SESSION[$guid]['gibbonPersonID']) == false) {
    die( __($guid, 'Your request failed because you do not have access to this action.') );
} else {

    $searchTerm = (isset($_REQUEST['q']))? $_REQUEST['q'] : '';

    // Allow for * as wildcard (as well as %)
    $searchTerm = str_replace('*', '%', $searchTerm);

    // Cancel out early for empty searches
    if (empty($searchTerm)) die('[]');

    // Check access levels
    $studentIsAccessible = isActionAccessible($guid, $connection2, '/modules/students/student_view.php');
    $staffIsAccessible = isActionAccessible($guid, $connection2, '/modules/Staff/staff_view.php');
    $classIsAccessible = false;
    $alarmIsAccessible = isActionAccessible($guid, $connection2, '/modules/System Admin/alarm.php');
    $highestActionClass = getHighestGroupedAction($guid, '/modules/Planner/planner.php', $connection2);
    if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php') and $highestActionClass != 'Lesson Planner_viewMyChildrensClasses') {
        $classIsAccessible = true;
    }

    $resultSet = array();
    $resultError = '[{"id":"","name":"Database Error"}]';

    // ACTIONS
    // Grab the cached set of translated actions from the session
    $actions = $gibbon->session->get('fastFinderActions');

    if (empty($actions)) {
        $actions = $gibbon->session->cacheFastFinderActions($gibbon->session->get('gibbonRoleIDCurrent'));
        $actions[] = array('');
    }
    
    if (!empty($actions) && is_array($actions)) {
        foreach ($actions as $action) {
            // Add actions that match the search query to the result set
            if (stristr($action['name'], $searchTerm) !== false) {
                $resultSet['Action'][] = $action;
            }

            // Handle the special Lockdown case
            if ($alarmIsAccessible) {
                if (stristr('Lockdown', $searchTerm) !== false && $action['name'] == 'Sound Alarm') {
                    $action['name'] = 'Lockdown';
                    $resultSet['Action'][] = $action;
                }
            }
        }
    }

    // CLASSES
    if ($classIsAccessible) {
        try {
            if ($highestActionClass == 'Lesson Planner_viewEditAllClasses' or $highestActionClass == 'Lesson Planner_viewAllEditMyClasses') {
                $data = array( 'search' => '%'.$searchTerm.'%', 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'] );
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID AS id, concat(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name, NULL as type
                        FROM gibbonCourseClass
                        JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                        WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID2
                        AND (gibbonCourse.name LIKE :search
                            OR gibbonCourse.nameShort LIKE :search
                            OR gibbonCourseClass.nameShort LIKE :search)
                        ORDER BY name";
            } else {
                $data = array('search' => '%'.$searchTerm.'%', 'gibbonSchoolYearID3' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'] );
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID AS id, concat(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name, NULL as type
                        FROM gibbonCourseClassPerson
                        JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                        JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                        WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID3
                        AND gibbonPersonID=:gibbonPersonID
                        AND (gibbonCourse.name LIKE :search
                            OR gibbonCourse.nameShort LIKE :search
                            OR gibbonCourseClass.nameShort LIKE :search)
                        ORDER BY name";
            }
            $resultList = $connection2->prepare($sql);
            $resultList->execute($data);
        } catch (PDOException $e) { die($resultError); }

        if ($resultList->rowCount() > 0) $resultSet['Class'] = $resultList->fetchAll();
    }

    // STAFF
    if ($staffIsAccessible == true) {
        try {
            $data = array('search' => '%'.$searchTerm.'%', 'today' => date('Y-m-d') );
            $sql = "SELECT gibbonPerson.gibbonPersonID AS id,
                    (CASE WHEN gibbonPerson.username LIKE :search
                        THEN concat(surname, ', ', preferredName, ' (', gibbonPerson.username, ')')
                        ELSE concat(surname, ', ', preferredName) END) AS name,
                    NULL as type
                    FROM gibbonPerson
                    JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    WHERE status='Full'
                    AND (dateStart IS NULL OR dateStart<=:today)
                    AND (dateEnd IS NULL  OR dateEnd>=:today)
                    AND (gibbonPerson.surname LIKE :search
                        OR gibbonPerson.preferredName LIKE :search
                        OR gibbonPerson.username LIKE :search)
                    ORDER BY name";
            $resultList = $connection2->prepare($sql);
            $resultList->execute($data);
        } catch (PDOException $e) { die($resultError); }

        if ($resultList->rowCount() > 0) $resultSet['Staff'] = $resultList->fetchAll();
    }

    // STUDENTS
    if ($studentIsAccessible == true) {
        try {
            $data = array('search' => '%'.$searchTerm.'%', 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'today' => date('Y-m-d') );
            $sql = "SELECT gibbonPerson.gibbonPersonID AS id,
                    (CASE WHEN gibbonPerson.username LIKE :search THEN concat(surname, ', ', preferredName, ' (', gibbonRollGroup.name, ', ', gibbonPerson.username, ')')
                        WHEN gibbonPerson.studentID LIKE :search THEN concat(surname, ', ', preferredName, ' (', gibbonRollGroup.name, ', ', gibbonPerson.studentID, ')')
                        WHEN gibbonPerson.firstName LIKE :search AND firstName<>preferredName THEN concat(surname, ', ', firstName, ' \"', preferredName, '\" (', gibbonRollGroup.name, ')' )
                        ELSE concat(surname, ', ', preferredName, ' (', gibbonRollGroup.name, ')') END) AS name,
                    NULL as type
                    FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup
                    WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID
                    AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID
                    AND status='Full'
                    AND (dateStart IS NULL OR dateStart<=:today)
                    AND (dateEnd IS NULL OR dateEnd>=:today)
                    AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND (gibbonPerson.surname LIKE :search
                        OR gibbonPerson.firstName LIKE :search
                        OR gibbonPerson.preferredName LIKE :search
                        OR gibbonPerson.username LIKE :search
                        OR gibbonPerson.studentID LIKE :search
                        OR gibbonRollGroup.name LIKE :search)
                    ORDER BY name";
            $resultList = $connection2->prepare($sql);
            $resultList->execute($data);
        } catch (PDOException $e) { die($resultError); }

        if ($resultList->rowCount() > 0) $resultSet['Student'] = $resultList->fetchAll();
    }

    $list = '';
    foreach ($resultSet as $type => $results) {
        foreach ($results as $token) {
            if ($token['type'] == 'Core') {
                $list .= '{"id": "'.substr($type, 0, 3).'-'.$token['id'].'", "name": "'.htmlPrep(__($guid, $type)).' - '.htmlPrep(__($guid, $token['name'])).'"},';
            }
            else if ($token['type'] == 'Additional') {
                $list .= '{"id": "'.substr($type, 0, 3).'-'.$token['id'].'", "name": "'.htmlPrep(__($guid, $type)).' - '.htmlPrep(__($guid, $token['name'], $token['module'])).'"},';
            }
            else {
                $list .= '{"id": "'.substr($type, 0, 3).'-'.$token['id'].'", "name": "'.htmlPrep(__($guid, $type)).' - '.htmlPrep($token['name']).'"},';
            }
        }
    }

    // Output the json
    echo '['.substr($list, 0, -1).']';
}
