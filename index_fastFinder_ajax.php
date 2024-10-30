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

use Gibbon\Http\Url;
use Gibbon\Domain\System\ActionGateway;

include './gibbon.php';

if (!isset($_SESSION[$guid]) or !$session->exists('gibbonPersonID')) {
    die( __('Your request failed because you do not have access to this action.') );
} else {

    $searchTerm = $_REQUEST['search'] ?? '';
    $searchType = $_REQUEST['searchType'] ?? '';

    // Allow for * as wildcard (as well as %)
    $searchTermSafe = preg_replace('/([#-.]|[[-^]|[?|{}]|[\/])/', '\\\\$1', $searchTerm);
    $searchTerm = str_replace('*', '%', $searchTerm);

    // Cancel out early for empty searches
    if (empty($searchTerm) or strlen($searchTerm) < 2) die('<span class="block px-4 py-2 text-sm text-gray-800">'.__('Start typing a name...').'</span>');

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
    $resultCount = 0;
    $resultError = '<span class="block px-4 py-2 text-sm text-gray-800">'.__('Your request failed due to a database error.').'</span>';

    // ACTIONS
    // Grab the cached set of translated actions from the session
    if (!$session->has('fastFinderActions')) {
        $actions = $container->get(ActionGateway::class)->getFastFinderActions($session->get('gibbonRoleIDCurrent'));
        $session->set('fastFinderActions', $actions);
    } else {
        $actions = $session->get('fastFinderActions');
    }
    
    if (($searchType == 'all' || $searchType == 'actions') && !empty($actions) && is_array($actions)) {
        foreach ($actions as $action) {
            // Add actions that match the search query to the result set
            if (stristr($action['name'], $searchTerm) !== false) {
                $resultSet['Action'][] = $action;
                $resultCount++;
            }

            // Handle the special Lockdown case
            if ($alarmIsAccessible) {
                if (stristr('Lockdown', $searchTerm) !== false && $action['name'] == 'Sound Alarm') {
                    $action['name'] = 'Lockdown';
                    $resultSet['Action'][] = $action;
                    $resultCount++;
                }
            }
        }
    }

    // DEPARTMENT
    if (($searchType == 'all' || $searchType == 'departments') && $departmentIsAccessible == true) {
        try {
            $data = array('search' => '%'.$searchTerm.'%');
            $sql = "SELECT gibbonDepartment.gibbonDepartmentID AS id,
                    gibbonDepartment.name AS name,
                    gibbonDepartment.type as type
                    FROM gibbonDepartment
                    WHERE gibbonDepartment.name LIKE :search 
                    ORDER BY name";
            $resultList = $pdo->select($sql, $data);
        } catch (PDOException $e) { die($resultError); }

        if ($resultList->rowCount() > 0) $resultSet['Department'] = $resultList->fetchAll();
        $resultCount += $resultList->rowCount();
    }
    
    // CLASSES
    if (($searchType == 'all' || $searchType == 'classes') && $classIsAccessible) {
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
            $resultList = $pdo->select($sql, $data);
        } catch (PDOException $e) { die($resultError); }

        if ($resultList->rowCount() > 0) $resultSet['Class'] = $resultList->fetchAll();
        $resultCount += $resultList->rowCount();
    }

    // STAFF
    if (($searchType == 'all' || $searchType == 'staff') && $staffIsAccessible == true) {
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
            $resultList = $pdo->select($sql, $data);
        } catch (PDOException $e) { die($resultError); }

        if ($resultList->rowCount() > 0) $resultSet['Staff'] = $resultList->fetchAll();
        $resultCount += $resultList->rowCount();
    }

    // FACILITY
    if (($searchType == 'all' || $searchType == 'facilities') && $facilityIsAccessible == true) {
        try {
            $data = array('search' => '%'.$searchTerm.'%');
            $sql = "SELECT gibbonSpace.gibbonSpaceID AS id,
                    gibbonSpace.name AS name,
                    NULL as type
                    FROM gibbonSpace
                    WHERE gibbonSpace.name LIKE :search 
                    AND gibbonSpace.active='Y'
                    ORDER BY name";
            $resultList = $pdo->select($sql, $data);
        } catch (PDOException $e) { die($resultError); }

        if ($resultList->rowCount() > 0) $resultSet['Facility'] = $resultList->fetchAll();
        $resultCount += $resultList->rowCount();
    }

    // STUDENTS
    if (($searchType == 'all' || $searchType == 'students') && $studentIsAccessible == true) {

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
            $resultList = $pdo->select($sql, $data);
        } catch (PDOException $e) { die($resultError); }

        if ($resultList->rowCount() > 0) $resultSet['Student'] = $resultList->fetchAll();
        $resultCount += $resultList->rowCount();
    }

    $output = '';
    $outputCount = 0;
    foreach ($resultSet as $type => $results) {
        foreach ($results as $token) {

            if ($outputCount > 30) {
                $output .= '<span class="block px-4 py-2 text-sm italic text-gray-800">'.__('+{n} More Results', ['n' => $resultCount - $outputCount]).'</span>';
                break 2;
            }

            if ($type == 'Student') {
                $URL = Url::fromModuleRoute('Students', 'student_view_details')->withQueryParam('gibbonPersonID', $token['id']);
            } elseif ($type == 'Action') {
                $URL = Url::fromModuleRoute(strstr($token['id'], '/', true), trim(strstr($token['id'], '/'), '/ '));
            } elseif ($type == 'Staff') {
                $URL = Url::fromModuleRoute('Staff', 'staff_view_details')->withQueryParam('gibbonPersonID', $token['id']);
            } elseif ($type == 'Class') {
                $URL = Url::fromModuleRoute('Departments', 'department_course_class')->withQueryParam('gibbonCourseClassID', $token['id']);
            } elseif ($type == 'Facility') {
                $URL = Url::fromModuleRoute('Timetable', 'tt_space_view')->withQueryParam('gibbonSpaceID', $token['id']);
            } elseif ($type == 'Department') {
                $URL = Url::fromModuleRoute('Departments', 'department')->withQueryParam('gibbonDepartmentID', $token['id']);
            }

            if ($token['type'] == 'Core') {
                $name = htmlPrep(__($token['name']));
            } else if ($token['type'] == 'Additional') {
                $name = htmlPrep(__($token['name'], $token['module']));
            } else {
                $name = htmlPrep($token['name']);
            }


            $name = preg_replace('/'.$searchTermSafe.'/i', '<strong>$0</strong>', $name);

            $output .= '<a @click="finderOpen = false" hx-boost="true" hx-target="#content-wrap" hx-select="#content-wrap" hx-swap="outerHTML show:no-scroll swap:0s" href="'.($URL ?? '').'" class="block cursor-pointer px-4 py-2 text-sm text-gray-800 hover:bg-indigo-500 hover:text-white" role="menuitem" tabindex="-1" id="menu-item-0">'.htmlPrep(__($type)).' - '.$name.'</a>';
            $outputCount++;
            
        }
    }

    if ($resultCount == 0 || empty($output)) {
        die('<span class="block px-4 py-2 text-sm text-gray-800">'.($searchType == 'all' ? __('No results') : __('No results in {type}', 
        ['type' => __(ucfirst($searchType)) ] ) ).'</span>');
    }

    echo $output;
}
