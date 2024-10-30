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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

//Module includes
include './moduleFunctions.php';

$gibbonDepartmentID = $_GET['gibbonDepartmentID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/department_course_edit.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=$gibbonCourseID";

if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if ($gibbonDepartmentID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        //Proceed!
        //Validate Inputs
        $description = $_POST['description'] ?? '';

        if ($gibbonDepartmentID == '' or $gibbonCourseID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Check access to specified course

                $data = array('gibbonCourseID' => $gibbonCourseID);
                $sql = 'SELECT * FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID';
                $result = $connection2->prepare($sql);
                $result->execute($data);

            if ($result->rowCount() != 1) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                //Get role within learning area
                $role = getRole($session->get('gibbonPersonID'), $gibbonDepartmentID, $connection2);

                if ($role != 'Coordinator' and $role != 'Assistant Coordinator' and $role != 'Teacher (Curriculum)') {
                    $URL .= '&return=error0';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('description' => $description, 'gibbonCourseID' => $gibbonCourseID);
                        $sql = 'UPDATE gibbonCourse SET description=:description WHERE gibbonCourseID=:gibbonCourseID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
