<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http: //www.gnu.org/licenses/>.
*/

use Gibbon\Contracts\Database\Connection;
use Gibbon\Database\Migrations\Migration;

/**
 * Remove Citizenship Fields Migration - remove the citizenship, id card, visa and residency fields no longer needed.
 */
class RemoveCitizenshipFields extends Migration
{
    protected $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }   

    public function migrate()
    {
        $partialFail = false;

        // gibbonPerson
        $fieldPresent = $this->db->select("SHOW COLUMNS FROM `gibbonPerson` LIKE 'citizenship1'");
        if (!empty($fieldPresent)) {
            $sql = "ALTER TABLE `gibbonPerson` DROP `citizenship1`, DROP `citizenship1Passport`, DROP `citizenship1PassportExpiry`, DROP `citizenship1PassportScan`, DROP `citizenship2`, DROP `citizenship2Passport`, DROP `citizenship2PassportExpiry`, DROP `nationalIDCardNumber`, DROP `nationalIDCardScan`, DROP `residencyStatus`, DROP `visaExpiryDate`;";

            $success = $this->db->statement($sql);
            $partialFail &= !$success;
        }

        // gibbonPersonUpdate
        $fieldPresent = $this->db->select("SHOW COLUMNS FROM `gibbonPersonUpdate` LIKE 'citizenship1'");
        if (!empty($fieldPresent)) {
            $sql = "ALTER TABLE `gibbonPersonUpdate` DROP `citizenship1`, DROP `citizenship1Passport`, DROP `citizenship1PassportExpiry`, DROP `citizenship2`, DROP `citizenship2Passport`, DROP `citizenship2PassportExpiry`, DROP `nationalIDCardCountry`, DROP `nationalIDCardNumber`, DROP `residencyStatus`, DROP `visaExpiryDate`;";

            $success = $this->db->statement($sql);
            $partialFail &= !$success;
        }


        // gibbonApplicationForm
        $fieldPresent = $this->db->select("SHOW COLUMNS FROM `gibbonApplicationForm` LIKE 'citizenship1'");
        if (!empty($fieldPresent)) {
            $sql = "ALTER TABLE `gibbonApplicationForm` DROP `citizenship1`, DROP `citizenship1Passport`, DROP `citizenship1PassportExpiry`, DROP `nationalIDCardNumber`, DROP `residencyStatus`, DROP `visaExpiryDate`, DROP `parent1citizenship1`, DROP `parent1nationalIDCardNumber`, DROP `parent1residencyStatus`, DROP `parent1visaExpiryDate`, DROP `parent2citizenship1`, DROP `parent2nationalIDCardNumber`, DROP `parent2residencyStatus`, DROP `parent2visaExpiryDate`;";

            $success = $this->db->statement($sql);
            $partialFail &= !$success;
        }

        // gibbonStaffApplicationForm
        $fieldPresent = $this->db->select("SHOW COLUMNS FROM `gibbonStaffApplicationForm` LIKE 'citizenship1'");
        if (!empty($fieldPresent)) {
            $sql = "ALTER TABLE `gibbonStaffApplicationForm` DROP `citizenship1`, DROP `citizenship1Passport`, DROP `nationalIDCardNumber`, DROP `residencyStatus`, DROP `visaExpiryDate`;";

            $success = $this->db->statement($sql);
            $partialFail &= !$success;
        }

        return !$partialFail;
    }
}
