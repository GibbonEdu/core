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
/**
 */
namespace Gibbon\Record ;

/**
 * Behaviour Record
 *
 * @version	27th May 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class behaviour extends record
{
	use \Gibbon\core\functions\dateFunctions ;

	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonBehaviour';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonBehaviourID';
	
	/**
	 * Unique Test
	 *
	 * @version	4th May 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		return false;
	}

	/**
	 * can Delete
	 *
	 * @version	27th May 2016
	 * @since	27th May 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return false;
	}

	/**
	 * get Person 
	 *
	 * with the current student record set.
	 * @version	3rd October 2016
	 * @since	3rd October 2016
	 * @param	integer		$personID
	 * @return	Gibbon\Record\person 
	 */
	public function getPerson($personID = null)
	{
		if (is_null($personID)) $personID = $this->record->gibbonPersonID;
		$this->person = $this->view->getRecord('person');
		$this->person->find($personID);
		return $this->person ;
	}
}
