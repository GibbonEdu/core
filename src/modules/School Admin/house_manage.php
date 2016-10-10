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

if (! $this instanceof \Gibbon\core\view) die();

use Gibbon\core\view ;
use Gibbon\Record\house ;
use Gibbon\core\pagination ;

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Houses';
	$trail->render($this);
	$this->render('default.flash');

	$this->linkTop(array('add' => array('q'=>'/modules/School Admin/house_manage_edit.php', 'gibbonHouseID'=>'Add')));
	
    //Set pagination variable
	$data = array();
	$where = '';
	$order = array('name' => 'ASC');
	$join = '';
	$select = '';

	$pagin = new pagination($this, $where, $data, $order, new house($this), $join, $select);


	$this->h2('Manage Houses');

	if ($pagin->get('total') > 0) {
		$pagin->printPagination('top');
		$el = new \stdClass();
		$el->action = true ;
		$this->render('house.listStart', $el);
	
		foreach($pagin->get('results') as $house) {
			$house->action = true ;
			$this->render('house.listMember', $house);
		}
		$this->render('house.listEnd');
		$pagin->printPagination('bottom');
	}
}
