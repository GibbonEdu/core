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
 * Department Staff Record
 *
 * @version	27th September 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class departmentStaff extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonDepartmentStaff';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonDepartmentStaffID';
	
	/**
	 * Unique Test
	 *
	 * @version	27th September 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$required = array();
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		return true ;
		$data  = array("gibbonPersonID"=>$this->record->gibbonPersonID, "gibbonDepartmentID"=>$this->record->gibbonDepartmentID); 
		$v = clone $this; // Do not overwrite existing record.
		$x = $v->findAll("SELECT `gibbonDepartmentStaffID`
			FROM `gibbonDepartmentStaff` 
			WHERE `gibbonPersonID` = :gibbonPersonID 
				AND `gibbonDepartmentID` = :gibbonDepartmentID", $data) ;
		if (count($x)  > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', $this->table, (array)$this->returnRecord()) ;
		return true ;
	}
	
	/**
	 * can Delete
	 *
	 * @version	13th July 2016
	 * @since	13th July 2016
	 * @return	boolean
	 */
	public function canDelete()
	{
		return true ;
	}
	
	/**
	 * get Department Staff
	 *
	 * @version	13th July 2016
	 * @since	13th July 2016
	 * @return	array
	 */
	public function getDepartmentStaff($id)
	{
		$data = array("gibbonDepartmentID"=>$id); 
		$sql = "SELECT `preferredName`, `surname`, `gibbonDepartmentStaff`.* 
			FROM `gibbonDepartmentStaff` 
				JOIN `gibbonPerson` ON (`gibbonDepartmentStaff`.`gibbonPersonID` = `gibbonPerson`.`gibbonPersonID`) 
			WHERE `gibbonDepartmentID` = :gibbonDepartmentID 
				AND `gibbonPerson`.`status` = 'Full' 
			ORDER BY `surname`, `preferredName`" ; 
		return $this->findAll($sql, $data);
	}
	
	/**
	 * format Name
	 *
	 * @version	13th July 2016
	 * @since	13th July 2016
	 * @param	boolean		$reverse	Reverse
	 * @param	boolean		$informal	Informal
	 * @return	string
	 */
	public function formatName($reverse = false, $informal = false)	
	{
		$pObj = new person($this->view, $this->record->gibbonPersonID);
		return $pObj->formatName($reverse, $informal);
	}
}
