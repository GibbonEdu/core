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

use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Data\Validator;
use Gibbon\Http\Url;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$URL = Url::fromModuleRoute('Timetable Admin', 'course_manage_edit')
    ->withQueryParams([
        'gibbonCourseID' => $gibbonCourseID,
        'gibbonSchoolYearID' => $_POST['gibbonSchoolYearID'] ?? '',
    ]);

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_edit.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
} else {
    //Proceed!
    //Check if special day specified
    if ($gibbonCourseID == '') {
        header('Location: ' . $URL->withReturn('error1'));
    } else {
        try {
            $data = array('gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT * FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            header('Location: ' . $URL->withReturn('error2'));
            exit();
        }

        if ($result->rowCount() != 1) {
            header('Location: ' . $URL->withReturn('error2'));
        } else {
            //Validate Inputs
            $gibbonDepartmentID = !empty($_POST['gibbonDepartmentID']) ? $_POST['gibbonDepartmentID'] : null;
            $name = $_POST['name'] ?? '';
            $nameShort = $_POST['nameShort'] ?? '';
            $orderBy = $_POST['orderBy'] ?? '';
            $description = $_POST['description'] ?? '';
            $map = $_POST['map'] ?? '';
            $gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
            $gibbonYearGroupIDList = implode(',', $_POST['gibbonYearGroupIDList'] ?? []);

            if ($name == '' or $nameShort == '' or $gibbonSchoolYearID == '' or $map == '') {
                header('Location: ' . $URL->withReturn('error3'));
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID);
                    $sql = 'SELECT * FROM gibbonCourse WHERE (name=:name AND gibbonSchoolYearID=:gibbonSchoolYearID) AND NOT (gibbonCourseID=:gibbonCourseID)';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    header('Location: ' . $URL->withReturn('error2'));
                    exit();
                }

                $customRequireFail = false;
                $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('Course', [], $customRequireFail);

                if ($customRequireFail) {
                    header('Location: ' . $URL->withReturn('error1'));
                    exit;
                }

                if ($result->rowCount() > 0) {
                    header('Location: ' . $URL->withReturn('error3'));
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonDepartmentID' => $gibbonDepartmentID, 'name' => $name, 'nameShort' => $nameShort, 'orderBy' => $orderBy, 'description' => $description, 'map' => $map, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'fields' => $fields, 'gibbonCourseID' => $gibbonCourseID);
                        $sql = 'UPDATE gibbonCourse SET gibbonDepartmentID=:gibbonDepartmentID, name=:name, nameShort=:nameShort, orderBy=:orderBy, description=:description, map=:map, gibbonYearGroupIDList=:gibbonYearGroupIDList, fields=:fields WHERE gibbonCourseID=:gibbonCourseID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        header('Location: ' . $URL->withReturn('error2'));
                        exit();
                    }

                    header('Location: ' . $URL->withReturn('success0'));
                }
            }
        }
    }
}
