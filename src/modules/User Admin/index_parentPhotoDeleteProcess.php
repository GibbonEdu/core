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


namespace Module\User_Admin ;

use Gibbon\core\view ;

if (! $this instanceof view) die();

$personID = $_GET['gibbonPersonID'];
$URL = GIBBON_URL . 'index.php';

//Proceed!
//Check if planner specified
if (empty($personID) || $personID != $this->session->get('gibbonPersonID')) {
    $this->insertMessage('return.error.1', 'error', false, 'flash-photo');
    $this->redirect($URL);
} else {
	$obj = $this->getRecord('person');
	$obj->find($personID);

    if (! $obj->getSuccess()) {
        $this->insertMessage('return.error.2', 'error', false, 'flash-photo');
        $this->redirect($URL);
    } else {
        //UPDATE
		$file = $obj->getField('image_240');
		
		$obj->setField('image_240', '');
		
		if (! $obj->writeRecord(array('image_240')))
		{
			$this->insertMessage('return.error.2', 'error', false, 'flash-photo');
			$this->redirect($URL);
		}

        //Update session variables
        $this->session->clear('image_240');

        //Clear cusotm sidebar
        $this->session->clear('index_customSidebar');
		
		if (is_file(GIBBON_ROOT . ltrim($file, '/')))
			@unlink(GIBBON_ROOT . ltrim($file, '/'));

		$this->insertMessage('return.success.0', 'success', false, 'flash-photo');
        //Success 0
    	$this->redirect($URL);
    }
}
