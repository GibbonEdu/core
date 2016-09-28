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

use Gibbon\Record\space ;
use Gibbon\People\staff ;
/**
 * Roll Group Record
 *
 * @version	28th September 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class rollGroup extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonRollGroup';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonRollGroupID';
	
	/**
	 * @var	Gibbon\Record\person	
	 */
	protected	$tutor1 ;
	
	/**
	 * @var	Gibbon\People\staff	
	 */
	protected	$tutor2 ;
	
	/**
	 * @var	Gibbon\People\staff	
	 */
	protected	$tutor3 ;
	
	/**
	 * @var	array of Gibbon\People\staff	
	 */
	protected	$tutors ;
	
	/**
	 * @var	Gibbon\Record\space	
	 */
	protected	$space ;
	
	/**
	 * @var	Gibbon\Record\rollGroup	
	 */
	protected	$rollGroupNext ;
	
	/**
	 * Unique Test
	 *
	 * @version	28th September 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
        $required = array('name', 'nameShort');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		if (empty($this->record->gibbonSpaceID))
		{
			$data = array(	'name' => $this->record->name, 
							'nameShort' => $this->record->nameShort, 
							'gibbonRollGroupID' => $this->record->gibbonRollGroupID, 
							'gibbonSchoolYearID' => $this->record->gibbonSchoolYearID
						);
			$sql = 'SELECT * 
				FROM `gibbonRollGroup` 
				WHERE (`name`=:name OR `nameShort` = :nameShort) 
					AND NOT `gibbonRollGroupID` = :gibbonRollGroupID 
					AND `gibbonSchoolYearID` = :gibbonSchoolYearID';
		} else {
			$data = array(	'name' => $this->record->name, 
							'nameShort' => $this->record->nameShort, 
							'gibbonSpaceID' => $this->record->gibbonSpaceID, 
							'gibbonRollGroupID' => $this->record->gibbonRollGroupID, 
							'gibbonSchoolYearID' => $this->record->gibbonSchoolYearID
						);
			$sql = 'SELECT * 
				FROM `gibbonRollGroup` 
				WHERE (`name` = :name OR `nameShort` = :nameShort OR `gibbonSpaceID` = :gibbonSpaceID) 
					AND NOT `gibbonRollGroupID` = :gibbonRollGroupID 
					AND `gibbonSchoolYearID` = :gibbonSchoolYearID';
		}
		$v = clone $this;
		$roles = $v->findAll($sql, $data);
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', $this->table, array((array)$this->returnRecord())) ;
		return true ;
	}
	
	/**
	 * find
	 *
	 * @version	24th May 2016
	 * @since	24th May 2016
	 * @param	integer		$id		Role Group ID
	 * @return	stdClass	Record
	 */
	public function find($id)
	{
		if (parent::find($id))
		{
			return $this->record;
		}
		return false ;
	}
		
	/**
	 * get Tutor 1
	 *
	 * @version	24th May 2016
	 * @since	24th May 2016
	 * @return	Gibbon\People\staff
	 */
	public function getTutor1()
	{
		if ($this->tutor1 instanceof staff)
			return $this->tutor1 ;
		return $this->tutor1 = new staff($this->view, $this->record->gibbonPersonIDTutor);
	}
	
	/**
	 * get Tutor 1
	 *
	 * @version	24th May 2016
	 * @since	24th May 2016
	 * @return	Gibbon\People\staff
	 */
	public function getTutor2()
	{
		if ($this->tutor2 instanceof staff)
			return $this->tutor2 ;
		return $this->tutor2 = new staff($this->view, $this->record->gibbonPersonIDTutor2);
	}
	
	/**
	 * get Tutor 1
	 *
	 * @version	24th May 2016
	 * @since	24th May 2016
	 * @return	Gibbon\People\staff
	 */
	public function getTutor3()
	{
		if ($this->tutor3 instanceof staff)
			return $this->tutor3 ;
		return $this->tutor3 = new staff($this->view, $this->record->gibbonPersonIDTutor3);
	}
	
	/**
	 * get Space
	 *
	 * @version	24th May 2016
	 * @since	24th May 2016
	 * @return	Gibbon\Record\space
	 */
	public function getSpace()
	{
		if ($this->space instanceof space)
			return $this->space ;
		return $this->space = new space($this->view, $this->record->gibbonSpaceID);
	}
	
	/**
	 * get Space
	 *
	 * @version	7th September 2016
	 * @since	24th May 2016
	 * @return	Gibbon\Record\space
	 */
	public function getRollGroupNext()
	{
		if ($this->rollGroupNext instanceof rollGroup)
			return $this->rollGroupNext ;
		return $this->rollGroupNext = new rollGroup($this->view, $this->record->gibbonRollGroupIDNext);
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

	/**
	 * Get Tutors
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @param	array			
	 */
	public function getTutors()
	{
		$this->getTutor1();
		$this->getTutor2();
		$this->getTutor3();
		$this->tutors = array();
		if ($this->tutor1->getSuccess() && $this->record->gibbonPersonIDTutor > 0)
			$this->tutors[1] = $this->tutor1;
		if ($this->tutor2->getSuccess() && $this->record->gibbonPersonIDTutor2 > 0)
			$this->tutors[2] = $this->tutor2;
		if ($this->tutor3->getSuccess() && $this->record->gibbonPersonIDTutor3 > 0)
			$this->tutors[3] = $this->tutor3;
		return $this->tutors;
	}
}
