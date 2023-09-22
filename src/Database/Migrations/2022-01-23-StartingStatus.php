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

use Gibbon\Contracts\Database\Connection;
use Gibbon\Database\Migrations\Migration;
use Gibbon\Domain\User\UserStatusLogGateway;
use Gibbon\Domain\User\UserGateway;

/**
 * Starting Status - create an initial status log entry for any users with a start date.
 */
class StartingStatus extends Migration
{
    protected $db;
    protected $userGateway;
    protected $userStatusLogGateway;

    public function __construct(Connection $db, UserGateway $userGateway, UserStatusLogGateway $userStatusLogGateway)
    {
        $this->db = $db;
        $this->userGateway = $userGateway;
        $this->userStatusLogGateway = $userStatusLogGateway;
    }   

    public function migrate()
    {
        $partialFail = false;

        // Get all Full users
        $allUsers = $this->userGateway->selectUserNamesByStatus(['Full'])->fetchAll();

        foreach ($allUsers as $person) {
            // Skip non-full users who do not have a start date
            if ($person['status'] != 'Full') continue;
            if (empty($person['dateStart'])) continue;

            // Skip any users who already have a log for any reason
            $existing = $this->userStatusLogGateway->selectBy(['gibbonPersonID' => $person['gibbonPersonID']])->fetchAll();
            if (!empty($existing)) continue;

            // Create the status log
            $inserted = $this->userStatusLogGateway->insert(['gibbonPersonID' => $person['gibbonPersonID'], 'statusOld' => 'Full', 'statusNew' => 'Full', 'reason' => __('Initial status'), 'timestamp' => $person['dateStart'].' 00:00:00']);
            $partialFail &= !$inserted;
        }

        return !$partialFail;
    }
}
