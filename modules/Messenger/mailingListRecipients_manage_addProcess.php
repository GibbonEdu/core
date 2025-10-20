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

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Messenger/mailingListRecipients_manage_add.php";

if (isActionAccessible($guid, $connection2, '/modules/Messenger/mailingListRecipients_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $mailingListRecipientGateway = $container->get(MailingListRecipientGateway::class);
    $randStrGenerator = new PasswordPolicy(true, true, false, 40);
    
    $data = [
        'surname'                           => $_POST['surname'] ?? '',
        'preferredName'                     => $_POST['preferredName'] ?? '',
        'email'                             => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
        'key'                               => $randStrGenerator->generate(),    
        'organisation'                      => $_POST['organisation'] ?? '',
        'gibbonMessengerMailingListIDList'  => ((is_array($_POST['gibbonMessengerMailingListIDList'])) ? implode(',', $_POST['gibbonMessengerMailingListIDList']) : ''),
    ];

    // Validate the required values are present
    if (empty($data['surname']) || empty($data['preferredName']) || empty($data['email'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$mailingListRecipientGateway->unique($data, ['email'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    $gibbonMessengerMailingListRecipientID = $mailingListRecipientGateway->insert($data);

    if ($gibbonMessengerMailingListRecipientID) {
        $URL .= "&return=success0&editID=$gibbonMessengerMailingListRecipientID";
    }
    else {
        $URL .= "&return=error2";
    }

    header("Location: {$URL}");
}
