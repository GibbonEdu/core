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

namespace Gibbon\Module\Reports;

use Gibbon\Comms\NotificationSender;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Services\BackgroundProcess;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Gibbon\Module\Reports\Domain\ReportingProofGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

/**
 * SendNotificationsProcess
 *
 * @version v19
 * @since   v19
 */
class SendNotificationsProcess extends BackgroundProcess implements ContainerAwareInterface
{
    use ContainerAwareTrait;


    public function __construct()
    {
        
    }

    public function runSendProofReadingEdits($gibbonReportingCycleIDList, $notificationText)
    {
        $notificationSender = $this->container->get(NotificationSender::class);

        $proofReadingGateway = $this->container->get(ReportingProofGateway::class);
        $edits = $proofReadingGateway->selectPendingProofReadingEdits($gibbonReportingCycleIDList)->fetchGrouped();

        foreach ($edits as $gibbonPersonID => $details) {
            $gibbonPersonID = str_pad($gibbonPersonID, 10, '0', STR_PAD_LEFT);
            $actionLink = '/index.php?q=/modules/Reports/reporting_proofread.php&filter=status:Edited';
            $actionText = __($notificationText, ['count' => count($details)]);
            
            $notificationSender->addNotification($gibbonPersonID, $actionText, __('Proof Reading Edits'), $actionLink);
        }
        
        return $notificationSender->sendNotifications();
    }
}
