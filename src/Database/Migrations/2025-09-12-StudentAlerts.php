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

use Gibbon\Contracts\Services\Session;
use Gibbon\Database\Migrations\Migration;
use Gibbon\Domain\StudentAlerts\AlertGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\UI\Components\Alert;

/**
 * Student Alerts - populate the database with initial automatic student alerts.
 */
class StudentAlerts extends Migration
{
    protected $session;
    protected $studentGateway;
    protected $alertGateway;
    protected $alert;

    public function __construct(Session $session, StudentGateway $studentGateway, AlertGateway $alertGateway, Alert $alert)
    {
        $this->session = $session;
        $this->studentGateway = $studentGateway;
        $this->alertGateway = $alertGateway;
        $this->alert = $alert;
    }   

    public function migrate()
    {
        $partialFail = false;

        // Get all Full users
        $criteria = $this->studentGateway->newQueryCriteria();
        $allStudents = $this->studentGateway->queryStudentEnrolmentBySchoolYear($criteria, $this->session->get('gibbonSchoolYearID'));

        foreach ($allStudents as $person) {
            // Skip any users who already have alerts for any reason
            $existing = $this->alertGateway->selectBy(['gibbonPersonID' => $person['gibbonPersonID']])->fetchAll();
            if (!empty($existing)) continue;

            // Calculate new automatic alerts
            $alerts = $this->alert->calculateAlerts($person['gibbonPersonID']);

            foreach ($alerts as $alert) {
                $inserted = $this->alertGateway->insert($alert + [
                    'gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'),
                    'gibbonPersonID'     => $person['gibbonPersonID'],
                ]);

                $partialFail &= !$inserted;
            } 
        }

        return !$partialFail;
    }
}
