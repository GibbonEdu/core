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

use Gibbon\Domain\Planner\PlannerEntryStudentTrackerGateway;
use Gibbon\Domain\Planner\PlannerEntryStudentHomeworkGateway;

include '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_deadlines.php') == false) {
    die('error0');
} else {
    $category = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
    if ($category != 'Student') {
        die('error0');
    } else {
        $complete = $_POST['complete'] ?? 'N';
        $type = $_POST['type'] ?? '';

        $data = [
            'gibbonPlannerEntryID' => $_POST['gibbonPlannerEntryID'] ?? '',
            'gibbonPersonID'       => $_SESSION[$guid]['gibbonPersonID'] ?? '',
        ];

        if (empty($complete) || empty($type) || empty($data['gibbonPlannerEntryID']) || empty($data['gibbonPersonID'])) {
            die('error1');
        }

        if ($type == 'teacherRecorded') {
            $studentTrackerGateway = $container->get(PlannerEntryStudentTrackerGateway::class);
            $values = $studentTrackerGateway->selectBy($data)->fetch();
            $data['homeworkComplete'] = $complete;

            if (!empty($values)) {
                $updated = $studentTrackerGateway->update($values['gibbonPlannerEntryStudentTrackerID'], $data);
            } else {
                $updated = $studentTrackerGateway->insert($data);
            }

        } elseif ($type == 'studentRecorded') {
            $studentHomeworkGateway = $container->get(PlannerEntryStudentHomeworkGateway::class);
            $values = $studentHomeworkGateway->selectBy($data)->fetch();
            $data['homeworkComplete'] = $complete;

            if (!empty($values)) {
                $updated = $studentHomeworkGateway->update($values['gibbonPlannerEntryStudentHomeworkID'], $data);
            } else {
                $updated = $studentHomeworkGateway->insert($data);
            }
        }

        die(!$updated ? 'error1' : 'success0');
    }
}
