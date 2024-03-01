<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

//Gibbon system-wide includes

use Gibbon\Domain\System\ActionGateway;

include './gibbon.php';

$themeName = $session->get('gibbonThemeName') ?? 'Default';

if (!isset($_SESSION[$guid]) or !$session->exists('gibbonPersonID')) {
    die( __('Your request failed because you do not have access to this action.') );
} else {

    $searchTerm = $_REQUEST['q'] ?? '';

    // Allow for * as wildcard (as well as %)
    $searchTerm = str_replace('*', '%', $searchTerm);

    // Cancel out early for empty searches
    if (empty($searchTerm)) die('[]');

    // Check access levels
    $studentIsAccessible = isActionAccessible($guid, $connection2, '/modules/students/student_view.php');
    $highestActionStudent = getHighestGroupedAction($guid, '/modules/students/student_view.php', $connection2);

    $departmentIsAccessible = isActionAccessible($guid, $connection2, '/modules/Departments/department.php');
    $facilityIsAccessible = isActionAccessible($guid, $connection2, '/modules/Timetable/tt_space_view.php');
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
    if (!$session->has('fastFinderActions')) {
        $actions = $container->get(ActionGateway::class)->getFastFinderActions($session->get('gibbonRoleIDCurrent'));
        $session->set('fastFinderActions', $actions);
    } else {
        $actions = $session->get('fastFinderActions');
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

    // DEPARTMENT
    if ($departmentIsAccessible == true) {
        try {
            $data = array('search' => '%'.$searchTerm.'%');
            $sql = "SELECT gibbonDepartment.gibbonDepartmentID AS id,
                    gibbonDepartment.name AS name,
                    gibbonDepartment.type as type
                    FROM gibbonDepartment
                    WHERE gibbonDepartment.name LIKE :search 
                    ORDER BY name";
            $resultList = $connection2->prepare($sql);
            $resultList->execute($data);
        } catch (PDOException $e) { die($resultError); }

        if ($resultList->rowCount() > 0) $resultSet['Department'] = $resultList->fetchAll();
    }
    
    // CLASSES
    if ($classIsAccessible) {
        try {
            if ($highestActionClass == 'Lesson Planner_viewEditAllClasses' or $highestActionClass == 'Lesson Planner_viewAllEditMyClasses') {
                $data = array( 'search' => '%'.$searchTerm.'%', 'gibbonSchoolYearID2' => $session->get('gibbonSchoolYearID') );
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID AS id, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name, NULL as type
                        FROM gibbonCourseClass
                        JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                        WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID2
                        AND (gibbonCourse.name LIKE :search OR CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) LIKE :search)
                        ORDER BY name";
            } else {
                $data = array('search' => '%'.$searchTerm.'%', 'gibbonSchoolYearID3' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID') );
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID AS id, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name, NULL as type
                        FROM gibbonCourseClassPerson
                        JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                        JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                        WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID3
                        AND gibbonPersonID=:gibbonPersonID
                        AND (gibbonCourse.name LIKE :search OR CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) LIKE :search)
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

    // FACILITY
    if ($facilityIsAccessible == true) {
        try {
            $data = array('search' => '%'.$searchTerm.'%');
            $sql = "SELECT gibbonSpace.gibbonSpaceID AS id,
                    gibbonSpace.name AS name,
                    NULL as type
                    FROM gibbonSpace
                    WHERE gibbonSpace.name LIKE :search 
                    AND gibbonSpace.active='Y'
                    ORDER BY name";
            $resultList = $connection2->prepare($sql);
            $resultList->execute($data);
        } catch (PDOException $e) { die($resultError); }

        if ($resultList->rowCount() > 0) $resultSet['Facility'] = $resultList->fetchAll();
    }

    // STUDENTS
    if ($studentIsAccessible == true) {

        $data = array('search' => '%'.$searchTerm.'%', 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'today' => date('Y-m-d') );

        // Allow parents to search students in any family they belong to
        if ($highestActionStudent == 'View Student Profile_myChildren') {
            $data['gibbonPersonID'] = $session->get('gibbonPersonID');
            $sql = "SELECT gibbonPerson.gibbonPersonID AS id,
                    (CASE WHEN gibbonPerson.username LIKE :search THEN concat(surname, ', ', preferredName, ' (', gibbonFormGroup.name, ', ', gibbonPerson.username, ')')
                        WHEN gibbonPerson.studentID LIKE :search THEN concat(surname, ', ', preferredName, ' (', gibbonFormGroup.name, ', ', gibbonPerson.studentID, ')')
                        WHEN gibbonPerson.firstName LIKE :search AND firstName<>preferredName THEN concat(surname, ', ', firstName, ' \"', preferredName, '\" (', gibbonFormGroup.name, ')' )
                        ELSE concat(surname, ', ', preferredName, ' (', gibbonFormGroup.name, ')') END) AS name,
                    NULL as type 
                    FROM gibbonPerson, gibbonStudentEnrolment, gibbonFormGroup, gibbonFamilyChild, gibbonFamilyAdult
                    WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID
                    AND gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID 
                    AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID
                    AND gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID 
                    AND gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID";
        }
        // Allow individuals to only search themselves
        else if ($highestActionStudent == 'View Student Profile_my') {
            $data['gibbonPersonID'] = $session->get('gibbonPersonID');
            $sql = "SELECT gibbonPerson.gibbonPersonID AS id,
                    (CASE WHEN gibbonPerson.username LIKE :search THEN concat(surname, ', ', preferredName, ' (', gibbonFormGroup.name, ', ', gibbonPerson.username, ')')
                        WHEN gibbonPerson.studentID LIKE :search THEN concat(surname, ', ', preferredName, ' (', gibbonFormGroup.name, ', ', gibbonPerson.studentID, ')')
                        WHEN gibbonPerson.firstName LIKE :search AND firstName<>preferredName THEN concat(surname, ', ', firstName, ' \"', preferredName, '\" (', gibbonFormGroup.name, ')' )
                        ELSE concat(surname, ', ', preferredName, ' (', gibbonFormGroup.name, ')') END) AS name,
                    NULL as type
                    FROM gibbonPerson, gibbonStudentEnrolment, gibbonFormGroup
                    WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID
                    AND gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID 
                    AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
        }
        // Allow searching of all students
        else {
            $sql = "SELECT gibbonPerson.gibbonPersonID AS id,
                    (CASE WHEN gibbonPerson.username LIKE :search THEN concat(surname, ', ', preferredName, ' (', gibbonFormGroup.name, ', ', gibbonPerson.username, ')')
                        WHEN gibbonPerson.studentID LIKE :search THEN concat(surname, ', ', preferredName, ' (', gibbonFormGroup.name, ', ', gibbonPerson.studentID, ')')
                        WHEN gibbonPerson.firstName LIKE :search AND firstName<>preferredName THEN concat(surname, ', ', firstName, ' \"', preferredName, '\" (', gibbonFormGroup.name, ')' )
                        ELSE concat(surname, ', ', preferredName, ' (', gibbonFormGroup.name, ')') END) AS name,
                    NULL as type
                    FROM gibbonPerson, gibbonStudentEnrolment, gibbonFormGroup
                    WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID
                    AND gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID
                    AND status='Full'";
        }

        $sql.=" AND (dateStart IS NULL OR dateStart<=:today)
                AND (dateEnd IS NULL OR dateEnd>=:today)
                AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                AND (gibbonPerson.surname LIKE :search
                    OR gibbonPerson.firstName LIKE :search
                    OR gibbonPerson.preferredName LIKE :search
                    OR gibbonPerson.username LIKE :search
                    OR gibbonPerson.studentID LIKE :search
                    OR gibbonFormGroup.name LIKE :search)
                ORDER BY name";

        try {
            $resultList = $connection2->prepare($sql);
            $resultList->execute($data);
        } catch (PDOException $e) { die($resultError); }

        if ($resultList->rowCount() > 0) $resultSet['Student'] = $resultList->fetchAll();
    }

    $list = '';
    foreach ($resultSet as $type => $results) {
        foreach ($results as $token) {
            if ($token['type'] == 'Core') {
                $list .= '{"id": "'.substr($type, 0, 3).'-'.$token['id'].'", "name": "'.htmlPrep(__($type)).' - '.htmlPrep(__($token['name'])).'"},';
            }
            else if ($token['type'] == 'Additional') {
                $list .= '{"id": "'.substr($type, 0, 3).'-'.$token['id'].'", "name": "'.htmlPrep(__($type)).' - '.htmlPrep(__($token['name'], $token['module'])).'"},';
            }
            else {
                $list .= '{"id": "'.substr($type, 0, 3).'-'.$token['id'].'", "name": "'.htmlPrep(__($type)).' - '.htmlPrep($token['name']).'"},';
            }
        }
    }

    // Output the json
    echo '['.substr($list, 0, -1).']';
}
