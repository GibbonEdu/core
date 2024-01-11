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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Comms\NotificationEvent;

include '../../gibbon.php';

$action =  $_POST['action'] ?? '';
$gibbonStaffID =  $_POST['gibbonStaffID'] ?? array();
$dateEnd = !empty($_POST['dateEnd']) ? Format::dateConvert($_POST['dateEnd']) : null;

$allStaff = $_GET['allStaff'] ?? '';
$search = $_GET['search'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/staff_manage.php&search=$search&allStaff=$allStaff";

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if (empty($action) || empty($gibbonStaffID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $staffGateway = $container->get(StaffGateway::class);
        $userGateway = $container->get(UserGateway::class);
        $gibbonStaffIDList = is_array($gibbonStaffID)? implode(',', $gibbonStaffID) : $gibbonStaffID;

        if ($action == 'Left') {
            $data = array('gibbonStaffIDList' => $gibbonStaffIDList, 'dateEnd' => $dateEnd);
            $sql = "UPDATE gibbonStaff
                    JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID)
                    SET gibbonPerson.status='Left', gibbonPerson.dateEnd=:dateEnd
                    WHERE FIND_IN_SET(gibbonStaffID, :gibbonStaffIDList)";

            $updated = $pdo->update($sql, $data);

            if ($pdo->getQuerySuccess() == false){
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            // Raise a new notification event
            $event = new NotificationEvent('Staff', 'Staff Left');

            $notificationText = __('The following staff members have been marked as Left on {date}:', [
                'date' => Format::date($dateEnd),
            ]).'<br/><ul>';

            foreach (explode(',', $gibbonStaffIDList) as $gibbonStaffID) {
                $staff =  $staffGateway->getByID($gibbonStaffID);
                $person = $userGateway->getByID($staff['gibbonPersonID']);
                $notificationText .= '<li>'.Format::name('', $person['preferredName'], $person['surname'], 'Staff', false, true).' ('.$person['username'].') '.$staff['jobTitle'].'</li>';
            }
            $notificationText .= '</ul>';

            $event->setNotificationText($notificationText);
            $event->setActionLink('/index.php?q=/modules/Staff/staff_view.php&allStaff=on');

            // Send notifications
            $event->sendNotifications($pdo, $session);
        }

        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit();
    }
}
