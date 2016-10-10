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

if (! $this instanceof post) die();

$URL = array('q'=>'/modules/System Admin/menu_manage.php');

if (! $this->getSecurity()->isActionAccessible("/modules/System Admin/systemSettings.php")) {
	$this->insertMessage('return.error.0');
	$this->redirect($URL);
}
else {
	//Proceed!
	$post = $_POST ;

	$required = array( 'mainMenuCategories', 'pageAnchorDisplay' ) ;
	foreach($required as $name)
		if (empty($post[$name]))
		{
			$this->insertMessage(array('Your request failed because %1$s was not supplied.', array($name))) ;
			$this->redirect($URL);
		}
	
	
		//Write to database
		$fail = false ;
		
		$this->config->setScope('System');
		if (! $this->config->setSettingByScope('mainMenuCategories', $post['mainMenuCategories']) ) $fail = true;
		if (! $this->config->setSettingByScope('pageAnchorDisplay', $post['pageAnchorDisplay']) ) $fail = true;
		if (! $this->config->setSettingByScope("pagination", $post['pagination']) ) $fail = true;
		
		if ( $fail ) {
			//Fail 2
			$this->insertMessage('return.error.2') ;
			$this->redirect($URL);
		}
		else {
			//Success 0
			$this->session->getSystemSettings($this->pdo) ;
			$this->session->set('display.menu.main.refresh', 0);
			$this->insertMessage('return.success.0', 'success') ;
			$this->redirect($URL);
		}
}
