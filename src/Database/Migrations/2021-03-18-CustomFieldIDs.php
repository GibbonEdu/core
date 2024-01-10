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

use Gibbon\Domain\User\UserGateway;
use Gibbon\Database\Migrations\Migration;
use Gibbon\Domain\DataUpdater\PersonUpdateGateway;
use Gibbon\Domain\Students\ApplicationFormGateway;

/**
 * Custom Field ID migration - update all custom fields to use 4 digit ID numbers.
 */
class CustomFieldIDs extends Migration
{
    protected $userGateway;
    protected $personUpdateGateway;
    protected $applicationGateway;

    public function __construct(UserGateway $userGateway, PersonUpdateGateway $personUpdateGateway, ApplicationFormGateway $applicationGateway)
    {
        $this->userGateway = $userGateway;
        $this->personUpdateGateway = $personUpdateGateway;
        $this->applicationGateway = $applicationGateway;
    }   

    public function migrate()
    {
        $partialFail = false;

        $updateIDs = function ($fieldData) {
            if (empty($fieldData) || !is_array($fieldData)) return [];

            return array_reduce(array_keys($fieldData), function ($group, $key) use (&$fieldData) {
                $group[str_pad($key, 4, '0', STR_PAD_LEFT)] = $fieldData[$key];
                return $group;
            }, []);
        };

        // Update user custom fields
        $users = $this->userGateway->selectBy([], ['gibbonPersonID', 'fields']); 

        foreach ($users as $user) {
            if (empty($user['fields'])) continue;

            $fieldData = json_decode($user['fields'], true);
            $fieldData = $updateIDs($fieldData);
            
            $partialFail &= !$this->userGateway->update($user['gibbonPersonID'], [
                'fields' => !empty($fieldData) ? json_encode($fieldData) : '',
            ]);
        }

        // Update data updater custom fields
        $updates = $this->personUpdateGateway->selectBy([], ['gibbonPersonUpdateID', 'fields']); 

        foreach ($updates as $update) {
            if (empty($update['fields'])) continue;

            $fieldData = json_decode($update['fields'], true);
            $fieldData = $updateIDs($fieldData);

            $partialFail &= !$this->personUpdateGateway->update($update['gibbonPersonUpdateID'], [
                'fields' => !empty($fieldData) ? json_encode($fieldData) : '',
            ]);
        }

        // Update application form custom fields
        $applications = $this->applicationGateway->selectBy([], ['gibbonApplicationFormID', 'fields', 'parent1fields', 'parent2fields']); 

        foreach ($applications as $application) {
            if (empty($application['fields']) && empty($application['parent1fields']) && empty($application['parent2fields'])) continue;

            $fieldData = json_decode($application['fields'], true);
            $fieldData = $updateIDs($fieldData);

            $parent1fieldData = json_decode($application['parent1fields'], true);
            $parent1fieldData = $updateIDs($parent1fieldData);

            $parent2fieldData = json_decode($application['parent2fields'], true);
            $parent2fieldData = $updateIDs($parent2fieldData);

            $partialFail &= !$this->applicationGateway->update($application['gibbonApplicationFormID'], [
                'fields' => !empty($fieldData) ? json_encode($fieldData) : '',
                'parent1fields' => !empty($parent1fieldData) ? json_encode($parent1fieldData) : '',
                'parent2fields' => !empty($parent2fieldData) ? json_encode($parent2fieldData) : '',
            ]);
        }

        return !$partialFail;
    }
}
