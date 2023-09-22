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

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/activities_manage_add.php&search='.$_GET['search'].'&gibbonSchoolYearTermID='.$_GET['gibbonSchoolYearTermID'];

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $partialFail = false;
    $name = $_POST['name'] ?? '';
    $provider = $_POST['provider'] ?? '';
    $active = $_POST['active'] ?? '';
    $registration = $_POST['registration'] ?? '';
    $dateType = $_POST['dateType'] ?? '';
    if ($dateType == 'Term') {
        $gibbonSchoolYearTermIDList =  $_POST['gibbonSchoolYearTermIDList'] ?? [];
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
        //Write to database
        $activityGateway = $container->get(ActivityGateway::class);
        
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

        $gibbonActivityID = $activityGateway->insert($data);

        if (!$gibbonActivityID) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        $activitySlotGateway = $container->get(ActivitySlotGateway::class);

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

            $activitySlotGateway->insert($slot);
        }

        // Scan through staff
        $staff = $_POST['staff'] ?? [];
        $role = $_POST['role'] ?? 'Other';

        // make sure that staff is an array
        if (!is_array($staff)) {
            $staff = [strval($staff)];
        }

        $activityStaffGateway = $container->get(ActivityStaffGateway::class);
        foreach ($staff as $staffPersonID) {
            $partialFail |= !$activityStaffGateway->insertActivityStaff($gibbonActivityID, $staffPersonID, $role);
        }

        if (isset($partialFail) && $partialFail == true) {
            $URL .= '&return=warning1';
        } else {
            $URL .= '&return=success0';
        }

        $URL .= '&editID=' . $gibbonActivityID;
        header("Location: {$URL}");
    }
}
