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
use Gibbon\Record\space ;
use Gibbon\core\pagination ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Facilities';
	$trail->render($this);
	
	$this->render('default.flash');
	$this->linkTop(array('Add' => array('q' => '/modules/School Admin/space_manage_edit.php', 'gibbonSpaceID' => 'Add')));
	$this->h2('Manage Facilities');

    //Set pagination variable
	$data = array('gibbonSpaceID');
	$where = '';
	$order = array('name' => 'ASC');
	$join = '';
	$select = '`gibbonSpaceID`';

	$pagin = new pagination($this, $where, $data, $order, new space($this), $join, $select);

    if ($pagin->get('total') < 1) {
        $this->displayMessage('There are no records to display.');
    } else {
        $pagin->printPagination('top');

		$el = new \stdClass();
		$el->action = true ;
		$this->render('space.listStart', $el);
        foreach ($pagin->get('results') as $w) {
            $space = new space($this, $w->gibbonSpaceID);
			$space->action = true ;
			$this->render('space.listMember', $space);
        }
        $this->render('space.listEnd');

        $pagin->printPagination('bottom');
    }
}
