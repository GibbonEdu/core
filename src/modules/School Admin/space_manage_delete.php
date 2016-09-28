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

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage Facilities', array('q'=>'/modules/School Admin/space_manage.php'));
	$trail->trailEnd = 'Delete Facility' ;
	$trail->render($this);
	
	$this->render('default.flash');
	$this->h2('Delete Facility');
	
    if (empty($_GET['gibbonSpaceID'])) {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		$dbObj = new space($this, $_GET['gibbonSpaceID']);

        if ($dbObj->rowCount() != 1) {
            $this->displayMessage('The specified record cannot be found.');
        } else {
            //Let's go!
			$dbObj->action = false ;
			$this->render('space.listStart', $dbObj);			
			$this->render('space.listMember', $dbObj);			
			$this->render('space.listEnd');
			$this->getForm(null, array('q' => '/modules/School Admin/space_manage_deleteProcess.php', 'gibbonSpaceID'=>$_GET["gibbonSpaceID"]), true)
				->deleteForm();
        }
    }
}
