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

use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivitySlotGateway;
use Gibbon\Domain\Activities\ActivityStaffGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$gibbonActivityID = $_POST['gibbonActivityID'] ?? '';
$search = $_POST['search'] ?? '';
$gibbonSchoolYearTermID = $_POST['gibbonSchoolYearTermID'] ?? '';

$URL = $session->get('absoluteURL') . '/index.php?q=/modules/' . $session->get('module') . "/activities_manage_edit.php&gibbonActivityID=$gibbonActivityID&search=$search&gibbonSchoolYearTermID=$gibbonSchoolYearTermID";

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonActivityID specified
    $activityGateway = $container->get(ActivityGateway::class);

    if (!$activityGateway->exists($gibbonActivityID)) {
        $URL .= '&return=error1';
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
            $listingStart = Format::dateConvert($_POST['listingStart'] ?? '');
            $listingEnd = Format::dateConvert($_POST['listingEnd'] ?? '');
            $programStart = Format::dateConvert($_POST['programStart'] ?? '');
            $programEnd = Format::dateConvert($_POST['programEnd'] ?? '');
        }

        $gibbonYearGroupIDList = $_POST['gibbonYearGroupIDList'] ?? [];
        $gibbonYearGroupIDList = implode(',', $gibbonYearGroupIDList);

        $maxParticipants = $_POST['maxParticipants'] ?? '';

        $settingGateway = $container->get(SettingGateway::class);
        $paymentMethod = $settingGateway->getSettingByScope('Activities', 'payment');
        if ($paymentMethod == 'None' || $paymentMethod == 'Single') {
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

        if ($dateType == '' || $name == '' || $provider == '' || $active == '' || $registration == '' || $maxParticipants == '' || ($paymentOn && ($payment == '' || $paymentType == '' || $paymentFirmness == '')) || ($dateType == 'Date' && ($listingStart == '' || $listingEnd == '' || $programStart == '' || $programEnd == ''))) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $partialFail = false;

            $activitySlotGateway = $container->get(ActivitySlotGateway::class);
            $activitySlots = [];

            $timeSlotOrder = $_POST['order'] ?? [];
            foreach ($timeSlotOrder as $order) {
                $slot = $_POST['timeSlots'][$order];

                if (empty($slot['gibbonDaysOfWeekID']) || empty($slot['timeStart']) || empty('timeEnd')) {
                    continue;
                }

                //If start is after end, swap times.
                if ($slot['timeStart'] > $slot['timeEnd']) {
                    $temp = $slot['timeStart'];
                    $slot['timeStart'] = $slot['timeEnd'];
                    $slot['timeEnd'] = $temp;
                }

                $slot['gibbonActivityID'] = $gibbonActivityID;

                $type = $slot['location'] ?? 'Internal';
                if ($type == 'Internal') {
                    $slot['locationExternal'] = '';
                } else {
                    $slot['gibbonSpaceID'] = null;
                }

                unset($slot['location']);

                if (!empty($slot['gibbonActivitySlotID'])) {
                    $gibbonActivitySlotID = $slot['gibbonActivitySlotID'];
                    $activitySlotGateway->update($gibbonActivitySlotID, $slot);
                } else {
                    $gibbonActivitySlotID = $activitySlotGateway->insert($slot);
                }

                $activitySlots[] = str_pad($gibbonActivitySlotID, 10, 0, STR_PAD_LEFT);
            }

            $activitySlotGateway->deleteActivitySlotsNotInList($gibbonActivityID, $activitySlots);

            // Scan through staff
            $staff = $_POST['staff'] ?? [];
            $role = $_POST['role'] ?? 'Other';

            // make sure that staff is an array
            if (!is_array($staff)) {
                $staff = [strval($staff)];
            }

            $activityStaffGateway = $container->get(ActivityStaffGateway::class);
            if (count($staff) > 0) {
                foreach ($staff as $staffPersonID) {
                    //Check to see if person is already registered in this activity
                    $resultGuest = $activityStaffGateway->selectActivityStaffByID($gibbonActivityID, $staffPersonID);

                    if ($resultGuest->isEmpty()) {
                        if (!$activityStaffGateway->insertActivityStaff($gibbonActivityID, $staffPersonID, $role)) {
                            $partialFail = true;
                        }
                    }
                }
            }

            //Write to database
            $type = $_POST['type'] ?? '';

            $data = [
                'gibbonSchoolYearID'    => $session->get('gibbonSchoolYearID'),
                'name'                  => $name,
                'provider'              => $provider,
                'type'                  => $type,
                'active'                => $active,
                'registration'          => $registration,
                'gibbonYearGroupIDList' => $gibbonYearGroupIDList,
                'maxParticipants'       => $maxParticipants,
                'payment'               => $payment,
                'paymentType'           => $paymentType,
                'paymentFirmness'       => $paymentFirmness,
                'description'           => $description
            ];

            if ($dateType == 'Date') {
                $data['gibbonSchoolYearTermIDList'] = '';
                $data['listingStart'] = $listingStart;
                $data['listingEnd'] = $listingEnd;
                $data['programStart'] = $programStart;
                $data['programEnd'] = $programEnd;
            } else {
                $data['gibbonSchoolYearTermIDList'] = $gibbonSchoolYearTermIDList;
                $data['listingStart'] = null;
                $data['listingEnd'] = null;
                $data['programStart'] = null;
                $data['programEnd'] = null;
            }

            if (!$activityGateway->update($gibbonActivityID, $data)) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $return = $partialFail ? 'error3' : 'success0';
                $URL .= "&return=$return";
                header("Location: {$URL}");
            }
        }
    }
}
