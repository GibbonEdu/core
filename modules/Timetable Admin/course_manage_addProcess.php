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

$gibbonDepartmentID = !empty($_POST['gibbonDepartmentID']) ? $_POST['gibbonDepartmentID'] : null;
$name = $_POST['name'] ?? '';
$nameShort = $_POST['nameShort'] ?? '';
$orderBy = $_POST['orderBy'] ?? '';
$description = $_POST['description'] ?? '';
$map = $_POST['map'] ?? 'N';
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
$gibbonYearGroupIDList = implode(',', $_POST['gibbonYearGroupIDList'] ?? []);

$URL = Url::fromModuleRoute('Timetable Admin', 'course_manage_add')->withQueryParam('gibbonSchoolYearID', $gibbonSchoolYearID);

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_add.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
} else {
    //Proceed!
    //Validate Inputs
    if ($gibbonSchoolYearID == '' or $name == '' or $nameShort == '' or $map == '') {
        header('Location: ' . $URL->withReturn('error1'));
    } else {
        //Check unique inputs for uniquness
        try {
            $data = array('name' => $name, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonCourse WHERE (name=:name AND gibbonSchoolYearID=:gibbonSchoolYearID)';
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
                $data = array('gibbonDepartmentID' => $gibbonDepartmentID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'name' => $name, 'nameShort' => $nameShort, 'orderBy' => $orderBy, 'description' => $description, 'map' => $map, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'fields' => $fields);
                $sql = 'INSERT INTO gibbonCourse SET gibbonDepartmentID=:gibbonDepartmentID, gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, nameShort=:nameShort, orderBy=:orderBy, description=:description, map=:map, gibbonYearGroupIDList=:gibbonYearGroupIDList, fields=:fields';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                header('Location: ' . $URL->withReturn('error2'));
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);
            header('Location: ' . $URL->withQueryParam('editID', $AI)->withReturn('success0'));
        }
    }
}
