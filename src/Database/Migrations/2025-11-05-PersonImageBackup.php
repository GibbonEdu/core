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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\PersonPhotoGateway;

/**
 * Person Image Backup - add the existing person images to the gibbonPersonPhoto table.
 */
class PersonImageBackup extends Migration
{
    protected $session;
    protected $userGateway;
    protected $personPhotoGateway;

    public function __construct(Session $session, UserGateway $userGateway, PersonPhotoGateway $personPhotoGateway)
    {
        $this->session = $session;
        $this->userGateway = $userGateway;
        $this->personPhotoGateway = $personPhotoGateway;
    }

    public function migrate()
    {
        $partialFail = false;

        // Get all Full users
        $criteria = $this->userGateway->newQueryCriteria()
            ->filterBy('status', 'Full');

        $allUsers = $this->userGateway->queryAllUsers($criteria);
        
        foreach ($allUsers as $person) {
            
            // Skip users who are not full
            if (empty($person['image_240'])) continue;

            // Skip any users who already have a backup image 
            $existing = $this->personPhotoGateway->selectBy(['gibbonPersonID' => $person['gibbonPersonID'], 'gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID')])->fetchAll();
            if (!empty($existing)) continue;

            // Add the current image to the image backup table for the current school year
            $inserted = $this->personPhotoGateway->insert(['gibbonPersonID' => $person['gibbonPersonID'], 'gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'personImage' => $person['image_240'] ?? '', 'gibbonPersonIDCreated' => $this->session->get('gibbonPersonID')]);
            $partialFail &= !$inserted;
        }

        return !$partialFail;
    }
}