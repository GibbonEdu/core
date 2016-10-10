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
use Gibbon\core\module ;
use Gibbon\core\trans ;
use Gibbon\Record\theme ;

if (! $this instanceof post) die();

$themeID = $_POST["gibbonThemeID"] ;
$URL = array('q' => '/modules/System Admin/theme_manage.php') ;

if (! $this->getSecurity()->isActionAccessible("/modules/System Admin/theme_manage.php")) {
	$this->insertMessage("return.error.0") ;
	$this->redirect($URL);
}
else {
	//Proceed!
	//Check if theme specified
	if (empty($themeID)) {
		$this->insertMessage("return.error.1") ;
		$this->redirect($URL);
	}
	else {
		$themeObj = new theme($this, $themeID);

		if ($themeObj) {
			//Deactivate all themes
			$sql = "UPDATE `gibbonTheme` SET `active` = 'N'" ;
			$this->pdo->executeQuery(array(), $sql);
			if (! $this->pdo->getQuerySuccess()) { 
				$this->insertMessage("Not able to clear the active theme.") ;
				$this->redirect($URL);
			}

			//Write to database
			$themeObj->setField('active', 'Y');
			if (! $themeObj->writeRecord(array('active'))) { 
				$this->insertMessage("return.error.2") ;
				$this->redirect($URL);
			}

			$this->session->set("pageLoads", -1) ;
			$themeObj->setDefaultTheme();
			$this->insertMessage("return.success.0", 'success') ;
			$this->redirect($URL);
		}
	}
}
