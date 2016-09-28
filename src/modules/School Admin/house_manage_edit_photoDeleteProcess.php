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

if (! $this instanceof view) die(__FILE__);


$houseID = intval($_GET["gibbonHouseID"]) ;
$URL = GIBBON_URL . "index.php?q=/modules/School Admin/house_manage_edit.php&gibbonHouseID=".$houseID ;

if ($this->getSecurity()->isActionAccessible("/modules/School Admin/house_manage_edit.php")) {
	//Proceed!
	if (intval($houseID) < 1) {
		$this->insertMessage('return.error.1');
		$this->redirect($URL);
	}
	else {
		$hObj = new house($this, $houseID);

		$file = $hObj->getField('logo');
		$hObj->setField('logo', '');
		if ($hObj->writeRecord(array('logo')))
		{
			if (file_exists(GIBBON_ROOT . ltrim($file, '/')))
			{
				unlink(GIBBON_ROOT . ltrim($file, '/'));
			} 
			$this->insertMessage('return.success.0', 'success');
			$this->redirect($URL);
		}
		else 
		{
			$this->insertMessage('return.error.2', 'success');
			$this->redirect($URL);
		}
	}
}
$this->redirect($URL);