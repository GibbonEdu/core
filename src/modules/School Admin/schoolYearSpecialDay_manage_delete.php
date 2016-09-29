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
use Gibbon\core\trans ;
use Gibbon\core\helper ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Delete Special Day';
	$trail->addTrail('Manage Special Days', array('q'=>'/modules/School Admin/schoolYearSpecialDay_manage.php','gibbonSchoolYearID'=>$_GET['gibbonSchoolYearID']));
	$trail->render($this);

	$this->render('default.flash');
	
    //Check if school year specified
    $schoolYearSpecialDayID = $_GET['gibbonSchoolYearSpecialDayID'];
    if (empty($schoolYearSpecialDayID)) {
        $this->displayMessage('You have not specified one or more required parameters.');
    } else {
		if ($specDayObj = new \Gibbon\Record\schoolYearSpecialDay($this, $schoolYearSpecialDayID)) {
            $this->h2('Delete Special Day') ;
			$schoolYearObj = new \Gibbon\Record\schoolYear($this, $_GET['gibbonSchoolYearID']);
			$specDayObj->schoolYear = $schoolYearObj->getField('name');
            //Let's go!
			$this->render('specialDays.displayMember', $specDayObj);						
			$this->getForm(GIBBON_ROOT.'modules/School Admin/schoolYearSpecialDay_manage_deleteProcess.php', array(
				'gibbonSchoolYearSpecialDayID'=>$schoolYearSpecialDayID,
				'gibbonSchoolYearID'=>$_GET['gibbonSchoolYearID']
				), true)
				->deleteForm();
        }
    }
}