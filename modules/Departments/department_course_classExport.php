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

use Gibbon\Domain\Timetable\CourseClassPersonGateway;
use Gibbon\Domain\Timetable\CourseGateway;

include '../../gibbon.php';

//Module includes
include './moduleFunctions.php';

$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['address'])."/department_course_class.php&gibbonCourseClassID=$gibbonCourseClassID";
$highestAction = getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2);

if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_class.php') == false || empty($highestAction)) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {

    if ($highestAction != 'View Student Profile_full' && $highestAction != 'View Student Profile_fullNoNotes'  && $highestAction != 'View Student Profile_fullEditAllNotes') {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    if ($gibbonCourseClassID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {

            $result = $container->get(CourseGateway::class)->selectCourseAndClassNameByCourseClassID($gibbonCourseClassID);
        if ($result->rowCount() < 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        } else {
            //Proceed!
            $result = $container->get(CourseClassPersonGateway::class)->selectStudentsInEachClass($gibbonCourseClassID, $session->get('gibbonSchoolYearID'));

            $exp = new Gibbon\Excel();
            $exp->exportWithQuery($result, 'classList.xls');
        }
    }
}
