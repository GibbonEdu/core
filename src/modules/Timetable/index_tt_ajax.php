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

namespace Gibbon\Timetable ;

use Gibbon\core\view ;
use Module\Timetable\Functions\functions ;

if (! $this instanceof view) die();

//Setup variables
$output = '';
$id = isset($_POST['gibbonTTID']) ? $_POST['gibbonTTID'] : 0 ;

if (! $this->getSecurity()->isActionAccessible('/modules/Timetable/tt.php')) {
    //Acess denied
    $output .= $this->returnMessage('Your request failed because you do not have access to this action.');
} else {
   	$mf = new functions($this);
    $ttDate = '';
    if (! empty($_POST['ttDate'])) {
        $ttDate = $mf->dateConvertToTimestamp($mf->dateConvert($_POST['ttDate']));
    }
	$_POST['fromTT'] = ! empty($_POST['fromTT']) ? $_POST['fromTT'] : 'N';
	
    if ($_POST['fromTT'] == 'Y') {
        $this->session->set('viewCalendarSchool', ($_POST['schoolCalendar'] == 'on' || $_POST['schoolCalendar'] == 'Y' ? 'Y' : 'N'));

        $this->session->set('viewCalendarPersonal', ($_POST['personalCalendar'] == 'on' or $_POST['personalCalendar'] == 'Y' ? 'Y' : 'N'));

        $this->session->set('viewCalendarSpaceBooking', ($_POST['spaceBookingCalendar'] == 'on' or $_POST['spaceBookingCalendar'] == 'Y' ? 'Y' : 'N'));
    }
    $tt = $mf->renderTT($this->session->get('gibbonPersonID'), $id, false, $ttDate, '', '', 'trim');
    if ($tt !== false) {
        $output .= $tt;
    } else {
        $this->returnMessage('There is no information for the date specified.');
    }
}

echo $output;
$this->displayScripts();
