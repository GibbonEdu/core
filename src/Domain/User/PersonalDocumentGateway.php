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

use Gibbon\Services\Format;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * @version v22
 * @since   v22
 */
class PersonalDocumentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonPersonalDocument';
    private static $primaryKey = 'gibbonPersonalDocumentID';

    private static $searchableColumns = [];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryDocumentsByPerson(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonPersonalDocumentID', 'gibbonPersonID',
            ]);

        $criteria->addFilterRules([
            'type' => function ($query, $type) {
                return $query
                    ->where('gibbonPersonalDocument.type = :type')
                    ->bindValue('type', $type);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectPersonalDocuments($foreignTable = null, $foreignTableID = null, $params = [])
    {
        $query = $this
            ->newSelect()
            ->cols(['gibbonPersonalDocumentType.*'])
            ->from('gibbonPersonalDocumentType')
            ->where("gibbonPersonalDocumentType.active='Y'");

        if (!empty($foreignTable) && !empty($foreignTableID)) {
            $query
                ->cols(['gibbonPersonalDocument.gibbonPersonalDocumentID', 'gibbonPersonalDocument.foreignTable', 'gibbonPersonalDocument.foreignTableID', 'gibbonPersonalDocument.documentName', 'gibbonPersonalDocument.documentNumber', 'gibbonPersonalDocument.documentType', 'gibbonPersonalDocument.country', 'gibbonPersonalDocument.dateIssue', 'gibbonPersonalDocument.dateExpiry', 'gibbonPersonalDocument.filePath'])
                ->leftJoin('gibbonPersonalDocument', 'gibbonPersonalDocument.gibbonPersonalDocumentTypeID=gibbonPersonalDocumentType.gibbonPersonalDocumentTypeID AND gibbonPersonalDocument.foreignTable=:foreignTable AND gibbonPersonalDocument.foreignTableID=:foreignTableID')
                ->bindValue('foreignTable', $foreignTable)
                ->bindValue('foreignTableID', $foreignTableID);
        }

        // Handle role category flags as ORs
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
        if ($params['notEmpty'] ?? false) {
            $query->where('gibbonPersonalDocument.gibbonPersonalDocumentID IS NOT NULL');
        }

        $query->orderBy(['sequenceNumber', 'name']);

        return $this->runSelect($query);
    }
}
