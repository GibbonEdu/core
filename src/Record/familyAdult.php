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
 * Family Adult Record
 *
 * @version	11th August 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class familyAdult extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonFamilyAdult';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonFamilyAdultID';
	
	/**
	 * Unique Test
	 *
	 * @version	25th July 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $table) ;
		$required = array('gibbonPersonID', 'gibbonFamilyID');
		foreach ($required as $name)
		{
			if (empty($this->record->$name))
			{
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $table, array($name)) ;
			}
		}
		$data = array('gibbonFamilyID' => $this->record->gibbonFamilyID, 'gibbonPersonID' => $this->record->gibbonPersonID);
        $sql = "SELECT * 
			FROM `gibbonPerson`, `gibbonFamily`, `gibbonFamilyAdult` 
			WHERE `gibbonFamily`.`gibbonFamilyID` = `gibbonFamilyAdult`.`gibbonFamilyID` 
				AND `gibbonFamilyAdult`.`gibbonPersonID` = `gibbonPerson`.`gibbonPersonID` 
				AND `gibbonFamily`.`gibbonFamilyID` = :gibbonFamilyID 
				AND `gibbonFamilyAdult`.`gibbonPersonID` = :gibbonPersonID 
				AND (`gibbonPerson`.`status` = 'Full' OR `gibbonPerson`.`status` = 'Expected')";
		$v = clone $this;
		$w = $v->findAll($sql, $data);
		if (count($w) > 1)
			return $this->uniqueFailed('The record failed a unique value.', 'Debug', $table, $data) ;
		return true ;
	}
	
	/**
	 * can Delete
	 *
	 * @version	21st July 2016
	 * @since	21st July 2016
	 * @return	boolean
	 */
	public function canDelete()
	{
		return true ;
	}

	/**
	 * get Person
	 *
	 * @version	21st July 2016
	 * @since	21st July 2016
	 * @return	person
	 */
	public function getPerson()
	{
		$person = $this->view->getRecord('person');
		$person->find($this->record->gibbonPersonID);
		return $person ;
	}

	/**
	 * write Record
	 *
	 * @version	25th July 2016
	 * @since	25th July 2016
	 * @return	boolean
	 */
	public function writeRecord()
	{
		$v = clone $this ;
		//Enforce one and only one contactPriority=1 parent
		if ($this->getField('contactPriority') == 1) {
			//Set all other parents in family who are set to 1, to 2
			$data = array('gibbonPersonID' => $this->record->gibbonPersonID, 'gibbonFamilyID' => $this->record->gibbonFamilyID, 'contactPriority' => 2);
			$sql = 'UPDATE `gibbonFamilyAdult` 
				SET `contactPriority` = :contactPriority 
				WHERE `gibbonFamilyID` = :gibbonFamilyID 
					AND NOT `gibbonPersonID` = :gibbonPersonID';
			$v->executeQuery($data, $sql);
			if (! $v->getSuccess()) return false ;
		} else {
			//Check to see if there is a parent set to 1 already, and if not, change this one to 1
			$data = array('gibbonPersonID' => $this->record->gibbonPersonID, 'gibbonFamilyID' => $this->record->gibbonFamilyID, 'contactPriority' => 2);
			$sql = 'SELECT * 
				FROM `gibbonFamilyAdult` 
				WHERE `contactPriority` = :contactPriority
					AND `gibbonFamilyID` = :gibbonFamilyID 
					AND NOT `gibbonPersonID` = :gibbonPersonID';
			$v->executeQuery($data, $sql);
			if (! $v->getSuccess()) return false ;
			if ($v->rowCount() < 1) {
				$this->setField('contactPriority', 1);
			}
		}
		if ($this->getField('contactPriority') == 1) {
			$this->setField('contactCall', 'Y');
			$this->setField('contactSMS', 'Y');
			$this->setField('contactEmail', 'Y');
			$this->setField('contactMail', 'Y');
		}
		return parent::writeRecord();
	}

	/**
	 * default Record
	 *
	 * @version	2nd August 2016
	 * @since	2nd August 2016
	 * @return	boolean
	 */
	public function defaultRecord()
	{
		parent::defaultRecord();
		$this->setField('childDataAccess', 'Y');
		$this->setField('contactCall', 'Y');
		$this->setField('contactSMS', 'Y');
		$this->setField('contactEmail', 'Y');
		$this->setField('contactMail', 'Y');
	}

	/**
	 * get Family Students
	 *
	 * @version	11th August 2016
	 * @since	11th August 2016
	 * @return	array		Gibbon\Record\familyChild
	 */
	public function getFamilyStudents()
	{
		$data = array('gibbonFamilyID' => $this->record->gibbonFamilyID);
		$obj = $this->view->getRecord('familyChild');
		$child = $obj->findBy($data);
		$results = array();
		if ($obj->getSuccess())
		{
			do
			{
				$results[$child->gibbonFamilyChildID] = clone $obj;
				$obj->find($child->gibbonFamilyChildID);
				
			} while ($child = $obj->next());
		}
		return $results;
	}
}
