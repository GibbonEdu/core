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

namespace Gibbon\Module\Staff\View;

use Gibbon\View\Page;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;


/**
 * CoverageView
 *
 * @version v18
 * @since   v18
 */
class CoverageView
{
    protected $staffCoverageGateway;
    protected $userGateway;

    public function __construct(StaffCoverageGateway $staffCoverageGateway, UserGateway $userGateway)
    {
        $this->staffCoverageGateway = $staffCoverageGateway;
        $this->userGateway = $userGateway;
    }

    public function setCoverage($gibbonStaffCoverageID)
    {
        $this->gibbonStaffCoverageID = $gibbonStaffCoverageID;

        return $this;
    }

    public function compose(Page $page)
    {
        $coverage = $this->staffCoverageGateway->getByID($this->gibbonStaffCoverageID);
        if (empty($coverage)) return;

        $requester = $this->userGateway->getByID($coverage['gibbonPersonIDStatus']);
        $substitute = !empty($coverage['gibbonPersonIDCoverage'])
            ? $this->userGateway->getByID($coverage['gibbonPersonIDCoverage'])
            : null;

        if ($coverage['status'] == 'Requested') {
            if ($coverage['requestType'] == 'Individual') {
                $params = [
                    'type' => __('Individual'),
                    'name' => Format::name($substitute['title'], $substitute['preferredName'], $substitute['surname'], 'Staff', false, true),
                ];
            } elseif ($coverage['requestType'] == 'Broadcast') {
                if ($notificationList = json_decode($coverage['notificationList'])) {
                    $notified = $this->userGateway->selectNotificationDetailsByPerson($notificationList)->fetchGroupedUnique();
                    $notified = Format::nameList($notified, 'Staff', false, true, ', ');
                }

                $params = [
                    'type' => $coverage['substituteTypes'] ?? __('Open'),
                    'name' => $notified ?? __('Unknown'),
                ];
            }
            $message = __('{type} request sent to {name}', $params);
        }

        // Coverage Request
        $page->writeFromTemplate('statusComment.twig.html', [
            'name'    => Format::name($requester['title'], $requester['preferredName'], $requester['surname'], 'Staff', false, true),
            'action'   => __('Requested Coverage'),
            'photo'   => $requester['image_240'],
            'date'    => Format::relativeTime($coverage['timestampStatus']),
            'status'  => $coverage['status'] == 'Requested' || $coverage['status'] == 'Cancelled' ? __($coverage['status']) : '',
            'tag'     => $this->getStatusColor($coverage['status']),
            'comment' => $coverage['notesStatus'],
            'message' => $message ?? '',
        ]);

        // Coverage Reply
        if ($substitute && ($coverage['status'] == 'Accepted' || $coverage['status'] == 'Declined')) {
            $page->writeFromTemplate('statusComment.twig.html', [
                'name'    => Format::name($substitute['title'], $substitute['preferredName'], $substitute['surname'], 'Staff', false, true),
                'action'  => __($coverage['status']),
                'photo'   => $substitute['image_240'],
                'date'    => Format::relativeTime($coverage['timestampCoverage']),
                'status'  => __($coverage['status']),
                'tag'     => $this->getStatusColor($coverage['status']),
                'comment' => $coverage['notesCoverage'],
            ]);
        }
    }

    protected function getStatusColor($status)
    {
        switch ($status) {
            case 'Accepted':
                return 'success';

            case 'Declined':
                return 'error';

            case 'Cancelled':
                return 'dull';

            default:
                return 'message';
        }
    }
}
