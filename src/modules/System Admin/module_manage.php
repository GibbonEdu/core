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
use Gibbon\Record\module ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Modules';
	$trail->render($this);
		
	if ($this->session->notEmpty("moduleInstallError")) 
		$this->insertMessage("The following SQL statements caused errors:" . " " . $_SESSION["moduleInstallError"] );
	$this->session->clear("moduleInstallError") ;
	$this->render('default.flash');
	
	//Get modules from database, and store in an array
	$obj = new module($this);
	$modulesSQL = $obj->findAll("SELECT * FROM gibbonModule ORDER BY name", array(), '_', 'name');
	foreach ($modulesSQL as $q=>$w)
		$modulesSQL[$q]->setField('status', 'orphaned');
	
	//Get list of modules in /modules directory
	$modulesFS = glob(GIBBON_ROOT . "src/modules/*" , GLOB_ONLYDIR);

	$this->insertMessage(array('To install a module, upload the module folder to %1$s on your server and then refresh this page. After refresh, the module should appear in the list below: use the install button in the Actions column to set it up.', array("<b><u>" . GIBBON_ROOT . "src/modules/</u></b>")), 'info', true);
	
	if (count($modulesFS)<1) {
		$this->insertMessage("There are no records to display.", '', true);
	}
	else {
		$params = new \stdClass();
		$params->action = true;
		$this->render('module.listStart', $params);
			
		foreach ($modulesFS AS $moduleFS) {
			$params = new \stdClass();
			$params->moduleName = substr($moduleFS, strlen( GIBBON_ROOT . "src/modules/")) ;
			if (! isset($modulesSQL[$params->moduleName]))
				$modulesSQL[$params->moduleName] = new module($this);
			$modulesSQL[$params->moduleName]->setField('status', "present");
			$params->installed = true ;
			if (intval($modulesSQL[$params->moduleName]->getField('gibbonModuleID')) == 0) {
				$params->installed = false;
				$rowNum = "info" ;
			}
			$params->moduleObj = $modulesSQL[$params->moduleName];
		
			$params->action = true ;
			//COLOR ROW BY STATUS!
			$this->render('module.listMember', $params);
		}
		$this->render('module.listEnd');
	}
	
	//Find and display orphaned modules
	$orphans = false ;
	foreach($modulesSQL AS $moduleSQL) {
		if ($moduleSQL->getField('status') == "orphaned") {
			$orphans = true ;
		}
	}
	
	if ($orphans) {
		$this->render('module.orphan.listStart');
			foreach($modulesSQL AS $moduleName=>$moduleSQL) {
				if ($moduleSQL->getField('status')=="orphaned") {
					$params = new \stdClass();
					$params->moduleObj = $moduleSQL;
				
					$this->render('module.orphan.listMember', $params);
				}
			}
		$this->render('module.orphan.listEnd');
	}
}
