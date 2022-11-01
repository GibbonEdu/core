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

use Gibbon\Data\Validator;
use Gibbon\Services\Format;
use Gibbon\Module\Messenger\MessageProcess;
use Gibbon\Module\Messenger\MessageTargets;
use Gibbon\Domain\Messenger\MessengerGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['body' => 'HTML']);

$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL') . '/index.php?q=/modules/Messenger/messenger_manage_post.php&sidebar=true';
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
        'gibbonSchoolYearID'=> $gibbon->session->get('gibbonSchoolYearID'),
        'status'            => $_POST['status'] ?? 'Draft',
        'email'             => $_POST['email'] ?? 'N',
        'messageWall'       => $_POST['messageWall'] ?? 'N',
        'messageWallPin'    => $_POST['messageWallPin'] ?? 'N',
        'messageWall_date1' => !empty($_POST['date1']) ? Format::dateConvert($_POST['date1']) : null,
        'messageWall_date2' => !empty($_POST['date2']) ? Format::dateConvert($_POST['date2']) : null,
        'messageWall_date3' => !empty($_POST['date3']) ? Format::dateConvert($_POST['date3']) : null,
        'sms'               => $_POST['sms'] ?? 'N',
        'subject'           => $_POST['subject'] ?? '',
        'body'              => $_POST['body'] ?? '',
        'emailFrom'         => $_POST['emailFrom'] ?? $session->get('email'),
        'emailReplyTo'      => $_POST['emailReplyTo'] ?? $session->get('email'),
        'emailReceipt'      => $_POST['emailReceipt'] ?? 'N',
        'emailReceiptText'  => $_POST['emailReceiptText'] ?? '',
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

        header("Location: {$URLSend}");
        exit;
    } elseif ($saveMode == 'Preview' && $data['messageWall'] == 'Y') {
        $messengerGateway->update($gibbonMessengerID, ['status' => 'Sent']);
    }

    // Otherwise save any edits to targets
    $messageTargets->createMessageTargets($gibbonMessengerID, $partialFail);

    $URLEdit .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URLEdit}") ;
}
