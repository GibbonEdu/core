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

use Gibbon\Module\Messenger\MessageProcess;
use Gibbon\Domain\Messenger\MessengerGateway;
use Gibbon\Services\Format;

include '../../gibbon.php';

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/messenger_post.php";

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php") == false) {
    $URL .= "&addReturn=fail0";
    header("Location: {$URL}");
    exit;
} else {
    $messengerGateway = $container->get(MessengerGateway::class);

    $from = $_POST['from'] ?? '';
    $data = [
        'gibbonSchoolYearID'=> $gibbon->session->get('gibbonSchoolYearID'), 
        'email'             => $_POST['email'] ?? 'N',
        'messageWall'       => $_POST['messageWall'] ?? 'N',
        'messageWallPin'    => $_POST['messageWallPin'] ?? 'N',
        'messageWall_date1' => !empty($_POST['date1']) ? Format::dateConvert($_POST['date1']) : null,
        'messageWall_date2' => !empty($_POST['date2']) ? Format::dateConvert($_POST['date2']) : null,
        'messageWall_date3' => !empty($_POST['date3']) ? Format::dateConvert($_POST['date3']) : null,
        'sms'               => $_POST['sms'] ?? 'N',
        'subject'           => $_POST['subject'] ?? '',
        'body'              => $_POST['body'] ?? '',
        'emailReceipt'      => $_POST['emailReceipt'] ?? 'N',
        'emailReceiptText'  => $_POST['emailReceiptText'] ?? '',
        'gibbonPersonID'    => $_SESSION[$guid]['gibbonPersonID'],
        'timestamp'         => date('Y-m-d H:i:s'),
    ];

    // Validate that the required values are present
    if (empty($data['subject']) || empty($data['body']) || ($data['email'] == 'Y' && $from == '') || ($data['emailReceipt'] == 'Y' && $data['emailReceiptText'] == '')) {
        $URL .= "&addReturn=fail3";
        header("Location: {$URL}");
        exit;
    }

    // Check for empty POST. This can happen if attachments go horribly wrong.
    if (empty($_POST)) {
        $URL .= "&addReturn=fail5";
        header("Location: {$URL}");
        exit;
    }

    $gibbonMessengerID = $messengerGateway->insert($data);

    $process = $container->get(MessageProcess::class);
    $process->startSendMessage(
        $gibbonMessengerID,
        $gibbon->session->get('gibbonSchoolYearID'),
        $gibbon->session->get('gibbonPersonID'),
        $gibbon->session->get('gibbonRoleIDCurrent'),
        $_POST
    );

    $_SESSION[$guid]['pageLoads'] = null;
    $notification = $data['email'] == 'Y' || $data['sms'] == 'Y' ? 'Y' : 'N';

    $URL.="&addReturn=success0&notification={$notification}";
    header("Location: {$URL}") ;
}
