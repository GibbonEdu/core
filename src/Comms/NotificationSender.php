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

namespace Gibbon\Comms;

use Gibbon\session;
use Gibbon\Domain\System\NotificationGateway;

/**
 * Notification Sender
 *
 * Holds a collection of notifications and sends them.
 * TODO: Add background processing for notification emails.
 *
 * @version v14
 * @since   v14
 */
class NotificationSender
{
    protected $notifications = array();

    protected $gateway;
    protected $session;
    protected $mailer;

    public function __construct(NotificationGateway $gateway, session $session)
    {
        $this->gateway = $gateway;
        $this->session = $session;
        $this->mailer = new GibbonMailer($session);
    }

    public function addNotification($gibbonPersonID, $text, $moduleName, $actionLink)
    {
        $this->notifications[] = array(
            'gibbonPersonID' => $gibbonPersonID, 
            'text'           => $text, 
            'moduleName'     => $moduleName, 
            'actionLink'     => $actionLink
        );
    }

    public function getNotificationCount()
    {
        return count($this->notifications);
    }

    public function sendNotifications()
    {
        if ($this->getNotificationCount() == 0) {
            return false;
        }

        foreach ($this->notifications as $row) {
            //Check for existence of notification in new status
            $result = $this->gateway->selectNotificationByStatus($row, 'New');

            if ($result && $result->rowCount() > 0) {
                $notification = $result->fetch();
                $this->gateway->updateNotificationCount($notification['gibbonNotificationID'], $notification['count']+1);
            } else {
                $this->gateway->insertNotification($row);
            }

            //Check for email notification preference and email address, and send if required
            $emailPreference = $this->gateway->getNotificationPreference($row['gibbonPersonID']);

            if (!empty($emailPreference) && $emailPreference['receiveNotificationEmails'] == 'Y') {
                $guid = $this->session->guid();
                $absoluteURL = $this->session->get('absoluteURL');
                $systemName = $this->session->get('systemName');

                $organisationName = $this->session->get('organisationName');
                $organisationNameShort = $this->session->get('organisationNameShort');
                $organisationEmail = $this->session->get('organisationEmail');
                $organisationAdministratorEmail = $this->session->get('organisationAdministratorEmail');

                //Attempt email send
                $subject = sprintf(__('You have received a notification on %1$s at %2$s (%3$s %4$s)'), $systemName, $organisationNameShort, date('H:i'), dateConvertBack($guid, date('Y-m-d')));
                
                $body = __('Notification').': '.$row['text'].'<br/><br/>';
                $body .= sprintf(__('Login to %1$s and use the notification icon to check your new notification, or %2$sclick here%3$s.'), $systemName, "<a href='".$absoluteURL."/index.php?q=notifications.php'>", '</a>');
                $body .= '<br/><br/>';
                $body .= '<hr/>';
                $body .= "<p style='font-style: italic; font-size: 85%'>";
                $body .= sprintf(__('If you do not wish to receive email notifications from %1$s, please %2$sclick here%3$s to adjust your preferences:'), $systemName, "<a href='".$absoluteURL."/index.php?q=preferences.php'>", '</a>');
                $body .= '<br/><br/>';
                $body .= sprintf(__('Email sent via %1$s at %2$s.'), $systemName, $organisationName);
                $body .= '</p>';
                $bodyPlain = emailBodyConvert($body);

                //$this->mailer->IsSMTP();
                if (!empty($organisationEmail)) {
                    $this->mailer->SetFrom($organisationEmail, $organisationName);
                } else {
                    $this->mailer->SetFrom($organisationAdministratorEmail, $organisationName);
                }
                $this->mailer->AddAddress($emailPreference['email']);
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $body;
                $this->mailer->AltBody = $bodyPlain;
                $this->mailer->Send();
            }
        }
    }
}
