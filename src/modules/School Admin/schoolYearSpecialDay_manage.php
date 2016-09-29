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
use Gibbon\Record\daysOfWeek ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Special Days';
	$trail->render($this);

	$this->render('default.flash');

    $schoolYearID = isset($_GET['gibbonSchoolYearID']) ? $_GET['gibbonSchoolYearID'] : null;

    if (empty($schoolYearID) || $schoolYearID == $this->session->get('gibbonSchoolYearID')) {
        $schoolYearID = $this->session->get('gibbonSchoolYearID');
        $schoolYearName = $this->session->get('gibbonSchoolYearName');
    } elseif ($schoolYearID != $this->session->get('gibbonSchoolYearID')) {
		if (! $yearObj = new schoolYear($this, $_GET['gibbonSchoolYearID'])) {
            $this->displayMessage('The specified record does not exist.');
        } else {
            $schoolYearID = $yearObj->getField('gibbonSchoolYearID');
            $schoolYearName = $yearObj->getField('name');
        }
    }
    if (! empty($schoolYearID)) {
        $yearObj = new schoolYear($this, $schoolYearID, '_');

		$links = array();
        if ($x = $yearObj->getPreviousSchoolYearID($schoolYearID)) 
			$links['Previous Year'] = array('q'=>'/modules/School Admin/schoolYearSpecialDay_manage.php','gibbonSchoolYearID'=>$x);
        if ($x = $yearObj->getNextSchoolYearID($schoolYearID)) 
			$links['Next Year'] = array('q'=>'/modules/School Admin/schoolYearSpecialDay_manage.php','gibbonSchoolYearID'=>$x);
		$this->linkTop($links);
		$this->h2($schoolYearName);

        if (count($yearObj->schoolYearTerms) < 1) {
            $this->displayMessage('There are no terms in the specied year.');
        } else {
            foreach($yearObj->schoolYearTerms as $termObj) {
                $this->h3($termObj->getField('name'));
                list($firstDayYear, $firstDayMonth, $firstDayDay) = explode('-', $termObj->getField('firstDay'));
                $firstDayStamp = mktime(0, 0, 0, $firstDayMonth, $firstDayDay, $firstDayYear);
                list($lastDayYear, $lastDayMonth, $lastDayDay) = explode('-', $termObj->getField('lastDay'));
                $lastDayStamp = mktime(0, 0, 0, $lastDayMonth, $lastDayDay, $lastDayYear);

                //Count back to first DOW before first day
                $startDayStamp = $firstDayStamp;
				$fdow = substr($this->config->getSettingByScope('System', 'firstDayOfTheWeek'), 0, 3);
				$ldow = $fdow == 'Mon' ? 'Sun' : 'Sat' ;
                while (date('D', $startDayStamp) != $fdow) {
                    $startDayStamp = $startDayStamp - 86400;
                }

                //Count forward to first DOW after last day
                $endDayStamp = $lastDayStamp;
                while (date('D', $endDayStamp) != $ldow) {
                    $endDayStamp = $endDayStamp + 86400;
                }


				$dowObj = new daysOfWeek($this);
				$days = $dowObj->getSchoolDays();

                $count = 1;

				$this->render('specialDays.listStart', $startDayStamp);

                $specialDayStamp = $termObj->getSpecialDayStamp();
				
				
				$i = $startDayStamp;
				 
                do {
					$key = date('D', $i);
					if ($key == $fdow) {
						$week = new \stdClass();
						$week->tObj = $termObj;
                    }

					$week->days[$key] = array();
                    if ($i < $firstDayStamp || $i > $lastDayStamp || $days[date('D', $i)] == 'N') {
						$week->days[$key]['status'] = 'empty';
                    } else {
                        if ($i == $specialDayStamp) {
 							$week->days[$key]['status'] = 'special';
 							$week->days[$key]['name'] = $termObj->currentSpecialDay->getField('name');
 							$week->days[$key]['specialDayID'] = $termObj->currentSpecialDay->getField('gibbonSchoolYearSpecialDayID');
							$specialDayStamp = $termObj->getSpecialDayStamp();

                        } else {
 							$week->days[$key]['status'] = 'normal';
 							$week->days[$key]['termID'] = $termObj->getField('gibbonSchoolYearTermID');
 							$week->days[$key]['firstDay'] = $firstDayStamp;
 							$week->days[$key]['lastDay'] = $lastDayStamp;
                        }
 						$week->days[$key]['timestamp'] = $i;
 						$week->days[$key]['schoolYearID'] = $schoolYearID;
                    } 
					if ($key == $ldow)
						$this->render('specialDays.week', $week);

					$i = $i + 86400 ;
                } while ($i <= $endDayStamp);

               $this->render('specialDays.listEnd');

            }
        }
    }
}
