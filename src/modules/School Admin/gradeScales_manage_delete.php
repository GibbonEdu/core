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
use Gibbon\Record\scale ;

if (! $this instanceof view ) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Delete Grade Scale' ;
	$trail->addTrail('Manage Grade Scales', array('q' => '/modules/School Admin/gradeScales_manage.php'));
	$trail->render($this);
	
	$this->render('default.flash');
	$this->h2('Delete Grade Scale');
	
	//Check if school year specified
	$scaleID = $_GET["gibbonScaleID"] ;
	if (empty($scaleID)) {
		$this->displayMessage("You have not specified one or more required parameters.");
	}
	else {
		$sObj = new scale ($this, $scaleID);

		if (! $sObj->getSuccess()) {
			$this->displayMessage("The specified record cannot be found.");
		}
		else 
		{
			//Let's go!
			$el = new \stdClass();
			$el->action = false;
			$this->render('scale.listStart', $el);
			$sObj->action = false;
			$this->render('scale.listMember', $sObj);
			$this->render('scale.listEnd');
			$this->getForm(GIBBON_ROOT . 'modules/School Admin/gradeScales_manage_deleteProcess.php', array('gibbonScaleID' => $scaleID), true)
				->deleteForm();
		}
	}
}
