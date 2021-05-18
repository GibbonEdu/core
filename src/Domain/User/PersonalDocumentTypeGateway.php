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

namespace Gibbon\Domain\User;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v22
 * @since   v22
 */
class PersonalDocumentTypeGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonPersonalDocumentType';
    private static $primaryKey = 'gibbonPersonalDocumentTypeID';

    private static $searchableColumns = [];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryDocumentTypes(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonPersonalDocumentTypeID', 'name', 'active', 'required',
            ]);

        $criteria->addFilterRules([
            'active' => function ($query, $active) {
                return $query
                    ->where('gibbonPersonalDocumentType.active = :active')
                    ->bindValue('active', $active);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectDocumentTypes($params)
    {
        $query = $this
            ->newSelect()
            ->cols(['*'])
            ->from('gibbonPersonalDocumentType')
            ->where("active='Y'");

        $query->where(function ($query) use (&$params) {
            if ($params['student'] ?? false) {
                $query->orWhere('activePersonStudent=:student', ['student' => $params['student']]);
            }
            if ($params['staff'] ?? false) {
                $query->orWhere('activePersonStaff=:staff', ['staff' => $params['staff']]);
            }
            if ($params['parent'] ?? false) {
                $query->orWhere('activePersonParent=:parent', ['parent' => $params['parent']]);
            }
            if ($params['other'] ?? false) {
                $query->orWhere('activePersonOther=:other', ['other' => $params['other']]);
            }
        });

        // Handle additional flags as ANDs
        if ($params['applicationForm'] ?? false) {
            $query->where('activeApplicationForm=:applicationForm', ['applicationForm' => $params['applicationForm']]);
        }
        if ($params['dataUpdater'] ?? false) {
            $query->where('activeDataUpdater=:dataUpdater', ['dataUpdater' => $params['dataUpdater']]);
        }

        $query->orderBy(['sequenceNumber', 'name']);

        return $this->runSelect($query);
    }
}
