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
 * External Assessment Record
 *
 * @version	25th September 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class externalAssessment extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonExternalAssessment';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonExternalAssessmentID';
	
	/**
	 * Unique Test
	 *
	 * @version	25th September 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$required = array('name', 'nameShort', 'description', 'active') ;
		foreach ($required as $name)
			if (empty($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug',  $this->table, array($name)) ;

		$data = array("name" => $this->record->name, "nameShort" => $this->record->nameShort, "gibbonExternalAssessmentID" => $this->record->gibbonExternalAssessmentID ); 
		$sql = "SELECT * 
			FROM `gibbonExternalAssessment` 
			WHERE (`name` = :name OR `nameShort` = :nameShort) 
				AND NOT `gibbonExternalAssessmentID` = :gibbonExternalAssessmentID" ;
		$v = clone $this ;
		if (count($v->findAll($sql, $data)) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', $this->table, array((array)$this->returnRecord())) ; ;
		return true ;
	}
	
	/**
	 * Can Delete
	 *
	 * @version	15th July 2016
	 * @since	15th July 2016
	 * @return	boolean
	 */
	public function canDelete()
	{
		return true ;
	}
	
	/**
	 * get Fields
	 *
	 * @version	18th July 2016
	 * @since	18th July 2016
	 * @return	array
	 */
	public function getFields()
	{
		$data = array("gibbonExternalAssessmentID" => $this->record->gibbonExternalAssessmentID ); 
		$sql = "SELECT * 
			FROM `gibbonExternalAssessmentField` 
			WHERE `gibbonExternalAssessmentID` = :gibbonExternalAssessmentID 
			ORDER BY `category`, `order`" ; 
		$eafObj = new externalAssessmentField($this->view);
		$eafList = $eafObj->findAll($sql, $data);
		if (empty($eafList)) $eafList = array();
		$this->fieldList = $eafList ;
		return $eafList ;
	}
	
	/**
	 * delete Record
	 *
	 * @version	19th July 2016
	 * @since	19th July 2016
	 * @param	integer		$id		gibbonExternalAssessmentID
	 * @return	boolean
	 */
	public function deleteRecord($id)
	{
		$this->find($id);
		$eafList = $this->getFields() ;
		foreach($eafList as $fieldID=>$eafObj)
			if (! $eafObj->deleteRecord($fieldID)) return false ;
		return parent::deleteRecord($id);
	}
}