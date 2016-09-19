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

namespace Module\Notifications ;

use Gibbon\core\view ;
use Gibbon\core\trans ;
use Gibbon\Record\alarm ;
use Gibbon\Record\alarmConfirm ;
use Gibbon\Record\person ;

if (! $this instanceof view) die();

$type = isset($_GET['type']) ? $_GET['type'] : '';

$output = '';

if (in_array($type, array('general', 'lockdown', 'custom'))) {
	//Check alarm details
	$al = new alarm($this);
	$x = $al->findOneBy(array('status'=>'Current'));
	$al->type = $type ;
	
	$this->render('alarm.start');

	if (! $al->getSuccess())
		$this->displayMessage($el->getError());

    if ($al->rowCount() == 1) { //Alarm details OK

		$this->render('alarm.audio', $al);

        if ($al->getField('gibbonPersonID') != $this->session->get('gibbonPersonID')) { 
            //Check for confirmation
            $alC = new alarmConfirm($this);
            $alC->findBy(array('gibbonAlarmID' => $al->getField('gibbonAlarmID'), 'gibbonPersonID' => $this->session->get('gibbonPersonID')));
            if (! $alC->getSuccess()) 
                $this->displayMessage($alC->getError());
    		$alC->gibbonAlarmID = $al->getField('gibbonAlarmID');
			$this->render('alarm.confirmation', $alC);
		}
		
		$this->render('alarm.report.start');
		if ($this->getSecurity()->isActionAccessible('/modules/System Admin/alarm.php'))
		{
			$this->h3('Receipt Confirmation Report');
			$pObj = new person($this);
			$data = array('gibbonAlarmID' => $al->getField('gibbonAlarmID'));
			$sql = "SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, gibbonAlarmConfirmID 
				FROM gibbonPerson 
					JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) 
					LEFT JOIN gibbonAlarmConfirm ON (gibbonAlarmConfirm.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonAlarmID=:gibbonAlarmID) 
				WHERE gibbonPerson.status='Full' 
					AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') 
					AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') 
				ORDER BY surname, preferredName";
			$el = new \stdClass();
			$el->staff = $pObj->findAll($sql, $data);
    		$el->gibbonAlarmID = $al->getField('gibbonAlarmID');
    		$el->gibbonPersonID = $al->getField('gibbonPersonID');
			if (! $pObj->getSuccess()) $this->displayMessage($pObj->getError());
			if ($pObj->rowcount() < 1)
				$this->displayMessage('There are no records to display.');
			else
				$this->render('alarm.report.table', $el);
		}
		$this->render('alarm.report.end');
	}
	$this->render('alarm.end');
}
