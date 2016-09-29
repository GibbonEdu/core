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

use Gibbon\core\post ;
use Gibbon\Record\daysOfWeek ;

if (! $this instanceof post) die();

$URL = GIBBON_URL.'index.php?q=/modules/School Admin/daysOfWeek_manage.php';

if (! $this->getSecurity()->isActionAccessible('/modules/School Admin/daysOfWeek_manage.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {
    //Proceed!
	$dowObj = new daysOfWeek($this);
	$dowRows = $dowObj->findAllDays();

    if (count($dowRows) != 7) {
        $this->insertMessage('return.error.2');
        $this->redirect($URL);
    } else {
		foreach($dowRows as $dowObj)
		{
			$dowObj->setField('sequenceNumber', $dowObj->getField('sequenceNumber') + 10);
			$dowObj->writeRecord(array('sequenceNumber'));
		}

        $valid = true;
        $update = true;
		$post = $_POST ;
		$seq = 0;
	    foreach($dowRows as $dowObj) {
		
			
            $_POST = $post[$dowObj->getField('name')];
			$_POST['sequenceNumber'] = ++$seq;
			$dowObj->injectPost();
            //Validate Inputs
			if (! $dowObj->uniqueTest()) {
                $valid = false;
            } 
			else
			{
				if (! $dowObj->writeRecord()) $update = false;
			}
        }

        //Deal with invalid or not unique
        if (! $valid) {
            $this->insertMessage('return.error.3');
            $this->redirect($URL);
        } else {
            //Deal with failed update
            if (! $update) {
                $this->insertMessage('return.error.2');
                $this->redirect($URL);
            } else {
                $this->insertMessage('return.success.0', 'success');
                $this->redirect($URL);
            }
        }
    }
}
