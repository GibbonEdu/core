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

namespace Gibbon\Forms;

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\FormFactory;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Services\Format;

/**
 * DatabaseFormFactory
 *
 * Handles Form object creation that are pre-loaded from SQL queries
 *
 * @version v14
 * @since   v14
 */
class DatabaseFormFactory extends FormFactory
{
    /**
     * Database connection.
     *
     * @var Connection
     */
    protected $pdo;

    /**
     * Cached query results.
     *
     * @var array
     */
    protected $cachedQueries = array();

    /**
     * Is the Collator class available through the intl library?
     *
     * @var bool
     */
    protected static $intlCollatorAvailable = false;

    /**
     * Create a factory with access to the provided a database connection.
     * @param  Connection  $pdo
     */
    public function __construct(Connection $pdo)
    {
        $this->pdo = $pdo;

        static::$intlCollatorAvailable = class_exists('Collator');
    }

    /**
     * Create and return an instance of DatabaseFormFactory.
     * @return  object DatabaseFormFactory
     */
    public static function create(Connection $pdo = null)
    {
        return new DatabaseFormFactory($pdo);
    }

    public function createSelectSchoolYear($name, $status = 'All', $orderBy = 'ASC')
    {
        $orderBy = ($orderBy == 'ASC' || $orderBy == 'DESC') ? $orderBy : 'ASC';
        switch ($status) {
            case 'Active':
                $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE status='Current' OR status='Upcoming' ORDER BY sequenceNumber $orderBy"; break;

            case 'Upcoming':
                $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE status='Upcoming' ORDER BY sequenceNumber $orderBy"; break;

            case 'Past':
                $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE status='Past' ORDER BY sequenceNumber $orderBy"; break;

            case 'Recent':
                $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE status='Current' OR status='Past' ORDER BY sequenceNumber $orderBy"; break;

            case 'All':
            case 'Any':
            default:
                $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear ORDER BY sequenceNumber $orderBy"; break;
        }
        $results = $this->pdo->select($sql);

        return $this->createSelect($name)->fromResults($results)->placeholder();
    }

    /*
    The optional $all function adds an option to the top of the select, using * to allow selection of all year groups
    */
    public function createSelectYearGroup($name, $all = false)
    {
        $sql = "SELECT gibbonYearGroupID as value, name FROM gibbonYearGroup ORDER BY sequenceNumber";
        $results = $this->pdo->select($sql);

        if (!$all)
            return $this->createSelect($name)->fromResults($results)->placeholder();
        else
            return $this->createSelect($name)->fromArray(array("*" => "All"))->fromResults($results)->placeholder();
    }

    /*
    The optional $all function adds an option to the top of the select, using * to allow selection of all form groups
    */
    public function createSelectFormGroup($name, $gibbonSchoolYearID, $all = false)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonFormGroupID as value, name FROM gibbonFormGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY LENGTH(name), name";
        $results = $this->pdo->select($sql, $data);

