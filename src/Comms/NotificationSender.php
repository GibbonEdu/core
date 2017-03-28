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
 * Holds a collection of notifications. Sends notifications by inserting in the database and optionally sends 
 * by email based on the recipient's notification settings.
 * 
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

    /**
     * Injects a gateway and session dependency, used for database inserts and email formatting.
     *
     * @param  NotificationGateway  $gateway
     * @param  session              $session
     */
    public function __construct(NotificationGateway $gateway, session $session)
    {
        $this->gateway = $gateway;
        $this->session = $session;
    }

    /**
     * Adds a notification to the collection as an array.
     *
     * @param  int|string  $gibbonPersonID
     * @param  string  $text
     * @param  string  $moduleName
     * @param  string  $actionLink
     */
    public function addNotification($gibbonPersonID, $text, $moduleName, $actionLink)
    {
        $this->notifications[] = array(
            'gibbonPersonID' => $gibbonPersonID,
            'text'           => $text,
            'moduleName'     => $moduleName,
            'actionLink'     => $actionLink
        );
    }

    /**
     * Gets the current notification count.
     *
     * @return  int
     */
    public function getNotificationCount()
    {
        return count($this->notifications);
    }

    /**
     * Delivers all notifications by inserting/updating in database, and optionally by sends by email.
     *
     * @return  array Send report with success/fail counts.
     */
    public function sendNotifications()
    {
        $sendReport = array(
            'count' => $this->getNotificationCount(),
            'inserts' => 0,
            'updates' => 0,
            'emailSent' => 0,
            'emailFailed' => 0
        );

        if ($this->getNotificationCount() == 0) {
            return $sendReport;
        }

        $mail = new GibbonMailer($this->session);

        foreach ($this->notifications as $notification) {
            // Check for existence of notification in new status
            $result = $this->gateway->selectNotificationByStatus($notification, 'New');

            if ($result && $result->rowCount() > 0) {
                $row = $result->fetch();
                $this->gateway->updateNotificationCount($row['gibbonNotificationID'], $row['count']+1);
                $sendReport['updates']++;
            } else {
                $this->gateway->insertNotification($notification);
                $sendReport['inserts']++;
            }

            // Check for email notification preference and email address, and send if required
            $emailPreference = $this->gateway->getNotificationPreference($notification['gibbonPersonID']);

            if (!empty($emailPreference) && $emailPreference['receiveNotificationEmails'] == 'Y') {
                $organisationName = $this->session->get('organisationName');
                $organisationEmail = $this->session->get('organisationEmail');
                $organisationAdministratorEmail = $this->session->get('organisationAdministratorEmail');

                // Format the email content
                $body = __('Notification').': '.$notification['text'].'<br/><br/>';
                $body .= $this->getNotificationLink();
                $body .= $this->getNotificationFooter();
                
                $fromEmail = (!empty($organisationEmail))? $organisationEmail : $organisationAdministratorEmail;
                $mail->SetFrom($fromEmail, $organisationName);
                $mail->AddAddress($emailPreference['email']);
                $mail->Subject = $this->getNotificationSubject();
                $mail->Body = $body;
                $mail->AltBody = emailBodyConvert($body);

                // Attempt email send
                if ($mail->Send()) {
                    $sendReport['emailSent']++;
                } else {
                    $sendReport['emailFailed']++;
                }
            }
        }

        return $sendReport;
    }

    /**
     * Formatted notification subject.
     * @return  string
     */
    protected function getNotificationSubject()
    {
        return sprintf(__('You have received a notification on %1$s at %2$s (%3$s %4$s)'), $this->session->get('systemName'), $this->session->get('organisationNameShort'), date('H:i'), dateConvertBack($this->session->guid(), date('Y-m-d')));
    }

    /**
     * Formatted notification link.
     * @return  string
     */
    protected function getNotificationLink()
    {
        return sprintf(__('Login to %1$s and use the notification icon to check your new notification, or %2$sclick here%3$s.'), $this->session->get('systemName'), "<a href='".$this->session->get('absoluteURL')."/index.php?q=notifications.php'>", '</a>');
    }

    /**
     * Formatted notification footer.
     * @return  string
     */
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
