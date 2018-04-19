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

include '../../functions.php';
include '../../config.php';

//PHPMailer include
require $_SESSION[$guid]['absolutePath'].'/lib/PHPMailer/PHPMailerAutoload.php';

//Module includes
include './moduleFunctions.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';
$search = $_GET['search'];
$gibbonMessengerID = $_GET['gibbonMessengerID'];

if ($gibbonMessengerID == '' or $action != 'resend') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Messenger/messenger_manage_report.php&search=$search&gibbonMessengerID=$gibbonMessengerID&sidebar=true";

    if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage_report.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    } else {
        $highestAction=getHighestGroupedAction($guid, '/modules/Messenger/messenger_manage_report.php', $connection2) ;
        if ($highestAction==FALSE) {
            $URL.="&updateReturn=error0" ;
            header("Location: {$URL}");
            exit;
        }

        $gibbonMessengerReceiptIDs = array();
        if (isset($_POST['gibbonMessengerReceiptIDs'])) {
            $gibbonMessengerReceiptIDs = $_POST['gibbonMessengerReceiptIDs'];
        }

        if (count($gibbonMessengerReceiptIDs) < 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        } else {
            $partialFail = false;

            //Check message exists
            try {
                $data = array("gibbonMessengerID" => $gibbonMessengerID);
                $sql = "SELECT * FROM gibbonMessenger WHERE gibbonMessengerID=:gibbonMessengerID";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) { }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error0';
                header("Location: {$URL}");
                exit;
            } else {
                $row = $result->fetch();

                if ($row['gibbonPersonID'] != $_SESSION[$guid]['gibbonPersonID'] && $highestAction != 'Manage Messages_all') {
                    $URL .= '&return=error0';
                    header("Location: {$URL}");
                    exit;
                }
                else {
                    //Prep message
                    $emailCount = 0;
                    $bodyReminder = "<p style='font-style: italic; font-weight: bold'>" . __($guid, 'This is a reminder for an email that requires your action. Please look for the link in the email, and click it to confirm receipt and reading of this email.') ."</p>" ;
                    $bodyFin = "<p style='font-style: italic'>" . sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]["systemName"], $_SESSION[$guid]["organisationName"]) ."</p>" ;
                    $mail=getGibbonMailer($guid);
    				$mail->SetFrom($_SESSION[$guid]["email"], $_SESSION[$guid]["preferredName"] . " " . $_SESSION[$guid]["surname"]);
    				$mail->CharSet="UTF-8";
    				$mail->Encoding="base64" ;
    				$mail->IsHTML(true);
    				$mail->Subject=__($guid, 'REMINDER:').' '.$row['subject'] ;

                    //Scan through receipients
                    foreach ($gibbonMessengerReceiptIDs as $gibbonMessengerReceiptID) {
                        //Check recipient status
                        try {
                            $dataRecipt = array("gibbonMessengerID" => $gibbonMessengerID, "gibbonMessengerReceiptID" => $gibbonMessengerReceiptID);
                            $sqlRecipt = "SELECT * FROM gibbonMessengerReceipt WHERE gibbonMessengerID=:gibbonMessengerID AND gibbonMessengerReceiptID=:gibbonMessengerReceiptID";
                            $resultRecipt = $connection2->prepare($sqlRecipt);
                            $resultRecipt->execute($dataRecipt);
                        } catch (PDOException $e) { }

                        if ($resultRecipt->rowCount() != 1) {
                            $partialFail = true;
                        } else {
                            $rowRecipt = $resultRecipt->fetch();

                            //Resend message
                            $emailCount ++;
                            $mail->ClearAddresses();
    						$mail->AddAddress($rowRecipt['contactDetail']);
    						//Deal with email receipt and body finalisation
    						if ($row['emailReceipt'] == 'Y') {
    							$bodyReadReceipt = "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Messenger/messenger_emailReceiptConfirm.php&gibbonMessengerID=$gibbonMessengerID&gibbonPersonID=".$rowRecipt['gibbonPersonID']."&key=".$rowRecipt['key']."'>".$row['emailReceiptText']."</a>";
    							if (is_numeric(strpos($row['body'], '[confirmLink]'))) {
    								$bodyOut = $bodyReminder.str_replace('[confirmLink]', $bodyReadReceipt, $row['body']).$bodyFin;
    							}
    							else {
    								$bodyOut = $bodyReminder.$row['body'].$bodyReadReceipt.$bodyFin;
    							}
    						}
    						else {
    							$bodyOut = $bodyReminder.$row['body'].$bodyFin;
    						}
    						$mail->Body = $bodyOut ;
    						$mail->AltBody = emailBodyConvert($bodyOut);
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
