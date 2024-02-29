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

use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$allStaff = '';
if (isset($_GET['allStaff'])) {
    $allStaff = $_GET['allStaff'] ?? '';
}
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'] ?? '';
}
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/staff_manage_add.php&search=$search&allStaff=$allStaff";

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
    $initials = $_POST['initials'] ?? '';
    if ($initials == '') {
        $initials = null;
    }
    $type = $_POST['type'] ?? '';
    $jobTitle = $_POST['jobTitle'] ?? '';
    $firstAidQualified = $_POST['firstAidQualified'] ?? '';
    $firstAidQualification = $_POST['firstAidQualification'] ?? null;
    $firstAidExpiry = ($firstAidQualified == 'Y' and !empty($_POST['firstAidExpiry'])) ? Format::dateConvert($_POST['firstAidExpiry']) : null;
    $countryOfOrigin = $_POST['countryOfOrigin'] ?? '';
    $qualifications = $_POST['qualifications'] ?? '';
    $biographicalGrouping = $_POST['biographicalGrouping'] ?? '';
    $biographicalGroupingPriority = $_POST['biographicalGroupingPriority'] ?? '';
    $biography = $_POST['biography'] ?? '';

    //Validate Inputs
    if ($gibbonPersonID == '' or $type == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniquness
        try {
            if ($initials == '') {
                $data = array('gibbonPersonID' => $gibbonPersonID);
                $sql = 'SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID';
            } else {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'initials' => $initials);
                $sql = 'SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID OR initials=:initials';
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        $customRequireFail = false;
        $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('Staff', [], $customRequireFail);

        if ($customRequireFail) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        if ($result->rowCount() > 0) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'initials' => $initials, 'type' => $type, 'jobTitle' => $jobTitle, 'firstAidQualified' => $firstAidQualified, 'firstAidQualification' => $firstAidQualification, 'firstAidExpiry' => $firstAidExpiry, 'countryOfOrigin' => $countryOfOrigin, 'qualifications' => $qualifications, 'biographicalGrouping' => $biographicalGrouping, 'biographicalGroupingPriority' => $biographicalGroupingPriority, 'biography' => $biography, 'fields' => $fields);
                $sql = 'INSERT INTO gibbonStaff SET gibbonPersonID=:gibbonPersonID, initials=:initials, type=:type, jobTitle=:jobTitle, firstAidQualified=:firstAidQualified, firstAidQualification=:firstAidQualification, firstAidExpiry=:firstAidExpiry, countryOfOrigin=:countryOfOrigin, qualifications=:qualifications, biographicalGrouping=:biographicalGrouping, biographicalGroupingPriority=:biographicalGroupingPriority, biography=:biography, fields=:fields';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 10, '0', STR_PAD_LEFT);

            // Raise a new notification event
            $event = new NotificationEvent('Staff', 'New Staff');

            $person = $container->get(UserGateway::class)->getByID($gibbonPersonID);
            $event->setNotificationText(__('A new staff member has been added: {name} ({username}) {jobTitle}', [
                'name' => Format::name('', $person['preferredName'], $person['surname'], 'Staff', false, true),
                'username' => $person['username'],
                'jobTitle' => $jobTitle,
            ]));
            $event->setActionLink('/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$gibbonPersonID.'&allStaff=&search=');

            // Send notifications
            $event->sendNotifications($pdo, $session);

            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
