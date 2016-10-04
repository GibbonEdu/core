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
use Gibbon\Record\house ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Delete House';
	$trail->addTrail('Manage Houses', "/index.php?q=/modules/School Admin/house_manage.php");
	$trail->render($this);

	$this->render('default.flash');
	$this->h2('Delete House');
	//Check if school year specified
	$houseID = intval($_GET["gibbonHouseID"]) ;
	if ($houseID < 1) {
		$this->displayMessage("You have not specified one or more required parameters.");
	}
	else {
		
		$hObj = new house($this, $houseID);
			//Let's go!
		$el = new \stdClass();
		$el->action = false ;
		$this->render('house.listStart', $el);

		$house = $hObj->returnRecord();
		$house->action = false;
		$this->render('house.listMember', $house);

		$this->render('house.listEnd');

		$this->getForm(null, array('q'=>"modules/School Admin/house_manage_deleteProcess.php", 'gibbonHouseID' => $houseID), true)
			->deleteForm();
	}
}
