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
use Gibbon\Module\Messenger\MessageProcess;
use Gibbon\Module\Messenger\MessageTargets;
use Gibbon\Domain\Messenger\MessengerGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['body' => 'HTML']);

//Module includes
include './moduleFunctions.php';

$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL') . '/index.php?q=/modules/Messenger/messenger_post.php&sidebar=true';
$URLSend = $session->get('absoluteURL') . '/index.php?q=/modules/Messenger/messenger_send.php&sidebar=true';
$URLEdit = $session->get('absoluteURL') . '/index.php?q=/modules/Messenger/messenger_manage_edit.php&sidebar=true';

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php") == false) {
    $URL .= "&return=error0";
    header("Location: {$URL}");
    exit;
} else {
    $messengerGateway = $container->get(MessengerGateway::class);
    $messageTargets = $container->get(MessageTargets::class);

    $sendTestEmail = $_POST['sendTestEmail'] ?? 'N';
    $saveMode = $_POST['saveMode'] ?? 'Preview';
    $data = [
        'gibbonSchoolYearID'=> $session->get('gibbonSchoolYearID'),
        'status'            => $_POST['status'] ?? 'Draft',
        'email'             => $_POST['email'] ?? 'N',
        'messageWall'       => $_POST['messageWall'] ?? 'N',
        'messageWallPin'    => $_POST['messageWallPin'] ?? 'N',
        'messageWall_dateStart' => !empty($_POST['dateStart']) ? Format::dateConvert($_POST['dateStart']) : null,
        'messageWall_dateEnd' => !empty($_POST['dateEnd']) ? Format::dateConvert($_POST['dateEnd']) : null,
        'sms'               => $_POST['sms'] ?? 'N',
        'subject'           => $_POST['subject'] ?? '',
        'body'              => $_POST['body'] ?? '',
        'emailFrom'         => $_POST['emailFrom'] ?? $session->get('email'),
        'emailReplyTo'      => $_POST['emailReplyTo'] ?? $session->get('email'),
        'emailReceipt'      => $_POST['emailReceipt'] ?? 'N',
        'emailReceiptText'  => $_POST['emailReceiptText'] ?? '',
        'enableSharingLink' => $_POST['enableSharingLink'] ?? 'N',
        'individualNaming'  => $_POST['individualNaming'] ?? 'N',
        'includeSignature'  => $_POST['includeSignature'] ?? 'N',
        'confidential'      => $_POST['confidential'] ?? 'N',
        'gibbonPersonID'    => $session->get('gibbonPersonID'),
        'timestamp'         => date('Y-m-d H:i:s'),
    ];

    // Validate that the required values are present
    if (empty($data['subject']) || empty($data['body']) || ($data['email'] == 'Y' && $data['emailFrom'] == '') || ($data['emailReceipt'] == 'Y' && $data['emailReceiptText'] == '')) {
        $URL .= "&return=error3";
        header("Location: {$URL}");
        exit;
    }

    // Check for empty POST. This can happen if attachments go horribly wrong.
    if (empty($_POST)) {
        $URL .= "&return=error5";
        header("Location: {$URL}");
        exit;
    }

    // Check for any emojis in the message and remove them
    $containsEmoji = hasEmojis($data['body']);
    if($containsEmoji) { 
        $data['body'] = removeEmoji($data['body']);
    }

    // Insert the message and get the ID
    $gibbonMessengerID = $messengerGateway->insert($data);
    
    $URLEdit .= "&gibbonMessengerID={$gibbonMessengerID}";
    $URLSend .= "&gibbonMessengerID={$gibbonMessengerID}";

    // Send Draft
    $testEmail = 0;
    if ($sendTestEmail == 'Y') {
        $process = $container->get(MessageProcess::class);
        $testEmail =  $process->runSendDraft($data);
        $URLSend .= "&testEmail={$testEmail}";
        $URLEdit .= "&testEmail={$testEmail}";
    }

    // Go to preview page?
    if ($saveMode == 'Preview' && ($data['email'] == 'Y' || $data['sms'] == 'Y')) {
        $recipients = $messageTargets->createMessageRecipientsFromTargets($gibbonMessengerID, $data);

        if (empty($recipients)) {
            $URLEdit.="&return=error6";
            header("Location: {$URLEdit}");
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

    // Otherwise save any edits to targets
    $messageTargets->createMessageTargets($gibbonMessengerID, $partialFail);

    if ($partialFail) {
        $URLEdit .= '&return=warning1';
    } else {
        $URLEdit .= $containsEmoji
            ? "&return=warning3"
            : "&return=success0";
    }

    header("Location: {$URLEdit}") ;
}
