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

    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

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

    public function createSelectYearGroup($name)
    {
        $sql = "SELECT gibbonYearGroupID as value, name FROM gibbonYearGroup ORDER BY sequenceNumber";
        $results = $this->pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder();
    }

    public function createCheckboxYearGroup($name)
    {
        $sql = "SELECT gibbonYearGroupID as `value`, name FROM gibbonYearGroup ORDER BY sequenceNumber";
        $results = $this->pdo->executeQuery(array(), $sql);

        // Get the yearGroups in a $key => $value array
        $yearGroups = ($results && $results->rowCount() > 0)? $results->fetchAll(\PDO::FETCH_KEY_PAIR) : array();

        return $this->createCheckbox($name)->fromArray($yearGroups);
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

    public function createSelectStudent($name, $allStudents = false)
    {
        if ($allStudents) {
            $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName
                FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonRole.category='Student'";
        } else {
            $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName
                FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY surname, preferredName";
        }

        $results = $this->pdo->executeQuery(array(), $sql);

        $values = array();
        if ($results && $results->rowCount() > 0) {
            while ($row = $results->fetch()) {
                $values[$row['gibbonPersonID']] = formatName(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Student', true, true);
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
            }
            $this->setCachedQuery('phoneNumber', $countryCodes);
        }

        $phoneNumberField = new Input\PhoneNumber($name);
        return $phoneNumberField->setCountryCodes($countryCodes);
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
