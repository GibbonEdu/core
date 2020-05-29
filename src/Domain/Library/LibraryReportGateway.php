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

namespace Gibbon\Domain\Library;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Tables\DataTable;

/**
 * LibraryReportGateway
 *
 * @version v20
 * @since   v20
 */
class LibraryReportGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonLibraryItem';
    private static $primaryKey = 'gibbonLibraryItemID';

    public function queryStudentReportData(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonLibraryItem')
            ->cols([
                'gibbonLibraryItem.name',
                'gibbonLibraryItem.producer',
                'gibbonLibraryItem.id',
                'gibbonLibraryItem.imageType',
                'gibbonLibraryItem.imageLocation',
                'gibbonLibraryItem.fields',
                'gibbonLibraryType.fields as typeFields',
                'gibbonLibraryItem.locationDetail',
                'gibbonSpace.name as spaceName',
                'gibbonLibraryItemEvent.timestampOut',
                'gibbonLibraryItemEvent.returnExpected',
                'gibbonLibraryItemEvent.status',
                "IF(gibbonLibraryItemEvent.returnExpected <= CURRENT_TIMESTAMP,'Y','N') as pastDue"
            ])
            ->innerJoin('gibbonLibraryType', 'gibbonLibraryType.gibbonLibraryTypeID = gibbonLibraryItem.gibbonLibraryTypeID')
            ->innerJoin('gibbonLibraryItemEvent', 'gibbonLibraryItemEvent.gibbonLibraryItemID = gibbonLibraryItem.gibbonLibraryItemID')
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID = gibbonLibraryItem.gibbonSpaceID');

        $criteria->addFilterRules([
            'gibbonPersonID' => function ($query, $personid) {
                return $query
                    ->where('gibbonLibraryItemEvent.gibbonPersonIDStatusResponsible = :personid')
                    ->bindValue('personid', $personid);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryCatalogSummary(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonLibraryItem')
            ->cols(['gibbonLibraryItem.*', 'gibbonLibraryType.name as type', 'gibbonSpace.name as space', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->leftJoin('gibbonLibraryType', 'gibbonLibraryType.gibbonLibraryTypeID=gibbonLibraryItem.gibbonLibraryTypeID')
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=gibbonLibraryItem.gibbonSpaceID')
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonLibraryItem.gibbonPersonIDOwnership');

        $criteria->addFilterRules([
            'id' => function ($query, $gibbonLibraryTypeID) {
                return $query
                    ->where('gibbonLibraryItem.gibbonLibraryTypeID = :gibbonLibraryTypeID')
                    ->bindValue('gibbonLibraryTypeID', $gibbonLibraryTypeID);
            },
            'ownershipType' => function ($query, $ownershipType) {
                return $query
                    ->where('gibbonLibraryItem.ownershipType = :ownershipType')
                    ->bindValue('ownershipType', $ownershipType);
            },
            'space' => function ($query, $gibbonSpaceID) {
                return $query
                    ->where('gibbonLibraryItem.gibbonSpaceID = :gibbonSpaceID')
                    ->bindValue('gibbonSpaceID', $gibbonSpaceID);
            },
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonLibraryItem.status = :status')
                    ->bindValue('status', $status);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }
    
    public function selectOverdueItems($ignoreStatus = null)
    {
        $data = ['today' => date('Y-m-d')];

        if ($ignoreStatus == 'on') {
            $sql = "SELECT gibbonLibraryItem.*, surname, preferredName, email 
            FROM gibbonLibraryItem 
            JOIN gibbonPerson ON (gibbonLibraryItem.gibbonPersonIDStatusResponsible=gibbonPerson.gibbonPersonID) 
            WHERE gibbonLibraryItem.status='On Loan' 
            AND borrowable='Y' 
            AND returnExpected<:today 
            ORDER BY surname, preferredName";
        } else {
            $sql = "SELECT gibbonLibraryItem.*, surname, preferredName, email 
            FROM gibbonLibraryItem 
            JOIN gibbonPerson ON (gibbonLibraryItem.gibbonPersonIDStatusResponsible=gibbonPerson.gibbonPersonID) 
            WHERE gibbonLibraryItem.status='On Loan' 
            AND borrowable='Y' 
            AND returnExpected<:today AND gibbonPerson.status='Full' 
            ORDER BY surname, preferredName";
        }

        return $this->db()->select($sql, $data);
    }
}
