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

use Gibbon\Record\personMedicalConditionUpdate ;

/**
 * Person Medical Condition Record
 *
 * @version	6th August 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class personMedicalCondition extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonPersonMedicalCondition';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonPersonMedicalConditionID';
	
	/**
	 * @var	stdClass	Titles for Fields
	 */
	protected $title ;
	
	/**
	 * Unique Test
	 *
	 * @version	6th August 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$required = array('gibbonPersonMedicalID', 'name', 'gibbonAlertLevelID');
		foreach ($required as $name) {
			if (empty($this->record->$name))
			{
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
			}
		}
		$data = array('gibbonPersonMedicalID' => $this->record->gibbonPersonMedicalID, 'gibbonPersonMedicalConditionID' => $this->record->gibbonPersonMedicalConditionID, 'name' => $this->record->name);
		$sql = 'SELECT * 
			FROM `'.$this->table.'` 
			WHERE `gibbonPersonMedicalID` = :gibbonPersonMedicalID
				AND `name` = :name
				AND NOT `gibbonPersonMedicalConditionID` = :gibbonPersonMedicalConditionID';
		$tester = clone $this;
		$s = $tester->findAll($sql, $data);
		if (count($s) > 0)
			return $this->uniqueFailed('A medical condition record for the student already exists.', 'Debug', $this->table, $data) ;
		return true ;
	}

	/**
	 * can Delete
	 *
	 * @version	5th August 2016
	 * @since	5th August 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}

	/**
	 * create Medical Condition Update
	 *
	 * @version	6th August 2016
	 * @since	5th August 2016
	 * @return	Gibbon\Record\personMedicalUpdate
	 */
	public function createMedicalConditionUpdate()
	{
		$mcu = new personMedicalConditionUpdate($this->view);
		$mcu->defaultRecord();
		$mcu->injectPost((array)$this->record);
		return $mcu ;
	}
}
