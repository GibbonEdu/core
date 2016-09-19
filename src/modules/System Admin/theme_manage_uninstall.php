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
use Gibbon\core\trans ;
use Gibbon\Record\theme ;
use Module\System_Admin\Functions\functions ;

if (! $this instanceof view) die();

$mf = new functions($this);

if ($this->getSecurity()->isActionAccessible()) 
{
	$orphaned = isset($_GET["orphaned"]) && $_GET["orphaned"]=="true" ? 'true' : '' ;

	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Uninstall Theme';
	$trail->addTrail('Manage Themes', "/index.php?q=/modules/System Admin/theme_manage.php");
	$trail->render($this);
	
	$this->render('default.flash');
	
	//Check if school year specified
	$themeID=$_GET["gibbonThemeID"] ;
	if ($themeID=="") {
		$this->displayMessage("You have not specified one or more required parameters.");
	}
	else {
		$themeObj = new theme($this, $themeID);
		if (! $themeObj->getSuccess() || $themeObj->getField('active') == 'Y') {
			$this->displayMessage("The specified theme cannot be found or is active and so cannot be removed.");
		}
		else {
			$params = new \stdClass();
			$params->action = $themeObj->action = false;
			$themeObj->rowNum = 'odd';
			$themeObj->installed = true ;
			$themeObj->themeVersion = $themeObj->getField('version');
			$this->render('theme.listStart', $params);
			$this->render('theme.listMember', $themeObj);
			$this->render('theme.listEnd', $params);


			
			$form = $this->getForm(null, array('q'=>'/modules/System Admin/theme_manage_uninstallProcess.php', 'gibbonThemeID' => $themeID, 'orphaned' => $orphaned), true)
				->deleteForm();

		}
	}
}
