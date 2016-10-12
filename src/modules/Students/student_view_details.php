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

namespace Module\Students ;

use Gibbon\core\view ;
use Gibbon\People\student ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
    //Get action with highest precendence
	$details = new \stdClass();
    $details->highestAction = $this->getSecurity()->getHighestGroupedAction($_GET['q']);
	$details->links = array();
    if (! $details->highestAction) {
        $this->displayMessage('The highest grouped action cannot be determined.');
    } else {
        $details->personID = isset($_GET['gibbonPersonID']) ? $_GET['gibbonPersonID'] : 0;
		$details->subpage = isset($_GET['subpage']) ? $_GET['subpage'] : null ;
        $details->search = isset($_GET['search']) ? $_GET['search'] : null;
        $details->allStudents = isset($_GET['allStudents']) ? $_GET['allStudents'] : '';
        $details->sort = isset($_GET['sort']) ? $_GET['sort'] : '';
        $details->hook = isset($_GET['hook']) ? $_GET['hook'] : '';
        $details->module = isset($_GET['module']) ? $_GET['module'] : '';
        $details->action = isset($_GET['action']) ? $_GET['action'] : '';

        if (intval($details->personID) == 0) {
            $this->displayMessage('You have not specified one or more required parameters.');
        } else {
			$details->student = new student($this, $details->personID);
            $skipBrief = true;
            //Test if View Student Profile_brief and View Student Profile_myChildren are both available and parent has access to this student...if so, skip brief, and go to full.
            if ($this->getSecurity()->isActionAccessible('/modules/Students/student_view_details.php', 'View Student Profile_brief', '') 
				&& $this->getSecurity()->isActionAccessible('/modules/Students/student_view_details.php', 'View Student Profile_myChildren', '') 
					&& $details->student->checkChildDataAccess())
                    	$skipBrief = false;

            if ($details->highestAction == 'View Student Profile_brief' && $skipBrief) {
				$this->render('Students.student.details.brief', $details);
            } else {
				if ($details->highestAction == 'View Student Profile_myChildren') {
					$this->render('Students.student.details.full', $details);
				} else {
					if ($details->allStudents == 'N') {
						$details->student->getEnrolment();
					} 
					if (! $details->student->validStudent) 
						$this->displayMessage('The selected record does not exist, or you do not have access to it.');
					else 
						$this->render('Students.student.details.full', $details);
				}
			}
		}
	}
}
