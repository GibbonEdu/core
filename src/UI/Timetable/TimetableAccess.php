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

namespace Gibbon\UI\Timetable;

use Gibbon\Support\Facades\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Students\StudentGateway;

/**
 * Timetable UI: TimetableAccess
 * 
 * Controls access to the timetable via actions and permissions, based on the current session.
 *
 * @version  v29
 * @since    v29
 */
class TimetableAccess
{
    protected $session;
    protected $userGateway;
    protected $studentGateway;
    protected $staffGateway;

    public function __construct(Session $session, UserGateway $userGateway, StudentGateway $studentGateway, StaffGateway $staffGateway)
    {
        $this->session = $session;
        $this->userGateway = $userGateway;
        $this->studentGateway = $studentGateway;
        $this->staffGateway = $staffGateway;
    }

    public function checkAccess($context)
    {
        $action = Access::get('Timetable', 'tt');

        if ($action->highest('View Timetable by Person_allYears')) return true;
        
        if ($this->session->get('gibbonSchoolYearIDCurrent') != $context->get('gibbonSchoolYearID')) return false;

        if ($action->highest('View Timetable by Person')) return true;

        if ($action->highest('View Timetable by Person_my')) {
            return $this->session->get('gibbonPersonID') == $context->get('gibbonPersonID');
        }

        if ($action->highest('View Timetable by Person_myChildren')) {
            $children = $this->studentGateway->selectActiveStudentsByFamilyAdult($context->get('gibbonSchoolYearID'), $this->session->get('gibbonPersonID'))->fetchGroupedUnique();

            return !empty($children[$context->get('gibbonPersonID')]);
        }

        return false;
    }

    public function getExportablePersonID($gibbonPersonID): string
    {
        if ($gibbonPersonID == '') {
            return '';
        }

        $action = Access::get('Timetable', 'tt');
        if (!$action->allows()) {
            return '';
        }

        $sessionPersonID = $this->session->get('gibbonPersonID');
        $gibbonSchoolYearID = $this->session->get('gibbonSchoolYearID');

        if ($action->highest('View Timetable by Person_allYears')) {
            $person = $this->userGateway->getByID($gibbonPersonID, ['gibbonPersonID']);
            return $person['gibbonPersonID'] ?? '';
        }

        if ($gibbonPersonID == $sessionPersonID && !$action->highest('View Timetable by Person_myChildren')) {
            if ($this->session->get('gibbonRoleIDCurrentCategory') != 'Parent') {
                return $sessionPersonID;
            }

            $student = $this->studentGateway->selectActiveStudentByPerson($gibbonSchoolYearID, $sessionPersonID)->fetch();
            if (!empty($student)) {
                return $student['gibbonPersonID'];
            }

            $staff = $this->staffGateway->selectStaffByID($sessionPersonID)->fetch();
            if (!empty($staff['type'])) {
                return $staff['gibbonPersonID'];
            }

            return '';
        }

        if ($action->allows('View Timetable by Person_myChildren')) {
            $children = $this->studentGateway->selectActiveStudentsByFamilyAdult($gibbonSchoolYearID, $sessionPersonID)->fetchAll();
            foreach ($children as $child) {
                if ($child['gibbonPersonID'] == $gibbonPersonID) {
                    return $child['gibbonPersonID'];
                }
            }
        }

        return '';
    }

    public function getPreferences()
    {
        if (!$this->session->has('gibbonPersonID')) return [];
        
        return $this->userGateway->getUserPreferences($this->session->get('gibbonPersonID'));
    }
}
