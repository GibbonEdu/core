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
use Gibbon\Record\scale ;
use Gibbon\core\helper ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Grade Scales';
	$trail->render($this);

	$this->render('default.flash');
	
	$this->linkTop(array('Add'=>array('q'=>'/modules/School Admin/gradeScales_manage_edit.php', 'gibbonScaleID'=>'Add')));

	$this->h2('Manage Grade Scales');
	$this->displayMessage("Grade scales are used through the ARR modules to control what grades can be entered into the system. Editing some of the inbuilt scales can impact other areas of the system. It is advised to take a backup of the entire system before proceeding.", 'info');
	
	
	$sObj = new scale($this);
	$scales  = $sObj->findAll("SELECT * 
		FROM `gibbonScale` 
		ORDER BY `name`");

	if (count($scales) < 1) {
		$this->displayMessage("There are no records to display.") ;
	}
	else {
		$this->render('scale.listStart');
		foreach($scales as $scale)
			$this->render('scale.listMember', $scale);
		$this->render('scale.listEnd');
	}
}
