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
 * Department Record
 *
 * @version	27th September 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class department extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonDepartment';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonDepartmentID';
	
	/**
	 * @var	array	$departmentStaff	Department Staff
	 */
	protected $departmentStaff;
	
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
		$required = array('name', 'nameShort');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		return true ;
	}
	
	/**
	 * find
	 *
	 * @version	13th July 2016
	 * @since	11th May 2016
	 * @param	integer		$id	
	 * @return	mixed	false or Record Object
	 */
	public function find($id)
	{
		if (parent::find($id)) {
			$this->getDepartmentStaff($id) ;
			return $this->record ;
		}
		return false ;
	}
	
	/**
	 * get Department Staff
	 *
	 * @version	13th July 2016
	 * @since	5th May 2016
	 * @param	integer		$id	Course ID
	 * @return	void
	 */
	public function getDepartmentStaff($id)
	{
		$v = clone $this ;
		$sql = 'SELECT `gibbonDepartmentStaffID` 
			FROM `gibbonDepartmentStaff` 
			WHERE `gibbonDepartmentID` = ' . intval($id) ;
		$v->executeQuery(array(), $sql);
		$x = $v->result->fetchAll(\PDO::FETCH_CLASS);
		$this->departmentStaff = array();
		foreach($x as $w)
			$this->departmentStaff[$w->gibbonDepartmentStaffID] = new departmentStaff($this->view, $w->gibbonDepartmentStaffID);
	}

	/**
	 * can Delete
	 *
	 * @version	12th July 2016
	 * @since	12th July 2016
	 * @return	boolean
	 */
	public function canDelete()
	{
		return true ;
	}
	
	/**
	 * inject Post
	 *
	 * @version	13th July 2016
	 * @since	12th May 2016
	 * @return	boolean
	 */
	public function injectPost($data = null)
	{
		$this->imageFail = false ;
		$department = filter_var($_POST['name']);
		$_POST['logo'] = $this->managePhoto('file', 'logo', $department.'-Logo', array(125, 125));
		return parent::injectPost();
	}
	
	/**
	 * inject Department Staff
	 *
	 * @version	14th July 2016
	 * @since	13th July 2016
	 * @return	boolean
	 */
	protected function injectDepartmentStaff()
	{
		if (! isset($_POST['staff'])) return true ;
		$ok = true;
		if (is_array($_POST['staff']) && isset($_POST['role'])) {
			foreach ($_POST['staff'] as $personID)
			{
				$dsObj = new departmentStaff($this->view);
				$dsObj->setField('gibbonDepartmentID', $this->record->gibbonDepartmentID);
				$dsObj->setField('gibbonPersonID', $personID);
				$dsObj->setField('role', $_POST['role']);
				if ($dsObj->uniqueTest())
					if (! $dsObj->writeRecord()) 
						$ok = false ;
			}
		}
		return $ok;
	}
	
	/**
	 * remove Department Staff
	 *
	 * @version	14th July 2016
	 * @since	14th July 2016
	 * @return	boolean
	 */
	protected function removeDepartmentStaff()
	{
		if (! isset($_POST['deleteStaff'])) return true ;
		if (is_array($_POST['deleteStaff'])) {
			$dsObj = new departmentStaff($this->view);
			foreach ($_POST['deleteStaff'] as $departmentStaffID)
			{
				$dsObj->findOneBy(array('gibbonDepartmentID' => $this->record->gibbonDepartmentID, 'gibbonDepartmentStaffID' => $departmentStaffID));
				if ($dsObj->getSuccess())
					if (!$dsObj->deleteRecord($dsObj->getField('gibbonDepartmentStaffID'))) return false;
			}
		}
		return true;
	}
	
	/**
	 * delete Record
	 *
	 * @version	14th July 2016
	 * @since	14th July 2016
	 * @param	integer		$id		Department ID
	 * @return	boolean
	 */
	public function deleteRecord($id)
	{
		$this->getDepartmentStaff($id);
		if (is_array($this->departmentStaff))
			foreach($this->departmentStaff as $q=>$w)
				if (! $w->deleteRecord($q)) return false ;
		return parent::deleteRecord($id);
	}
	
	/**
	 * write Record
	 *
	 * @version	14th July 2016
	 * @since	14th July 2016
	 * @return	boolean
	 */
	public function writeRecord()
	{
		if (! parent::writeRecord()) return false ;
		if (! $this->removeDepartmentStaff()) return false ;
		if (! $this->injectDepartmentStaff()) return false ;
		return true ;
	}
}
