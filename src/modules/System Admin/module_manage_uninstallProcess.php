<?php
/*
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

namespace Module\System_Admin ;

use Gibbon\core\post ;
use Gibbon\core\module as helper ;
use Gibbon\core\trans ;
use Gibbon\Record\module ;

if (! $this instanceof post) die();

$orphaned = false ;
if (isset($_GET["orphaned"]) && $_GET["orphaned"]=="true") $orphaned = true ;

$moduleID = $_GET["gibbonModuleID"] ;
$URL = GIBBON_URL . "index.php?q=/modules/System Admin/module_manage_uninstall.php&gibbonModuleID=" . $moduleID ;
$URLDelete = GIBBON_URL . "index.php?q=/modules/System Admin/module_manage.php" ;

if (! $this->getSecurity()->isActionAccessible("/modules/System Admin/module_manage_uninstall.php")) {
	$this->insertMessage("return.error.0") ;
	$this->redirect($URL);
}
else {
	//Proceed!
	//Check if role specified
	if ($moduleID=="") {
		$this->insertMessage("return.error.1");
		$this->redirect($URL);
	}
	else {
		$module = new module($this, $moduleID);
		if (! $module->getSuccess() || $module->rowCount() != 1) {
			$this->insertMessage("return.error.2") ;
			$this->redirect($URL);
		} else {
			$row = (array) $module->returnRecord() ;
			$moduleName = $row["name"] ;
			$partialFail = false ;
			
			//Check for tables and views to remove, and remove them
			$tables=NULL ;
			if (isset($_POST["remove"])) {
				$tables=$_POST["remove"] ;
			}
			if (is_array($tables)) {
				if (count($tables)>0) {
			 		foreach ($tables AS $table) {
			 			$type=NULL ;
			 			$name=NULL ;
			 			if (substr($table, 0 ,5)=="Table") {
			 				$type="TABLE" ;
			 				$name=substr($table, 6) ;
			 			}
			 			else if (substr($table, 0 ,4)=="View") {
			 				$type="VIEW" ;
			 				$name=substr($table, 5) ;
			 			}
			 			if ($type!=NULL AND $name!=NULL) {
							$module->executeQuery(array(), "DROP $type IF EXISTS `$name`");
							if (! $module->getSuccess()) { 
								echo $module->getError() . "<br/><br/>" ;
								$partialFail = true ;
							}
			 			}
			 		}
			 	}
			}
			
			$module->deleteRecord($module->getField('gibbonModuleID'));
			//Update main menu
			$mainMenu = new \Gibbon\Menu\main($this);
			$mainMenu->setMenu() ;
		
			if (! $orphaned) 
				$this->insertMessage("Uninstall was successful. You will still need to remove the module's files yourself.", 'warning') ;
			else 
				$this->insertMessage("Uninstall was successful.", 'success') ;
			$this->redirect($URLDelete);
		}
	}
}
?>