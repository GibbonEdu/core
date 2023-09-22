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

use Gibbon\Services\BackgroundProcess;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Messenger\GroupGateway;
use Gibbon\Module\Staff\MessageSender;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Module\Staff\Messages\NewAbsence;
use Gibbon\Module\Staff\Messages\AbsenceApproval;
use Gibbon\Module\Staff\Messages\AbsencePendingApproval;
use Gibbon\Module\Staff\Messages\NewAbsencePendingApproval;

/**
 * AbsenceNotificationProcess
 *
 * @version v18
 * @since   v18
 */
class AbsenceNotificationProcess extends BackgroundProcess
{
    protected $staffAbsenceGateway;
    protected $groupGateway;

    protected $messageSender;
    protected $urgentNotifications;
    protected $urgencyThreshold;

    public function __construct(StaffAbsenceGateway $staffAbsenceGateway, GroupGateway $groupGateway, SettingGateway $settingGateway, MessageSender $messageSender)
    {
        $this->staffAbsenceGateway = $staffAbsenceGateway;
        $this->groupGateway = $groupGateway;
        $this->messageSender = $messageSender;

        $this->urgentNotifications = $settingGateway->getSettingByScope('Staff', 'urgentNotifications');
        $this->urgencyThreshold = intval($settingGateway->getSettingByScope('Staff', 'urgencyThreshold')) * 86400;
    }
    
    /**
     * Sends a message to alert users of a new absence in the system. Includes anyone selected in a notification
     * group or optional additional list of people to notify.
     *
     * @param string $gibbonStaffAbsenceID
     * @return array
     */
    public function runNewAbsence($gibbonStaffAbsenceID)
    {
        $absence = $this->getAbsenceDetailsByID($gibbonStaffAbsenceID);
        if (empty($absence)) return false;

        $message = new NewAbsence($absence);

        // Target the absence message to the selected staff
        $recipients = !empty($absence['notificationList']) ? json_decode($absence['notificationList']) : [];

        // Add the notification group members, if selected
        if (!empty($absence['gibbonGroupID'])) {
            $groupRecipients = $this->groupGateway->selectPersonIDsByGroup($absence['gibbonGroupID'])->fetchAll(\PDO::FETCH_COLUMN, 0);
            $recipients = array_merge($recipients, $groupRecipients);
        }

        // Add the absent person
        $recipients[] = $absence['gibbonPersonID'];

        if ($sent = $this->messageSender->send($message, $recipients, $absence['gibbonPersonID'])) {
            $this->staffAbsenceGateway->update($gibbonStaffAbsenceID, [
                'notificationSent' => 'Y',
            ]);
        }

        return $sent;
    }

    /**
     * Sends a message back to a staff member that their absence was approved (or declined).
     *
     * @param string $gibbonStaffAbsenceID
     * @return array
     */
    public function runAbsenceApproval($gibbonStaffAbsenceID)
    {
        $absence = $this->getAbsenceDetailsByID($gibbonStaffAbsenceID);
        if (empty($absence)) return false;
        
        $message = new AbsenceApproval($absence);
        $recipients = [$absence['gibbonPersonID']];

        return $this->messageSender->send($message, $recipients, $absence['gibbonPersonIDApproval']);
    }

    /**
     * Sends a message to the selected approval to notify them of a new absence needing approval.
     *
     * @param string $gibbonStaffAbsenceID
     * @return array
     */
    public function runAbsencePendingApproval($gibbonStaffAbsenceID)
    {
        $absence = $this->getAbsenceDetailsByID($gibbonStaffAbsenceID);
        if (empty($absence)) return false;

        $message = new AbsencePendingApproval($absence);
        $recipients = [$absence['gibbonPersonIDApproval']];

        $sent = $this->messageSender->send($message, $recipients, $absence['gibbonPersonID']);

        $message = new NewAbsencePendingApproval($absence);
        $this->messageSender->send($message, [$absence['gibbonPersonID']]);

        return $sent;
    }

    /**
     * Gets the absence details from a gateway and appends the urgency information based on the Staff settings.
     *
     * @param string $gibbonStaffAbsenceID
     * @return array
     */
    private function getAbsenceDetailsByID($gibbonStaffAbsenceID)
    {
        if ($absence = $this->staffAbsenceGateway->getAbsenceDetailsByID($gibbonStaffAbsenceID)) {
            if ($this->urgentNotifications == 'Y') {
                $relativeSeconds = strtotime($absence['dateStart']) - time();
                $absence['urgent'] = $relativeSeconds <= $this->urgencyThreshold;
            } else {
                $absence['urgent'] = false;
            }
        }

        return $absence ?? [];
    }
}
