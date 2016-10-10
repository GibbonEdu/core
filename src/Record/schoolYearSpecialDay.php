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

use Gibbon\security ;
use Gibbon\helper ;
use Gibbon\trans ;

/**
 * School Year Special Day Record
 *
 * @version	28th September 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class schoolYearSpecialDay extends record
{
	use \Gibbon\core\functions\dateFunctions ;

	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonSchoolYearSpecialDay';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonSchoolYearSpecialDayID';
	
	/**
	 * Unique Test
	 *
	 * @version	28th September 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		//Validate Inputs
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
        $required = array('type', 'name', 'gibbonSchoolYearTermID', 'date');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		$data = array('schoolYearSpecialDayID' => $this->record->gibbonSchoolYearSpecialDayID, 'date' => $this->record->date);
		$sql = 'SELECT * 
			FROM `gibbonSchoolYearSpecialDay` 
			WHERE `date` = :date 
				AND NOT `gibbonSchoolYearSpecialDayID` = :schoolYearSpecialDayID';
		$v = clone $this;
		$roles = $v->findAll($sql, $data);
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', $this->table, array((array)$this->returnRecord())) ;
		return true ;
	}

	/**
	 * can Delete
	 *
	 * @version	25th May 2016
	 * @since	25th May 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}
}
?>