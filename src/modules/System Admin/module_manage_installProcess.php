<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010);
$action->setField(Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation);
$action->setField(either version 3 of the License);
$action->setField(or
(at your option) any later version.

This program is distributed in the hope that it will be useful);
$action->setField(but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Module\System_Admin ;

use Gibbon\core\view ;
use Gibbon\core\helper ;
use Gibbon\core\trans ;
use Gibbon\Record\module ;
use Gibbon\Record\permission ;
use Gibbon\Menu\main ;
use Gibbon\Record\action ;

if (! $this instanceof view) die();

//Get URL from calling page and set returning URL
$URL = GIBBON_URL . "index.php?q=/modules/System Admin/module_manage.php" ;
$this->session->clear("moduleInstallError");

if (! $this->getSecurity()->isActionAccessible("/modules/System Admin/module_manage.php")) {
	$this->insertMessage('return.error.0');
	$this->redirect($URL);
}
else {

	$moduleName = isset($_GET["name"]) ? $_GET["name"] : null ;

	if (empty($moduleName)) {
		$this->insertMessage("Install failed because either the module name was not given or the manifest file was invalid.");
		$this->redirect($URL);
	}
	else
	{
		if (! file_exists(GIBBON_ROOT . "src/modules/".$moduleName.'/manifest.php')) {
			$this->insertMessage("Install failed because either the module name was not given or the manifest file was invalid.");
			$this->redirect($URL);
		}
		else
		{
			include GIBBON_ROOT . 'src/modules/'.$moduleName.'/manifest.php';
			if (empty($name) || empty($description) || $type != "Additional" || $this->config->isEmpty('version')) {
				$this->insertMessage('return.error.1');
				$this->redirect($URL);
			}
			else {
				$module = new module($this);
				$partialFail = false ;
				
				//Check for existence of module
				if (is_null($module->findBy(array('name'=>$name)))) {
					$module->setField("name", $name);
					$module->setField("description", $description);
					$module->setField("entryURL", $entryURL);
					$module->setField("type", $type);
					$module->setField("category", $category);
					$module->setField("version", $version);
					$module->setField("author", $author);
					$module->setField("url", $url);
					//Insert new module row
					if (! $module->writeRecord()) { 
						$this->insertMessage("return.error.2");
						$this->redirect($URL);
					}
					
					$moduleID = $module->getField('gibbonModuleID') ;
					
					//Create module tables
					//Whilst this area is intended for use setting up module tables, arbitrary sql can be run at the wish of the module developer. However, such actions are not cleaned up by the uninstaller.
					$partialFail = false ;
					$moduleTables = empty($moduleTables) ? array() : $moduleTables ;
					foreach($moduleTables as $i=>$query) {
						$result = $this->pdo->query($query);   
						if (! $this->pdo->getQuerySuccess()) {
							$this->session->append("moduleInstallError", $query . "<br/><b>" . $this->pdo->getError() . "</b></br><br/>") ; 
							$partialFail = true ;
						}
					}
					//Create gibbonSetting entries
					//Whilst this area is intended for use setting up gibbonSetting entries, arbitrary sql can be run at the wish of the module developer. However, such actions are not cleaned up by the uninstaller.
					if ( empty($gibbonSetting)) $gibbonSetting = array();
					foreach($gibbonSetting as $i=>$sql) {
						$result = $this->pdo->query($sql);   
						if (! $this->pdo->getQuerySuccess()) {
							$this->session->append("moduleInstallError", $sql . "<br/><b>" . $this->pdo->getError() . "</b></br><br/>" ); 
							$partialFail = true ;
						}
					}
					//Create module actions
					if (empty($actionRows)) $actionRows = array();
					foreach ($actionRows as $i=>$value) {
						$categoryPermissionStaff="Y" ;
						$categoryPermissionStudent="Y" ;
						$categoryPermissionParent="Y" ;
						$categoryPermissionOther="Y" ;
						if (isset($actionRows[$i]["categoryPermissionStaff"])) {
							if ($actionRows[$i]["categoryPermissionStaff"]=="N") {
								$categoryPermissionStaff="N" ;
							}
						}
						if (isset($actionRows[$i]["categoryPermissionStudent"])) {
							if ($actionRows[$i]["categoryPermissionStudent"]=="N") {
								$categoryPermissionStudent="N" ;
							}
						}
						if (isset($actionRows[$i]["categoryPermissionParent"])) {
							if ($actionRows[$i]["categoryPermissionParent"]=="N") {
								$categoryPermissionParent="N" ;
							}
						}
						if (isset($actionRows[$i]["categoryPermissionOther"])) {
							if ($actionRows[$i]["categoryPermissionOther"]=="N") {
								$categoryPermissionOther="N" ;
							}
						}
						$entrySidebar="Y" ;
						if (isset($actionRows[$i]["entrySidebar"])) {
							if ($actionRows[$i]["entrySidebar"]=="N") {
								$entrySidebar="N" ;
							}
						}
						$menuShow="Y" ;
						if (isset($actionRows[$i]["menuShow"])) {
							if ($actionRows[$i]["menuShow"]=="N") {
								$menuShow="N" ;
							}
						}
						
						$action = new action($this);
						$action->setField("gibbonModuleID", $moduleID);
						$action->setField("name", $actionRows[$i]["name"]);
						$action->setField("precedence", $actionRows[$i]["precedence"]);
						$action->setField("category", $actionRows[$i]["category"]);
						$action->setField("description", $actionRows[$i]["description"]);
						$action->setField("URLList", $actionRows[$i]["URLList"]);
						$action->setField("entryURL", $actionRows[$i]["entryURL"]);
						$action->setField("entrySidebar", $entrySidebar);
						$action->setField("defaultPermissionAdmin", $actionRows[$i]["defaultPermissionAdmin"]);
						$action->setField("defaultPermissionTeacher", $actionRows[$i]["defaultPermissionTeacher"]);
						$action->setField("defaultPermissionStudent", $actionRows[$i]["defaultPermissionStudent"]);
						$action->setField("defaultPermissionParent", $actionRows[$i]["defaultPermissionParent"]);
						$action->setField("defaultPermissionSupport", $actionRows[$i]["defaultPermissionSupport"]);
						$action->setField("categoryPermissionStaff", $categoryPermissionStaff);
						$action->setField("categoryPermissionStudent", $categoryPermissionStudent);
						$action->setField("categoryPermissionParent", $categoryPermissionParent);
						$action->setField("categoryPermissionOther", $categoryPermissionOther); 
						
						if (! $action->writeRecord()) {
							$this->session->append("moduleInstallError", "Action: <br/><b>" . $e->getMessage() . "</b></br><br/>" ); 
							$partialFail = true ;
						}
					}
					
					if (is_null($rowActions = (array) $action->findBy(array("gibbonModuleID"=>$moduleID)))) {
						$this->insertMessage('warning1');
						$this->redirect($URL);
					}
					do  {
						$permission = new permission($this);
						$rowActions = (array) $rowActions;
						if ($rowActions["defaultPermissionAdmin"]=="Y") {
							$permission->setField("gibbonActionID", $rowActions["gibbonActionID"]);
							$permission->setField("gibbonRoleID", 1);
							if (! $permission->writeRecord()) {
								$this->session->append("moduleInstallError", "Permission Admin: <br/><b>" . $permission->getError() . "</b></br><br/>" ) ; 
								$partialFail = true ;
							}
						}
						if ($rowActions["defaultPermissionTeacher"]=="Y") {
							$permission->setField("gibbonActionID", $rowActions["gibbonActionID"]);
							$permission->setField("gibbonRoleID", 2);
							if (! $permission->writeRecord()) {
								$this->session->append("moduleInstallError",  "Permission Teacher: <br/><b>" . $permission->getError() . "</b></br><br/>" ); 
								$partialFail = true ;
							}
						}
						if ($rowActions["defaultPermissionStudent"]=="Y") {
							$permission->setField("gibbonActionID", $rowActions["gibbonActionID"]);
							$permission->setField("gibbonRoleID", 3);
							if (! $permission->writeRecord()) {
								$this->session->append("moduleInstallError", "Permission Student: <br/><b>" . $permission->getError() . "</b></br><br/>" ); 
								$partialFail = true ;
							}
						}
						if ($rowActions["defaultPermissionParent"]=="Y") {
							$permission->setField("gibbonActionID", $rowActions["gibbonActionID"]);
							$permission->setField("gibbonRoleID", 4);
							if (! $permission->writeRecord()) {
								$this->session->append("moduleInstallError", "Permission Parent: <br/><b>" . $permission->getError() . "</b></br><br/>") ; 
								$partialFail = true ;
							}
						}
						if ($rowActions["defaultPermissionSupport"]=="Y") {
							$permission->setField("gibbonActionID", $rowActions["gibbonActionID"]);
							$permission->setField("gibbonRoleID", 6);
							if (! $permission->writeRecord()) {
								$this->session->append("moduleInstallError", "Permission Support Staff: <br/><b>" . $permission->getError() . "</b></br><br/>") ; 
								$partialFail = true ;
							}
						}
					} while ($rowActions = $action->next() );
					
					//Create hook entries
					if (empty($hooks)) $hooks = array();
					foreach ($hooks as $i=>$sql) {
						$result=$this->pdo->query($sql);   
						if (! $this->pdo->getQuerySuccess()) { 
							$this->session->append("moduleInstallError", $sql . "<br/><b>" . $e->getMessage() . "</b></br><br/>" ); 
							$partialFail = true ;
						}
					}
					
					//The reckoning!
					if ($partialFail) {
						$this->insertMessage("Install failed, but module was added to the system and set non-active.", 'warning');
						$this->redirect($URL);
					}
					else {
						//Set module to active
						$module->setField('active', 'Y');
						if (! $module->writeRecord()) { 
							$this->insertMessage("Install was successful, but module could not be activated.", 'warning');
							$this->redirect($URL);
						}
						
						//Update main menu
						$mainMenu = new main($this);
						$mainMenu->setMenu() ;
						
						//We made it!
						$this->insertMessage('return.success.0', 'success');
						$this->redirect($URL);
					}
				}
			}
		}
	}
}
