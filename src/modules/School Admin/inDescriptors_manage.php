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

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Individual Needs Descriptors';
	$trail->render($this);
	
	$this->render('default.flash');

	$dbObj = new \Gibbon\Record\INDescriptor($this);
	$descriptors = $dbObj->findAll('SELECT * FROM gibbonINDescriptor ORDER BY sequenceNumber');

	$this->linkTop(array('Add'=>array('q'=>'/modules/School Admin/inDescriptors_manage_edit.php', 'gibbonINDescriptorID'=>'Add')));

	$this->h2('Manage Individual Needs Descriptors');
    if (count($descriptors) < 1) {
        $this->displayMessage('There are no records to display.');
    } else {
		$this->render('individualNeeds.listStart');

        foreach($descriptors as $row) {
			$row->action = true ;
            $this->render('individualNeeds.listMember', $row);
        }
       	$this->render('individualNeeds.listEnd');
    }
}
