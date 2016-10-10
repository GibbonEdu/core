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
 * Family Child Record
 *
 * @version	2nd August 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class familyChild extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonFamilyChild';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonFamilyChildID';
	
	/**
	 * @var	object	Gibbon\Record\person
	 */
	protected $person;
	
	/**
	 * @var	object	Gibbon\Record\yearGroup
	 */
	protected $yearGroup;
	
	/**
	 * @var	object	Gibbon\Record\rollGroup
	 */
	protected $rollGroup;
	
	/**
	 * @var	object	Gibbon\Record\studentEnrolment
	 */
	protected $studentEnrolment;

	/**
	 * Unique Test
	 *
	 * @version	2nd August 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$required = array('gibbonPersonID', 'gibbonFamilyID');
		foreach ($required as $name)
		{
			if (empty($this->record->$name))
			{
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
			}
		}
		$data = array('gibbonFamilyID' => $this->record->gibbonFamilyID, 'gibbonPersonID' => $this->record->gibbonPersonID);
		$sql = "SELECT * 
			FROM `gibbonPerson`, `gibbonFamily`, `gibbonFamilyChild` 
			WHERE `gibbonFamily`.`gibbonFamilyID` = `gibbonFamilyChild`.`gibbonFamilyID`
				AND `gibbonFamilyChild`.`gibbonPersonID` = `gibbonPerson`.`gibbonPersonID` 
				AND `gibbonFamily`.	gibbonFamilyID` = :gibbonFamilyID 
				AND `gibbonFamilyChild`.`gibbonPersonID` = :gibbonPersonID 
				AND (`gibbonPerson`.`status` = 'Full' OR `gibbonPerson`.`status` = 'Expected')";
		$v = clone $this;
		$w = $v->findAll($sql, $data);
		if (count($w) > 1)
			return $this->uniqueFailed('The record failed a unique value.', 'Debug', $this->table, $data) ;
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
		if ($this->person instanceof person && $this->person->getField('gibbonPersonID') == $this->record->gibbonPersonID)
			return $this->person;
		$this->person = $this->view->getRecord('person');
		return $this->person->findAll($this->record->gibbonPersonID);
	}

	/**
	 * is Current
	 *
	 * @version	11th August 2016
	 * @since	11th August 2016
	 * @return	boolean
	 */
	public function isCurrent()
	{
		$obj = new studentEnrolment($this->view);
		$data = array('personID' => $this->record->gibbonPersonID, 'startDate' => date('Y-m-d'), 'endDate' => date('Y-m-d'), 'schoolYearID' => $this->view->session->get('gibbonSchoolYearID'), 'status' => 'Full');
		$sql = 'SELECT * 
			FROM `gibbonStudentEnrolment`
				JOIN `gibbonPerson` ON `gibbonStudentEnrolment`.`gibbonPersonID` = `gibbonPerson`.`gibbonPersonID`
			WHERE `gibbonStudentEnrolment`.`gibbonPersonID` = :personID
				AND `gibbonPerson`.`status` = :status
				AND (`dateStart` IS NULL OR `dateStart` <= :startDate) 
				AND (`dateEnd` IS NULL OR `dateEnd` >= :endDate) 
				AND `gibbonStudentEnrolment`.`gibbonSchoolYearID` = :schoolYearID';
		$child = $obj->findAll($sql, $data);
		if (count($child) === 1)
			return true ;
		return false ;
	}

	/**
	 * get Year Group
	 *
	 * @version	11th August 2016
	 * @since	11th August 2016
	 * @return	yearGroup
	 */
	public function getYearGroup()
	{
		if ($this->yearGroup instanceof yearGroup)
			return $this->yearGroup;
		$this->studentEnrolment = $this->getStudentEnrolment();
		$this->yearGroup = new yearGroup($this->view);
		$data = array('gibbonYearGroupID' => $this->studentEnrolment->getField('gibbonYearGroupID'));
		$this->yearGroup->findBy($data);
		return $this->yearGroup;
	}

	/**
	 * get Student Enrolment
	 *
	 * @version	11th August 2016
	 * @since	11th August 2016
	 * @return	studentEnrolment
	 */
	public function getStudentEnrolment()
	{
		if ($this->studentEnrolment instanceof studentEnrolment)
			return $this->studentEnrolment;
		$this->studentEnrolment = new studentEnrolment($this->view);
		$data = array('gibbonPersonID' => $this->record->gibbonPersonID);
		$this->studentEnrolment->findBy($data);
		return $this->studentEnrolment;
	}

	/**
	 * get Roll Group
	 *
	 * @version	11th August 2016
	 * @since	11th August 2016
	 * @return	rollGroup
	 */
	public function getRollGroup()
	{
		if ($this->rollGroup instanceof rollGroup)
			return $this->rollGroup;
		$this->studentEnrolment = $this->getStudentEnrolment();
		$this->rollGroup = new rollGroup($this->view);
		$data = array('gibbonRollGroupID' => $this->studentEnrolment->getField('gibbonRollGroupID'));
		$this->rollGroup->findBy($data);
		return $this->rollGroup;
	}
}
