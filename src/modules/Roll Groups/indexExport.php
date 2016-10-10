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

namespace Module\Roll_Groups ;

use Gibbon\core\view ;
use Gibbon\core\Excel ;

if (! $this instanceof view) die();

$rollGroupID = intval($_GET['gibbonRollGroupID']);
$URL = GIBBON_URL . 'index.php';

$obj = $this->getRecord('rollGroup');

$data = array('personIDTutor' => $this->session->get('gibbonPersonID'), 'personIDTutor2' => $this->session->get('gibbonPersonID'), 'personIDTutor3' => $this->session->get('gibbonPersonID'));
$sql = 'SELECT * FROM `gibbonRollGroup` WHERE `gibbonPersonIDTutor` = :personIDTutor OR `gibbonPersonIDTutor2` = :personIDTutor2 OR `gibbonPersonIDTutor3` = :personIDTutor3';
$roll = $obj->findAll($sql, $data);
if (! $obj->getSuccess())
{
	$this->insertMessage('return.error.0');
    $this->redirect($URL);
}
else
{
    if (empty($rollGroupID)) {
		$this->insertMessage('return.error.1');
        $this->redirect($URL);
    } else {
        if (count($roll) < 1) {
			$this->insertMessage('return.error.3');
            $this->redirect($URL);
        } else {
            //Proceed!
            $sql = 'SELECT `surname`, `preferredName`, `email` 
				FROM `gibbonStudentEnrolment` 
					INNER JOIN `gibbonPerson` ON `gibbonStudentEnrolment`.`gibbonPersonID` = `gibbonPerson`.`gibbonPersonID` 
				WHERE `gibbonRollGroupID` = '.$rollGroupID." 
					AND `status` = 'Full' 
					AND (`dateStart` IS NULL OR `dateStart` <= '".date('Y-m-d')."') 
					AND (`dateEnd` IS NULL  OR `dateEnd` >= '".date('Y-m-d')."') 
				ORDER BY `surname`, `preferredName`";
            $exp = new Excel($this);
            $exp->exportWithQuery($sql, 'classList.xls');
        }
    }
}
