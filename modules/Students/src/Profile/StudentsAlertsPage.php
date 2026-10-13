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

namespace Gibbon\Module\Students\Profile;

use Gibbon\Contracts\Services\Session;
use Gibbon\Module\StudentAlerts\Tables\ManageAlerts;
use Gibbon\Services\Format;
use Gibbon\Support\Facades\Access;

/**
 * StudentsAlertsPage
 *
 * Student profile subpage for the alerts of one student.
 */
class StudentsAlertsPage extends ProfilePage
{
    private ManageAlerts $manageAlerts;

    public function __construct(Session $session, ManageAlerts $manageAlerts)
    {
        parent::__construct($session);
        $this->manageAlerts = $manageAlerts;
    }

    /**
     * Same permission as Manage Student Alerts.
     */
    public function checkAccess(): bool
    {
        return Access::allows('Student Alerts', 'studentAlerts_manage');
    }

    public function getPageName(): string
    {
        return __('Student Alerts');
    }

    public function getOutput(): string
    {
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('You have not specified one or more required parameters.'));
        }

        $table = $this->manageAlerts->create($this->gibbonSchoolYearID, $this->gibbonPersonID, includeFilters: false);

        return $table->getOutput();
    }
}
