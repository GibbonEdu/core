<?php
/**
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

namespace Gibbon\People;

use Gibbon\Record\person ;

/**
 * Staff Member
 *
 * @version	26th September 2016
 * @since	26th September 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	People
 */
class staff extends person
{
	/**
	 * all Staff
	 *
	 * @version	29th September 2016
	 * @since	26th September 2016
	 * @param	string		$status		Status to load.
	 * @return	array		Gibbon\People\staff 
	 */
	public function allStaff($status = 'Full')
	{
		return $this->findAllStaffByType($status);
	}

	/**
	 * find All Staff by Type
	 *
	 * @version	29th September 2016
	 * @since	29th September 2016
	 * @param	string		$status		Status to load.
	 * @return	array		Gibbon\People\staff 
	 */
	public function findAllStaffByType($status = 'Full')
	{
		$sql = "SELECT `gibbonPerson`.*
			FROM `gibbonPerson`
			JOIN `gibbonStaff` ON `gibbonPerson`.`gibbonPersonID` = `gibbonStaff`.`gibbonPersonID`
			WHERE `status` = :status
			ORDER BY `surname`,`preferredName`" ;
		$x = $this->findAll($sql, array('status' => $status));
		return $x;
	}
}