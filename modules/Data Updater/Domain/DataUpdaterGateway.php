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
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\DataUpdater\Domain;

/**
 * Data Updater Gateway
 *
 * @version v16
 * @since   v16
 */
class DataUpdaterGateway
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Gets a list of users this person can update data for, checking by family. Always returns the user themself even if not in a family.
     * 
     * @param string $gibbonPersonID
     * @return \PDOStatement
     */
    public function selectUpdatableUsersByPerson($gibbonPersonID)
    {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = "
        (SELECT GROUP_CONCAT(gibbonFamily.gibbonFamilyID ORDER BY gibbonFamily.name SEPARATOR ',') as gibbonFamilyID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.image_240, gibbonPerson.gibbonPersonID, 0 as sequenceNumber
            FROM gibbonPerson 
            LEFT JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID)
            LEFT JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
            WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full' GROUP BY gibbonPerson.gibbonPersonID)
        UNION ALL 
        (SELECT gibbonFamilyAdult.gibbonFamilyID, child.surname, child.preferredName, child.image_240, child.gibbonPersonID, 1 as sequenceNumber
            FROM gibbonFamilyAdult 
            JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) 
            JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
            JOIN gibbonPerson as child ON (gibbonFamilyChild.gibbonPersonID=child.gibbonPersonID) 
            WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID 
            AND gibbonFamilyAdult.childDataAccess='Y' AND child.status='Full') 
        UNION ALL 
        (SELECT gibbonFamily.gibbonFamilyID, adult.surname, adult.preferredName, adult.image_240, adult.gibbonPersonID, 2 as sequenceNumber
            FROM gibbonFamilyAdult 
            JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
            JOIN gibbonFamilyAdult as familyAdult ON (familyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID AND familyAdult.gibbonPersonID<>:gibbonPersonID)
            JOIN gibbonPerson as adult ON (familyAdult.gibbonPersonID=adult.gibbonPersonID) 
            WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND adult.status='Full')
        ORDER BY sequenceNumber, surname, preferredName
        ";

        return $this->db->executeQuery($data, $sql);
    }

    /**
     * Gets a list of data updates and the last updated timestamp for a given user.
     * 
     * @param string $gibbonPersonID
     * @return \PDOStatement
     */
    public function selectDataUpdatesByPerson($gibbonPersonID)
    {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = "
        (SELECT 'Personal' as type, gibbonPerson.gibbonPersonID as id, 'gibbonPersonID' as idType, IFNULL(timestamp, 0) as lastUpdated, '' as name
            FROM gibbonPerson 
            LEFT JOIN gibbonPersonUpdate ON (gibbonPersonUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID) 
            WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY timestamp DESC LIMIT 1)
        UNION ALL
        (SELECT 'Medical' as type, gibbonPerson.gibbonPersonID as id, 'gibbonPersonID' as idType, IFNULL(timestamp, 0) as lastUpdated, '' as name
            FROM gibbonPerson 
            JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll))
            LEFT JOIN gibbonPersonMedicalUpdate ON (gibbonPersonMedicalUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID) 
            WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonRole.category='Student'
            GROUP BY gibbonPerson.gibbonPersonID ORDER BY timestamp DESC LIMIT 1)
        UNION ALL
        (SELECT 'Finance' as type, gibbonFinanceInvoicee.gibbonFinanceInvoiceeID as id, 'gibbonFinanceInvoiceeID' as idType, IFNULL(timestamp, 0) as lastUpdated, '' as name
            FROM gibbonPerson 
            JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID)
            LEFT JOIN gibbonFinanceInvoiceeUpdate ON (gibbonFinanceInvoiceeUpdate.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) 
            WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID 
            GROUP BY gibbonPerson.gibbonPersonID ORDER BY timestamp DESC LIMIT 1)    
        UNION ALL
        (SELECT 'Family' as type, gibbonFamilyAdult.gibbonFamilyID as id, 'gibbonFamilyID' as idType, IFNULL(timestamp, 0) as lastUpdated, gibbonFamily.name
            FROM gibbonFamilyAdult 
            JOIN gibbonFamily ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
            LEFT JOIN gibbonFamilyUpdate ON (gibbonFamilyUpdate.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) 
            WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID GROUP BY gibbonFamily.gibbonFamilyID ORDER BY timestamp DESC)
            UNION ALL
        (SELECT 'Family' as type, gibbonFamilyChild.gibbonFamilyID as id, 'gibbonFamilyID' as idType, IFNULL(timestamp, 0) as lastUpdated, gibbonFamily.name
            FROM gibbonFamilyChild 
            JOIN gibbonFamily ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID)
            LEFT JOIN gibbonFamilyUpdate ON (gibbonFamilyUpdate.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) 
            WHERE gibbonFamilyChild.gibbonPersonID=:gibbonPersonID GROUP BY gibbonFamily.gibbonFamilyID ORDER BY timestamp DESC)
        ";

        return $this->db->executeQuery($data, $sql);
    }

    public function countAllRequiredUpdatesByPerson($gibbonPersonID)
    {
        $updatablePeople = $this->selectUpdatableUsersByPerson($gibbonPersonID);

        if ($updatablePeople->rowCount() == 0) return 0;

        $cutoffDate = getSettingByScope($this->db->getConnection(), 'Data Updater', 'cutoffDate');
        $requiredUpdatesByType = getSettingByScope($this->db->getConnection(), 'Data Updater', 'requiredUpdatesByType');
        $requiredUpdatesByType = explode(',', $requiredUpdatesByType);

        if (empty($requiredUpdatesByType) || empty($cutoffDate)) return 0;
        
        $count = 0;

        // Loop over each updatable person to look for required updates
        foreach ($updatablePeople as $person) {
            $dataUpdatesByType = $this->selectDataUpdatesByPerson($person['gibbonPersonID'])->fetchAll(\PDO::FETCH_GROUP);

            foreach ($requiredUpdatesByType as $type) {
                // Skip data update types not applicable to this user
                if (empty($dataUpdatesByType[$type])) continue;

                // Loop over each type of data update and check the last update
                foreach ($dataUpdatesByType[$type] as $dataUpdate) {
                    if (empty($dataUpdate['lastUpdated']) || $dataUpdate['lastUpdated'] < $cutoffDate) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }
}
