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
use Gibbon\core\trans ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage Modules', "/index.php?q=/modules/System Admin/module_manage.php");
	$trail->trailEnd = 'Update Module';
	$trail->render($this);
	
	$this->insertReturn("warning1", "Some aspects of your request failed, but others were successful. The elements that failed are shown below:") ;
	if ( $this->session->notEmpty("moduleUpdateError")) {
		$this->insertMessage("The following SQL statements caused errors:" . " " . $this->session->get("moduleUpdateError"));
	}
	$this->session->clear("moduleUpdateError");
	$this->render('default.flash');
	
	$this->h2('Module Update');
	//Check if school year specified
	$moduleID=$_GET["gibbonModuleID"] ;
	if (intval($moduleID) < 1) {
		$this->displayMessage("You have not specified one or more required parameters.");
	}
	else {
		$moduleObj = new module($this, $moduleID);
		if ($moduleObj) {
			//Let's go!
			$row = (array) $moduleObj->returnRecord();
			
			$versionDB = $row["version"] ;
			if (file_exists(GIBBON_ROOT . "src/modules/" . $row["name"] . "/version.php")) {
				include GIBBON_ROOT . "src/modules/" . $row["name"] . "/version.php" ;
			}	
			@$versionCode = $moduleVersion ;
			
			$this->displayMessage(array('This page allows you to semi-automatically update the %1$s module to a new version. You need to take care of the file updates, and based on the new files, Gibbon will do the database upgrades.', array($this->htmlPrep($row["name"]))), 'info') ;
			
			if ($versionDB>$versionCode OR $versionCode=="") {
				//Error
				$this->displayMessage("An error has occurred determining the version of the system you are using.") ;
			}
			elseif (! $versionDB == $versionCode) {
				//Instructions on how to update
				$this->h3("Update Instructions") ; 
				$list = $this->startList('ol');
				$list->addListElement('You are currently using %1$s v%2$s.', array($this->htmlPrep($row["name"]), $versionCode));
				$list->addListElement('Check %1$s for a newer version of this module.', array("<a target='_blank' href='https://gibbonedu.org/extend'>gibbonedu.org</a>"));
				$list->addListElement('Download the latest version, and unzip it on your computer.');
				$list->addListElement('Use an FTP client to upload the new files to your server\'s modules folder.'); 
				$list->addListElement('Reload this page and follow the instructions to update your database to the latest version.'); 
				$list->renderList($this);
			}
			elseif ($versionDB<$versionCode) {
				//Time to update 
				$this->h3("Datebase Update") ; 
				$this->paragraph('It seems that you have updated your %1$s module code to a new version, and are ready to update your database from v%2$s to v%3$s. Click "Update" below to continue. %4$sThis operation cannot be undone: backup your entire database prior to running the update!%5$s', array('<strong>'.$this->htmlPrep($row["name"]).'</strong>', $versionDB, $versionCode, '<br /><span style="color: red;"><strong>', '</strong></span>')); 
				$form = $this->getForm(null, array('q' => '/modules/System Admin/module_manage_updateProcess.php', 'gibbonModuleID' => $moduleID), true);
				$form->addElement('hidden', 'versionDB', $versionDB);
				$form->addElement('hidden', 'versionCode', $versionCode);
				$el = $form->addElement('submitBtn', null, 'Update');
				$el->description = '';
				$form->render();
			}
			else
				$this->displayMessage(array('The module %1$s is at the current version and does not require update.', array('<strong>'.$this->htmlPrep($row["name"]).'</strong>')), 'info');
		}
	}
}
