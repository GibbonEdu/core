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
	$trail->addTrail('Manage Individual Needs Descriptors', array('q'=>'/modules/School Admin/inDescriptors_manage.php'));
	$trail->trailEnd = 'Delete Descriptor';
	$trail->render($this);
	
	$this->render('default.flash');

    //Check if school year specified
    if (empty($_GET['gibbonINDescriptorID'])) {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		$dbObj = new \Gibbon\Record\INDescriptor($this, $_GET['gibbonINDescriptorID']);
        if ($dbObj->rowCount() != 1) {
            $this->displayMessage('The specified record cannot be found.');
        } else {
            //Let's go!
			$this->h2('Delete Descriptor');
			$dbObj->action = false;
			$this->render('individualNeeds.listStart', $dbObj);
			$this->render('individualNeeds.listMember', $dbObj);
			$this->render('individualNeeds.listEnd');
			$this->getForm(GIBBON_ROOT . 'modules/School Admin/inDescriptors_manage_deleteProcess.php', array('gibbonINDescriptorID'=>$_GET['gibbonINDescriptorID']), true)
				->deleteForm();
        }
    }
}
