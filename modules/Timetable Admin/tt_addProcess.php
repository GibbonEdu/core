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

use Gibbon\Data\Validator;
use Gibbon\Http\Url;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$name = $_POST['name'] ?? '';
$nameShort = $_POST['nameShort'] ?? '';
$nameShortDisplay = $_POST['nameShortDisplay'] ?? '';
$active = $_POST['active'] ?? '';
$count = $_POST['count'] ?? '';
$gibbonYearGroupIDList = implode(',', $_POST['gibbonYearGroupID'] ?? []);
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';

$URL = Url::fromModuleRoute('Timetable Admin', 'tt_add')->withQueryParam('gibbonSchoolYearID', $gibbonSchoolYearID);

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_add.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
} else {
    //Proceed!
    //Validate Inputs
    if ($gibbonSchoolYearID == '' or $name == '' or $nameShort == '' or $nameShortDisplay == '') {
        header('Location: ' . $URL->withReturn('error1'));
    } else {
        //Check unique inputs for uniquness
        try {
            $data = array('name' => $name, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonTT WHERE (name=:name AND gibbonSchoolYearID=:gibbonSchoolYearID)';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            header('Location: ' . $URL->withReturn('error2'));
            exit();
        }

        if ($result->rowCount() > 0) {
            header('Location: ' . $URL->withReturn('error3'));
        } else {
            //Write to database
            try {
                $data = array('name' => $name, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'nameShort' => $nameShort, 'nameShortDisplay' => $nameShortDisplay, 'active' => $active, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList);
                $sql = 'INSERT INTO gibbonTT SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, nameShort=:nameShort, nameShortDisplay=:nameShortDisplay, active=:active, gibbonYearGroupIDList=:gibbonYearGroupIDList';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                header('Location: ' . $URL->withReturn('error2'));
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

            header('Location: ' . $URL->withQueryParam('editID', $AI)->withReturn('error0'));
        }
    }
}
