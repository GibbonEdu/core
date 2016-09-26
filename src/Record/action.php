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
 * Action Record
 *
 * @version	23rd July 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage                     Record
 */
class action extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonAction';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonActionID';
	
	/**
	 * @var	array	$permissions	Permissions for this Action	
	 */
	public $permissions ;
	
	/**
	 * Unique Test
	 *
	 * @version	4th May 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		return true ;
	}
	
	/**
	 * find
	 *
	 * @version	9th May 2016
	 * @since	9th May 2016
	 * @return	integer		$id	Action ID
	 * @return	mixed		false or Record Object
	 */
	public function find($id)
	{
		if (parent::find($id)) {
			$this->getPermissions($id);
			return $this->record;
		}
		return false ;
	}
		
	/**
	 * get Permissions
	 *
	 * @version	23rd July 2016
	 * @since	9th May 2016
	 * @return	integer		$id	Action ID
	 * @return	void
	 */
	public function getPermissions($id)
	{
		$sql = 'SELECT `permissionID` 
			FROM `gibbonPermission` 
				WHERE `gibbonActionID` = :actionID' ;
		$v = new permission($this->view);
		$permissions = $v->findAll($sql, array('actionID' => $id));
		$this->permissions = array();
		foreach($permissions as $id=>$w)
			$this->permissions[$id] = new permission($this->view, $id);
		return $this->permissions;
	}

	/**
	 * delete Record
	 *
	 * @version	9th May 2016
	 * @since	9th May 2016
	 * @param	integer		$id Action ID
	 * @return	boolean		Deleted Correctly
	 */
	public function deleteRecord($id)
	{
		if ($this->canDelete()){
			foreach($this->permissions as $permissionID=>$record)
				$this->permissions[$permissionID]->deleteRecord($permissionID);
			return parent::deleteRecord($id);
		}
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