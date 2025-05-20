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

use Gibbon\Data\Validator;
use Gibbon\Module\Messenger\MessageProcess;
use Gibbon\Module\Messenger\MessageTargets;
use Gibbon\Domain\Messenger\MessengerGateway;

require_once '../../gibbon.php';

// Module includes
include './moduleFunctions.php';

$validator = $container->get(Validator::class);
$_POST = $validator->sanitize($_POST, ['body' => 'HTML']);

$gibbonMessengerID = $_POST['gibbonMessengerID'] ?? '';
$search = $_GET['search'] ?? '';
$resendEmail = $_POST['resendEmail'] ?? '';

$URL = $session->get('absoluteURL') . "/index.php?q=/modules/Messenger/messenger_manage_report.php&sidebar=true&search=$search&gibbonMessengerID=$gibbonMessengerID";

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage_report.php")==FALSE) {
    $URL.="&return=error0";
    header("Location: {$URL}");
} else {
    // Proceed!
    $highestAction=getHighestGroupedAction($guid, '/modules/Messenger/messenger_manage_report.php', $connection2);
    if ($highestAction == FALSE) {
        $URL.="&return=error0";
        header("Location: {$URL}");
        exit;
    }

    // Check for empty POST. This can happen if no recipients are selected
    if (empty($gibbonMessengerID) || empty($_POST['individualList'])) {
        $URL.="&return=error1";
        header("Location: {$URL}");
        exit;
    }

    $messengerGateway = $container->get(MessengerGateway::class);
    $messageTargets = $container->get(MessageTargets::class);

    $message = $messengerGateway->getByID($gibbonMessengerID);
    if (empty($message)) {
        $URL.="&return=error2";
        header("Location: {$URL}");
        exit;
    }

    $data = [
        'sms'           => $message['sms'] ?? 'N',
        'email'         => $message['email'] ?? 'N',
        'emailReceipt'  => $message['emailReceipt'] ?? 'N',
    ];

    $partialFail = false;
    $gibbonMessengerReceiptIDs = $messageTargets->createMessageRecipientsFromTargets($gibbonMessengerID, $data, $partialFail);

    if (empty($gibbonMessengerReceiptIDs)) {
        $URL.="&return=error6";
        header("Location: {$URL}");
        exit;
    }

    if ($resendEmail == 'Y') {
        $process = $container->get(MessageProcess::class);
        $process->startSendEmailToRecipients($gibbonMessengerID, $gibbonMessengerReceiptIDs);

        $URL .= $partialFail 
            ? '&return=error4'
            : "&return=success1";
    } else {
        $URL .= $partialFail 
            ? '&return=error4'
            : "&return=success0";
    }
    
    header("Location: {$URL}");
}
