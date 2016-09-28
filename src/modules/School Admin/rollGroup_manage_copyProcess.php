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
use Gibbon\Record\schoolYear ;

if (! $this instanceof view) die();

$URL = array('q'=>'/modules/School Admin/rollGroup_manage.php', 'gibbonSchoolYearID'=>$_GET['gibbonSchoolYearID']);

if (! $this->getSecurity()->isActionAccessible('/modules/School Admin/rollGroup_manage_edit.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {
    //Proceed!
    //Check if school years specified (current and next)
    if (empty($_GET['gibbonSchoolYearID']) || empty($_GET['gibbonSchoolYearIDNext'])) {
        $this->insertMessage('return.error.1');
        $this->redirect($URL);
    } else {
        //GET CURRENT ROLL GROUPS
		$syObj = new schoolYear($this, $_GET['gibbonSchoolYearID']);
        if (count($syObj->getRollGroups()) < 1 || count($syObj->getNextRollGroups()) > 0) {
            $this->insertMessage('return.error.2');
            $this->redirect($URL);
        } else {
            $partialFail = false;
            foreach($syObj->getRollGroups() as $rGObj) {
				
				$rGObj->setField('gibbonRollGroupID', NULL);
				$rGObj->setField('gibbonSchoolYearID',  $_GET['gibbonSchoolYearIDNext']);
				$_GET['gibbonSchoolYearID'] =  $_GET['gibbonSchoolYearIDNext'] ;
				if (! $rGObj->uniqueTest() || ! $rGObj->writeRecord()) $partialFail = true ;
            }

            if ($partialFail) {
                $this->insertMessage('return.error.3');
                $this->redirect($URL);
            } else {
                $this->insertMessage('return.success.0', 'success');
                $this->redirect($URL);
            }
        }
    }
}
