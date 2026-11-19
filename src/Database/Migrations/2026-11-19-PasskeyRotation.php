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

/**
 * Passkey Rotation - add the deferred-rotation marker and rotation setting.
 */
class PasskeyRotation extends Migration
{
    protected Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function migrate()
    {
        $pdo = $this->db->getConnection();

        try {
            $pdo->exec("ALTER TABLE `gibbonPersonPasskey` ADD `rotationDeferredUntil` TIMESTAMP NULL DEFAULT NULL AFTER `lastUsedAt`");
        } catch (\Throwable $e) {
            // The column already exists on upgraded installations.
        }

        $pdo->exec("INSERT INTO `gibbonSetting` (`scope`, `name`, `nameDisplay`, `description`, `value`)
            SELECT 'System', 'passkeyRotationDays', 'Passkey Rotation (days)',
                   'Days before a passkey is rotated at the next passkey login. Set to 0 to disable.', '180'
            FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1 FROM `gibbonSetting` WHERE `scope`='System' AND `name`='passkeyRotationDays'
            )");

        return true;
    }
}
