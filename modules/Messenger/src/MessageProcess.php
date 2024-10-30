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

namespace Gibbon\Module\Messenger;

use Gibbon\Data\Validator;
use Gibbon\Services\Format;
use Gibbon\Services\BackgroundProcess;
use Gibbon\Contracts\Comms\SMS;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Session\SessionFactory;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\LogGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\Messenger\MessengerGateway;
use Gibbon\Domain\Messenger\MessengerReceiptGateway;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;
use Gibbon\Module\Messenger\Signature;

/**
 * MessageProcess
 *
 * @version v20
 * @since   v20
 */
class MessageProcess extends BackgroundProcess implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct()
    {
    }

    public function runSendMessage($gibbonMessengerID, $gibbonSchoolYearID, $gibbonPersonID, $gibbonRoleIDCurrent, $message)
    {
        // Setup the core Gibbon objects
        $container = $this->getContainer();
        $pdo = $container->get(Connection::class);
        $connection2 = $pdo->getConnection();
        $session = $container->get(Session::class);

        // Setup session variables for this user
        SessionFactory::populateSettings($session, $pdo);
        $userData = $container->get(UserGateway::class)->getSafeUserData($gibbonPersonID);
        $session->set($userData);

        $session->set('gibbonRoleIDCurrent', $gibbonRoleIDCurrent);
        $session->set('gibbonSchoolYearID', $gibbonSchoolYearID);
        $session->set('gibbonSchoolYearIDCurrent', $gibbonSchoolYearID);

        // Setup messenger variables
        $AI = str_pad($gibbonMessengerID, 12, '0', STR_PAD_LEFT);

        // Setup gateways
        $messengerReceiptGateway = $container->get(MessengerReceiptGateway::class);
        $messengerGateway = $container->get(MessengerGateway::class);
        $settingGateway = $container->get(SettingGateway::class);
        $logGateway = $container->get(LogGateway::class);

        // Get the recipients (which have already been manually checked)
        $report = $messengerReceiptGateway->selectMessageRecipientList($gibbonMessengerID)->fetchAll();

        //Proceed!
        //Setup return variables
        $emailCount = $smsCount = $smsBatchCount = 0;

        // Validate Inputs
        $validator = $container->get(Validator::class);
        $message = $validator->sanitize($message, ['body' => 'HTML']);
        
        $email = $message['email'] ?? 'N';
        $from = $message['emailFrom'];
        $emailReplyTo = $message['emailReplyTo'] ?? '';

        $sms = $message['sms'] ?? 'N';
        $smsCreditBalance = ($sms == 'Y' && !empty($message['smsCreditBalance'])) ? $message['smsCreditBalance'] : null;

        $subject = $message['subject'] ?? '';
        $body = stripslashes($message['body'] ?? '');

        $emailReceipt = $message['emailReceipt'] ?? 'N';
        $emailReceiptText = $message['emailReceiptText'] ?? '';
        $individualNaming = $message['individualNaming'] ?? 'N';
        $includeSignature = $message['includeSignature'] ?? 'N';
        $confidential = $message['confidential'] ?? 'N';

        // SMS Credit notification
        if ($smsCreditBalance != null && $smsCreditBalance < 1000) {
            $notificationGateway = $container->get(NotificationGateway::class);
            $notificationSender = $container->get(NotificationSender::class);
            $organisationAdministrator = $settingGateway->getSettingByScope('System', 'organisationAdministrator');
            $notificationString = __('Low SMS credit warning.');
            $notificationSender->addNotification($organisationAdministrator, $notificationString, "Messenger", "/index.php?q=/modules/Messenger/messenger_post.php");
            $notificationSender->sendNotifications();
        }
        
        $partialFail = false;

        if ($sms=="Y") {
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
            $logGateway->addLog($session->get('gibbonSchoolYearIDCurrent'), getModuleID($connection2, $message["address"]), $session->get('gibbonPersonID'), 'SMS Send Status', array('Status' => $smsStatus, 'Result' => count($result), 'Recipients' => $recipients));
        }

        if ($email=="Y") {
            //Set up email
            $emailCount = 0;
            $emailErrors = [];
            $mail= $container->get(Mailer::class);
            $mail->SMTPKeepAlive = true;
            $mail->SMTPDebug = 0;
            $mail->Debugoutput = 'error_log';
            
            if ($emailReplyTo!="") {
                $mail->AddReplyTo($emailReplyTo, '');
            }
            if ($from!=$session->get('email')) {	//If sender is using school-wide address, send from school
                $mail->SetFrom($from, $session->get('organisationName'));
            } else { //Else, send from individual
                $mail->SetFrom($from, $session->get('preferredName') . " " . $session->get('surname'));
            }
            
            // Turn copy-pasted div breaks into paragraph breaks
            $body = str_ireplace(['<div ', '<div>', '</div>'], ['<p ', '<p>', '</p>'], $body);

            if ($includeSignature == 'Y') {
                $signature = $container->get(Signature::class)->getSignature($gibbonPersonID);
                $body .= $signature;
            }

            $mail->Subject = $subject;
            $mail->renderBody('mail/email.twig.html', [
                'title'  => $subject,
                'body'   => $body
            ]);

            //Send to sender, if not in recipient list
            $includeSender = true;
            foreach ($report as $reportEntry) {
                if ($reportEntry['contactType'] == 'Email') {
                    if ($reportEntry['contactDetail'] == $from) {
                        $includeSender = false;
                    }
                }
            }
            if ($includeSender) {
                $emailCount ++;
                $mail->AddAddress($from);

                if ($message['emailReceipt'] == 'Y') {
                    $mail->renderBody('mail/email.twig.html', [
                        'title'  => $subject,
                        'body'   => $this->handleFakeReadReceiptLink($body, $emailReceiptText),
                    ]);
                }
                
                if(!$mail->Send()) {
                    $partialFail = TRUE;
                }
            }

            //If sender is using school-wide address, and it is not in recipient list, send to school-wide address
            if ($from!=$session->get('email')) { //If sender is using school-wide address, add them to recipient list.
                $includeSender = true;
                foreach ($report as $reportEntry) {
                    if ($reportEntry['contactType'] == 'Email') {
                        if ($reportEntry['contactDetail'] == $session->get('email')) {
                            $includeSender = false;
                        }
                    }
                }
                if ($includeSender) {
                    $emailCount ++;
                    $mail->ClearAddresses();
                    $mail->AddAddress($session->get('email'));

                    if ($message['emailReceipt'] == 'Y') {
                        $mail->renderBody('mail/email.twig.html', [
                            'title'  => $subject,
                            'body'   => $this->handleFakeReadReceiptLink($body, $emailReceiptText),
                        ]);
                    }
                    
                    if(!$mail->Send()) {
                        $partialFail = TRUE;
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
                        $bodyReadReceipt = "<hr style='border: 1px solid #dddddd;'><a target='_blank' href='".$session->get('absoluteURL')."/index.php?q=/modules/Messenger/messenger_emailReceiptConfirm.php&gibbonMessengerID=$AI&gibbonPersonID=".$reportEntry['gibbonPersonID']."&key=".$reportEntry['key']."'>".$emailReceiptText."</a><hr style='border: 1px solid #dddddd;'><br/>";
                        if (strpos($body, '[confirmLink]') !== false) {
                            $bodyOut = str_replace('[confirmLink]', $bodyReadReceipt, $body);
                        }
                        else {
                            $bodyOut = $bodyReadReceipt.$body;
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
                                $studentNames = '<i>'.__('This email relates to the following students: ').$studentNameList.'</i><br/>';
                            } else {
                                $studentNames = '<i>'.__('This email relates to the following student: ').$studentNameList.'</i><br/>';
                            }
                        }
                        $bodyOut = $studentNames.$bodyOut;
                    }

                    $mail->renderBody('mail/email.twig.html', [
                        'title'  => $subject,
                        'body'   => $bodyOut
                    ]);
                    if(!$mail->Send()) {
                        $partialFail = TRUE;
                        $logGateway->addLog($session->get('gibbonSchoolYearIDCurrent'), getModuleID($connection2, $message["address"]), $session->get('gibbonPersonID'), 'Email Send Status', array('Status' => 'Not OK', 'Result' => $mail->ErrorInfo, 'Recipients' => $reportEntry['contactDetail']));
                        $emailErrors[] = $reportEntry['contactDetail'];
                    } else {
                        $messengerReceiptGateway->update($reportEntry['gibbonMessengerReceiptID'], ['sent' => 'Y']);
                    }
                }
            }

            // Optionally send bcc copies of this message, excluding recipients already sent to.
            $recipientList = array_column($report, 4);
            $messageBccList = array_map('trim', explode(',', $settingGateway->getSettingByScope('Messenger', 'messageBcc')));
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

        $sendResult = [
            'return'        => $partialFail ? 'error4' : 'success0',
            'email'         => $message['email'],
            'emailCount'    => $emailCount ?? 0,
            'emailErrors'   => $emailErrors ?? [],
            'sms'           => $message['sms'],
            'smsCount'      => $smsCount ?? 0,
            'smsBatchCount' => $smsBatchCount ?? 0,
        ];
    
        // Send a notification to the user and return the result
        if ((!empty($message['email']) && $message['email'] == 'Y') || (!empty($message['sms']) && $message['sms'] == 'Y')) {
            $this->sendResultNotification($gibbonPersonID, $gibbonMessengerID, $message['subject'], $sendResult);
        }

        return $sendResult;
    }

    public function runSendDraft($message)
    {
        if ($message['email'] != 'Y') return false;

        $session = $this->getContainer()->get(Session::class);
        $mail = $this->getContainer()->get(Mailer::class);

        $mail->Subject = __('Draft').': '.$message['subject'];
        $mail->SetFrom($message['emailFrom']);
        $mail->AddReplyTo($message['emailReplyTo']);
        $mail->AddAddress($session->get('email'));

        $message['body'] = str_ireplace(['<div ', '<div>', '</div>'], ['<p ', '<p>', '</p>'], $message['body']);

        if ($message['emailReceipt'] == 'Y') {
            $message['body'] = $this->handleFakeReadReceiptLink($message['body'], $message['emailReceiptText']);
        }

        if ($message['includeSignature'] == 'Y') {
            $signature = $this->getContainer()->get(Signature::class)->getSignature($session->get('gibbonPersonID'));
            $message['body'] .= $signature;
        }

        $mail->renderBody('mail/email.twig.html', [
            'title'  => $message['subject'],
            'body'   => $message['body'],
        ]);

        return $mail->Send();
    }

    protected function handleFakeReadReceiptLink($body, $emailReceiptText)
    {
        $session = $this->getContainer()->get(Session::class);

        $bodyReadReceipt = "<hr style='border: 1px solid #dddddd;'><a target='_blank' href='".$session->get('absoluteURL')."/index.php?q=/modules/Messenger/messenger_emailReceiptConfirm.php&gibbonMessengerID=test&gibbonPersonID=test&key=test'>".$emailReceiptText."</a><hr style='border: 1px solid #dddddd;'><br/>";
        if (is_numeric(strpos($body, '[confirmLink]'))) {
            return str_replace('[confirmLink]', $bodyReadReceipt, $body);
        } else {
            return $bodyReadReceipt.$body;
        }
    }

    protected function sendResultNotification($gibbonPersonID, $gibbonMessengerID, $subject, $sendResult)
    {
        switch ($sendResult['return']) {
            case 'success0':
                $actionText = __('Your message "{subject}" was sent successfully.', ['subject' => $subject]);

                if ($sendResult['email'] == 'Y' && is_numeric($sendResult['emailCount'])) {
                    $actionText .= '<br/>'. sprintf(__('%1$s email(s) were dispatched.'), $sendResult['emailCount']);
                }
                if ($sendResult['sms'] == 'Y' && is_numeric($sendResult['smsCount'])) {
                    $actionText .= '<br/>' . sprintf(__('%1$s SMS(es) were dispatched in %2$s batch(es).'), $sendResult['smsCount'], $sendResult['smsBatchCount']);
                }
                break;
            case 'error0':
                $actionText = __('Your request failed because you do not have access to this action.');
                break;
            case 'error2':
                $actionText = __('Your request failed due to a database error.');
                break;
            case 'error3':
                $actionText = __('Your request failed because your inputs were invalid.');
                break;
            case 'error5':
                $actionText = __('Your request failed due to an attachment error.');
                break;
            default:
                $actionText = __('Your request was completed successfully, but some or all messages could not be delivered.');
                break;
        }

        if (!empty($sendResult['emailErrors'])) {
            $actionText .= '<br/><br/>' .__('You message failed to deliver to the following recipients. This can happen because the email is invalid or the receiving mail server did not respond.').'<br/>';
            $actionText .= Format::list($sendResult['emailErrors']);
        }

        $notificationSender = $this->getContainer()->get(NotificationSender::class);
        $notificationSender->addNotification($gibbonPersonID, $actionText, 'Messenger', '/index.php?q=/modules/Messenger/messenger_manage_report.php&gibbonMessengerID='.$gibbonMessengerID.'&sidebar=true&search=');
        $notificationSender->sendNotifications();
    }
}
