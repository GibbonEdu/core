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

    public function __construct(NotificationGateway $gateway, session $session)
    {
        $this->gateway = $gateway;
        $this->session = $session;
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

        $mail = new GibbonMailer($this->session);

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
                $body .= $this->getNotificationLink();
                $body .= $this->getNotificationFooter();
                
                $bodyPlain = emailBodyConvert($body);

                if (!empty($organisationEmail)) {
                    $mail->SetFrom($organisationEmail, $organisationName);
                } else {
                    $mail->SetFrom($organisationAdministratorEmail, $organisationName);
                }
                $mail->AddAddress($emailPreference['email']);
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->AltBody = $bodyPlain;
                $mail->Send();
            }
        }
    }

    protected function getNotificationLink()
    {
        return sprintf(__('Login to %1$s and use the notification icon to check your new notification, or %2$sclick here%3$s.'), $this->session->get('systemName'), "<a href='".$this->session->get('absoluteURL')."/index.php?q=notifications.php'>", '</a>');
    }

    protected function getNotificationFooter()
    {
        $output = '<br/><br/>';
        $output .= '<hr/>';
        $output .= "<p style='font-style: italic; font-size: 85%'>";
        $output .= sprintf(__('If you do not wish to receive email notifications from %1$s, please %2$sclick here%3$s to adjust your preferences:'), $this->session->get('systemName'), "<a href='".$this->session->get('absoluteURL')."/index.php?q=preferences.php'>", '</a>');
        $output .= '<br/><br/>';
        $output .= sprintf(__('Email sent via %1$s at %2$s.'), $this->session->get('systemName'), $this->session->get('organisationName'));
        $output .= '</p>';

        return $output;
    }
}
