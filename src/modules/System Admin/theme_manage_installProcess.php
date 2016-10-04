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

if (! $this instanceof view) die();

//Get URL from calling page, and set returning URL
$URL=array('q'=>'/modules/System Admin/theme_manage.php');

if (! $this->getSecurity()->isActionAccessible("/modules/System Admin/theme_manage_install.php")) {
	$this->insertMessage("return.error.0");
	$this->redirect($URL);
}
else {
	$themeName = isset($_GET["name"]) ? $_GET["name"] : null ;

	if (empty($themeName)) {
		$this->insertMessage("return.error.1") ;
		$this->redirect($URL);
	}
	else {
		if (!(include GIBBON_ROOT . 'src/themes/'.$themeName.'/manifest.php')) {
			$this->insertMessage("return.error.1") ;
			$this->redirect($URL);
		}
		else {
			//Validate Inputs
			if (empty($name) || empty($description) || empty($version)) {
				$this->insertMessage('The manifest file is not correctly formatted.');
				$this->redirect($URL);
			}
			else {
				//Check for existence of theme
				$themeObj = new theme($this);
				$themeObj->findBy(array('name'=>$name));

				if ($themeObj->rowCount()>0) {
					$this->insertMessage("A theme with the same name found in the manifest file is already installed. ") ;
					$this->redirect($URL);
				}
				else {
					//Insert new theme row
					$themeObj->defaultRecord();
					$themeObj->setField('name', $name);
					$themeObj->setField('description', $description);
					$themeObj->setField('version', $version);
					$themeObj->setField('author', $author);
					$themeObj->setField('url', $url);
					if (! $themeObj->writeRecord()) {
						$this->insertMessage("return.error.2") ;
						$this->redirect($URL);
					}
						
					$this->insertMessage("Install was successful.", 'success') ;
					$this->redirect($URL);
				}
			}
		}
	}
}