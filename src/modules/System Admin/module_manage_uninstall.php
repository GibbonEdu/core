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

use Gibbon\core\view ;
//use Gibbon\core\module as helper ;
use Gibbon\core\trans ;
use Gibbon\Record\module ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$orphaned = false ;
	if (isset($_GET["orphaned"]) && $_GET["orphaned"] == "true") {
		$orphaned = true ;
	}

	$trail = $this->initiateTrail();
	$trail->addTrail('Manage Modules', "/index.php?q=/modules/System Admin/module_manage.php");
	$trail->trailEnd = 'Uninstall Module';
	$trail->render($this);

	$this->render('default.flash');

	$this->h3('Uninstall Module');
	//Check if school year specified
	$moduleID = $_GET["gibbonModuleID"] ;
	if (intval($moduleID) < 1) {
		$this->displayMessage("You have not specified one or more required parameters.") ;
	}
	else {
		$module = new \Gibbon\Record\module($this, $moduleID);
		if ($module->rowCount()!=1) {
			$this->displayMessage("You have not specified one or more required parameters.") ;
		}
		else {
			//Let's go!
			$row = (array)$module->returnRecord();
			$params = new \stdClass();
			$params->action = false;
			$this->render('module.listStart', $params);
			$params = new \stdClass();
			$params->moduleObj =  $module;
			$params->action = false;
			$params->rowNum = 'odd';
			$params->moduleName = $params->moduleObj->getField('name');
			$params->installed = true;
			$this->render('module.listMember', $params);
			$this->render('module.listEnd');
			
			$form = $this->getForm(null, array('q'=>'/modules/System Admin/module_manage_uninstallProcess.php', 'gibbonModuleID' => $moduleID, 'orphaned' => $orphaned ? 'true' : 'false'), true);

			if (! $orphaned) { 
				$el = $form->addElement('free', '', '');
				$el->nameDisplay = 'RemoveData';
				$el->description = "Would you like to remove the following tables and views from your database?";
				
				if (! is_file(GIBBON_ROOT . "src/modules/" . $row["name"] . "/manifest.php")) {
					$el->value = $this->returnMessage("An error has occurred accessing the manifest file of the module.") ;
				}
				else {
					$el->value = '';
					$count = 0 ;
					include GIBBON_ROOT . "src/modules/" . $row["name"] . "/manifest.php" ;
					if (is_array($moduleTables)) {
						foreach ($moduleTables AS $moduleTable) {
							$type=NULL ;
							$tokens=NULL ;
							$name="" ;
							$moduleTable = trim($moduleTable) ;
							if (substr($moduleTable, 0, 12)=="CREATE TABLE") {
								$type = trans::__("Table") ;
							}
							else if (substr($moduleTable, 0, 11)=="CREATE VIEW") {
								$type = trans::__("View") ;
							}
							if (! empty($type)) {
								$tokens = preg_split('/ +/', $moduleTable);
								if (isset($tokens[2])) {
									$name = str_replace("`", "", $tokens[2]) ;
									if (! empty($name)) {
										$el->value .= "<strong>" . $type . "</strong>: " . $name ;
										$el->value .= " <input checked type='checkbox' name='remove[]' value='" . $type . "-" . $name . "' /><br/>" ;
										$count++ ;
									}
								}
							}
						} 
					}
					if ($count == 0) {
						$el->value = $this->returnMessage("There are no records to display.", 'info') ;
					}
				}
			} 
			
			$form->deleteForm();
		}
	}
}
