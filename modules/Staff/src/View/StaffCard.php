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

use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\View\Page;

/**
 * StaffCard
 * 
 * A view composer class for the staff card template: set a gibbonPersonID and display the staff details and links to their info.
 *
 * @version v18
 * @since   v18
 */
class StaffCard
{
    protected $session;
    protected $db;
    protected $staffGateway;
    protected $formGroupGateway;
    protected $gibbonPersonID;
    protected $status;
    protected $tag;

    public function __construct(Session $session, Connection $db, StaffGateway $staffGateway, FormGroupGateway $formGroupGateway)
    {
        $this->session = $session;
        $this->db = $db;
        $this->staffGateway = $staffGateway;
        $this->formGroupGateway = $formGroupGateway;
    }

    public function setPerson($gibbonPersonID)
    {
        $this->gibbonPersonID = $gibbonPersonID;

        return $this;
    }

    public function setStatus($status, $tag = '')
    {
        $this->status = $status;
        $this->tag = $tag;

        return $this;
    }

    public function compose(Page $page)
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $page->writeFromTemplate('staffCard.twig.html', [
            'staff'             => $this->staffGateway->selectStaffByID($this->gibbonPersonID ?? '')->fetch(),
            'formGroup'         => $this->formGroupGateway->selectFormGroupsByTutor($this->gibbonPersonID ?? '')->fetch(),
            'canViewProfile'    => isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php'),
            'canViewAbsences'   => isActionAccessible($guid, $connection2, '/modules/Staff/absences_view_byPerson.php', 'View Absences_any'),
            'canViewTimetable'  => isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php'),
            'canViewFormGroups' => isActionAccessible($guid, $connection2, '/modules/Form Groups/formGroups.php'),
            'status'            => $this->status,
            'tag'               => $this->tag,
        ]);
    }
}
