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

use Gibbon\Tables\DataTable;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Module\Attendance\StudentHistoryData;
use Gibbon\Module\Attendance\StudentHistoryView;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentHistory_print.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

    if ($highestAction != 'Student History_all' || empty($gibbonPersonID)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $student = $container->get(UserGateway::class)->getByID($gibbonPersonID);

    if (!empty($student)) {
        // ATTENDANCE DATA
        $attendanceData = $container
            ->get(StudentHistoryData::class)
            ->getAttendanceData($_SESSION[$guid]['gibbonSchoolYearID'], $student['gibbonPersonID'], $student['dateStart'], $student['dateEnd']);

        // DATA TABLE
        $renderer = $container->get(StudentHistoryView::class);
        $renderer->addData('printView', true);
        
        $table = DataTable::create('studentHistory', $renderer);
        $table->setTitle(__('Attendance History for').' '.formatName('', $student['preferredName'], $student['surname'], 'Student'));
        $table->addHeaderAction('print', __('Print'))
            ->setExternalURL('javascript:window.print()')
            ->setIcon('print')
            ->displayLabel();

        echo $table->render($attendanceData);
    }
}
