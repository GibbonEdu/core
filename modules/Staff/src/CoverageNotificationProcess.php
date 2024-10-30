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
use Gibbon\Module\Staff\Messages\NewCoverageRequest;
use Gibbon\Module\Staff\Messages\NewAbsenceWithCoverage;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Module\Staff\Messages\AbsenceWithCoverageCancelled;

/**
 * CoverageNotificationProcess
 *
 * @version v18
 * @since   v18
 */
class CoverageNotificationProcess extends BackgroundProcess
{
    protected $staffAbsenceGateway;
    protected $staffCoverageGateway;
    protected $staffCoverageDateGateway;
    protected $substituteGateway;
    protected $groupGateway;

    protected $messageSender;
    protected $urgentNotifications;
    protected $internalCoverage;
    protected $urgencyThreshold;
    protected $organisationHR;
    protected $coverageMode;

    public function __construct(
        StaffAbsenceGateway $staffAbsenceGateway,
        StaffCoverageGateway $staffCoverageGateway,
        StaffCoverageDateGateway $staffCoverageDateGateway,
        SubstituteGateway $substituteGateway,
        GroupGateway $groupGateway,
        SettingGateway $settingGateway,
        MessageSender $messageSender
    ) {
        $this->staffAbsenceGateway = $staffAbsenceGateway;
        $this->staffCoverageGateway = $staffCoverageGateway;
        $this->staffCoverageDateGateway = $staffCoverageDateGateway;
        $this->substituteGateway = $substituteGateway;
        $this->groupGateway = $groupGateway;
        $this->messageSender = $messageSender;

        $this->internalCoverage = $settingGateway->getSettingByScope('Staff', 'coverageInternal');
        $this->urgentNotifications = $settingGateway->getSettingByScope('Staff', 'urgentNotifications');
        $this->urgencyThreshold = intval($settingGateway->getSettingByScope('Staff', 'urgencyThreshold')) * 86400;
        $this->organisationHR = $settingGateway->getSettingByScope('System', 'organisationHR');
        $this->coverageMode =  $settingGateway->getSettingByScope('Staff', 'coverageMode');
    }

    public function runNewCoverageRequest($coverageList)
    {
        if (empty($coverageList)) return false;

        $dates = $this->getCoverageDates($coverageList);

        $coverage = $this->getCoverageDetailsByID(current($coverageList));

        $recipients = [$this->organisationHR];
        $message = new NewCoverageRequest($coverage, $dates);

        // Add the absent person, if this coverage request was created by someone else
        if ($coverage['gibbonPersonID'] != $coverage['gibbonPersonIDStatus']) {
            $recipients[] = $coverage['gibbonPersonID'];
        }

        // Add the notification group members, if selected
        if (!empty($coverage['gibbonGroupID'])) {
            $groupRecipients = $this->groupGateway->selectPersonIDsByGroup($coverage['gibbonGroupID'])->fetchAll(\PDO::FETCH_COLUMN, 0);
            $recipients = array_merge($recipients, $groupRecipients);
        }

        if ($sent = $this->messageSender->send($message, $recipients, $coverage['gibbonPersonID'])) {
            $data = [
                'notificationSent' => 'Y',
                'notificationList' => json_encode($recipients),
            ];
            foreach ($coverageList as $gibbonStaffCoverageID) {
                $this->staffCoverageGateway->update($gibbonStaffCoverageID, $data);
            }
            
        }

        return $sent;
    }

    public function runNewAbsenceWithCoverageRequest($coverageList)
    {
        if (empty($coverageList)) return false;

        $dates = $this->getCoverageDates($coverageList);

        $coverage = $this->getCoverageDetailsByID(current($coverageList));
        $absence = $this->staffAbsenceGateway->getAbsenceDetailsByID($coverage['gibbonStaffAbsenceID'] ?? '');

        $message = new NewAbsenceWithCoverage($absence, $coverage, $dates);

        $recipients = !empty($coverage['notificationListAbsence']) ? json_decode($coverage['notificationListAbsence']) : [];
        $recipients[] = $this->organisationHR;

        // Add the absent person, if this coverage request was created by someone else
        if ($coverage['gibbonPersonID'] != $coverage['gibbonPersonIDStatus'] || empty($coverage['gibbonPersonIDApproval'])) {
            $recipients[] = $coverage['gibbonPersonID'];
        }

        // Add the notification group members, if selected
        if (!empty($coverage['gibbonGroupID'])) {
            $groupRecipients = $this->groupGateway->selectPersonIDsByGroup($coverage['gibbonGroupID'])->fetchAll(\PDO::FETCH_COLUMN, 0);
            $recipients = array_merge($recipients, $groupRecipients);
        }

        if ($sent = $this->messageSender->send($message, $recipients, $coverage['gibbonPersonID'])) {
            $data = [
                'status' => $this->coverageMode == 'Assigned' && !empty($coverage['gibbonPersonIDCoverage'])? 'Accepted' : 'Requested',
                'notificationSent' => 'Y',
                'notificationList' => json_encode($recipients),
            ];
            foreach ($coverageList as $gibbonStaffCoverageID) {
                $this->staffCoverageGateway->update($gibbonStaffCoverageID, $data);
            }

            $this->staffAbsenceGateway->update($coverage['gibbonStaffAbsenceID'], [
                'notificationSent' => 'Y',
            ]);
        }

        return $sent;
    }

