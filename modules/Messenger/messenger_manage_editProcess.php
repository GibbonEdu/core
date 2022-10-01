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
use Gibbon\Module\Messenger\MessageTargets;
use Gibbon\Module\Messenger\MessageProcess;
use Gibbon\Domain\Messenger\MessengerGateway;

require_once '../../gibbon.php';

$gibbonMessengerID = $_POST['gibbonMessengerID'] ?? '';
$sendTestEmail = $_POST['sendTestEmail'] ?? '';
$search = $_GET['search'] ?? '';
$address = $_POST['address'] ?? '';

$URL=$session->get('absoluteURL') . "/index.php?q=/modules/Messenger/messenger_manage_edit.php&sidebar=true&search=$search&gibbonMessengerID=" . $gibbonMessengerID;
$URLSend = $session->get('absoluteURL') . "/index.php?q=/modules/Messenger/messenger_postPreview.php&sidebar=true&gibbonMessengerID={$gibbonMessengerID}";

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
    $messageTargets = $container->get(MessageTargets::class);

    $status = $_POST['status'] ?? 'Sent';
    $data = [
        'messageWall'       => $_POST['messageWall'] ?? 'N',
        'messageWallPin'    => $_POST['messageWallPin'] ?? 'N',
        'messageWall_date1' => !empty($_POST['date1']) ? Format::dateConvert($_POST['date1']) : null,
        'messageWall_date2' => !empty($_POST['date2']) ? Format::dateConvert($_POST['date2']) : null,
        'messageWall_date3' => !empty($_POST['date3']) ? Format::dateConvert($_POST['date3']) : null,
        'subject'           => $_POST['subject'] ?? '',
        'body'              => $_POST['body'] ?? '',
        'confidential'      => $_POST['confidential'] ?? 'N',
        'timestamp'         => date('Y-m-d H:i:s'),
    ];

    if ($status != 'Sent') {
        $data += [
            'status'           => $status,
            'email'            => $_POST['email'] ?? 'N',
            'sms'              => $_POST['sms'] ?? 'N',
            'emailFrom'        => $_POST['emailFrom'] ?? $session->get('email'),
            'emailReplyTo'     => $_POST['emailReplyTo'] ?? $session->get('email'),
            'emailReceipt'     => $_POST['emailReceipt'] ?? 'N',
            'emailReceiptText' => $_POST['emailReceiptText'] ?? '',
        ];
    }

    $data['messageWallPin'] = ($data['messageWall'] == 'Y' && isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage.php', 'Manage Messages_all')) ? $data['messageWallPin'] : 'N';

    // Validate that the required values are present
    if (empty($data['subject']) || empty($data['body']) || ($data['email'] == 'Y' && $data['emailFrom'] == '') || ($data['emailReceipt'] == 'Y' && $data['emailReceiptText'] == '')) {
        $URL.="&return=error3";
        header("Location: {$URL}");
        exit;
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

    $sqlRemove="DELETE FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID";
    $pdo->delete($sqlRemove, ['gibbonMessengerID' => $gibbonMessengerID]);

    // Go to preview page?
    if ($status == 'Sending') {
        $sqlRemove="DELETE FROM gibbonMessengerReceipt WHERE gibbonMessengerID=:gibbonMessengerID";
        $pdo->delete($sqlRemove, ['gibbonMessengerID' => $gibbonMessengerID]);

        $recipients = $messageTargets->createMessageRecipientsFromTargets($gibbonMessengerID, $data, $partialFail);
        if (empty($recipients)) {
            $URL.="&return=error6";
            header("Location: {$URL}");
            exit;
        }

        header("Location: {$URLSend}");
        exit;
    }

    // Otherwise save any edits to targets
    $messageTargets->createMessageTargets($gibbonMessengerID, $partialFail);

    $URL .= $partialFail
        ? "&return=error4"
        : "&return=success0";
    
    header("Location: {$URL}");
}
