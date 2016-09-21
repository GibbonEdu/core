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
 * Module Record
 *
 * @version	6th September 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @todo	Add sort ability to Category Field (but not enum.)
 * @package		Gibbon
 * @subpackage	Record
 */
class module extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonModule';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonModuleID';
	
	/**
	 * @var	array	$actions	Actions for this Module
	 */
	public $actions ;
	
	/**
	 * @var	array	$hooks		Hooks for this Module
	 */
	public $hooks ;
	
	/**
	 * @var	array	$settings	Settings for this Module
	 */
	public $settings ;
	
	/**
	 * Unique Test
	 *
	 * @version	6th September 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', 'Module') ;
        $required = array('category','active','name','type');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', 'Module', array($name)) ;
		$data = array('name' => $this->record->name, 'moduleID' => $this->record->gibbonModuleID);
		$sql = 'SELECT * 
			FROM `gibbonModule` 
			WHERE `name` = :name 
				AND NOT `gibbonModuleID` = :moduleID';
		$v = clone $this;
		$roles = $v->findAll($sql, $data);
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', 'Module', array((array)$this->returnRecord())) ;
		return true ;
	}
	
	/**
	 * find
	 *
	 * @version	9th May 2016
	 * @since	9th May 2016
	 * @return	integer		$id	Module ID
	 * @return	mixed		false or Record Object
	 */
	public function find($id)
	{
		if (parent::find($id)) {
			$this->getActions($id);
			$this->getHooks($id);
			$this->getSettings($this->getField('name'));
			return $this->record;
		}
		return false ;
	}
		
	/**
	 * get Actions
	 *
	 * @version	23rd July 2016
	 * @since	9th May 2016
	 * @return	integer		$id	Module ID
	 * @return	array of Gibbon\Record\action
	 */
	public function getActions($id)
	{
		$sql = 'SELECT `gibbonActionID` 
			FROM `gibbonAction` 
			WHERE `gibbonModuleID` = :moduleID 
			ORDER BY `category`, `precedence` DESC, `name`' ;
		$v = new action($this->view) ;
		$actions = $v->findAll($sql, array('moduleID' => $id));
		$this->actions = array();
		foreach($actions as $q=>$w)
			$this->actions[$q] = new action($this->view, $q);
		return $this->actions;
	}
		
	/**
	 * get Hooks
	 *
	 * @version	9th May 2016
	 * @since	9th May 2016
	 * @return	integer		$id	Module ID
	 * @return	void
	 */
	public function getHooks($id)
	{
		$sql = 'SELECT `gibbonHookID` FROM `gibbonHook` WHERE `gibbonModuleID` = ' . intval($id) ;
		$result = $this->result ;
		$this->executeQuery(array(), $sql);
		$x = $this->result->fetchAll(\PDO::FETCH_CLASS);
		$this->hooks = array();
		foreach($x as $w)
			$this->hooks[$w->gibbonHookID] = new hook($this->view, $w->gibbonHookID);
		$this->result = $result ;
	}
		
	/**
	 * get Settings
	 *
	 * @version	9th May 2016
	 * @since	9th May 2016
	 * @return	string	$scope	Module Name
	 * @return	void
	 */
	public function getSettings($scope)
	{
		$sql = 'SELECT `gibbonSystemSettingsID` FROM `gibbonSetting` WHERE `scope` = "'.$scope.'"';
		$result = $this->result ;
		$this->executeQuery(array(), $sql);
		$x = $this->result->fetchAll(\PDO::FETCH_CLASS);
		$this->settings = array();
		foreach($x as $w)
			$this->settings[$w->gibbonSystemSettingsID] = new setting($this->view, $w->gibbonSystemSettingsID);
		$this->result = $result ;
	}

	/**
	 * delete Record
	 *
	 * @version	9th May 2016
	 * @since	9th May 2016
	 * @param	integer		$id Module ID
	 * @return	boolean		Deleted Correctly
	 */
	public function deleteRecord($id)
	{
		if ($this->canDelete())
		{
			foreach($this->actions as $actionID=>$record)
				$this->actions[$actionID]->deleteRecord($actionID);
			foreach($this->hooks as $hookID=>$record)
				$this->hooks[$hookID]->deleteRecord($hookID);
			foreach($this->settings as $settingID=>$record)
				$this->settings[$settingID]->deleteRecord($settingID);
			return parent::deleteRecord($id);
		}
	}

	/**
	 * get Action By Role
	 *
	 * Removes any Action that fails to meet the Given Role.  
	 * @version	25th June 2016
	 * @since	16th May 2016
	 * @param	integer		$role Role ID
	 * @return	void
	 */
	public function getActionByRole($role = null)
	{
		$role = intval($role);
		foreach($this->actions as $q=>$action) 
		{
			$delete = true;
			foreach($action->permissions as $p)
			{
				if (intval($p->getField('gibbonRoleID')) === $role)
				{
					$delete = false ;
					break ;
				}
			}
			if ($delete)
				unset($this->actions[$q]);
		}
	}

	/**
	 * can Delete
	 *
	 * @version	6th September 2016
	 * @since	25th May 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		if ($this->record->type === 'Core')
			return false ;
		return true;
	}
}
?>