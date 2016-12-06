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

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//PHPMailer include
require $_SESSION[$guid]['absolutePath'].'/lib/PHPMailer/PHPMailerAutoload.php';

//Module includes
include './moduleFunctions.php';

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$action = $_POST['action'];
$search = $_GET['search'];
$gibbonMessengerID = $_GET['gibbonMessengerID'];

if ($gibbonMessengerID == '' or $action == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Messenger/messenger_manage_report.php&search=$search&gibbonMessengerID=$gibbonMessengerID&sidebar=true";

    if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage_report.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $gibbonMessengerReceiptIDs = array();
        if (isset($_POST['gibbonMessengerReceiptIDs']))
            $gibbonMessengerReceiptIDs = $_POST['gibbonMessengerReceiptIDs'];

        if (count($gibbonMessengerReceiptIDs) < 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $partialFail = false;

            //Check message owner
            try {
                $data = array("gibbonMessengerID" => $gibbonMessengerID, "gibbonPersonID" => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT * FROM gibbonMessenger WHERE gibbonMessengerID=:gibbonMessengerID AND gibbonPersonID=:gibbonPersonID";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) { }

            if ($result->rowCount() != 1) {
                $partialFail = true;
            } else {
                $row = $result->fetch();

                //Prep message
                $emailCount = 0;
                $bodyReminder = "<p style='font-style: italic; font-weight: bold'>" . __($guid, 'This is a reminder for an email that requires your action. Please look for the link at the bottom of the email, and click it to confirm receipt and reading of this email.') ."</p>" ;
                $bodyFin = "<p style='font-style: italic'>" . sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]["systemName"], $_SESSION[$guid]["organisationName"]) ."</p>" ;
                $mail=getGibbonMailer($guid);
				$mail->IsSMTP();
				$mail->SetFrom($_SESSION[$guid]["email"], $_SESSION[$guid]["preferredName"] . " " . $_SESSION[$guid]["surname"]);
				$mail->CharSet="UTF-8";
				$mail->Encoding="base64" ;
				$mail->IsHTML(true);
				$mail->Subject=$row['subject'] ;

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
						$mail->AddAddress($rowRecipt['contactDetail']);
						//Deal with email receipt and body finalisation
						if ($row['emailReceipt'] == 'Y') {
							$bodyReadReceipt = "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Messenger/messenger_emailReceiptConfirm.php&gibbonMessengerID=$gibbonMessengerID&gibbonPersonID=".$rowRecipt['gibbonPersonID']."&key=".$rowRecipt['key']."'>".$row['emailReceiptText']."</a>";
							if (is_numeric(strpos($row['body'], '[confirmLink]'))) {
								$bodyOut = str_replace('[confirmLink]', $bodyReadReceipt, $row['body']).$bodyFin;
							}
							else {
								$bodyOut = $row['body'].$bodyReadReceipt.$bodyFin;
							}
						}
						else {
							$bodyOut = $row['body'].$bodyFin;
						}
						$mail->Body = $bodyOut ;
						$mail->AltBody = emailBodyConvert($bodyOut);
                        if(!$mail->Send()) {
							$partialFail = TRUE ;
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
