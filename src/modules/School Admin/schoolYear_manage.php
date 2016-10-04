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
use Gibbon\Record\schoolYear ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage School Year';
	$trail->render($this);
	
	
	$data=array(); 
	$schoolYearObj = new schoolYear($this);
	$schoolYearList = $schoolYearObj->findAll("SELECT * FROM `gibbonSchoolYear` ORDER BY `sequenceNumber`") ; 

	$this->linkTop(array('Add'=> array('q' => '/modules/School Admin/schoolYear_manage_edit.php', 'gibbonSchoolYearID' => 'Add')));
	$this->h2('Manage School Year');
	$this->render('default.flash');
	if (count($schoolYearList)<1) {
		$this->displayMessage("There are no records to display.", 'info') ;
	}
	else {
	
		$this->render('schoolYear.listStart');
			
		foreach($schoolYearList as $row) 
			$this->render('schoolYear.listMember', $row);
		$this->render('schoolYear.listEnd');
	}
}
