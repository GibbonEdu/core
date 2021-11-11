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

namespace Gibbon\Module\Messenger;

use Gibbon\Services\Format;
use Gibbon\Session\SessionFactory;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Comms\NotificationSender;
use Gibbon\Services\BackgroundProcess;
use Gibbon\Contracts\Database\Connection;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;

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

    public function runSendMessage($gibbonMessengerID, $gibbonSchoolYearID, $gibbonPersonID, $gibbonRoleIDCurrent, $messageData)
    {
        // Extract the message data back into POST
        $_POST = $messageData;
        
        // Setup the core Gibbon objects
        $container = $this->getContainer();
        $gibbon = $container->get('config');
        $guid = $gibbon->getConfig('guid');
        $pdo = $container->get(Connection::class);
        $connection2 = $pdo->getConnection();
        $session = $gibbon->session;

        // Setup session variables for this user
        SessionFactory::populateSettings($session, $pdo);
        $userData = $container->get(UserGateway::class)->getSafeUserData($gibbonPersonID);
        $session->set($userData);

        $gibbon->session->set('gibbonRoleIDCurrent', $gibbonRoleIDCurrent);
        $gibbon->session->set('gibbonSchoolYearID', $gibbonSchoolYearID);
        $gibbon->session->set('gibbonSchoolYearIDCurrent', $gibbonSchoolYearID);

        // Setup messenger variables
        $AI = str_pad($gibbonMessengerID, 12, '0', STR_PAD_LEFT);

        // Run the original message process script
        $sendResult = include __DIR__ . '/../messenger_postProcess.php';

        // Send a notification to the user and return the result
        if ((!empty($messageData['email']) && $messageData['email'] == 'Y') || (!empty($messageData['sms']) && $messageData['sms'] == 'Y')) {
            $this->sendResultNotification($gibbonPersonID, $gibbonMessengerID, $messageData['subject'], $sendResult);
        }

        return $sendResult;
    }

    protected function sendResultNotification($gibbonPersonID, $gibbonMessengerID, $subject, $sendResult)
    {
        switch ($sendResult['return']) {
            case 'success0':
                $actionText = __('Your message "{subject}" was sent successfully.', ['subject' => $subject]);

                if (is_numeric($sendResult['emailCount'])) {
                    $actionText .= '<br/>'. sprintf(__('%1$s email(s) were dispatched.'), $sendResult['emailCount']);
                }
                if (is_numeric($sendResult['smsCount']) && is_numeric($sendResult['smsBatchCount'])) {
                    $actionText .= '<br/>' . sprintf(__('%1$s SMS(es) were dispatched in %2$s batch(es).'), $sendResult['smsCount'], $sendResult['smsBatchCount']);
                }
                break;
            case 'fail0':
                $actionText = __('Your request failed because you do not have access to this action.');
                break;
            case 'fail2':
                $actionText = __('Your request failed due to a database error.');
                break;
            case 'fail3':
                $actionText = __('Your request failed because your inputs were invalid.');
                break;
            case 'fail5':
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
