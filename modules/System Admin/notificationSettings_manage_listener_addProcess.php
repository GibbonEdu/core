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

use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonNotificationEventID = $_POST['gibbonNotificationEventID'] ?? null;
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/notificationSettings_manage_edit.php&gibbonNotificationEventID=".$gibbonNotificationEventID;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/notificationSettings_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    if ($gibbonNotificationEventID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $gateway = new NotificationGateway($pdo);

        $result = $gateway->selectNotificationEventByID($gibbonNotificationEventID);
        if ($result->rowCount() != 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        $gibbonPersonID = (isset($_POST['gibbonPersonID']))? $_POST['gibbonPersonID'] : '';
        $scopeType = (isset($_POST['scopeType']))? $_POST['scopeType'] : '';
        $scopeID = (isset($_POST[$scopeType]))? $_POST[$scopeType] : 0;

        if (empty($gibbonPersonID) || empty($scopeType)) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        } else {
            $listener = array(
                'gibbonNotificationEventID' => $gibbonNotificationEventID,
                'gibbonPersonID'            => $gibbonPersonID,
                'scopeType'                 => $scopeType,
                'scopeID'                   => $scopeID
            );

            $result = $gateway->insertNotificationListener($listener);

            $URL .= '&return=success0';
            header("Location: {$URL}");
            exit;
        }
    }
}
