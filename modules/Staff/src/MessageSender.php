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

namespace Gibbon\Module\Staff;

use Gibbon\Contracts\Comms\Mailer as MailerContract;
use Gibbon\Contracts\Comms\SMS as SMSContract;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\UserGateway;

/**
 * MessageSender
 *
 * @version v18
 * @since   v18
 */
class MessageSender
{
    protected $notificationGateway;
    protected $userGateway;
    protected $settings;
    protected $mail;
    protected $sms;
    protected $via;

    public function __construct(SettingGateway $settingGateway, MailerContract $mail, SMSContract $sms, NotificationGateway $notificationGateway, UserGateway $userGateway)
    {
        $this->settings = [
            'absoluteURL' => $settingGateway->getSettingByScope('System', 'absoluteURL'),
        ];
        $this->notificationGateway = $notificationGateway;
        $this->userGateway = $userGateway;
        $this->mail = $mail;
        $this->sms = $sms;
    }

    /**
     * Send a message class to a group of recipients via multiple channels.
     *
     * @param Message   $message
     * @param array     $recipients gibbonPersonID
     * @param string    $senderID   gibbonPersonID
     * @return array
     */
    public function send(Message $message, array $recipients, $senderID = '') : array
    {
        // Get the user data per gibbonPersonID
        $sender = !empty($senderID) ? $this->userGateway->getByID($senderID) : [];
        $recipients = array_map(function ($gibbonPersonID) {
            return $this->userGateway->getByID($gibbonPersonID);
        }, array_filter(array_unique($recipients)));

        $result = [];

        foreach ($message->via() as $via) {
            switch ($via) {
                case 'sms':
                    $sent = $this->sendViaSMS($message, $recipients);
                    break;

                case 'mail':
                    $sent = $this->sendViaMail($message, $recipients, $sender);
                    break;

                case 'database':
                    $sent = $this->sendViaDatabase($message, $recipients);
                    break;
            }
            $result[$via] = $sent;
        }

        return $result;
    }

    /**
     * Sends the message via SMS and returns an array of the successful phone numbers.
     *
     * @param Message   $message
     * @param array     $recipients
     * @return array
     */
    protected function sendViaSMS(Message $message, array $recipients = []) : array
    {
        if (empty($this->sms)) return [];

        $phoneNumbers = array_map(function ($person) {
            return ($person['phone1CountryCode'] ?? '').($person['phone1'] ?? '');
        }, $recipients);

        $sent = $this->sms
            ->content($message->toSMS()."\n".'['.$this->settings['absoluteURL'].']')
            ->send($phoneNumbers);

        return is_array($sent) ? $sent : [$sent];
    }

    /**
     * Sends the message via Email and returns an array of the successful email addresses.
     *
     * @param Message   $message
     * @param array     $recipients
     * @param array     $sender
     * @return array
     */
    protected function sendViaMail(Message $message, array $recipients = [], array $sender = []) : array
    {
        if (empty($this->mail)) return [];

        $this->mail->setDefaultSender($message->toMail()['subject']);
        $this->mail->renderBody('mail/message.twig.html', $message->toMail());

        if (!empty($sender['email'])) {
            $this->mail->addReplyTo($sender['email'], $sender['preferredName'].' '.$sender['surname']);
        }

        $sent = [];
        foreach ($recipients as $person) {
            if (empty($person['email']) || $person['receiveNotificationEmails'] == 'N') continue;

            $this->mail->clearAllRecipients();
            $this->mail->clearReplyTos();
            $this->mail->AddAddress($person['email'], $person['preferredName'].' '.$person['surname']);

            if ($this->mail->Send()) {
                $sent[] = $person['email'];
            }
        }

        return $sent;
    }

    /**
     * Inserts a message into the Notification table and returns an array of gibbonPersonID.
     *
     * @param Message   $message
     * @param array     $recipients
     * @return array
     */
    protected function sendViaDatabase(Message $message, array $recipients = []) : array
    {
        if (empty($this->notificationGateway)) return [];

        $sent = [];
        foreach ($recipients as $person) {
            $notification = $message->toDatabase() + ['gibbonPersonID' => $person['gibbonPersonID']];
            $row = $this->notificationGateway->selectNotificationByStatus($notification, 'New')->fetch();

            $success = !empty($row)
                ? $this->notificationGateway->updateNotificationCount($row['gibbonNotificationID'], $row['count']+1)
                : $this->notificationGateway->insertNotification($notification);

            if ($success) {
                $sent[] = $person['gibbonPersonID'];
            }
        }

        return $sent;
    }
}
