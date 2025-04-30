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

use Gibbon\Module\Messenger\MessageProcess;

include '../../gibbon.php';

//Module includes
include './moduleFunctions.php';

$action = $_POST['action'] ?? '';
$search = $_GET['search'] ?? '';
$gibbonMessengerID = $_GET['gibbonMessengerID'] ?? '';

if ($gibbonMessengerID == '' or $action != 'resend') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL')."/index.php?q=/modules/Messenger/messenger_manage_report.php&search=$search&gibbonMessengerID=$gibbonMessengerID&sidebar=true";

    if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage_report.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    } else {
        $highestAction=getHighestGroupedAction($guid, '/modules/Messenger/messenger_manage_report.php', $connection2) ;
        if ($highestAction==FALSE) {
            $URL.="&return=error0" ;
            header("Location: {$URL}");
            exit;
        }

        $gibbonMessengerReceiptIDs = $_POST['gibbonMessengerReceiptIDs'] ?? [];

        if (count($gibbonMessengerReceiptIDs) < 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        } else {
            $partialFail = false;

            // Check message exists
            $data = ["gibbonMessengerID" => $gibbonMessengerID];
            $sql = "SELECT * FROM gibbonMessenger WHERE gibbonMessengerID=:gibbonMessengerID";
            $result = $connection2->prepare($sql);
            $result->execute($data);

            if ($result->rowCount() != 1) {
                $URL .= '&return=error0';
                header("Location: {$URL}");
                exit;
            } else {
                $values = $result->fetch();

                if ($values['gibbonPersonID'] != $session->get('gibbonPersonID') && $highestAction != 'Manage Messages_all') {
                    $URL .= '&return=error0';
                    header("Location: {$URL}");
                    exit;
                } else {
                    $process = $container->get(MessageProcess::class);
                    $process->startSendEmailToRecipients($gibbonMessengerID, $gibbonMessengerReceiptIDs);
                }
            }

            $URL .= '&return=success1';
            header("Location: {$URL}");
        }
    }
}
