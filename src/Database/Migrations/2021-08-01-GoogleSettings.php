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

/**
 * Google Settings migration - moves multiple settings into a single JSON setting.
 */
class GoogleSettings extends Migration
{
    /**
     * Database connection
     *
     * @var Connection
     */
    protected $db;

    /**
     * Setting Gateway
     *
     * @var SettingGateway
     */
    protected $settingGateway;

    public function __construct(Connection $db, SettingGateway $settingGateway)
    {
        $this->db = $db;
        $this->settingGateway = $settingGateway;
    }

    public function migrate()
    {
        $partialFail = false;

        $googleOAuth = $this->settingGateway->getSettingByScope('System', 'googleOAuth');
        if ($googleOAuth == 'Migrated') {
            return true;
        }

        $data = [
            'enabled'      => $googleOAuth,
            'clientName'   => $this->settingGateway->getSettingByScope('System', 'googleClientName'),
            'clientID'     => $this->settingGateway->getSettingByScope('System', 'googleClientID'),
            'clientSecret' => $this->settingGateway->getSettingByScope('System', 'googleClientSecret'),
            'developerKey' => $this->settingGateway->getSettingByScope('System', 'googleDeveloperKey'),
        ];

        $updated = $this->settingGateway->updateSettingByScope('System Admin', 'ssoGoogle', json_encode($data));
        $partialFail = !$updated;

        if (!$partialFail) {
            $this->settingGateway->updateSettingByScope('System', 'googleOAuth', 'Migrated');
            $this->settingGateway->deleteWhere(['scope' => 'System', 'name' => 'googleClientName']);
            $this->settingGateway->deleteWhere(['scope' => 'System', 'name' => 'googleClientID']);
            $this->settingGateway->deleteWhere(['scope' => 'System', 'name' => 'googleClientSecret']);
            $this->settingGateway->deleteWhere(['scope' => 'System', 'name' => 'googleRedirectUri']);
            $this->settingGateway->deleteWhere(['scope' => 'System', 'name' => 'googleDeveloperKey']);
        }

        return !$partialFail;
    }
}
