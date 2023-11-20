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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Database\Migrations\Migration;
use Gibbon\Domain\Activities\ActivityTypeGateway;

/**
 * Activity Types migration - turn csv value of types into individual table rows.
 */
class ActivityTypes extends Migration
{
    protected $db;
    protected $settingGateway;
    protected $activityTypeGateway;

    public function __construct(Connection $db, SettingGateway $settingGateway, ActivityTypeGateway $activityTypeGateway)
    {
        $this->db = $db;
        $this->settingGateway = $settingGateway;
        $this->activityTypeGateway = $activityTypeGateway;
    }   

    public function migrate()
    {
        $partialFail = false;

        $activityTypes = $this->settingGateway->getSettingByScope('Activities', 'activityTypes');
        
        // Activity Types - CSV to Table Migration
        if (!empty($activityTypes)) {
            $activityTypes = array_map('trim', explode(',', $activityTypes));
            $access = $this->settingGateway->getSettingByScope('Activities', 'access');
            $enrolmentType = $this->settingGateway->getSettingByScope('Activities', 'enrolmentType');
            $backupChoice = $this->settingGateway->getSettingByScope('Activities', 'backupChoice');

            foreach ($activityTypes as $type) {
                $inserted = $this->activityTypeGateway->insert(['name' => $type, 'access' => $access, 'enrolmentType' => $enrolmentType, 'backupChoice' => $backupChoice]);
                $partialFail &= !$inserted;
            }

            if (!$partialFail) {
                $this->settingGateway->deleteWhere(['scope' => 'Activities', 'name' => 'activityTypes']);
            }
        }

        return !$partialFail;
    }
}
