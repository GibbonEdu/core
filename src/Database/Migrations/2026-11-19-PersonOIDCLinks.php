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
 * Person OIDC Links - explicit OIDC account bindings and provider settings.
 */
class PersonOIDCLinks extends Migration
{
    protected Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function migrate()
    {
        $pdo = $this->db->getConnection();

        $pdo->exec("CREATE TABLE IF NOT EXISTS `gibbonPersonOIDC` (
            `gibbonPersonOIDCID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
            `gibbonPersonID` INT(10) UNSIGNED ZEROFILL NOT NULL,
            `issuer` VARCHAR(255) NOT NULL DEFAULT '',
            `subject` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL DEFAULT '',
            `displayName` VARCHAR(255) NOT NULL DEFAULT '',
            `timestampLinked` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`gibbonPersonOIDCID`),
            UNIQUE KEY `issuerSubject` (`issuer`, `subject`),
            KEY `gibbonPersonID` (`gibbonPersonID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("INSERT INTO `gibbonSetting` (`scope`, `name`, `nameDisplay`, `description`, `value`)
            SELECT 'System Admin', 'ssoOIDC', 'OpenID Connect Provider', '', '{\"enabled\":\"N\",\"accountMatching\":\"manual\",\"requireVerifiedEmail\":\"Y\",\"matchAttribute\":\"preferred_username\",\"fallbackToManual\":\"N\",\"jwksUri\":\"\",\"discoveryUrl\":\"\",\"autoDiscovery\":\"Y\",\"matchClaims\":\"email=email\"}'
            FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1 FROM `gibbonSetting` WHERE `scope`='System Admin' AND `name`='ssoOIDC'
            )");

        return true;
    }
}
