<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Data\Validator;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\Timetable\CourseClassGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonSchoolYearIDNext = $_GET['gibbonSchoolYearIDNext'] ?? '';
$URL = $session->get('absoluteURL')."/index.php?q=/modules/Timetable Admin/course_manage.php&gibbonSchoolYearID=$gibbonSchoolYearIDNext";

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    
    // Check if school years specified (current and next)
    if (empty($gibbonSchoolYearID) || empty($gibbonSchoolYearIDNext)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } 

    $courseGateway = $container->get(CourseGateway::class);
    $courseClassGateway = $container->get(CourseClassGateway::class);

    // Get current courses
    $courses = $courseGateway->selectBy(['gibbonSchoolYearID' => $gibbonSchoolYearID])->fetchAll();
    if (empty($courses)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    }

    $partialFail = false;

    foreach ($courses as $course) {
        $data = [
            'gibbonSchoolYearID'    => $gibbonSchoolYearIDNext,
            'gibbonDepartmentID'    => $course['gibbonDepartmentID'],
            'name'                  => $course['name'],
            'nameShort'             => $course['nameShort'],
            'description'           => $course['description'],
            'gibbonYearGroupIDList' => $course['gibbonYearGroupIDList'],
            'orderBy'               => $course['orderBy'],
            'map'                   => $course['map'],
            'fields'                => $course['fields'],
        ];

        // Skip courses that already exist
        if (!$courseGateway->unique($data, ['gibbonSchoolYearID', 'nameShort'])) {
            continue;
        }

        // Insert course into database
        $gibbonCourseIDNew = $courseGateway->insert($data);
            
        if (empty($gibbonCourseIDNew)) {
            $partialFail = true;
            continue;
        }
        
        $classes = $courseClassGateway->selectBy(['gibbonCourseID' => $course['gibbonCourseID']])->fetchAll();

        foreach ($classes as $class) {
            $data = [
                'gibbonCourseID'      => $gibbonCourseIDNew,
                'name'                => $class['name'],
                'nameShort'           => $class['nameShort'],
                'reportable'          => $class['reportable'],
                'attendance'          => $class['attendance'],
                'enrolmentMin'        => $class['enrolmentMin'],
                'enrolmentMax'        => $class['enrolmentMax'],
                'gibbonScaleIDTarget' => $class['gibbonScaleIDTarget'],
                'fields'              => $class['fields'],
            ];

            // Skip classes that already exist
            if (!$courseClassGateway->unique($data, ['gibbonCourseID', 'nameShort'])) {
                continue;
            }

            // Insert class into database
            $gibbonCourseClassIDNew = $courseClassGateway->insert($data);

            if (empty($gibbonCourseClassIDNew)) {
                $partialFail = true;
            }
        }
    }

    $URL .= $partialFail == true
        ? '&return=error5'
        : '&return=success0';

    header("Location: {$URL}");
}
