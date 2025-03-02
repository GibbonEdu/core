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

use Gibbon\Domain\System\SettingGateway;

require_once '../../gibbon.php';

require_once  './moduleFunctions.php';

$publicUnits = $container->get(SettingGateway::class)->getSettingByScope('Free Learning', 'publicUnits');

$highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse_details.php', $connection2);

//Get params
$freeLearningUnitID = $_GET['freeLearningUnitID'] ?? '';
$canManage = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and $highestAction == 'Browse Units_all';
$showInactive = ($canManage and isset($_GET['showInactive'])) ? $_GET['showInactive'] : 'N';
$gibbonDepartmentID = $_REQUEST['gibbonDepartmentID'] ?? '';
$difficulty = $_GET['difficulty'] ?? '';
$name = $_GET['name'] ?? '';
$view = $_GET['view'] ?? '';
if ($view != 'grid' and $view != 'map') {
    $view = 'list';
}
$gibbonPersonID = ($canManage and isset($_GET['gibbonPersonID'])) ? $_GET['gibbonPersonID'] : $session->get('gibbonPersonID');

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/units_browse_details_enrolMultiple.php&freeLearningUnitID='.$freeLearningUnitID.'&gibbonDepartmentID='.$gibbonDepartmentID.'&difficulty='.$difficulty.'&name='.$name.'&showInactive='.$showInactive.'&tab=2&view='.$view;

if (!isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php') || !isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php')) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if ($highestAction == false) {
        //Fail 0
        $URL .= '&updateReturn=error0';
        header("Location: {$URL}");
    } else {
        $roleCategory = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);

        $freeLearningUnitID = $_GET['freeLearningUnitID'] ?? '';

        if ($freeLearningUnitID == '') {
            //Fail 3
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            try {
                $unitList = getUnitList($connection2, $guid, $session->get('gibbonPersonID'), $roleCategory, $highestAction, null, null, null, $showInactive, $publicUnits, $freeLearningUnitID, null);
                $data = $unitList[0];
                $sql = $unitList[1];
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                //Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
            }

            if ($result->rowCount() != 1) {
                //Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $row = $result->fetch();

                //Proceed!
                $gibbonPersonIDMulti = $_POST['gibbonPersonIDMulti'] ?? null;
                $gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';
                $status = $_POST['status'] ?? '';

                if (is_null($gibbonPersonIDMulti) == true or $status == '' or $gibbonCourseClassID == '') {
                    //Fail 3
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    $partialFail = false;

                    foreach ($gibbonPersonIDMulti as $gibbonPersonID) {
                        //Write to database
                        try {
                            $data = array('gibbonPersonID' => substr($gibbonPersonID, 9), 'freeLearningUnitID' => $freeLearningUnitID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonCourseClassID' => $gibbonCourseClassID, 'grouping' => 'Individual', 'status' => $status);
                            $sql = 'INSERT INTO freeLearningUnitStudent SET gibbonPersonIDStudent=:gibbonPersonID, freeLearningUnitID=:freeLearningUnitID, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonCourseClassID=:gibbonCourseClassID, `grouping`=:grouping, status=:status';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }

                    if ($partialFail == true) {
                        //Fail 5
                        $URL .= '&return=error5';
                        header("Location: {$URL}");
                    } else {
                        //Success 0
                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
