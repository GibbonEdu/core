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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\SMS;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\LogGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\Messenger\MessengerGateway;

//Module includes
include "./moduleFunctions.php" ;

$logGateway = $container->get(LogGateway::class);
$time=time() ;

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php")==FALSE) {
	//Fail 0
    return ['return' => 'error0'];
}
else {
	if (empty($_POST)) {
        //Fail 5
        return ['return' => 'error5'];
	}
	else {
		//Proceed!
		//Setup return variables
		$emailCount=NULL ;
		$smsCount=NULL ;
		$smsBatchCount=NULL ;

        $validator = $container->get(Validator::class);
        $_POST = $validator->sanitize($_POST, ['body' => 'HTML']);

		//Validate Inputs
		$email=$_POST["email"] ;
		if ($email!="Y") {
			$email="N" ;
		}
		if ($email=="Y") {
			$from=$_POST["from"] ;
		}
		$emailReplyTo="" ;
		if (isset($_POST["emailReplyTo"])) {
			$emailReplyTo=$_POST["emailReplyTo"] ;
		}
		$messageWall="" ;
		if (isset($_POST["messageWall"])) {
			$messageWall=$_POST["messageWall"] ;
		}
		if ($messageWall!="Y") {
			$messageWall="N" ;
		}
		$messageWallPin = ($messageWall == "Y" && isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage.php", "Manage Messages_all") & !empty($_POST['messageWallPin'])) ? $_POST['messageWallPin'] : 'N' ;
		$date1=NULL ;
		if (isset($_POST["date1"])) {
			if ($_POST["date1"]!="") {
				$date1=Format::dateConvert($_POST["date1"]) ;
			}
		}
		$date2=NULL ;
		if (isset($_POST["date2"])) {
			if ($_POST["date2"]!="") {
				$date2=Format::dateConvert($_POST["date2"]) ;
			}
		}
		$date3=NULL ;
		if (isset($_POST["date3"])) {
			if ($_POST["date3"]!="") {
				$date3=Format::dateConvert($_POST["date3"]) ;
			}
		}
		$sms=NULL ;
		if (isset($_POST["sms"])) {
			$sms=$_POST["sms"] ;
		}
		if ($sms!="Y") {
			$sms="N" ;
		}
		$smsCreditBalance = ($sms == "Y" && !empty($_POST["smsCreditBalance"])) ? $_POST["smsCreditBalance"] : null;

        $subject = $_POST['subject'] ?? '';
        $body = stripslashes($_POST['body'] ?? '');

        $from = $_POST["emailFrom"] ;
		$emailReceipt = $_POST["emailReceipt"] ;
		$emailReceiptText = null;
		if (isset($_POST["emailReceiptText"])) {
			$emailReceiptText = $_POST["emailReceiptText"] ;
		}
		$individualNaming = $_POST["individualNaming"] ?? 'N';
        $confidential = $_POST['confidential'] ?? 'N';

		if ($subject == "" OR $body == "" OR ($email == "Y" AND $from == "") OR $emailReceipt == '' OR ($emailReceipt == "Y" AND $emailReceiptText == "") OR $individualNaming == "") {
            //Fail 3
            return ['return' => 'error3'];
		}
		else {
            $settingGateway = $container->get(SettingGateway::class);

			//SMS Credit notification
			if ($smsCreditBalance != null && $smsCreditBalance < 1000) {
				$notificationGateway = new NotificationGateway($pdo);
                $notificationSender = new NotificationSender($notificationGateway, $session);
				$organisationAdministrator = $settingGateway->getSettingByScope('System', 'organisationAdministrator');
				$notificationString = __('Low SMS credit warning.');
				$notificationSender->addNotification($organisationAdministrator, $notificationString, "Messenger", "/index.php?q=/modules/Messenger/messenger_post.php");
				$notificationSender->sendNotifications();
			}
			
            $partialFail = false;

            if ($sms=="Y") {
				if ($countryCode=="") {
					$partialFail = true;
				} else {
                    $recipients = array_filter(array_reduce($report, function ($phoneNumbers, $reportEntry) {
                        if ($reportEntry['contactType'] == 'SMS') $phoneNumbers[] = '+'.$reportEntry['contactDetail'];
                        return $phoneNumbers;
                    }, []));

                    $sms = $container->get(SMS::class);

                    $result = $sms
                        ->content($body)
                        ->send($recipients);

                    $smsCount = count($recipients);
                    $smsBatchCount = count($result);

                    $smsStatus = $result ? 'OK' : 'Not OK';
                    $partialFail &= !empty($result);

                    // Update the send status based on SMS results
                    foreach ($report as $reportEntry) {
                        if (in_array('+'.$reportEntry['contactDetail'], $result)) {
                            $messengerReceiptGateway->update($reportEntry['gibbonMessengerReceiptID'], ['sent' => 'Y']);
                        }
                    }

					//Set log
					$logGateway->addLog($session->get('gibbonSchoolYearIDCurrent'), getModuleID($connection2, $_POST["address"]), $session->get('gibbonPersonID'), 'SMS Send Status', array('Status' => $smsStatus, 'Result' => count($result), 'Recipients' => $recipients));
				}
			}

			if ($email=="Y") {
				//Set up email
				$emailCount=0 ;
                $emailErrors = [];
				$mail= $container->get(Mailer::class);
                $mail->SMTPKeepAlive = true;
                $mail->SMTPDebug = 1;
                $mail->Debugoutput = 'error_log';
                
				if ($emailReplyTo!="")
					$mail->AddReplyTo($emailReplyTo, '');
				if ($from!=$session->get('email'))	//If sender is using school-wide address, send from school
					$mail->SetFrom($from, $session->get('organisationName'));
				else //Else, send from individual
					$mail->SetFrom($from, $session->get('preferredName') . " " . $session->get('surname'));
				$mail->CharSet="UTF-8";
				$mail->Encoding="base64" ;
				$mail->IsHTML(true);
                $mail->Subject=$subject ;
                
                // Turn copy-pasted div breaks into paragraph breaks
                $body = str_ireplace(['<div ', '<div>', '</div>'], ['<p ', '<p>', '</p>'], $body);

				$mail->renderBody('mail/email.twig.html', [
					'title'  => $subject,
					'body'   => $body
				]);

				//Send to sender, if not in recipient list
				$includeSender = true ;
				foreach ($report as $reportEntry) {
					if ($reportEntry['contactType'] == 'Email') {
						if ($reportEntry['contactDetail'] == $from) {
							$includeSender = false ;
						}
					}
				}
				if ($includeSender) {
					$emailCount ++ ;
					$mail->AddAddress($from);
					if(!$mail->Send()) {
						$partialFail = TRUE ;
					}
				}

				//If sender is using school-wide address, and it is not in recipient list, send to school-wide address
				if ($from!=$session->get('email')) { //If sender is using school-wide address, add them to recipient list.
					$includeSender = true ;
					foreach ($report as $reportEntry) {
						if ($reportEntry['contactType'] == 'Email') {
							if ($reportEntry['contactDetail'] == $session->get('email')) {
								$includeSender = false ;
							}
						}
					}
					if ($includeSender) {
						$emailCount ++;
						$mail->ClearAddresses();
						$mail->AddAddress($session->get('email'));
						if(!$mail->Send()) {
							$partialFail = TRUE ;
						}
					}
				}

				//Send to each recipient
				foreach ($report as $reportEntry) {
					if ($reportEntry['contactType'] == 'Email') {
						$emailCount ++;
						$mail->ClearAddresses();
						$mail->AddAddress($reportEntry['contactDetail']);

						//Deal with email receipt and body finalisation
						if ($emailReceipt == 'Y') {
							$bodyReadReceipt = "<a target='_blank' href='".$session->get('absoluteURL')."/index.php?q=/modules/Messenger/messenger_emailReceiptConfirm.php&gibbonMessengerID=$AI&gibbonPersonID=".$reportEntry['gibbonPersonID']."&key=".$reportEntry['key']."'>".$emailReceiptText."</a>";
							if (is_numeric(strpos($body, '[confirmLink]'))) {
								$bodyOut = str_replace('[confirmLink]', $bodyReadReceipt, $body);
							}
							else {
								$bodyOut = $body.$bodyReadReceipt;
							}
						}
						else {
							$bodyOut = $body;
						}

						//Deal with student names
						if ($individualNaming == "Y" && !empty($reportEntry['nameListStudent'])) {
							$studentNames = '';
                            $nameArray = array_filter(json_decode($reportEntry['nameListStudent'], true));

							if (!empty($nameArray) && count($nameArray) > 0) {
                                // Remove duplicates and build a string list of names
                                $nameArray = array_unique($nameArray);
                                $studentNameList = join(' & ', array_filter(array_merge(array(join(', ', array_slice($nameArray, 0, -1))), array_slice($nameArray, -1)), 'strlen'));

								if (count($nameArray) > 1) {
									$studentNames = '<i>'.__('This email relates to the following students: ').$studentNameList.'</i><br/><br/>';
								} else {
									$studentNames = '<i>'.__('This email relates to the following student: ').$studentNameList.'</i><br/><br/>';
								}
							}
							$bodyOut = $studentNames.$bodyOut;
                        }

						$mail->renderBody('mail/email.twig.html', [
							'title'  => $subject,
							'body'   => $bodyOut
						]);
						if(!$mail->Send()) {
                            $partialFail = TRUE ;
							$logGateway->addLog($session->get('gibbonSchoolYearIDCurrent'), getModuleID($connection2, $_POST["address"]), $session->get('gibbonPersonID'), 'Email Send Status', array('Status' => 'Not OK', 'Result' => $mail->ErrorInfo, 'Recipients' => $reportEntry['contactDetail']));
                            $emailErrors[] = $reportEntry['contactDetail'];
						} else {
                            $messengerReceiptGateway->update($reportEntry['gibbonMessengerReceiptID'], ['sent' => 'Y']);
                        }
					}
                }

                // Optionally send bcc copies of this message, excluding recipients already sent to.
                $recipientList = array_column($report, 4);
                $messageBccList = explode(',', $settingGateway->getSettingByScope('Messenger', 'messageBcc'));
                $messageBccList = array_filter($messageBccList, function($recipient) use ($recipientList, $from) {
                    return $recipient != $from && !in_array($recipient, $recipientList);
                });

                if (!empty($messageBccList) && !empty($report) && $confidential == 'N') {
                    $mail->ClearAddresses();
                    foreach ($messageBccList as $recipient) {
                        $mail->AddBCC($recipient, '');
                    }

                    $sender = Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff');
                    $date = Format::date(date('Y-m-d')).' '.date('H:i:s');

                    $mail->renderBody('mail/email.twig.html', [
						'title'  => $subject,
						'body'   => __('Message Bcc').': '.sprintf(__('The following message was sent by %1$s on %2$s and delivered to %3$s recipients.'), $sender, $date, $emailCount).'<br/><br/>'.$body
					]);
					$mail->Send();
                }

                $mail->smtpClose();
			}

            $messengerGateway->update($gibbonMessengerID, ['status' => 'Sent']);

            return [
                'return'        => $partialFail ? 'error4' : 'success0',
                'emailCount'    => $emailCount ?? 0,
                'emailErrors'   => $emailErrors ?? [],
                'smsCount'      => $smsCount ?? 0,
                'smsBatchCount' => $smsBatchCount ?? 0,
            ];
		}
	}
}
