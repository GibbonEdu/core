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

namespace Gibbon\Module\Staff;

use Gibbon\Services\BackgroundProcess;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Messenger\GroupGateway;
use Gibbon\Module\Staff\MessageSender;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Module\Staff\Messages\NewCoverage;
use Gibbon\Module\Staff\Messages\CoverageAccepted;
use Gibbon\Module\Staff\Messages\CoveragePartial;
use Gibbon\Module\Staff\Messages\CoverageCancelled;
use Gibbon\Module\Staff\Messages\CoverageDeclined;
use Gibbon\Module\Staff\Messages\IndividualRequest;
use Gibbon\Module\Staff\Messages\BroadcastRequest;
use Gibbon\Module\Staff\Messages\NoCoverageAvailable;

/**
 * CoverageNotificationProcess
 *
 * @version v18
 * @since   v18
 */
class CoverageNotificationProcess extends BackgroundProcess
{
    protected $staffCoverageGateway;
    protected $staffCoverageDateGateway;
    protected $substituteGateway;
    protected $groupGateway;

    protected $messageSender;
    protected $urgencyThreshold;
    protected $organisationHR;

    public function __construct(
        StaffCoverageGateway $staffCoverageGateway,
        StaffCoverageDateGateway $staffCoverageDateGateway,
        SubstituteGateway $substituteGateway,
        GroupGateway $groupGateway,
        SettingGateway $settingGateway,
        MessageSender $messageSender
    ) {
        $this->staffCoverageGateway = $staffCoverageGateway;
        $this->staffCoverageDateGateway = $staffCoverageDateGateway;
        $this->substituteGateway = $substituteGateway;
        $this->groupGateway = $groupGateway;
        $this->messageSender = $messageSender;

        $this->urgentNotifications = $settingGateway->getSettingByScope('Staff', 'urgentNotifications');
        $this->urgencyThreshold = intval($settingGateway->getSettingByScope('Staff', 'urgencyThreshold')) * 86400;
        $this->organisationHR = $settingGateway->getSettingByScope('System', 'organisationHR');
    }

    public function runIndividualRequest($gibbonStaffCoverageID)
    {
        $coverage = $this->getCoverageDetailsByID($gibbonStaffCoverageID);
        if (empty($coverage)) return false;

        $recipients = [$coverage['gibbonPersonIDCoverage']];
        $message = new IndividualRequest($coverage);

        if ($sent = $this->messageSender->send($message, $recipients, $coverage['gibbonPersonID'])) {
            $this->staffCoverageGateway->update($gibbonStaffCoverageID, [
                'notificationSent' => 'Y',
                'notificationList' => json_encode($recipients),
            ]);
        }

        return $sent;
    }

    public function runBroadcastRequest($gibbonStaffCoverageID)
    {
        $coverage = $this->getCoverageDetailsByID($gibbonStaffCoverageID);
        if (empty($coverage)) return false;

        $coverageDates = $this->staffCoverageDateGateway->selectDatesByCoverage($gibbonStaffCoverageID)->fetchAll();

        // Get available subs
        $availableSubs = [];
        foreach ($coverageDates as $date) {
            $criteria = $this->substituteGateway
                ->newQueryCriteria()
                ->filterBy('substituteTypes', $coverage['substituteTypes']);
            $availableByDate = $this->substituteGateway->queryAvailableSubsByDate($criteria, $date['date'])->toArray();
            $availableSubs = array_merge($availableSubs, $availableByDate);
        }
        
        if (count($availableSubs) > 0) {
            // Send messages to available subs
            $recipients = array_column($availableSubs, 'gibbonPersonID');
            $message = new BroadcastRequest($coverage);
        } else {
            // Send a message to admin - no coverage
            $recipients = [$this->organisationHR];
            $message = new NoCoverageAvailable($coverage);
        }

        if ($sent = $this->messageSender->send($message, $recipients, $coverage['gibbonPersonID'])) {
            $this->staffCoverageGateway->update($gibbonStaffCoverageID, [
                'notificationSent' => 'Y',
                'notificationList' => json_encode($recipients),
            ]);
        }

        return $sent;
    }

    public function runCoverageAccepted($gibbonStaffCoverageID, $uncoveredDates = [])
    {
        $coverage = $this->getCoverageDetailsByID($gibbonStaffCoverageID);
        if (empty($coverage)) return false;

        // Send the coverage accepted message to the requesting staff member
        $recipients = [$coverage['gibbonPersonIDStatus']];
        $message = !empty($uncoveredDates)
            ? new CoveragePartial($coverage, $uncoveredDates)
            : new CoverageAccepted($coverage);

        $sent = $this->messageSender->send($message, $recipients, $coverage['gibbonPersonIDCoverage']);

        // Send a coverage arranged message to the selected staff for this absence
        if (!empty($coverage['gibbonStaffAbsenceID'])) {
            $recipients = !empty($coverage['notificationListAbsence']) ? json_decode($coverage['notificationListAbsence']) : [];
            
            // Add the absent person, if this coverage request was created by someone else
            if ($coverage['gibbonPersonID'] != $coverage['gibbonPersonIDStatus']) {
                $recipients[] = $coverage['gibbonPersonID'];
            }

            // Add the notification group members, if selected
            if (!empty($coverage['gibbonGroupID'])) {
                $groupRecipients = $this->groupGateway->selectPersonIDsByGroup($coverage['gibbonGroupID'])->fetchAll(\PDO::FETCH_COLUMN, 0);
                $recipients = array_merge($recipients, $groupRecipients);
            }

            $message = new NewCoverage($coverage);
            $sent += $this->messageSender->send($message, $recipients, $coverage['gibbonPersonID']);
        }

        return $sent;
    }

    public function runCoverageDeclined($gibbonStaffCoverageID)
    {
        $coverage = $this->getCoverageDetailsByID($gibbonStaffCoverageID);
        if (empty($coverage)) return false;

        $recipients = [$coverage['gibbonPersonIDStatus']];
        $message = new CoverageDeclined($coverage);

        return $this->messageSender->send($message, $recipients, $coverage['gibbonPersonIDCoverage']);
    }

    public function runCoverageCancelled($gibbonStaffCoverageID)
    {
        $coverage = $this->getCoverageDetailsByID($gibbonStaffCoverageID);
        if (empty($coverage)) return false;

        $recipients = [$coverage['gibbonPersonID'], $coverage['gibbonPersonIDCoverage']];
        $message = new CoverageCancelled($coverage);

        return $this->messageSender->send($message, $recipients, $coverage['gibbonPersonID']);
    }
    
    private function getCoverageDetailsByID($gibbonStaffCoverageID)
    {
        if ($coverage = $this->staffCoverageGateway->getCoverageDetailsByID($gibbonStaffCoverageID)) {
            if ($this->urgentNotifications == 'Y') {
                $relativeSeconds = strtotime($coverage['dateStart']) - time();
                $coverage['urgent'] = $relativeSeconds > 0 && $relativeSeconds <= $this->urgencyThreshold;
            } else {
                $coverage['urgent'] = false;
            }
        }

        return $coverage ?? [];
    }
}
