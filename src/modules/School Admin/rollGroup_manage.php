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

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Roll Groups';
	$trail->render($this);
	
	$this->render('default.flash');

    $schoolYearID = isset($_GET['gibbonSchoolYearID']) ? $_GET['gibbonSchoolYearID'] : $this->session->get('gibbonSchoolYearID');

    if ($schoolYearID == $this->session->get('gibbonSchoolYearID')) 
	{
        $schoolYearID = $this->session->get('gibbonSchoolYearID');
        $schoolYearName = $this->session->get('gibbonSchoolYearName');
    } else {
        $syObj = new schoolYear($this, $schoolYearID);
        if ($syObj->rowCount() != 1) {
            $this->displayMessage('The specified record does not exist.');
        } else {
            $schoolYearName = $syObj->getField('name');
        }
    }

    if (! empty($schoolYearID)) {
        $syObj = new schoolYear($this, $schoolYearID);
		$prevYear = $syObj->getPreviousSchoolYearID();
		$nextYear = $syObj->getNextSchoolYearID();
		$links = array
				(
					'Copy All To Next Year' => array('q'=>'/modules/School Admin/rollGroup_manage_copyProcess.php', 'gibbonSchoolYearID'=>$schoolYearID, 'gibbonSchoolYearIDNext'=>$nextYear, 'divert'=>1),
					'Add' => array('q'=>'/modules/School Admin/rollGroup_manage_edit.php', 'gibbonSchoolYearID'=>$schoolYearID, 'gibbonRollGroupID'=>'Add')
				);

		$rGroups = $syObj->getRollGroups();

		if (! $nextYear || count($syObj->getNextRollGroups()) > 0 || count($rGroups) == 0)
			unset($links['Copy All To Next Year']);
		$this->linkTop( $links );
		$this->h2('Manage Roll Groups');

		$links = array
				(
					'Previous Year' => array('q'=>'/modules/School Admin/rollGroup_manage.php', 'gibbonSchoolYearID'=>$prevYear),
					'Current Year' => array('q'=>'/modules/School Admin/rollGroup_manage.php', 'gibbonSchoolYearID'=>$this->session->get('gibbonSchoolYearIDCurrent')),
					'Next Year' => array('q'=>'/modules/School Admin/rollGroup_manage.php', 'gibbonSchoolYearID'=>$nextYear)
				);
		if (intval($prevYear) === 0)
			unset($links['Previous Year']);
		if (intval($nextYear) === 0)
			unset($links['Next Year']);
		if (intval($schoolYearID) === intval($this->session->get('gibbonSchoolYearIDCurrent')))
			unset($links['Current Year']);

		$this->linkTop( $links );
		$this->h3($schoolYearName);

        if (count($rGroups) < 1) {
            $this->displayMessage('There are no records to display.');
        } else {
			$el = new \stdClass();
			$el->action = true ;
			$this->render('rollGroups.listStart', $el);
 
            foreach($rGroups as $rGroup)
				$this->render('rollGroups.listMember', $rGroup);
            $this->render('rollGroups.listEnd');
        }
    }
}
