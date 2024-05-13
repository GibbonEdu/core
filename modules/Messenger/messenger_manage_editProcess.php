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
use Gibbon\Services\Format;
use Gibbon\Module\Messenger\MessageTargets;
use Gibbon\Module\Messenger\MessageProcess;
use Gibbon\Domain\Messenger\MessengerGateway;
use Gibbon\Domain\Messenger\MessengerReceiptGateway;
use Gibbon\Domain\Messenger\MessengerTargetGateway;

require_once '../../gibbon.php';

//Module includes
include './moduleFunctions.php';

$gibbonMessengerID = $_POST['gibbonMessengerID'] ?? '';
$sendTestEmail = $_POST['sendTestEmail'] ?? '';
$search = $_GET['search'] ?? '';
$address = $_POST['address'] ?? '';

$URL=$session->get('absoluteURL') . "/index.php?q=/modules/Messenger/messenger_manage_edit.php&sidebar=true&search=$search&gibbonMessengerID=$gibbonMessengerID";
$URLSend = $session->get('absoluteURL') . "/index.php?q=/modules/Messenger/messenger_send.php&sidebar=true&gibbonMessengerID={$gibbonMessengerID}";

$time=time();

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage_edit.php")==FALSE) {
    $URL.="&return=error0";
    header("Location: {$URL}");
} else {
    // Proceed!
    $highestAction=getHighestGroupedAction($guid, $address, $connection2);
    if ($highestAction == FALSE) {
        $URL.="&return=error0";
        header("Location: {$URL}");
        exit;
    }

    // Check for empty POST. This can happen if attachments go horribly wrong.
    if (empty($_POST)) {
        $URL.="&return=error5";
        header("Location: {$URL}");
        exit;
    }

    // Validate Inputs
    $validator = $container->get(Validator::class);
    $_POST = $validator->sanitize($_POST, ['body' => 'HTML']);

    $messengerGateway = $container->get(MessengerGateway::class);
    $messengerTargetGateway = $container->get(MessengerTargetGateway::class);
    $messengerReceiptGateway = $container->get(MessengerReceiptGateway::class);
    $messageTargets = $container->get(MessageTargets::class);

    $values = $messengerGateway->getByID($gibbonMessengerID);
    if (empty($values)) {
        $URL.="&return=error2";
        header("Location: {$URL}");
        exit;
    }

    $saveMode = $_POST['saveMode'] ?? 'Preview';
    $status = $_POST['status'] ?? 'Draft';
    $data = [
        'messageWall'       => $_POST['messageWall'] ?? 'N',
        'messageWallPin'    => $_POST['messageWallPin'] ?? 'N',
        'messageWall_dateStart' => !empty($_POST['dateStart']) ? Format::dateConvert($_POST['dateStart']) : null,
        'messageWall_dateEnd' => !empty($_POST['dateEnd']) ? Format::dateConvert($_POST['dateEnd']) : null,
        'subject'           => $_POST['subject'] ?? '',
        'body'              => $_POST['body'] ?? '',
        'confidential'      => $_POST['confidential'] ?? 'N',
        'includeSignature'  => $_POST['includeSignature'] ?? 'N',
        'timestamp'         => date('Y-m-d H:i:s'),
        'enableSharingLink' => $_POST['enableSharingLink'] ?? 'N',
    ];

    if ($status == 'Draft') {
        $data += [
            'email'            => $_POST['email'] ?? 'N',
            'sms'              => $_POST['sms'] ?? 'N',
            'emailFrom'        => $_POST['emailFrom'] ?? $session->get('email'),
            'emailReplyTo'     => $_POST['emailReplyTo'] ?? $session->get('email'),
            'emailReceipt'     => $_POST['emailReceipt'] ?? 'N',
            'emailReceiptText' => $_POST['emailReceiptText'] ?? '',
        ];
    } else {
        $data['email'] = $values['email'];
        $data['emailReceipt'] = $values['emailReceipt'];
    }

    $data['messageWallPin'] = ($data['messageWall'] == 'Y' && isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage.php', 'Manage Messages_all')) ? $data['messageWallPin'] : 'N';

    // Validate that the required values are present
    if (empty($data['subject']) || empty($data['body']) || ($status == 'Draft' && $data['email'] == 'Y' && $data['emailFrom'] == '') || ($status == 'Draft' && $data['emailReceipt'] == 'Y' && $data['emailReceiptText'] == '')) {
        $URL.="&return=error3";
        header("Location: {$URL}");
        exit;
    }

    // Check for any emojis in the message and remove them
    $containsEmoji = hasEmojis($data['body']);
    if ($containsEmoji) { 
        $data['body'] = removeEmoji($data['body']);
    }
    
    // Write to database
    $updated = $messengerGateway->update($gibbonMessengerID, $data);
    if (!$updated) {
        $URL.="&return=error2";
        header("Location: {$URL}");
        exit();
    }

    // Send Draft
    if ($sendTestEmail == 'Y') {
        $process = $container->get(MessageProcess::class);
        $testEmail = $process->runSendDraft($data);
        
        $URL .= "&testEmail={$testEmail}";
        $URLSend .= "&testEmail={$testEmail}";
    }

    $partialFail = false;

    // Go to preview page?
    if ($saveMode == 'Preview' && $status == 'Draft' && ($data['email'] == 'Y' || $data['sms'] == 'Y')) {
        // Clear existing recipients, then add new ones
        $messengerTargetGateway->deleteWhere(['gibbonMessengerID' => $gibbonMessengerID]);
        $messengerReceiptGateway->deleteWhere(['gibbonMessengerID' => $gibbonMessengerID]);
        $recipients = $messageTargets->createMessageRecipientsFromTargets($gibbonMessengerID, $data, $partialFail);

        if (empty($recipients)) {
            $URL.="&return=error6";
            header("Location: {$URL}");
            exit;
        }

        if ($containsEmoji) {
            $URLSend .= '&return=warning3';
        }
        
        header("Location: {$URLSend}");
        exit;
    } elseif ($saveMode == 'Preview' && $data['messageWall'] == 'Y') {
        $messengerGateway->update($gibbonMessengerID, ['status' => 'Sent']);
    }

    // Remove existing targets, then save any edits by creating new targets
    $messengerTargetGateway->deleteWhere(['gibbonMessengerID' => $gibbonMessengerID]);
    $messageTargets->createMessageTargets($gibbonMessengerID, $partialFail);

    if ($partialFail) {
        $URL .= '&return=error4';
    } else {
        $URL .= $containsEmoji
            ? "&return=warning3"
            : "&return=success0";
    }
    
    header("Location: {$URL}");
}
