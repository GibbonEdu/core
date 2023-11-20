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
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Data\Validator;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Students\FirstAidGateway;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/firstAidRecord_add.php&gibbonFormGroupID='.$_GET['gibbonFormGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'];

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_add.php') == false) {
    $URL .= '&return=error0&step=1';
    header("Location: {$URL}");
} else {
    //Proceed!
    $data = [
        'gibbonPersonIDPatient'    => $_POST['gibbonPersonID'] ?? '',
        'gibbonPersonIDFirstAider' => $session->get('gibbonPersonID'),
        'gibbonPersonIDFollowUp'   => $_POST['gibbonPersonIDFollowUp'] ?? null,
        'date'                     => !empty($_POST['date']) ? Format::dateConvert($_POST['date']) : '',
        'timeIn'                   => $_POST['timeIn'] ?? '',
        'description'              => $_POST['description'] ?? '',
        'actionTaken'              => $_POST['actionTaken'] ?? '',
        'followUp'                 => $_POST['followUp'] ?? '',
        'gibbonSchoolYearID'       => $session->get('gibbonSchoolYearID'),
    ];

    $firstAidGateway = $container->get(FirstAidGateway::class);
    $student = $container->get(UserGateway::class)->getByID($data['gibbonPersonIDPatient'], ['preferredName', 'surname']);

    if ($data['gibbonPersonIDPatient'] == '' || $data['gibbonPersonIDFirstAider'] == '' || $data['date'] == '' || $data['timeIn'] == '' || empty($student)) {
        $URL .= '&return=error1&step=1';
        header("Location: {$URL}");
        exit;
    }

    $customRequireFail = false;
    $data['fields'] = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('First Aid', [], $customRequireFail);

    if ($customRequireFail) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $gibbonFirstAidID = $firstAidGateway->insert($data);
    $gibbonFirstAidID = str_pad($gibbonFirstAidID, 12, '0', STR_PAD_LEFT);

    // Send a notification to the requested user
    if (!empty($data['gibbonPersonIDFollowUp'])) {
        $notificationSender = $container->get(NotificationSender::class);

        $text = __('A first aid record has been created for {name} at {time}. Your follow-up has been requested. Please click below to view and enter details.', ['name' => Format::name('', $student['preferredName'], $student['surname'], 'Student'), 'time' => $data['timeIn']]);
        $actionLink = '/index.php?q=/modules/Students/firstAidRecord_edit.php&gibbonFirstAidID='.$gibbonFirstAidID;

        $notificationSender->addNotification($data['gibbonPersonIDFollowUp'], $text, 'First Aid', $actionLink);
        $notificationSender->sendNotifications();
    }

    $URL .= "&return=success0&editID=$gibbonFirstAidID";
    header("Location: {$URL}");

}
