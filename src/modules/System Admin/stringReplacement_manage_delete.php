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

namespace Module\System_Admin ;

use Gibbon\core\view ;
use Gibbon\Record\stringReplacement ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->addTrail('Manage String Replacements', array('q'=>'/modules/System Admin/stringReplacement_manage.php'));
	$trail->trailEnd = 'Delete String Replacement';
	$trail->render($this);
		
	$this->render('default.flash');

    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
	$this->h3('Delete String Replacement');
    //Check if school year specified
    $stringID = $_GET['gibbonStringID'];
    if (empty($stringID)) {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		$sObj = new stringReplacement($this, $stringID);
		
        if ($sObj->rowCount() != 1) {
            $this->displayMessage('The specified record cannot be found.');
        } else {
            //Let's go!
			$row = $sObj->returnRecord();
			$row->action = false ;
			$row->search = $search ;
			$this->render('stringReplacement.listStart', $row);
			$this->render('stringReplacement.listMember', $row);
			$this->render('stringReplacement.listEnd', $row);

            if (! empty($search)) {
				$this->linkTop(array('Back to Search Results' => "/modules/System Admin/stringReplacement_manage.php&search=" . $search));
            }
			$form = $this->getForm(null, array('q'=>'/modules/System Admin/stringReplacement_manage_deleteProcess.php', 'gibbonStringID' => $stringID, 'search' => $search), true)
				->deleteForm();

        }
    }
}
