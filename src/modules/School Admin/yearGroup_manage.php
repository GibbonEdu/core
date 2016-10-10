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
use Gibbon\Record\yearGroup ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Year Groups';
	$trail->render($this);
	
	$dbObj = new yearGroup($this);
	$yGroups = $dbObj->findAll('SELECT * 
		FROM `gibbonYearGroup` 
		ORDER BY `sequenceNumber`');

	$this->linkTop(array('Add' => array('q' => '/modules/School Admin/yearGroup_manage_edit.php', 'gibbonYearGroupID'=>'Add')));

	$this->h2('Manage Year Groups');
	$this->render('default.flash');
    if (count($yGroups) < 1) {
        $this->displayMessage('There are no records to display.');
    } else {
		$el = new \stdClass();
		$el->action = true ;
		$this->render('yearGroups.listStart', $el);
		
        foreach ($yGroups as $yGroup) {
			$this->render('yearGroups.listMember', $yGroup);

        }
        $this->render('yearGroups.listEnd');
    }
}