        if (!$all)
            return $this->createSelect($name)->fromResults($results)->placeholder();
        else
            return $this->createSelect($name)->fromArray(array("*" => "All"))->fromResults($results)->placeholder();
    }

    public function createSelectHouse($name)
    {
        $sql = "SELECT gibbonHouseID as value, name FROM gibbonHouse;";
        $results = $this->pdo->select($sql)->fetchKeyPair();
        $results = $this->localeFriendlySort($results);

        return $this->createSelect($name)->fromArray($results)->placeholder();
    }

    public function createSelectCourseByYearGroup($name, $gibbonSchoolYearID, $gibbonYearGroupIDList = '')
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList];
        $sql = "SELECT gibbonCourse.gibbonCourseID as value, CONCAT(gibbonCourse.nameShort, ' - ', gibbonCourse.name) as name
                FROM gibbonCourse
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList))
                WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList)
                GROUP BY gibbonCourse.gibbonCourseID
                ORDER BY gibbonCourse.nameShort";
        $results = $this->pdo->select($sql, $data);

        return $this->createSelect($name)->fromResults($results)->placeholder();
    }

    public function createSelectClass($name, $gibbonSchoolYearID, $gibbonPersonID = null, $params = array())
    {
        $params = array_replace(['allClasses' => true], $params);

        $classes = array();

        if (!empty($gibbonPersonID) && !empty($params['courseFilter'])) {
            $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'courseFilter' => '%'.$params['courseFilter'].'%'];
            $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS class
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID )
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonCourse.name LIKE :courseFilter
                AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID
                AND NOT gibbonCourseClassPerson.role LIKE '% - Left%'
                ORDER BY class";

            $result = $this->pdo->select($sql, $data);
            if ($result->rowCount() > 0) {
                $classes[$params['courseFilter']] = $result->fetchAll(\PDO::FETCH_KEY_PAIR);
            }
        }

        if (!empty($gibbonPersonID) && !empty($params['departments'])) {
            $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'gibbonDepartmentIDList' => $params['departments']];
            $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS class
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID )
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                AND FIND_IN_SET(gibbonCourse.gibbonDepartmentID, :gibbonDepartmentIDList)
                AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID
                AND NOT gibbonCourseClassPerson.role LIKE '% - Left%'
                ORDER BY class";

            $result = $this->pdo->select($sql, $data);
            if ($result->rowCount() > 0) {
                $classes[__('Learning Area')] = $result->fetchAll(\PDO::FETCH_KEY_PAIR);
            }
        }

        if (!empty($gibbonPersonID)) {
            $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
            $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name
                FROM gibbonCourseClassPerson
                JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonPersonID=:gibbonPersonID
                AND NOT gibbonCourseClassPerson.role LIKE '% - Left%'";
            if (isset($params['attendance'])) {
                $data['attendance'] = $params['attendance'];
                $sql .= " AND gibbonCourseClass.attendance=:attendance";
            }
            if (isset($params['reportable'])) {
                $data['reportable'] = $params['reportable'];
                $sql .= " AND gibbonCourseClass.reportable=:reportable";
            }
            $sql .= " ORDER BY name";
            $result = $this->pdo->select($sql, $data);
            if ($result->rowCount() > 0) {
                $classes[__('My Classes')] = $result->fetchAll(\PDO::FETCH_KEY_PAIR);
            }
        }

        if ($params['allClasses']) {
            $data=['gibbonSchoolYearID'=>$gibbonSchoolYearID];
            $sql= "SELECT gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID";
            if (isset($params['attendance'])) {
                $data['attendance'] = $params['attendance'];
                $sql .= " AND gibbonCourseClass.attendance=:attendance";
            }
            if (isset($params['reportable'])) {
                $data['reportable'] = $params['reportable'];
                $sql .= " AND gibbonCourseClass.reportable=:reportable";
            }
            $sql .= " ORDER BY name";
            $result = $this->pdo->select($sql, $data);

            if ($result->rowCount() > 0) {
                if (!empty($gibbonPersonID)) {
                    $classes[__('All Classes')] = $result->fetchAll(\PDO::FETCH_KEY_PAIR);
                } else {
                    $classes = $result->fetchAll(\PDO::FETCH_KEY_PAIR);
                }
            }
        }

        return $this->createSelect($name)->fromArray($classes)->placeholder();
    }

    public function createCheckboxYearGroup($name)
    {
        $sql = "SELECT gibbonYearGroupID as `value`, name FROM gibbonYearGroup ORDER BY sequenceNumber";
        $results = $this->pdo->select($sql);

        // Get the yearGroups in a $key => $value array
        $yearGroups = ($results && $results->rowCount() > 0)? $results->fetchAll(\PDO::FETCH_KEY_PAIR) : array();

        return $this->createCheckbox($name)->fromArray($yearGroups);
    }

    public function createCheckboxSchoolYearTerm($name, $gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonSchoolYearTermID as `value`, name FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber";
        $results = $this->pdo->select($sql, $data);

        // Get the terms in a $key => $value array
        $terms = ($results && $results->rowCount() > 0)? $results->fetchAll(\PDO::FETCH_KEY_PAIR) : array();

        return $this->createCheckbox($name)->fromArray($terms);
    }

    public function createSelectDepartment($name)
    {
        $sql = "SELECT type, gibbonDepartmentID as value, name FROM gibbonDepartment ORDER BY name";
        $results = $this->pdo->select($sql);

        $departments = array();

        if ($results && $results->rowCount() > 0) {
            while ($row = $results->fetch()) {
                $departments[$row['type']][$row['value']] = $row['name'];
            }
        }

        return $this->createSelect($name)->fromArray($departments)->placeholder();
    }

    public function createSelectSchoolYearTerm($name, $gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonSchoolYearTermID as `value`, name FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber";
        $results = $this->pdo->select($sql, $data);

        return $this->createSelect($name)->fromResults($results)->placeholder();
    }

    public function createSelectTheme($name)
    {
        $sql = "SELECT gibbonThemeID as value, (CASE WHEN active='Y' THEN CONCAT(name, ' (', '".__('System Default')."', ')') ELSE name END) AS name FROM gibbonTheme ORDER BY name";
        $results = $this->pdo->select($sql);

        return $this->createSelect($name)->fromResults($results)->placeholder();
    }

    public function createSelectI18n($name)
    {
        $sql = "SELECT * FROM gibboni18n WHERE active='Y' ORDER BY code";
        $results = $this->pdo->select($sql);

        $values = array_reduce($results->fetchAll(), function ($group, $item) {
            if (isset($item['installed']) && $item['installed'] == 'Y') {
                $group[$item['gibboni18nID']] = $item['systemDefault'] == 'Y'? $item['name'].' ('.__('System Default').')' : $item['name'];
            }
            return $group;
        }, []);

        return $this->createSelect($name)->fromArray($values)->placeholder();
    }

    public function createSelectLanguage($name)
    {
        $sql = "SELECT name as value, name FROM gibbonLanguage ORDER BY name";
        $results = $this->pdo->select($sql)->fetchKeyPair();
        $results = $this->localeFriendlySort($results);

        return $this->createSelect($name)->fromArray($results)->placeholder();
    }

    public function createSelectCountry($name)
    {
        $sql = "SELECT printable_name as value, printable_name as name FROM gibbonCountry ORDER BY printable_name";
        $results = $this->pdo->select($sql)->fetchKeyPair();
        $results = $this->localeFriendlySort($results);

        return $this->createSelect($name)->fromArray($results)->placeholder();
    }

    public function createSelectRole($name)
    {
        $sql = "SELECT gibbonRoleID as value, name FROM gibbonRole ORDER BY name";
        $results = $this->pdo->select($sql);

        return $this->createSelect($name)->fromResults($results)->placeholder();
    }

    public function createSelectStatus($name)
    {
        global $container;

        $statuses = array(
            'Full'     => __('Full'),
            'Expected' => __('Expected'),
            'Left'     => __('Left'),
        );

        if ($container->get(SettingGateway::class)->getSettingByScope('User Admin', 'enablePublicRegistration') == 'Y') {
            $statuses['Pending Approval'] = __('Pending Approval');
        }

        return $this->createSelect($name)->fromArray($statuses);
    }

    public function createSelectStaff($name)
    {
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, username
                FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID)
                WHERE status='Full' ORDER BY surname, preferredName";

        $staff = $this->pdo->select($sql)->fetchGroupedUnique();

        $staff = array_map(function ($person) {
            return Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true)." (".$person['username'].")";
        }, $staff);

        return $this->createSelectPerson($name)->fromArray($staff);
    }

    public function createSelectUsersFromList($name, $people = [])
    {
        $data = ['gibbonPersonIDList' => implode(',', $people)];
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, username
                FROM gibbonPerson
                WHERE status='Full'
                AND FIND_IN_SET(gibbonPersonID, :gibbonPersonIDList)
                ORDER BY FIND_IN_SET(gibbonPersonID, :gibbonPersonIDList), surname, preferredName";

        $people = $this->pdo->select($sql, $data)->fetchGroupedUnique();

        $people = array_map(function ($person) {
            return Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true)." (".$person['username'].")";
        }, $people);

        return $this->createSelectPerson($name)->fromArray($people);
    }

    public function createSelectUsers($name, $gibbonSchoolYearID = false, $params = [])
    {
        $params = array_replace(['includeStudents' => false, 'includeStaff' => false, 'useMultiSelect' => false], $params);

        $users = array();

        if ($params['includeStaff'] == true) {
            $data = array('date' => date('Y-m-d'));
            $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, username
                    FROM gibbonPerson
                    JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) ";

            if (!empty($gibbonSchoolYearID)) {
                $sql .= " WHERE (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')";
            }
            $sql .= " ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

            $result = $this->pdo->select($sql);
            if ($result->rowCount() > 0) {
                $users[__('Staff')] = array_reduce($result->fetchAll(), function ($group, $item) {
                    $group[$item['gibbonPersonID']] = Format::name('', htmlPrep($item['preferredName']), htmlPrep($item['surname']), 'Staff', true, true)." (".$item['username'].")";
                    return $group;
                }, array());
            }
        }

        if ($params['includeStudents'] == true) {
            $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, username, gibbonFormGroup.name AS formGroupName
                    FROM gibbonPerson
                    JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                    JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                     ";

            if (!empty($gibbonSchoolYearID)) {
                $sql .= "WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                        AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')
                        AND (dateStart IS NULL OR dateStart<=:date)
                        AND (dateEnd IS NULL OR dateEnd>=:date)";
            }

            $sql .= " ORDER BY formGroupName, gibbonPerson.surname, gibbonPerson.preferredName";

            $result = $this->pdo->select($sql, ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'date' => date('Y-m-d')]);

            if ($result->rowCount() > 0) {
                $users[__('Enrolable Students')] = array_reduce($result->fetchAll(), function($group, $item) {
                    $group[$item['gibbonPersonID']] = $item['formGroupName'].' - '.Format::name('', $item['preferredName'], $item['surname'], 'Student', true). " (".$item['username'].")";
                    return $group;
                }, array());
            }
        }

        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, username, gibbonRole.category
                FROM gibbonPerson
                JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary) ";

        if (!empty($gibbonSchoolYearID)) {
            $sql .= " WHERE (status='Full' OR status='Expected') ";
        }

        $sql .= " ORDER BY surname, preferredName";

        $result = $this->pdo->select($sql);

        if ($result->rowCount() > 0) {
            $users[__('All Users')] = array_reduce($result->fetchAll(), function ($group, $item) {
                $group[$item['gibbonPersonID']] = Format::name('', $item['preferredName'], $item['surname'], 'Student', true).' ('.$item['username'].', '.__($item['category']).')';
                return $group;
            }, array());
        }

        if ($params['useMultiSelect']) {
            $multiSelect = $this->createMultiSelect($name);
            $multiSelect->source()->fromArray($users);

            return $multiSelect;
        } else {
            return $this->createSelectPerson($name)->fromArray($users);
        }
    }

    /*
    $params is an array, with the following options as keys:
        allStudents - false by default. true displays students regardless of status and start/end date
        byName - true by default. Adds students organised by name
        byForm - false by default. Adds students organised by form group. Can be used in conjunction with byName to have multiple sections
        showForm - true by default. Displays form group beside student's name, when organised byName. Incompatible with allStudents
    */
    public function createSelectStudent($name, $gibbonSchoolYearID, $params = [])
    {
        //Create arrays for use later on
        $values = [];
        $data = [];

        // Check params and set defaults if not defined
        $params = array_replace(['allStudents' => false, 'activeStudents' => false, 'byName' => true, 'byForm' => false, 'showForm' => true], $params);

        //Check for multiple by methods, so we know when to apply optgroups
        $multipleBys = false;
        if ($params["byName"] && $params["byForm"]) {
            $multipleBys = true;
        }

        //Add students by form group
        if ($params["byForm"]) {
            if ($params["allStudents"]) {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, username, gibbonFormGroup.name AS name
                    FROM gibbonPerson
                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                    WHERE gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                    ORDER BY name, surname, preferredName";
            } elseif ($params["activeStudents"]) {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, username, gibbonFormGroup.name AS name
                    FROM gibbonPerson
                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                    WHERE gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND (gibbonPerson.status='Full' || gibbonPerson.status='Expected')
                    ORDER BY name, surname, preferredName";
            } else {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'date' => date('Y-m-d'));
                $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, username, gibbonFormGroup.name AS name
                    FROM gibbonPerson
                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                    WHERE status='Full'
                        AND (dateStart IS NULL OR dateStart<=:date)
                        AND (dateEnd IS NULL  OR dateEnd>=:date)
                        AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                    ORDER BY name, surname, preferredName";
            }

            $results = $this->pdo->select($sql, $data);

            if ($results && $results->rowCount() > 0) {
                while ($row = $results->fetch()) {
                    if ($multipleBys) {
                        $values[__('Students by Form Group')][$row['gibbonPersonID']] = htmlPrep($row['name']).' - '.Format::name('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true)." (".$row['username'].")";
                    } else {
                        $values[$row['gibbonPersonID']] = htmlPrep($row['name']).' - '.Format::name('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true)." (".$row['username'].")";
                    }
                }
            }
        }

        //Clear all values
        $data = [];

        //Add students by name
        if ($params["byName"]) {
            if ($params["allStudents"]) {
                $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, username, null AS name
                    FROM gibbonPerson
                        JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                    WHERE gibbonRole.category='Student'
                    ORDER BY surname, preferredName";
            } elseif ($params["activeStudents"]) {
                $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, username, null AS name
                    FROM gibbonPerson
                        JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                    WHERE gibbonRole.category='Student'
                    AND (gibbonPerson.status='Full' || gibbonPerson.status='Expected')
                    ORDER BY surname, preferredName";
            } else {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'date' => date('Y-m-d'));
                $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, username, gibbonFormGroup.name AS name
                    FROM gibbonPerson
                        JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                        JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                    WHERE status='Full'
                        AND (dateStart IS NULL OR dateStart<=:date)
                        AND (dateEnd IS NULL  OR dateEnd>=:date)
                        AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                    ORDER BY surname, preferredName";
            }

            $results = $this->pdo->select($sql, $data);

            if ($results && $results->rowCount() > 0) {
                while ($row = $results->fetch()) {
                    if ($multipleBys) {
                        if (!$params['allStudents'] && $params['byName'] && $params['showForm']) {
                            $values[__('Students by Name')][$row['gibbonPersonID']] = Format::name(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Student', true, true).' ('.$row['name'].', '.$row['username'].')';
                        }
                        else {
                            $values[__('Students by Name')][$row['gibbonPersonID']] = Format::name(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Student', true, true).' ('.$row['username'].')';
                        }
                    } else {
                        if (!$params['allStudents'] && $params['byName'] && $params['showForm']) {
                            $values[$row['gibbonPersonID']] = Format::name(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Student', true, true).' ('.$row['name'].', '.$row['username'].')';
                        }
                        else {
                            $values[$row['gibbonPersonID']] = Format::name(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Student', true, true).' ('.$row['username'].')';
                        }
                    }
                }
            }
        }

        return $this->createSelectPerson($name)->fromArray($values);
    }

    public function createSelectGradeScale($name)
    {
        $sql = "SELECT gibbonScaleID as value, name FROM gibbonScale WHERE (active='Y') ORDER BY name";

        return $this->createSelect($name)->fromQuery($this->pdo, $sql)->placeholder();
    }

    public function createSelectGradeScaleGrade($name, $gibbonScaleID, $params = array())
    {
        // Check params and set defaults if not defined
        $params = array_replace(array(
            'honourDefault' => true,
            'valueMode' => 'value',
            'labelMode' => 'value',
        ), $params);

        $data = array('gibbonScaleID' => $gibbonScaleID);
        $sql = "SELECT gibbonScaleGradeID, value, descriptor, isDefault FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber";
        $results = $this->pdo->select($sql, $data);

        $grades = ($results->rowCount() > 0)? $results->fetchAll() : array();
        $gradeOptions = array_reduce($grades, function ($group, $item) use ($params) {
            $identifier = $params['valueMode'] == 'id' ? 'gibbonScaleGradeID' : 'value';
            $value = $params['labelMode'] == 'descriptor' ? $item['descriptor'] : $item['value'];

            if ($params['labelMode'] == 'both') {
                $value = $item['value'] == $item['descriptor'] ? $item['value'] : $item['value'].' - '.$item['descriptor'];
            }

            $group[$item[$identifier]] = $value;
            return $group;
        }, []);

        $default = array_search('Y', array_column($grades, 'isDefault'));
        $selected = ($params['honourDefault'] && !empty($default))? $grades[$default]['value'] : '';

        return $this->createSelect($name)->fromArray($gradeOptions)->selected($selected)->placeholder()->addClass('gradeSelect');
    }

    public function createSelectRubric($name, $gibbonYearGroupIDList = '', $gibbonDepartmentID = '')
    {
        $data = array('gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'gibbonDepartmentID' => $gibbonDepartmentID, 'rubrics' => __('Rubrics'));
        $sql = "SELECT CONCAT(scope, ' ', :rubrics) as groupBy, gibbonRubricID as value,
                (CASE WHEN category <> '' THEN CONCAT(category, ' - ', gibbonRubric.name) ELSE gibbonRubric.name END) as name
                FROM gibbonRubric
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonRubric.gibbonYearGroupIDList))
                WHERE gibbonRubric.active='Y'
                AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList)
                AND (scope='School' OR (scope='Learning Area' AND gibbonDepartmentID=:gibbonDepartmentID))
                GROUP BY gibbonRubric.gibbonRubricID
                ORDER BY scope, category, name";

        return $this->createSelect($name)->fromQuery($this->pdo, $sql, $data, 'groupBy')->placeholder();
    }

    public function createSelectReportingCycle($name)
    {
        $sql = "SELECT gibbonSchoolYear.name as schoolYear, gibbonReportingCycleID as value, gibbonReportingCycle.name FROM gibbonReportingCycle JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonReportingCycle.gibbonSchoolYearID) ORDER BY gibbonSchoolYear.sequenceNumber DESC, gibbonReportingCycle.sequenceNumber";

        return $this->createSelect($name)->fromQuery($this->pdo, $sql, [], 'schoolYear')->placeholder();
    }

    public function createPhoneNumber($name)
    {
        $countryCodes = $this->getCachedQuery('phoneNumber');

        if (empty($countryCodes)) {
            $sql = "SELECT iddCountryCode, printable_name FROM gibbonCountry ORDER BY (SELECT value FROM gibbonSetting WHERE scope='System' AND name='country' LIMIT 1)=printable_name DESC, iddCountryCode, printable_name";
            $results = $this->pdo->select($sql);
            if ($results && $results->rowCount() > 0) {
                $countryCodes = $results->fetchAll();

                // Transform the row data into value => name pairs
                $countryCodes = array_reduce($countryCodes, function($codes, $item) {
                    if (!empty($item['iddCountryCode'])) {
                        if (array_key_exists($item['iddCountryCode'], $codes)) {
                            $codes[$item['iddCountryCode']] = $codes[$item['iddCountryCode']].', '.__($item['printable_name']);
                        }
                        else {
                            $codes[$item['iddCountryCode']] = $item['iddCountryCode'].' - '.__($item['printable_name']);
                        }
                    }
                    return $codes;

                }, array());
            }
            $this->setCachedQuery('phoneNumber', $countryCodes);
        }

        return new Input\PhoneNumber($this, $name, $countryCodes);
    }

    public function createSequenceNumber($name, $tableName, $sequenceNumber = '', $columnName = null)
    {
        $columnName = empty($columnName)? $name : $columnName;

        $data = array('sequenceNumber' => $sequenceNumber);
        $sql = "SELECT GROUP_CONCAT(DISTINCT `{$columnName}` SEPARATOR '\',\'') FROM `{$tableName}` WHERE (`{$columnName}` IS NOT NULL AND `{$columnName}` <> :sequenceNumber) ORDER BY `{$columnName}`";
        $results = $this->pdo->select($sql, $data);

        $field = $this->createNumber($name)->minimum(1)->onlyInteger(true);

        if ($results && $results->rowCount() > 0) {
            $field->addValidation('Validate.Exclusion', 'within: [\''.$results->fetchColumn(0).'\'], failureMessage: "'.__('Value already in use!').'", partialMatch: false, caseSensitive: false');
        }

        if (!empty($sequenceNumber) || $sequenceNumber === false) {
            $field->setValue($sequenceNumber);
        } else {
            $sql = "SELECT MAX(`{$columnName}`) FROM `{$tableName}`";
            $results = $this->pdo->select($sql);
            $sequenceNumber = ($results && $results->rowCount() > 0)? $results->fetchColumn(0) : 1;

            $field->setValue($sequenceNumber+1);
        }

        return $field;
    }

    /*
    The optional $all function adds an option to the top of the select, using * to allow selection of all year groups
    */
    public function createSelectTransport($name, $all = false)
    {
        $sql = "SELECT DISTINCT transport AS value, transport AS name FROM gibbonPerson WHERE status='Full' AND NOT transport='' ORDER BY transport";
        $results = $this->pdo->select($sql);

        if (!$all)
            return $this->createSelect($name)->fromResults($results)->placeholder();
        else
            return $this->createSelect($name)->fromArray(array("*" => "All"))->fromResults($results)->placeholder();
    }

    public function createSelectSpace($name, $params = [])
    {
        $params = array_replace(array(
            'byType' => true,
        ), $params);

        if ($params['byType'] == true) {
            $sql = "SELECT gibbonSpaceID as value, name, type as groupBy FROM gibbonSpace ORDER BY type, name";
            $results = $this->pdo->select($sql);
            return $this->createSelect($name)->fromResults($results, 'groupBy')->placeholder();

        } else {
            $sql = "SELECT gibbonSpaceID as value, name FROM gibbonSpace ORDER BY name";
            $results = $this->pdo->select($sql);
            return $this->createSelect($name)->fromResults($results)->placeholder();
        }
    }

    public function createTextFieldDistrict($name)
    {
        $sql = "SELECT DISTINCT name FROM gibbonDistrict ORDER BY name";
        $result = $this->pdo->select($sql);
        $districts = ($result && $result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN) : array();

        return $this->createTextField($name)->maxLength(30)->autocomplete($districts);
    }

    public function createSelectAlert($name)
    {
        $sql = 'SELECT gibbonAlertLevelID AS value, name FROM gibbonAlertLevel ORDER BY sequenceNumber';
        $results = $this->pdo->select($sql);

        return $this->createSelect($name)->fromResults($results)->placeholder();
    }

    protected function getCachedQuery($name)
    {
        return (isset($this->cachedQueries[$name]))? $this->cachedQueries[$name] : array();
    }

    protected function setCachedQuery($name, $results)
    {
        $this->cachedQueries[$name] = $results;
    }

    protected function localeFriendlySort($values)
    {
        $values = array_map('__', $values);
    
        if (static::$intlCollatorAvailable) {
            $locale = \Locale::getDefault();
            $collator = new \Collator($locale);
            $collator->sort($values);
        } else {
            usort($values, 'strcoll');
        }

        return $values;
    }
}
