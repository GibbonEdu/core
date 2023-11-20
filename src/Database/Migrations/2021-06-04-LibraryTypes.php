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
use Gibbon\Domain\Library\LibraryTypeGateway;

/**
 * Library Types migration - adds and updates library type fields.
 */
class LibraryTypes extends Migration
{
    protected $db;
    protected $libraryTypeGateway;

    public function __construct(Connection $db, LibraryTypeGateway $libraryTypeGateway)
    {
        $this->db = $db;
        $this->libraryTypeGateway = $libraryTypeGateway;
    }   

    public function migrate()
    {
        $partialFail = false;

        // Add a new Telephone type, if it doesn't exist
        $existingType = $this->libraryTypeGateway->selectBy(['name' => 'Telephone'])->fetch();
        if (empty($existingType)) {
            $sql = "INSERT INTO `gibbonLibraryType` (`name`, `active`, `fields`) VALUES ('Telephone', 'Y', '[{\"name\":\"Serial Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Model Name\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Model ID\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Telephone Number\",\"description\":\"External telephone number\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Telephone Extension\",\"description\":\"Internal telephone extension\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Accessories\",\"description\":\"Any chargers, remotes controls, etc?\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Warranty Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Warranty Expiry\",\"description\":\"Format: dd\\/mm\\/yyyy.\",\"type\":\"Date\",\"options\":\"\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Wireless MAC Address\",\"description\":\"\",\"type\":\"Text\",\"options\":\"17\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Wired MAC Address\",\"description\":\"\",\"type\":\"Text\",\"options\":\"17\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Repair Log\\/Notes\",\"description\":\"\",\"type\":\"Textarea\",\"options\":\"10\",\"default\":\"\",\"required\":\"N\"}]');";
            $success = $this->db->statement($sql);
            $partialFail &= !$success;
        }
      
        // Update Computer type, if it exists
        $existingType = $this->libraryTypeGateway->selectBy(['name' => 'Computer'])->fetch();
        if (!empty($existingType)) {
            $sql = "UPDATE gibbonLibraryType SET fields = '[{\"name\":\"Form Factor\",\"description\":\"\",\"type\":\"Select\",\"options\":\"Desktop, Laptop, Tablet, Phone, Set-Top Box, Rack-Mounted Server, Other\",\"default\":\"Laptop\",\"required\":\"Y\"},{\"name\":\"Operating System\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Serial Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Model Name\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Model ID\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"CPU Type\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"CPU Speed\",\"description\":\"In GHz.\",\"type\":\"Text\",\"options\":\"6\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Memory\",\"description\":\"Total RAM, in GB.\",\"type\":\"Text\",\"options\":\"6\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Storage Type\",\"description\":\"Primary internal storage type.\",\"type\":\"Select\",\"options\":\",HDD, SSD, Hybrid, Other\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Storage\",\"description\":\"Total HDD/SDD capacity, in GB.\",\"type\":\"Text\",\"options\":\"6\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Wireless MAC Address\",\"description\":\"\",\"type\":\"Text\",\"options\":\"17\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Wired MAC Address\",\"description\":\"\",\"type\":\"Text\",\"options\":\"17\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Accessories\",\"description\":\"Any chargers, display dongles, remotes etc?\",\"type\":\"Text\",\"options\":\"255\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Warranty Number\",\"description\":\"\",\"type\":\"Text\",\"options\":\"50\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Warranty Expiry\",\"description\":\"Format: dd/mm/yyyy.\",\"type\":\"Date\",\"options\":\"\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Last Reinstall Date\",\"description\":\"Format: dd/mm/yyyy.\",\"type\":\"Date\",\"options\":\"\",\"default\":\"\",\"required\":\"N\"},{\"name\":\"Repair Log/Notes\",\"description\":\"\",\"type\":\"Textarea\",\"options\":\"10\",\"default\":\"\",\"required\":\"N\"}]' WHERE name = 'Computer';";

            $success = $this->db->statement($sql);
            $partialFail &= !$success;
        }

        return !$partialFail;
    }
}
