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
use Gibbon\Record\externalAssessment ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage External Assessments';
	$trail->render($this);
	
	$this->render('default.flash');
	
	$eaObj = new externalAssessment($this);
	$eaList = $eaObj->findAll("SELECT * 
		FROM `gibbonExternalAssessment` 
		ORDER BY `name`");

	$this->linkTop(array('add' => array('q'=>'/modules/School Admin/externalAssessments_manage_edit.php', 'gibbonExternalAssessmentID'=>'Add')));	
	$this->h2('Manage External Assessments');	

	if (count($eaList) < 1) {
		$this->displayMessage("There are no records to display.") ;
	}
	else {
		$this->render('externalAssessment.listStart');			
		foreach($eaList as $ea)
			$this->render('externalAssessment.listMember', $ea);			
		$this->render('externalAssessment.listEnd');			
	}
}