    public function runApprovedRequest($coverageList)
    {
        if (empty($coverageList)) return false;
        $sent = [];

        foreach ($coverageList as $gibbonStaffCoverageID) {
            $coverage = $this->getCoverageDetailsByID($gibbonStaffCoverageID);

            $sent = $coverage['requestType'] == 'Broadcast'
                ? $this->runBroadcastRequest($gibbonStaffCoverageID)
                : $this->runIndividualRequest($gibbonStaffCoverageID);
        }

        return $sent;
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
                ->filterBy('allStaff', $this->internalCoverage == 'Y')
                ->filterBy('substituteTypes', $coverage['substituteTypes']);
            $availableByDate = $this->substituteGateway->queryAvailableSubsByDate($criteria, $date['date'])->toArray();
            $availableSubs = array_merge($availableSubs, $availableByDate);
        }
        
        if ($this->internalCoverage == 'N' || $coverage['urgent'] == true) {
            if (count($availableSubs) > 0) {
                // Send messages to available subs
                $recipients = array_column($availableSubs, 'gibbonPersonID');
                $message = new BroadcastRequest($coverage);
            } else {
                // Send a message to admin - no coverage
                $recipients = [$this->organisationHR];
                $message = new NoCoverageAvailable($coverage);
            }
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
        if ($coverage['requestType'] == 'Assigned') {
            $recipients[] = $this->organisationHR;
        }

        $message = new CoverageDeclined($coverage);

        return $this->messageSender->send($message, $recipients, $coverage['gibbonPersonIDCoverage']);
    }

    public function runCoverageCancelled($gibbonStaffCoverageID)
    {
        $coverage = $this->getCoverageDetailsByID($gibbonStaffCoverageID);
        if (empty($coverage)) return false;

        $dates = $this->getCoverageDates($gibbonStaffCoverageID);

        $recipients = [$coverage['gibbonPersonIDStatus'], $coverage['gibbonPersonIDCoverage'], $coverage['gibbonPersonID']];
        if ($coverage['requestType'] == 'Assigned') {
            $recipients[] = $this->organisationHR;
        }

        // Add the absence approver, if there was one.
        if (!empty($coverage['gibbonPersonIDApproval'])) {
            $recipients[] = $coverage['gibbonPersonIDApproval'];
        }

        $message = new CoverageCancelled($coverage, $dates);

        return $this->messageSender->send($message, $recipients, $coverage['gibbonPersonID']);
    }

    /**
     * Sends a message to relevant users when an absence with coverage has been cancelled.
     *
     * @param string $gibbonStaffAbsenceID
     * @param array $coverageList
     * @return array
     */
    public function runAbsenceWithCoverageCancelled($gibbonStaffAbsenceID, $coverageList = [])
    {
        $absence = $this->staffAbsenceGateway->getAbsenceDetailsByID($gibbonStaffAbsenceID);
        $dates = $this->getCoverageDates($coverageList);
        $coverage = $this->getCoverageDetailsByID(current($coverageList));

        if (empty($absence) || empty($coverage)) return false;

        // Target the absence message to the selected staff
        $message = new AbsenceWithCoverageCancelled($absence, $coverage, $dates);
        $recipients = !empty($absence['notificationList']) ? json_decode($absence['notificationList']) : [];
        $recipients[] = $absence['gibbonPersonID'];

        // Add the coverage creator, if it is not the same as the absent person
        if ($coverage['gibbonPersonIDStatus'] != $absence['gibbonPersonID']) {
            $recipients[] = $coverage['gibbonPersonIDStatus'];
        }
        
        // If this is assigned coverage, let the manager know
        if ($coverage['requestType'] == 'Assigned') {
            $recipients[] = $this->organisationHR;
        }

        // Add the absence approver, if there is one
        if (!empty($absence['gibbonPersonIDApproval'])) {
            $recipients[] = $absence['gibbonPersonIDApproval'];
        }

        // Add the notification group members, if selected
        if (!empty($absence['gibbonGroupID'])) {
            $groupRecipients = $this->groupGateway->selectPersonIDsByGroup($absence['gibbonGroupID'])->fetchAll(\PDO::FETCH_COLUMN, 0);
            $recipients = array_merge($recipients, $groupRecipients);
        }

        $sent = $this->messageSender->send($message, $recipients, $absence['gibbonPersonID']);

        // Notify the coverage teachers who have received individual requests
        $message = new CoverageCancelled($coverage, $dates);
        $recipients = [];

        // Add any users who have already been assigned to this coverage
        foreach ($dates as $date) {
            if ($date['requestType'] != 'Individual') continue;
            if (empty($date['gibbonPersonIDCoverage'])) continue;
            $recipients[] = $date['gibbonPersonIDCoverage'];
        }

        $sent += $this->messageSender->send($message, $recipients, $absence['gibbonPersonID']);
    

        return $sent;
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

    private function getCoverageDates($gibbonStaffCoverageID)
    {
        $dates = $this->staffCoverageDateGateway->selectDatesByCoverage($gibbonStaffCoverageID)->toDataSet();

        $coverageByTimetable = count(array_filter($dates->toArray(), function($item) {
            return !empty($item['foreignTableID']);
        }));

        if (!$coverageByTimetable) return $dates;

        $dates->transform(function (&$item) {
            if (empty($item['foreignTableID'])) return;

            $times = $this->staffCoverageDateGateway->getCoverageTimesByForeignTable($item['foreignTable'], $item['foreignTableID'], $item['date']);

            $item['period'] = $times['period'] ?? '';
            $item['contextName'] = $times['contextName'] ?? '';
        });

        return $dates;
    }
}
