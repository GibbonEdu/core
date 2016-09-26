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
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
*/
/**
 */
namespace Gibbon\Record ;

/**
 * Scale Record
 *
 * @version	9th September 2016
 * @since	5th May 2016
 */
class scale extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonScale';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonScaleID';
	
	/**
	 * Unique Test
	 *
	 * @version	9th September 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$required = array('name', 'nameShort', 'usage', 'active', 'numeric');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		$v = clone $this;
		$roles = $v->findAll("SELECT * 
			FROM `gibbonScale` 
			WHERE (`name` = :name OR `nameShort` = :nameShort) 
				AND NOT `gibbonScaleID` = :gibbonScaleID", 
			array('gibbonScaleID' => $this->record->gibbonScaleID));
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', $this->table, (array)$this->returnRecord()) ;
		return true ;

	}

	/**
	 * can Delete
	 *
	 * @version	20th June 2016
	 * @since	20th June 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}
	
	/**
	 * get Scale Grade
	 *
	 * @version	18th July 2016
	 * @since 	18th July 2016
	 * @return	array	Scale Grades
	 */
	public function getScaleGrades()
	{
		$sgObj = new scaleGrade($this->view);
		$x = $sgObj->findAll("SELECT * 
				FROM `gibbonScaleGrade` 
				WHERE `gibbonScaleID` = :gibbonScaleID 
				ORDER BY `sequenceNumber`", 
			array("gibbonScaleID" => $this->record->gibbonScaleID));
		if (empty($x)) $x = array();
		return $x ;
	}

	/**
	 * delete Record
	 *
	 * @version	18th July 2016
	 * @since	18th July 2016
	 * @param	integer		$id Record ID
	 * @return	boolean		Deleted Correctly
	 */
	public function deleteRecord($id)
	{
		$scaleGrades = $this->getScaleGrades();
		$ok = true;
		foreach($scaleGrades as $grade)
			if ($ok && ! $grade->deleteRecord($grade->getField('gibbonScaleGradeID'))) $ok = false;
		if ($ok)
			$ok = parent::deleteRecord($id);
		return $ok ;
	}

	/**
	 * get Grade
	 *
	 * @version	18th July 2016
	 * @since	18th July 2016
	 * @param	integer/string	$id
	 * @return	scaleGrade		
	 */
	public function getGrade($id)
	{
  		
		$sgObj = new scaleGrade($this->view, intval($id));
		if ($sgObj->getField('gibbonScaleID') !== $this->record->gibbonScaleID)
		{
			$sgObj->defaultRecord();
			$sgObj->setField('gibbonScaleID', $this->record->gibbonScaleID);
		}
		$this->scaleGrade = $sgObj;
		return $sgObj;
	}

	/**
	 * Default Record
	 *
	 * @version	18th July 2016
	 * @since	18th July 2016
	 * @return	stdClass	Record
	 */
	public function defaultRecord()
	{
		parent::defaultRecord();
		$this->setField('active', 'N');
		$this->setField('numeric', 'N');
		return $this->record ;
	}
}
