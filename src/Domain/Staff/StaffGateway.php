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
    private static $primaryKey = 'gibbonStaffID';

    private static $searchableColumns = ['preferredName', 'surname', 'username', 'gibbonStaff.jobTitle'];

    /**
     * Queries the list of users for the Manage Staff page.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryAllStaff(QueryCriteria $criteria, $gibbonSchoolYearID = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.status', 'gibbonPerson.username', 'gibbonPerson.image_240', 'gibbonPerson.email', 'gibbonPerson.phone1', 'gibbonPerson.phone1Type', 'gibbonPerson.phone1CountryCode', 'gibbonPerson.phone2', 'gibbonPerson.phone2Type', 'gibbonPerson.phone2CountryCode',
                'gibbonStaff.gibbonStaffID', 'gibbonStaff.initials', 'gibbonStaff.type', 'gibbonStaff.jobTitle', 'gibbonStaff.biography', 'gibbonStaff.qualifications', 'gibbonStaff.countryOfOrigin','gibbonStaff.biographicalGrouping', 'gibbonStaff.biographicalGroupingPriority'
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID');

        if (!$criteria->hasFilter('all')) {
            $query->where('gibbonPerson.status = "Full"');
        }

        if (!empty($gibbonSchoolYearID)) {
            $biographicalGroupingOrder = $this->db()->selectOne("SELECT value FROM gibbonSetting WHERE scope='Staff' AND name='biographicalGroupingOrder'");

            $query->cols([
                'gibbonRollGroup.name AS rollGroupName',
                "GROUP_CONCAT(DISTINCT gibbonSpace.name ORDER BY gibbonSpace.name SEPARATOR '<br/>') as facility",
                "GROUP_CONCAT(DISTINCT gibbonSpace.phoneInternal ORDER BY gibbonSpace.name SEPARATOR '<br/>') as extension",
                "GROUP_CONCAT(DISTINCT gibbonDepartment.name ORDER BY gibbonDepartment.name SEPARATOR '<br/>') as department",
                "(CASE WHEN FIND_IN_SET(gibbonStaff.biographicalGrouping, :biographicalGroupingOrder) > 0 THEN FIND_IN_SET(gibbonStaff.biographicalGrouping, :biographicalGroupingOrder) WHEN gibbonStaff.biographicalGrouping <> '' THEN 998 ELSE 999 END) AS biographicalGroupingOrder",
            ])
            ->leftJoin('gibbonRollGroup', '((gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID)')
            ->leftJoin('gibbonSpacePerson', 'gibbonSpacePerson.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonSpace', '(gibbonSpace.gibbonSpaceID=gibbonSpacePerson.gibbonSpaceID OR gibbonSpace.gibbonSpaceID=gibbonRollGroup.gibbonSpaceID)')
            ->leftJoin('gibbonDepartmentStaff', 'gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonDepartment', 'gibbonDepartment.gibbonDepartmentID=gibbonDepartmentStaff.gibbonDepartmentID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->bindValue('biographicalGroupingOrder', !empty($biographicalGroupingOrder) ? $biographicalGroupingOrder : null)
            ->groupBy(['gibbonPerson.gibbonPersonID']);
        }

        $criteria->addFilterRules([
            'type' => function ($query, $type) {
                return $query
                    ->where('gibbonStaff.type = :type')
                    ->bindValue('type', ucfirst($type));
            },

            'biographicalGrouping' => function ($query, $grouping) {
                return $query
                    ->where('gibbonStaff.biographicalGrouping = :grouping')
                    ->bindValue('grouping', $grouping);
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
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.image_240, gibbonStaff.type, gibbonStaff.jobTitle
                FROM gibbonPerson
                LEFT JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID)
                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID
                AND gibbonPerson.status='Full'";

        if (!empty($type)) $sql .= " AND gibbonStaff.type='Teaching'";

        return $this->db()->select($sql, $data);
    }

    public function selectStaffByStaffID($gibbonStaffID) {
        $data = array('gibbonStaffID' => $gibbonStaffID);
        $sql = 'SELECT gibbonStaff.*, title, surname, preferredName, initials, dateStart, dateEnd FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffID=:gibbonStaffID';

        return $this->db()->select($sql, $data);
    }
}
