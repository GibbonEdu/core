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

use Gibbon\Contracts\Comms\Mailer;

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

        $gibbonMessengerReceiptIDs = $_POST['gibbonMessengerReceiptIDs'] ?? array();

        if (count($gibbonMessengerReceiptIDs) < 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        } else {
            $partialFail = false;

            //Check message exists

                $data = array("gibbonMessengerID" => $gibbonMessengerID);
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
                }
                else {
                    //Prep message
                    $emailCount = 0;
                    $bodyReminder = "<p style='font-style: italic; font-weight: bold'>" . __('This is a reminder for an email that requires your action. Please look for the link in the email, and click it to confirm receipt and reading of this email.') ."</p>";

                    $mail= $container->get(Mailer::class);
                    $mail->SMTPKeepAlive = true;
    				$mail->SetFrom($session->get('email'), $session->get('preferredName') . ' ' . $session->get('surname'));
    				$mail->Subject = $values['emailReceipt'] == 'Y' ? __('REMINDER:').' '.$values['subject'] : $values['subject'];

                    //Scan through receipients
                    foreach ($gibbonMessengerReceiptIDs as $gibbonMessengerReceiptID) {
                        //Check recipient status

                            $dataRecipt = array("gibbonMessengerID" => $gibbonMessengerID, "gibbonMessengerReceiptID" => $gibbonMessengerReceiptID);
                            $sqlRecipt = "SELECT * FROM gibbonMessengerReceipt WHERE gibbonMessengerID=:gibbonMessengerID AND gibbonMessengerReceiptID=:gibbonMessengerReceiptID";
                            $resultRecipt = $connection2->prepare($sqlRecipt);
                            $resultRecipt->execute($dataRecipt);

                        if ($resultRecipt->rowCount() != 1) {
                            $partialFail = true;
                        } else {
                            $rowRecipt = $resultRecipt->fetch();

                            //Resend message
                            $emailCount ++;
                            $mail->ClearAddresses();
    						$mail->AddAddress($rowRecipt['contactDetail']);
    						//Deal with email receipt and body finalisation
    						if ($values['emailReceipt'] == 'Y') {
    							$bodyReadReceipt = '<hr style="border: 1px solid #dddddd;"><a target="_blank" href="'.$session->get('absoluteURL').'/index.php?q=/modules/Messenger/messenger_emailReceiptConfirm.php&gibbonMessengerID='.$gibbonMessengerID.'&gibbonPersonID='.$rowRecipt['gibbonPersonID'].'&key='.$rowRecipt['key'].'">'.$values['emailReceiptText'].'</a><hr style="border: 1px solid #dddddd;"><br/>';
    							if (strpos($bodyReminder, '[confirmLink]') !== false) {
    								$bodyOut = $bodyReminder.str_replace('[confirmLink]', $bodyReadReceipt, $values['body']);
    							}
    							else {
    								$bodyOut = $bodyReminder.$bodyReadReceipt.$values['body'];
    							}
    						}
    						else {
    							$bodyOut = $values['body'];
    						}

                            $mail->renderBody('mail/email.twig.html', [
                                'title'  => $values['subject'],
                                'body'   => $bodyOut
                            ]);


                            if(!$mail->Send()) {
    							$partialFail = TRUE ;
    						}
                        }
                    }
                }
            }

            if ($partialFail == true) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
