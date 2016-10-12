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

use Gibbon\core\post ;
use Gibbon\core\fileManager;

if (! $this instanceof post) die();

$personID = $_GET['gibbonPersonID'];
$URL = GIBBON_URL . 'index.php';

//Proceed!
//Check if planner specified
if (empty($personID) || $personID != $this->session->get('gibbonPersonID') || empty($_FILES['file1']['tmp_name'])) {
    $this->insertMessage('return.error.1', 'error', false, 'flash-photo');
    $this->redirect($URL);
} else {
	$obj = $this->getRecord('person');
	$obj->find($personID);

    if (! $obj->getSuccess()) {
        $this->insertMessage('return.error.2', 'error', false, 'flash-photo');
        $this->redirect($URL);
    } else {

		$fm = new fileManager($this);
		$fm->flash = 'flash-photo';
		
		if (! $fm->fileManage('file1', $this->session->get('username').'_240')) 
		{
			$this->insertMessage('return.warning.1', 'warning', false, 'flash-photo');
    		$this->redirect($URL);
		}

		if (! $fm->validImage(480, 640, 0.6, 0.8)) 
            $this->redirect($URL);

		//update image
		$obj->setField('image_240', $fm->fileName);
		if (! $obj->writeRecord(array('image_240')))
		{
			$this->insertMessage('return.error.2', 'error', false, 'flash-photo');
			$this->redirect($URL);
		}

		//Update session variables
		$this->session->set('image_240', $fm->fileName);

		//Clear custom sidebar
		$this->session->clear('index_customSidebar');

		$this->insertMessage('return.success.0', 'success', false, 'flash-photo');
		$this->redirect($URL);
    }
}
