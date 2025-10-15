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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Services\Format;
use Gibbon\Forms\FormFactory;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;

require_once '../../gibbon.php';

if (!isActionAccessible($guid, $connection2, '/modules/Student Alerts/studentAlerts_add.php')) {
    // Access denied
    exit;
} else {
    $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
    $gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';

    if (empty($gibbonCourseClassID)) exit;

    $students = $container->get(CourseEnrolmentGateway::class)->selectClassStudentEnrolment($gibbonCourseClassID)->fetchAll();

    echo $container->get(FormFactory::class)->createSelectPerson('gibbonPersonID')
        ->fromArray(Format::nameListArray($students, 'Student', true))
        ->placeholder()
        ->selected($gibbonPersonID)
        ->required()
        ->getOutput();
}
