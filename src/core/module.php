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
namespace Gibbon\core;

use Gibbon\core\view ;

/**
 * Module
 *
 * @version	16th May 2016
 * @since	15th May 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class module extends helper
{
	/**
	 * Is Module Accessible
	 *
	 * @version	27th June 2016
	 * @since	copied from functions.php
	 * @params	string	$address	Address of Module
	 * @params	Gibbon\view		$view
	 * @return	boolean
	 */
	static public function isModuleAccessible($address = '', view $view) {
		
		if (empty($address)) $address = self::getSession()->get("address");

		//Check user is logged in
		if (self::getSession()->notEmpty("username")) {
			//Check user has a current role set
			if (self::getSession()->notEmpty("gibbonRoleIDCurrent")) {
				//Check module ready
				$moduleID = self::checkModuleReady($address, $view);
				if ($moduleID) {
					$data=array("gibbonRoleID"=>self::getSession()->get("gibbonRoleIDCurrent"), "moduleID"=>$moduleID);
					$sql="SELECT * 
						FROM gibbonAction, gibbonPermission, gibbonRole 
						WHERE (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) 
							AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) 
							AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) 
							AND (gibbonAction.gibbonModuleID=:moduleID)" ;
					$result = self::getPDO()->executeQuery($data, $sql);
					if ($result->rowCount()>0) return true ;
				}
			}
		}
		return false ;
	}

	/**
	 * Check Module Ready
	 *
	 * Using the current address, checks to see that a module exists and is ready to use, returning the ID if it is
	 * @version	21st April 2016
	 * @since	21st April 2016
	 * @param	string		$address	Address
	 * @params	Gibbon\view		$view
	 * @return	mixed		ModuleID or false
	 */
	static public function checkModuleReady($address, view $view)
	{
		//Get module name from address
		$module = self::getModuleName($address, $view) ;
		$data = array("name" => $module, 'active' => 'Y');
		$mObj = new \Gibbon\Record\module($view);
		$mod = $mObj->findBy($data);
		if ($mObj->getSuccess() && $mObj->rowCount() == 1)
			return $mod->gibbonModuleID ;
		return false ;
	}

	/**
	 * Get Module Name
	 *
	 * Get the module name from the address
	 * @version	21st April 2016
	 * @since	21st April 2016
	 * @param	string		$address Address
	 * @return	string		Name
	 */
	static public function getModuleName($address) {
		if (strpos($address, '/modules/') !== false)
			return substr(substr($address,9),0,strpos(substr($address,9),"/")) ;
		return '';
	}

	/**
	 * Get Module Entry
	 *
	 * Get the module entry point from the address
	 * @version	16th May 2016
	 * @since	21st April 2016
	 * @param	string		$address Address
	 * @param	Gibbon\view	$view
	 * @return	string		URL
	 */
	static public function getModuleEntry($address, view $view)
	{
		$output = false ;
		$pdo = $view->getPDO();
		$session = $view->getSession();
		
		$data = array("moduleName"=>module::getModuleName($address),"gibbonRoleID"=>$session->get("gibbonRoleIDCurrent"));
		$sql = "SELECT DISTINCT gibbonModule.name, gibbonModule.category, gibbonModule.entryURL 
			FROM `gibbonModule`, gibbonAction, gibbonPermission 
			WHERE gibbonModule.name=:moduleName 
				AND (active='Y') 
				AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) 
				AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) 
				AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) 
			ORDER BY category, name";
		$result = $pdo->executeQuery($data, $sql);
		if ($result->rowCount()==1) {
			$row = $result->fetch() ;
			$entryURL = $row["entryURL"] ;
			if ($view->getSecurity()->isActionAccessible("/modules/" . $row["name"] . "/" . $entryURL, NULL, '')==FALSE AND $entryURL!="index.php") {
				$dataEntry=array("gibbonRoleID"=>$session->get("gibbonRoleIDCurrent"), "moduleName"=>$row["name"]);
				$sqlEntry="SELECT DISTINCT gibbonAction.entryURL 
					FROM gibbonModule, gibbonAction, gibbonPermission 
					WHERE (active='Y') 
						AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) 
						AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) 
						AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) 
						AND gibbonModule.name=:moduleName 
					ORDER BY gibbonAction.name";
				$resultEntry=$pdo->executeQuery($dataEntry, $sqlEntry);
			}
		}
	
		if (! empty($entryURL)) {
			$output = $entryURL ;
		}
		return $output ;
	}

	/**
	 * get ModuleID from Name
	 *
	 * @version	4th July 2016
	 * @since	copied from functions.php
	 * @param	string		$address Address
	 * @param	Gibbon\view	$view
	 * @return	integer		ModuleID
	 */
	public static function getModuleIDFromName($name, view $view)
	{
		$mObj = new \Gibbon\Record\module($view);
		$row = $mObj->findOneBy(array('name' => $name));
		return $row->gibbonModuleID;
	}
}
