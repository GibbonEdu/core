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

    public function createSelectSchoolYear($name)
    {
        $sql = 'SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear ORDER BY sequenceNumber';
        $results = $this->pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder('Please select...');
    }

    public function createSelectLanguage($name)
    {
        $sql = 'SELECT name as value, name FROM gibbonLanguage ORDER BY name';
        $results = $this->pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder('Please select...');
    }

    public function createSelectCountry($name)
    {
        $sql = 'SELECT printable_name as value, printable_name as name FROM gibbonCountry ORDER BY printable_name';
        $results = $this->pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder('Please select...');
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
