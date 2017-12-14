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

namespace Gibbon\Forms;

use Gibbon\Forms\FormFactory;

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
    protected $pdo;

    protected $cachedQueries = array();

    /**
     * Create a factory with access to the provided a database connection.
     * @param  \Gibbon\sqlConnection  $pdo
     */
    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create and return an instance of DatabaseFormFactory.
     * @return  object DatabaseFormFactory
     */
    public static function create(\Gibbon\sqlConnection $pdo = null)
    {
        return new DatabaseFormFactory($pdo);
    }

    public function createSelectSchoolYear($name, $status = 'All')
    {
        switch ($status) {
            case 'Active':
                $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE status='Current' OR status='Upcoming' ORDER BY sequenceNumber"; break;

            case 'Upcoming':
                $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE status='Upcoming' ORDER BY sequenceNumber"; break;

            case 'Past':
                $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE status='Past' ORDER BY sequenceNumber"; break;

            case 'All':
            case 'Any':
            default:
                $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear ORDER BY sequenceNumber"; break;
        }
        $results = $this->pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder();
    }

    /*
    The optional $all function adds an option to the top of the select, using * to allow selection of all year groups
    */
    public function createSelectYearGroup($name, $all = false)
    {
        $sql = "SELECT gibbonYearGroupID as value, name FROM gibbonYearGroup ORDER BY sequenceNumber";
        $results = $this->pdo->executeQuery(array(), $sql);

        if (!$all)
            return $this->createSelect($name)->fromResults($results)->placeholder();
        else
            return $this->createSelect($name)->fromArray(array("*" => "All"))->fromResults($results)->placeholder();
    }

    /*
    The optional $all function adds an option to the top of the select, using * to allow selection of all roll groups
    */
    public function createSelectRollGroup($name, $gibbonSchoolYearID, $all = false)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonRollGroupID as value, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY LENGTH(name), name";
        $results = $this->pdo->executeQuery($data, $sql);

        if (!$all)
            return $this->createSelect($name)->fromResults($results)->placeholder();
        else
            return $this->createSelect($name)->fromArray(array("*" => "All"))->fromResults($results)->placeholder();
    }

    public function createSelectClass($name, $gibbonSchoolYearID, $gibbonPersonID = null)
    {
        $classes = array();
        if (!empty($gibbonPersonID)) {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.attendance='Y' ORDER BY name";
            $result = $this->pdo->executeQuery($data, $sql);
            if ($result->rowCount() > 0) {
                $classes['--'. __('My Classes') . '--'] = $result->fetchAll(\PDO::FETCH_KEY_PAIR);
            }
        }

        $data=array('gibbonSchoolYearID'=>$gibbonSchoolYearID);
        $sql= "SELECT gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.attendance='Y' ORDER BY name" ;
        $result = $this->pdo->executeQuery($data, $sql);

        if ($result->rowCount() > 0) {
            if (!empty($gibbonPersonID)) {
                $classes['--' . __('All Classes') . '--'] = $result->fetchAll(\PDO::FETCH_KEY_PAIR);
            } else {
                $classes = $result->fetchAll(\PDO::FETCH_KEY_PAIR);
            }
        }

        return $this->createSelect($name)->fromArray($classes)->placeholder();
    }

    public function createCheckboxYearGroup($name)
    {
        $sql = "SELECT gibbonYearGroupID as `value`, name FROM gibbonYearGroup ORDER BY sequenceNumber";
        $results = $this->pdo->executeQuery(array(), $sql);

        // Get the yearGroups in a $key => $value array
        $yearGroups = ($results && $results->rowCount() > 0)? $results->fetchAll(\PDO::FETCH_KEY_PAIR) : array();

        return $this->createCheckbox($name)->fromArray($yearGroups);
    }

    public function createCheckboxSchoolYearTerm($name, $gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonSchoolYearTermID as `value`, name FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber";
        $results = $this->pdo->executeQuery($data, $sql);

        // Get the terms in a $key => $value array
        $terms = ($results && $results->rowCount() > 0)? $results->fetchAll(\PDO::FETCH_KEY_PAIR) : array();

        return $this->createCheckbox($name)->fromArray($terms);
    }

    public function createSelectDepartment($name)
    {
        $sql = "SELECT type, gibbonDepartmentID as value, name FROM gibbonDepartment ORDER BY name";
        $results = $this->pdo->executeQuery(array(), $sql);

        $departments = array();

        if ($results && $results->rowCount() > 0) {
            while ($row = $results->fetch()) {
                $departments[$row['type']][$row['value']] = $row['name'];
            }
        }

        return $this->createSelect($name)->fromArray($departments)->placeholder();
    }

    public function createSelectLanguage($name)
    {
        $sql = "SELECT name as value, name FROM gibbonLanguage ORDER BY name";
        $results = $this->pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder();
    }

    public function createSelectCountry($name)
    {
        $sql = "SELECT printable_name as value, printable_name as name FROM gibbonCountry ORDER BY printable_name";
        $results = $this->pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder();
    }

    public function createSelectRole($name)
    {
        $sql = "SELECT gibbonRoleID as value, name FROM gibbonRole ORDER BY name";
        $results = $this->pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder();
    }

    public function createSelectStatus($name)
    {
        $statuses = array(
            'Full'     => __('Full'),
            'Expected' => __('Expected'),
            'Left'     => __('Left'),
        );

        if (getSettingByScope($this->pdo->getConnection(), 'User Admin', 'enablePublicRegistration') == 'Y') {
            $statuses['Pending Approval'] = __('Pending Approval');
        }

        return $this->createSelect($name)->fromArray($statuses);
    }

    public function createSelectStaff($name)
    {
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName
                FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID)
                WHERE status='Full' ORDER BY surname, preferredName";

        $results = $this->pdo->executeQuery(array(), $sql);

        $values = array();
        if ($results && $results->rowCount() > 0) {
            while ($row = $results->fetch()) {
                $values[$row['gibbonPersonID']] = formatName(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Staff', true, true);
            }
        }

        return $this->createSelect($name)->fromArray($values);
    }

    public function createSelectUsers($name)
    {
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName
                FROM gibbonPerson
                WHERE status='Full' ORDER BY surname, preferredName";

        $results = $this->pdo->executeQuery(array(), $sql);

        $values = array();
        if ($results && $results->rowCount() > 0) {
            while ($row = $results->fetch()) {
                $values[$row['gibbonPersonID']] = formatName(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Staff', true, true);
            }
        }

        return $this->createSelect($name)->fromArray($values);
    }

    /*
    $params is an array, with the following options as keys:
        allStudents - false by default. true displays students regardless of status and start/end date
        byName - true by default. Adds students organised by name
        byRoll - false by default. Adds students organised by roll group. Can be used in conjunction with byName to have multiple sections
        showRoll - true by default. Displays roll group beside student's name, when organised byName. Incompatible with allStudents
    */
    public function createSelectStudent($name, $gibbonSchoolYearID, $params = array())
    {
        //Create arrays for use later on
        $values = array();
        $data = array();

        // Check params and set defaults if not defined
        $params = array_replace(array('allStudents' => false, 'byName' => true, 'byRoll' => false, 'showRoll' => true), $params);

        //Check for multiple by methods, so we know when to apply optgroups
        $multipleBys = false;
        if ($params["byName"] && $params["byRoll"]) {
            $multipleBys = true;
        }

        //Add students by roll group
        if ($params["byRoll"]) {
            if ($params["allStudents"]) {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name
                    FROM gibbonPerson
                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                    WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                    ORDER BY name, surname, preferredName";

            } else {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'date' => date('Y-m-d'));
                $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name
                    FROM gibbonPerson
                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                    WHERE status='Full'
                        AND (dateStart IS NULL OR dateStart<=:date)
                        AND (dateEnd IS NULL  OR dateEnd>=:date)
                        AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                    ORDER BY name, surname, preferredName";
            }

            $results = $this->pdo->executeQuery($data, $sql);

            if ($results && $results->rowCount() > 0) {
                while ($row = $results->fetch()) {
                    if ($multipleBys) {
                        $values[__('Students by Roll Group')][$row['gibbonPersonID']] = htmlPrep($row['name']).' - '.formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true);
                    } else {
                        $values[$row['gibbonPersonID']] = htmlPrep($row['name']).' - '.formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true);
                    }
                }
            }
        }

        //Add students by name
        if ($params["byName"]) {
            if ($params["allStudents"]) {
                $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, null AS name
                    FROM gibbonPerson
                        JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                    WHERE gibbonRole.category='Student'
                    ORDER BY surname, preferredName";
            } else {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'date' => date('Y-m-d'));
                $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, gibbonRollGroup.name AS name
                    FROM gibbonPerson
                        JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                        JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                    WHERE status='Full'
                        AND (dateStart IS NULL OR dateStart<=:date)
                        AND (dateEnd IS NULL  OR dateEnd>=:date)
                        AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                    ORDER BY surname, preferredName";
            }

            $results = $this->pdo->executeQuery($data, $sql);

            if ($results && $results->rowCount() > 0) {
                while ($row = $results->fetch()) {
                    if ($multipleBys) {
                        if (!$params['allStudents'] && $params['byName'] && $params['showRoll']) {
                            $values[__('Students by Name')][$row['gibbonPersonID']] = formatName(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Student', true, true)." (".$row['name'].")";
                        }
                        else {
                            $values[__('Students by Name')][$row['gibbonPersonID']] = formatName(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Student', true, true);
                        }
                    } else {
                        if (!$params['allStudents'] && $params['byName'] && $params['showRoll']) {
                            $values[$row['gibbonPersonID']] = formatName(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Student', true, true)." (".$row['name'].")";
                        }
                        else {
                            $values[$row['gibbonPersonID']] = formatName(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Student', true, true);
                        }
                    }
                }
            }
        }

        return $this->createSelect($name)->fromArray($values);
    }

    public function createPhoneNumber($name)
    {
        $countryCodes = $this->getCachedQuery('phoneNumber');

        if (empty($countryCodes)) {
            $sql = 'SELECT iddCountryCode, printable_name FROM gibbonCountry ORDER BY printable_name';
            $results = $this->pdo->executeQuery(array(), $sql);
            if ($results && $results->rowCount() > 0) {
                $countryCodes = $results->fetchAll();

                // Transform the row data into value => name pairs
                $countryCodes = array_reduce($countryCodes, function($codes, $item) {
                    $codes[$item['iddCountryCode']] = $item['iddCountryCode'].' - '.__($item['printable_name']);
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
        $results = $this->pdo->executeQuery($data, $sql);

        $field = $this->createNumber($name)->minimum(1)->onlyInteger(true);

        if ($results && $results->rowCount() > 0) {
            $field->addValidation('Validate.Exclusion', 'within: [\''.$results->fetchColumn(0).'\'], failureMessage: "'.__('Value already in use!').'", partialMatch: false, caseSensitive: false');
        }

        if (!empty($sequenceNumber) || $sequenceNumber === false) {
            $field->setValue($sequenceNumber);
        } else {
            $sql = "SELECT MAX(`{$columnName}`) FROM `{$tableName}`";
            $results = $this->pdo->executeQuery(array(), $sql);
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
        $results = $this->pdo->executeQuery(array(), $sql);

        if (!$all)
            return $this->createSelect($name)->fromResults($results)->placeholder();
        else
            return $this->createSelect($name)->fromArray(array("*" => "All"))->fromResults($results)->placeholder();
    }

    public function createSelectSpace($name)
    {
        $sql = "SELECT gibbonSpaceID as value, name FROM gibbonSpace ORDER BY name";
        $results = $this->pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder();
    }

    public function createTextFieldDistrict($name)
    {
        $sql = "SELECT DISTINCT name FROM gibbonDistrict ORDER BY name";
        $result = $this->pdo->executeQuery(array(), $sql);
        $districts = ($result && $result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN) : array();

        return $this->createTextField($name)->maxLength(30)->autocomplete($districts);
    }

    public function createSelectAlert($name)
    {
        $sql = 'SELECT gibbonAlertLevelID AS value, name FROM gibbonAlertLevel ORDER BY sequenceNumber';
        $results = $this->pdo->executeQuery(array(), $sql);

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
}
