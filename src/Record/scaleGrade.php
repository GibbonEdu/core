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

use Gibbon\logger ;

/**
 * Scale Grade Record
 *
 * @version	9th September 2016
 * @since	5th May 2016
 */
class scaleGrade extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonScaleGrade';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonScaleGradeID';
	
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
		$required = array('value', 'descriptor', 'sequenceNumber', 'isDefault', 'gibbonScaleID');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		$data = array("value" => $this->record->value, "sequenceNumber" => $this->record->sequenceNumber, "gibbonScaleID" => $this->record->gibbonScaleID, "gibbonScaleGradeID" => $this->record->gibbonScaleGradeID); 
		$sql = "SELECT * 
			FROM `gibbonScaleGrade` 
			WHERE (`value` = :value OR `sequenceNumber` = :sequenceNumber) 
				AND `gibbonScaleID` = :gibbonScaleID 
				AND NOT `gibbonScaleGradeID` = :gibbonScaleGradeID" ;
		$v = clone $this;
		$roles = $v->findAll($sql, $data);
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', $this->table, array((array)$this->returnRecord())) ;
		return true ;
	}

	/**
	 * can Delete
	 *
	 * @version	18th July 2016
	 * @since	18th July 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}
	
	/**
	 * Write Record
	 *
	 * @version	18th July 2016
	 * @since	18th July 2016
	 * @return	boolean
	 */
	public function writeRecord()
	{
		$ok = true;
		if ($this->record->isDefault == 'Y')
		{
			$v = clone $this ;
			$data = array("gibbonScaleID" => $this->record->gibbonScaleID, "gibbonScaleGradeID" => $this->record->gibbonScaleGradeID); 
			$sql = "UPDATE `gibbonScaleGrade`
				SET `isDefault` = 'N' 
				WHERE `gibbonScaleID` = :gibbonScaleID 
					AND NOT `gibbonScaleGradeID` = :gibbonScaleGradeID" ;
			$v->executeQuery($data, $sql);
			if (! $v->getSuccess()) $ok = false ;
		}
		if ($ok && ! parent::writeRecord()) $ok = false ;
		return $ok ;
	}
}
