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

use Gibbon\Domain\Activities\ActivitySlotGateway;

include '../../gibbon.php';

$gibbonActivityID = $_GET['gibbonActivityID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/activities_manage_edit.php&gibbonActivityID=$gibbonActivityID&search=".$_GET['search'].'&gibbonSchoolYearTermID='.$_GET['gibbonSchoolYearTermID'];

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonActivityID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonActivityID' => $gibbonActivityID);
            $sql = 'SELECT * FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            //Validate Inputs
            $name = $_POST['name'] ?? '';
            $provider = $_POST['provider'] ?? '';
            $active = $_POST['active'] ?? '';
            $registration = $_POST['registration'] ?? '';
            $dateType = $_POST['dateType'] ?? '';
            if ($dateType == 'Term') {
                $gibbonSchoolYearTermIDList = $_POST['gibbonSchoolYearTermIDList'] ?? [];
                $gibbonSchoolYearTermIDList = implode(',', $gibbonSchoolYearTermIDList);
            } elseif ($dateType == 'Date') {
                $listingStart = dateConvert($guid, $_POST['listingStart'] ?? '');
                $listingEnd = dateConvert($guid, $_POST['listingEnd'] ?? '');
                $programStart = dateConvert($guid, $_POST['programStart'] ?? '');
                $programEnd = dateConvert($guid, $_POST['programEnd'] ?? '');
            }
            $gibbonYearGroupIDList = $_POST['gibbonYearGroupIDList'] ?? array();
            $gibbonYearGroupIDList = implode(',', $gibbonYearGroupIDList);

            $maxParticipants = $_POST['maxParticipants'] ?? '';
            if (getSettingByScope($connection2, 'Activities', 'payment') == 'None' or getSettingByScope($connection2, 'Activities', 'payment') == 'Single') {
                $paymentOn = false;
                $payment = null;
                $paymentType = null;
                $paymentFirmness = null;
            } else {
                $paymentOn = true;
                $payment = $_POST['payment'] ?? '';
                $paymentType = $_POST['paymentType'] ?? '';
                $paymentFirmness = $_POST['paymentFirmness'] ?? '';
            }
            $description = $_POST['description'] ?? '';

            if ($dateType == '' or $name == '' or $provider == '' or $active == '' or $registration == '' or $maxParticipants == '' or ($paymentOn and ($payment == '' or $paymentType == '' or $paymentFirmness == '')) or ($dateType == 'Date' and ($listingStart == '' or $listingEnd == '' or $programStart == '' or $programEnd == ''))) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                $partialFail = false;

                $activitySlotGateway = $container->get(ActivitySlotGateway::class);
                $activitySlotGateway->deleteWhere(['gibbonActivityID' => $gibbonActivityID]);

                $timeSlotOrder = $_POST['order'] ?? [];
                foreach ($timeSlotOrder as $order) {
                    $slot = $_POST['timeSlots'][$order];

                    if (empty($slot['gibbonDaysOfWeekID']) || empty($slot['timeStart']) || empty('timeEnd')) {
                        continue;
                    }

                    $slot['gibbonActivityID'] = $gibbonActivityID;

                    $type = $slot['location'] ?? 'Internal';
                    if ($type == 'Internal') {
                        $slot['locationExternal'] = '';
                        if (empty($slot['gibbonSpaceID'])) {
                            continue;
                        }
                    } else {
                        $slot['gibbonSpaceID'] = null;
                        if (empty($slot['locationExternal'])) {
                            continue;
                        }
                    }

                    unset($slot['location']);

                    $activitySlotGateway->insert($slot);
                }

                // Scan through staff
                $staff = $_POST['staff'] ?? [];
                $role = $_POST['role'] ?? 'Other';

                // make sure that staff is an array
                if (!is_array($staff)) {
                    $staff = [(string) $staff];
                }

                if (count($staff) > 0) {
                    foreach ($staff as $t) {
                        //Check to see if person is already registered in this activity
                        try {
                            $dataGuest = array('gibbonPersonID' => $t, 'gibbonActivityID' => $gibbonActivityID);
                            $sqlGuest = 'SELECT * FROM gibbonActivityStaff WHERE gibbonPersonID=:gibbonPersonID AND gibbonActivityID=:gibbonActivityID';
                            $resultGuest = $connection2->prepare($sqlGuest);
                            $resultGuest->execute($dataGuest);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }

                        if ($resultGuest->rowCount() == 0) {
                            try {
                                $data = array('gibbonPersonID' => $t, 'gibbonActivityID' => $gibbonActivityID, 'role' => $role);
                                $sql = 'INSERT INTO gibbonActivityStaff SET gibbonPersonID=:gibbonPersonID, gibbonActivityID=:gibbonActivityID, role=:role';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                echo "here<div class='error'>".$e->getMessage().'</div>';
                                $partialFail = true;
                            }
                        }
                    }
                }

                //Write to database
                $type = $_POST['type'] ?? '';

                try {
                    if ($dateType == 'Date') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID, 'name' => $name, 'provider' => $provider, 'type' => $type, 'active' => $active, 'registration' => $registration, 'listingStart' => $listingStart, 'listingEnd' => $listingEnd, 'programStart' => $programStart, 'programEnd' => $programEnd, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'maxParticipants' => $maxParticipants, 'payment' => $payment, 'paymentType' => $paymentType, 'paymentFirmness' => $paymentFirmness, 'description' => $description);
                        $sql = "UPDATE gibbonActivity SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, provider=:provider, type=:type, active=:active, registration=:registration, gibbonSchoolYearTermIDList='', listingStart=:listingStart, listingEnd=:listingEnd, programStart=:programStart, programEnd=:programEnd, gibbonYearGroupIDList=:gibbonYearGroupIDList, maxParticipants=:maxParticipants, payment=:payment, paymentType=:paymentType, paymentFirmness=:paymentFirmness, description=:description WHERE gibbonActivityID=:gibbonActivityID";
                    } else {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID, 'name' => $name, 'provider' => $provider, 'type' => $type, 'active' => $active, 'registration' => $registration, 'gibbonSchoolYearTermIDList' => $gibbonSchoolYearTermIDList, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'maxParticipants' => $maxParticipants, 'payment' => $payment, 'paymentType' => $paymentType, 'paymentFirmness' => $paymentFirmness, 'description' => $description);
                        $sql = 'UPDATE gibbonActivity SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, provider=:provider, type=:type, active=:active, registration=:registration, gibbonSchoolYearTermIDList=:gibbonSchoolYearTermIDList, listingStart=NULL, listingEnd=NULL, programStart=NULL, programEnd=NULL, gibbonYearGroupIDList=:gibbonYearGroupIDList, maxParticipants=:maxParticipants, payment=:payment, paymentType=:paymentType, paymentFirmness=:paymentFirmness, description=:description WHERE gibbonActivityID=:gibbonActivityID';
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($partialFail == true) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
