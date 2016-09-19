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

use Gibbon\core\helper ;
use Gibbon\core\trans ;
use Gibbon\core\form ;
use Gibbon\core\view ;
use Module\System_Admin\Functions\functions ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Update';
	$trail->render($this);
	
	$return  = '';

	$this->h2('Update');
	
	$this->render('default.flash');

	if ($this->session->notEmpty('systemUpdateError')) 
		$this->displayMessage(trans::__("The following SQL statements caused errors:") . " " . $this->session->get("systemUpdateError"));
	
	$this->session->clear('systemUpdateError') ;
	
	$this->session->getSystemSettings($this->pdo) ;
	
	$versionDB = $this->config->getSettingByScope("System", "version") ;
	$versionCode = $this->config->getVersion() ;

	$this->paragraph("This page allows you to semi-automatically update your Gibbon installation to a new version. You need to take care of the file updates, and based on the new files, Gibbon will do the database upgrades.") ;
	
	$mf = new functions($this);

	$cuttingEdgeCode = $this->config->getSettingByScope("System", "cuttingEdgeCode" ) ;
	$cuttingEdgeCodeUpgrade = false;
	if ($cuttingEdgeCode == 'N')
	{
		$cuttingEdge = file_get_contents('https://gibbonedu.org/services/version/devCheck.php?version=' . $this->config->get('version') . '&callback=?');
		if (strpos($cuttingEdge, 'status') && strpos($cuttingEdge, 'true'))
			$cuttingEdgeCodeUpgrade = true;
	}
	
	if ($cuttingEdgeCode == 'N') {
		//Check for new version of Gibbon
		echo $mf->getCurrentVersion() ;
	
		if (version_compare($versionDB, $versionCode, "=")) {
			//Instructions on how to update
			$this->displayMessage('You seem to be all up to date, good work buddy!', 'info');
			$this->h3("Update Instructions") ;
			$list = $this->startList('ol')
				->addListElement('You are currently using Gibbon v%1$s.', array($versionCode))
				->addListElement('Check %1$sthe Gibbon download page%2$s for a newer version of Gibbon.', array("<a target='_blank' href='https://gibbonedu.org/download'>", "</a>"))
				->addListElement('Download the latest version, and unzip it on your computer.')
				->addListElement('Use an FTP client to upload the new files to your server, making sure not to overwrite any additional modules and themes previously added to the system.')
				->addListElement('Reload this page and follow the instructions to update your database to the latest version.')
				->renderList($this);
		}
		else if (version_compare($versionDB, $versionCode, ">")) {
			//Error
			$this->h3("An error has occurred determining the version of the system you are using.") ;
		}
		else if (version_compare($versionDB, $versionCode, "<")) {
			//Time to update
			$this->h3("Datebase Update") ;
			$this->paragraph('It seems that you have updated your Gibbon code to a new version, and are ready to update your database from v%1$s to v%2$s. %3$sClick "Update" below to continue. This operation cannot be undone: backup your entire database prior to running the update!%4$s', array($versionDB, $versionCode, '<strong>', '</strong>')) ;

			$form = $this->getForm($this->session->get("absolutePath") . "/modules/System Admin/updateProcess.php", array('type' => 'regularRelease'), true);
			if ($cuttingEdgeCodeUpgrade)
			{
				$el = $form->addElement('checkbox', 'cuttingEdgeUpgrade', 'Yes');
				$el->nameDisplay = 'Cutting Edge Code Available';
				$el->description = 'Check this box to change your installation to cutting Edge';
			}
			$form->addElement('hidden', "versionDB", $versionDB);
			$form->addElement('hidden', "versionCode", $versionCode);
			$form->addElement('submitBtn', 'submitBtn', 'Update');
			$form->render();
		}
	}
	else {
		$cuttingEdgeCodeLine = intval($this->config->getSettingByScope("System", "cuttingEdgeCodeLine" )) ;
		
		//Check to see if there are any updates
		include $mf->getInstallPath()."CHANGEDB.php" ;
		$versionMax=$sql[(count($sql))][0] ;
		$sqlTokens=explode(";end", $sql[(count($sql))][1]) ;
		$versionMaxLinesMax=(count($sqlTokens)-1) ;	
		$update=FALSE ;
		if (version_compare($versionMax, $versionDB, ">")) {
			$update=TRUE ;
		}
		else {
			if ($versionMaxLinesMax>$cuttingEdgeCodeLine) {
				$update=TRUE ;
			}
		}
		
		//Go! Start with warning about cutting edge code
		$this->displayMessage('Your system is set up to run Cutting Edge code, which may or may not be as reliable as regular release code. Backup before installing, and avoid using cutting edge in production.', 'warning');
		
		if ($return=="success0") {
			$this->paragraph('%1$sYou seem to be all up to date, good work buddy!%2$s', array('<strong>', '</strong>'));
		}
		elseif (! $update) {
			//Instructions on how to update
			$this->h3("Update Instructions") ;
			$list = $this->startList('ol')
				->addListElement('You are currently using Cutting Edge Gibbon v%1$s', array($versionCode))
				->addListElement('Check %1$sour GitHub repo%2$s to get the latest commits.', array("<a target='_blank' href='https://github.com/GibbonEdu/core'>", "</a>"))
				->addListElement('Download the latest commits, and unzip it on your computer.')
				->addListElement('Use an FTP client to upload the new files to your server, making sure not to overwrite any additional modules and themes previously added to the system.')
				->addListElement('Reload this page and follow the instructions to update your database to the latest version.')
				->renderList($this);
		}
		else {
			//Time to update
			$this->paragraph('It seems that you have updated your Gibbon code to a new version, and are ready to update your database from v%1$s line %2$s to v%3$s line %4$s. %5$sClick "Update" below to continue. This operation cannot be undone: backup your entire database prior to running the update!%6$s', array($versionDB, $cuttingEdgeCodeLine, $versionCode, $versionMaxLinesMax, '<strong>', '</strong>')) ;

			$form = $this->getForm($this->session->get("absolutePath") . "/modules/System Admin/updateProcess.php", array('type' => 'cuttingEdge'), true);
			$form->addElement('hidden', "versionDB", $versionDB);
			$form->addElement('hidden', "versionCode", $versionCode);
			$form->addElement('submitBtn', 'submitBtn', 'Update');
			
			$form->render();
		}
	}
}
?>