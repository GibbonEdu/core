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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['body' => 'HTML']);

$gibbonMessengerCannedResponseID = $_GET['gibbonMessengerCannedResponseID'] ?? '';
$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address).'/cannedResponse_manage_edit.php&gibbonMessengerCannedResponseID='.$gibbonMessengerCannedResponseID;

if (isActionAccessible($guid, $connection2, '/modules/Messenger/cannedResponse_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if role specified
    if ($gibbonMessengerCannedResponseID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonMessengerCannedResponseID' => $gibbonMessengerCannedResponseID);
            $sql = 'SELECT * FROM gibbonMessengerCannedResponse WHERE gibbonMessengerCannedResponseID=:gibbonMessengerCannedResponseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            //Validate Inputs
            $subject = $_POST['subject'] ?? '';
            $body = $_POST['body'] ?? '';

            if ($subject == '' or $body == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('subject' => $subject, 'gibbonMessengerCannedResponseID' => $gibbonMessengerCannedResponseID);
                    $sql = 'SELECT * FROM gibbonMessengerCannedResponse WHERE subject=:subject AND NOT gibbonMessengerCannedResponseID=:gibbonMessengerCannedResponseID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() > 0) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('subject' => $subject, 'body' => $body, 'gibbonMessengerCannedResponseID' => $gibbonMessengerCannedResponseID);
                        $sql = 'UPDATE gibbonMessengerCannedResponse SET subject=:subject, body=:body WHERE gibbonMessengerCannedResponseID=:gibbonMessengerCannedResponseID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
