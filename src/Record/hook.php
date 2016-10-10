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
 * Hook Record
 *
 * @version	9th October 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class hook extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonHook';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonHookID';
	
	/**
	 * Unique Test
	 *
	 * @version	5th May 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		return false ;
	}

	/**
	 * can Delete
	 *
	 * @version	27th May 2016
	 * @since	27th May 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return false;
	}

	/**
	 * is Permitted
	 *
	 * Is the current user allowed access to the Hook
	 * @version	27th May 2016
	 * @since	27th May 2016
	 * @return	boolean		
	 */
	public function isPermitted()
	{
		$options = unserialize($this->record->options);
		$data = array(
						'gibbonRoleIDCurrent' => $this->view->session->get('gibbonRoleIDCurrent'), 
						'ModuleName1' => $options['sourceModuleName'], 
						'ModuleName2' => $options['sourceModuleName'], 
						'moduleAction'=>$options['sourceModuleAction'],
						'type'=>'Student Profile'
					);
		$sql = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action 
			FROM gibbonHook 
			JOIN gibbonModule ON gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID 
			JOIN gibbonAction ON gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID 
			JOIN gibbonPermission ON gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID
		WHERE gibbonModule.name= :moduleName2 
			AND  gibbonAction.name= :moduleAction
			AND gibbonAction.gibbonModuleID=(
				SELECT gibbonModuleID 
					FROM gibbonModule 
					WHERE gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent 
						AND name = :ModuleName1) 
			AND gibbonHook.type = :type
		ORDER BY `name`";
		$v = clone($this);
		$w = $v->findAll($sql, $data);
		if ($v->getSuccess() && count($w) === 1)
			return true;
		return false ;
	}

	/**
	 * get Units (from Options)
	 *
	 * @version	24th August 2016
	 * @since	24th August 2016
	 * @param	integer		$unitsID
	 * @param	integer		$courseClassID
	 * @return	array		Unit Results
	 */
	public function getUnits($unitID, $courseClassID)
	{
		$output = array();

		$options = unserialize($this->record->options);
		if (! empty($options['unitTable']) && 
			! empty($options['unitIDField']) && 
			! empty($options['unitCourseIDField']) && 
			! empty($options['unitNameField']) && 
			! empty($options['unitDescriptionField']) && 
			! empty($options['classLinkTable']) && 
			! empty($options['classLinkJoinFieldUnit']) && 
			! empty($options['classLinkJoinFieldClass']) && 
			! empty($options['classLinkIDField'])) {
			$sql = 'SELECT * 
				FROM `'.$options['unitTable'].'` 
					JOIN `'.$options['classLinkTable'].'` ON `'.$options['unitTable'].'`.`'.$options['unitIDField'].'` = `'.$options['classLinkTable'].'`.`'.$options['classLinkJoinFieldUnit'].'` 
				WHERE `'.$options['unitTable'].'`.`'.$options['unitIDField'].'` = :unitID 
					AND `'.$options['classLinkJoinFieldClass'].'` = :courseClassID 
				ORDER BY `'.$options['classLinkTable'].'`.`'.$options['classLinkIDField'].'`';
			$data = array('courseClassID' => $courseClassID, 'unitID' => $unitID);
			$r = $this->findAll($sql, $data);
			if ($this->getSuccess() && count($r) == 1) {
				$w = reset($r);
				$output[0] = $w->getField($options['unitNameField']);
				$output[1] = $w->getField('name');
			}
		}
		return $output ;
	}
	
	/**
	 * find All By Type
	 *
	 * @version	28th September 2016
	 * @since	28th September 2016
	 * @param	string		$type
	 * @return	array of stdClass
	 */
	public function findAllByType($type)
	{
		return $this->findAllBy(array('type' => $type), array('name'=>'ASC'));
	}
	
	/**
	 * include Hook
	 *
	 * @version	9th October 2016
	 * @since	9th October 2016
	 * @param	string		$q
	 * @param	boolean		$errorDisplay
	 * @return	string
	 */
	public function includeHook($q, $errorDisplay = true)
	{
		if (file_exists(GIBBON_ROOT . 'src' . $q))
			return include GIBBON_ROOT . 'src' . $q ;
		elseif (file_exists(GIBBON_ROOT . ltrim($q, '/')))
			return include GIBBON_ROOT . ltrim($q, '/') ;
		if ($errorDisplay)
			return $this->view->returnMessage('The selected page cannot be displayed due to a hook error.');
	}
}
