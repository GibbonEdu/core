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
	$trail->addTrail('Manage Year Groups', array('q'=>'/modules/School Admin/yearGroup_manage.php'));
	$trail->trailEnd = 'Delete Year Group' ;
	$trail->render($this);
	
	$this->h2('Delete Year Group');
	$this->render('default.flash');
	
    if (empty($_GET['gibbonYearGroupID'])) {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		$dbObj = new yearGroup($this, $_GET['gibbonYearGroupID']);

        if ($dbObj->rowCount() != 1) {
            $this->displayMessage('The specified record cannot be found.');
        } else {
            //Let's go!
			$dbObj->action = false ;
			$this->render('yearGroups.listStart', $dbObj);			
			$this->render('yearGroups.listMember', $dbObj);			
			$this->render('yearGroups.listEnd');
			
			$this->getForm(null, array('q'=>'/modules/School Admin/yearGroup_manage_deleteProcess.php', 'gibbonYearGroupID'=>$_GET["gibbonYearGroupID"]), true)
				->deleteForm();
        }
    }
}
