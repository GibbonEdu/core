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

namespace Gibbon\Domain\Activities;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Activity Staff Gateway
 *
 * @version v22
 * @since   v22
 */
class ActivityStaffGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivityStaff';
    private static $primaryKey = 'gibbonActivityStaffID';

    private static $searchableColumns = [];

    public function selectActivityStaff($gibbonActivityID) {
    	$select = $this
    		->newSelect()
    		->cols(['preferredName, surname, gibbonActivityStaff.*'])
    		->from($this->getTableName())
    		->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonActivityStaff.gibbonPersonID')
    		->where('gibbonActivityStaff.gibbonActivityID = :gibbonActivityID')
    		->bindValue('gibbonActivityID', $gibbonActivityID)
    		->where('gibbonPerson.status="Full"')
    		->orderBy(['surname', 'preferredName']);

    	return $this->runSelect($select);
    }

    public function selectActivityStaffByID($gibbonActivityID, $gibbonPersonID) {
    	return $this->selectBy([
    		'gibbonPersonID' 	=> $gibbonPersonID,
    		'gibbonActivityID' 	=> $gibbonActivityID
    	]);
    }

    public function insertActivityStaff($gibbonActivityID, $gibbonPersonID, $role) {
    	return $this->insert([
    		'gibbonPersonID' 	=> $gibbonPersonID,
    		'gibbonActivityID' 	=> $gibbonActivityID,
    		'role'				=> $role
    	]);
    }
}
