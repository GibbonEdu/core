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
	$trail->trailEnd = 'Manage Terms';
	$trail->render($this);
	
	
	$this->render('default.flash');
	
	$yearObj = new schoolYear($this);
	
	$years = $yearObj->findAll('SELECT * FROM `gibbonSchoolYear` ORDER BY `sequenceNumber`', array(), '_');
	
	$this->linkTop(array('Add' => array('q'=>'/modules/School Admin/schoolYearTerm_manage_edit.php', 'gibbonSchoolYearTermID'=>'Add')));
	$this->h2('Manage Terms');
	
	if (count($years)<1) {
		$this->displayMessage("There are no records to display.") ;
	}
	else {
		$this->render('terms.listStart');
		foreach ($years as $key=>$year){
			$year->find($key);
			foreach ($year->schoolYearTerms as $row) {
				$row->yearName = $year->getField("name") ;
				$this->render('terms.listMember', $row);
			}
		}
		$this->render('terms.listEnd');
	}
}
