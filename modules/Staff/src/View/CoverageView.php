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

namespace Gibbon\Module\Staff\View;

use Gibbon\View\Page;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;


/**
 * CoverageView
 *
 * A view composer class: receives a coverage ID and displays the status information.
 *
 * @version v18
 * @since   v18
 */
class CoverageView
{
    protected $gibbonStaffCoverageID;
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
                    'type' => !empty($substitute['surname']) ? __('Individual') : __('Open'),
                    'name' => !empty($substitute['surname'])
                        ? Format::name($substitute['title'], $substitute['preferredName'], $substitute['surname'], 'Staff', false, true)
                        : __('Pending'),
                ];
            } elseif ($coverage['requestType'] == 'Broadcast') {
                if ($notificationList = json_decode($coverage['notificationList'])) {
                    $notified = $this->userGateway->selectNotificationDetailsByPerson($notificationList)->fetchGroupedUnique();
                    $notified = Format::nameList($notified, 'Staff', false, true, ', ');
                }

                $params = [
                    'type' => $coverage['substituteTypes'] ?? __('Open'),
                    'name' => $notified ?? __('Pending'),
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
            'status'  => $coverage['status'] != 'Accepted' && $coverage['status'] != 'Declined' ? __($coverage['status']) : '',
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

        // Attachment
        if (!empty($coverage['attachmentType'])) {
            $page->writeFromTemplate('statusComment.twig.html', [
                'name'       => __('Attachment'),
                'icon'       => 'internalAssessment',
                'tag'        => 'dull',
                'status'     => __($coverage['attachmentType']),
                'attachment' => $coverage['attachmentType'] != 'Text' ? Format::link($coverage['attachmentContent']) : '',
                'html'       => $coverage['attachmentType'] == 'Text' ? $coverage['attachmentContent'] : '',
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
