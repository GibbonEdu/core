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

namespace Gibbon\Domain\Staff;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Staff Gateway
 *
 * @version v16
 * @since   v16
 */
class StaffGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStaff';

    private static $searchableColumns = ['preferredName', 'surname', 'username', 'gibbonStaff.jobTitle'];
    
    /**
     * Queries the list of users for the Manage Staff page.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryAllStaff(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.status', 'gibbonStaff.gibbonStaffID', 'gibbonStaff.initials', 'gibbonStaff.type', 'gibbonStaff.jobTitle'
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID');

        if (!$criteria->hasFilter('all')) {
            $query->where('gibbonPerson.status = "Full"');
        }

        $criteria->addFilterRules([
            'type' => function ($query, $type) {
                if ($type == 'other') {
                    return $query
                        ->where('gibbonStaff.type <> "Teaching"')
                        ->where('gibbonStaff.type <> "Support"');
                } else {
                    return $query
                        ->where('gibbonStaff.type = :type')
                        ->bindValue('type', ucfirst($type));
                }
            },
            
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonPerson.status = :status')
                    ->bindValue('status', ucfirst($status));
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectStaffByID($gibbonPersonID, $type = null)
    {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname, gibbonStaff.type
                FROM gibbonStaff 
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID)
                WHERE gibbonStaff.gibbonPersonID=:gibbonPersonID 
                AND gibbonPerson.status='Full'";

        if (!empty($type)) $sql .= " AND gibbonStaff.type='Teaching'";

        return $this->db()->select($sql, $data);
    }
}
