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
use Gibbon\Data\PasswordPolicy;
use Gibbon\Domain\Messenger\MailingListRecipientGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Messenger/mailingListRecipients_manage_subscribe.php";

$mode = $_REQUEST['mode'] ?? 'subscribe';
$mode = ($mode == 'subscribe' || $mode == 'unsubscribe' || $mode == 'manage') ? $mode : 'subscribe';

$mailingListRecipientGateway = $container->get(MailingListRecipientGateway::class);

if ($mode == 'subscribe') {
    $randStrGenerator = new PasswordPolicy(true, true, false, 40);

    $data = [
        'surname'                           => $_POST['surname'] ?? '',
        'preferredName'                     => $_POST['preferredName'] ?? '',
        'email'                             => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
        'key'                               => $randStrGenerator->generate(),    
        'organisation'                      => $_POST['organisation'] ?? '',
        'gibbonMessengerMailingListIDList'  => implode(',', $_POST['gibbonMessengerMailingListIDList'] ?? []),
    ];

    // Validate the required values are present
    if (empty($data['surname']) || empty($data['preferredName']) || empty($data['email'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    $exists = $mailingListRecipientGateway->selectBy(['email' => $data['email']])->fetch();
    if (!empty($exists)) {
        // Update the record (rather than failing)
        $gibbonMessengerMailingListRecipientID = $exists['gibbonMessengerMailingListRecipientID'];
        $mailingListRecipientGateway->update($gibbonMessengerMailingListRecipientID, $data);

    } else {
        // Create the record
        $gibbonMessengerMailingListRecipientID = $mailingListRecipientGateway->insert($data);
    }

    if ($gibbonMessengerMailingListRecipientID) {
        $URL .= "&return=success0";
    }
    else {
        $URL .= "&return=error2";
    }

    header("Location: {$URL}");
} else if ($mode == 'manage') {
    $data = [
        'surname'                           => $_POST['surname'] ?? '',
        'preferredName'                     => $_POST['preferredName'] ?? '',
        'organisation'                      => $_POST['organisation'] ?? '',
        'gibbonMessengerMailingListIDList'  => ((is_array($_POST['gibbonMessengerMailingListIDList'])) ? implode(',', $_POST['gibbonMessengerMailingListIDList']) : ''),
    ];

    // Validate the required values are present
    if (empty($data['surname']) || empty($data['preferredName'])) {
        $URL .= "&return=error1&mode=$mode&email=$email&key=$key";
        header("Location: {$URL}");
        exit;
    }

    // Validate email and key
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $key = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['key'] ?? '');
    $keyCheck = $mailingListRecipientGateway->keyCheck($email, $key);
    
    if ($keyCheck->rowCount() != 1) {
        $URL .= "&return=error1&mode=$mode&email=$email&key=$key";
        header("Location: {$URL}");
        exit;
    } else {
        // Update the record
        $values = $keyCheck->fetchAll()[0];
        $updated = $mailingListRecipientGateway->update($values['gibbonMessengerMailingListRecipientID'], $data);

        $URL .= !$updated
            ? "&return=error2&mode=$mode&email=$email&key=$key"
            : "&return=success0&mode=$mode&email=$email&key=$key";

        header("Location: {$URL}");
    } 
} else {
    $data = [
        'gibbonMessengerMailingListIDList'  => ''
    ];

    // Validate email and key
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $key = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['key'] ?? '');
    $keyCheck = $mailingListRecipientGateway->keyCheck($email, $key);
    
    if ($keyCheck->rowCount() != 1) {
        $URL .= "&return=error1&mode=$mode&email=$email&key=$key";
        header("Location: {$URL}");
        exit;
    } else {
        // Update the record
        $values = $keyCheck->fetchAll()[0];
        $updated = $mailingListRecipientGateway->update($values['gibbonMessengerMailingListRecipientID'], $data);

        $URL .= !$updated
            ? "&return=error2&mode=$mode&email=$email&key=$key"
            : "&return=success0&mode=$mode&email=$email&key=$key";

        header("Location: {$URL}");
    } 

}
