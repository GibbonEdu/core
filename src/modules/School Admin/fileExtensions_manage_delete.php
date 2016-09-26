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

namespace Module\School_Admin ;

use Gibbon\core\view ;
use Gibbon\Record\fileExtension ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage File Extensions', array('q'=>'/modules/School Admin/fileExtensions_manage.php'));
	$trail->trailEnd = 'Delete File Extension';
	$trail->render($this);
	
	$this->render('default.flash');
	
	$this->h2('Delete File Extension');
	//Check if school year specified
	if (empty($_GET["gibbonFileExtensionID"])) {
		$this->displayMessage("You have not specified one or more required parameters.") ;
	}
	else {
		$dbObj = new fileExtension($this, $_GET["gibbonFileExtensionID"]);
		if ($dbObj->getSuccess()) {
			//Let's go!
			$el = $dbObj->returnRecord();
			$el->action = false ;
			$this->render('fileExtensions.listStart', $el);			
			$this->render('fileExtensions.listMember', $el);			
			$this->render('fileExtensions.listEnd');
			
			$this->getForm(null, array('q'=>'/modules/School Admin/fileExtensions_manage_deleteProcess.php', 'gibbonFileExtensionID' => $_GET["gibbonFileExtensionID"], 'page'=>$_GET['page']), true)
				->deleteForm();
		}
		else
		{
			$this->displayMessage('The specified record cannot be found.');
		}
	}
}
