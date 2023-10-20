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

namespace Gibbon\Domain\User;

use Gibbon\Services\Format;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByTimestamp;

/**
 * @version v22
 * @since   v22
 */
class PersonalDocumentGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByTimestamp;

    private static $tableName = 'gibbonPersonalDocument';
    private static $primaryKey = 'gibbonPersonalDocumentID';

    private static $searchableColumns = [];

    private static $scrubbableKey = 'timestamp';
    private static $scrubbableColumns = ['documentNumber' => null,'documentName' => null,'documentType' => null,'dateIssue' => null,'dateExpiry' => null,'filePath' => 'deleteFile','country' => null];

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

    public function queryStudentDocuments(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonIDList)
    {
        if (is_string($gibbonPersonIDList)) {
            $gibbonPersonIDList = explode(',', $gibbonPersonIDList);
        }

        if (is_array($gibbonPersonIDList)) {
            $gibbonPersonIDList = array_map(function($item) {
                return str_pad($item, 12, 0, STR_PAD_LEFT);
            }, $gibbonPersonIDList);
            $gibbonPersonIDList = implode(',', $gibbonPersonIDList);
        }

        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols(['gibbonPersonalDocument.gibbonPersonalDocumentID', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonFormGroup.name as formGroup', 'gibbonPersonalDocumentType.name as documentTypeName', 'gibbonPersonalDocumentType.document', 'gibbonPersonalDocument.documentNumber', 'gibbonPersonalDocument.documentName', 'gibbonPersonalDocument.documentType', 'gibbonPersonalDocument.dateIssue', 'gibbonPersonalDocument.dateExpiry', 'gibbonPersonalDocument.country', 'gibbonPersonalDocument.filePath'])
            ->innerJoin('gibbonPersonalDocumentType', 'gibbonPersonalDocument.gibbonPersonalDocumentTypeID=gibbonPersonalDocumentType.gibbonPersonalDocumentTypeID')
            ->innerJoin('gibbonPerson', 'LPAD(gibbonPerson.gibbonPersonID, 12, "0")=gibbonPersonalDocument.foreignTableID')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonPersonalDocument.foreignTable="gibbonPerson"')
            ->where('FIND_IN_SET(gibbonPersonalDocument.foreignTableID, :gibbonPersonIDList)')
            ->bindValue('gibbonPersonIDList', $gibbonPersonIDList);

        $criteria->addFilterRules([
            'documents' => function ($query, $documents) {
                if (empty($documents)) return $query;
                return $query
                    ->where('FIND_IN_SET(gibbonPersonalDocumentType.gibbonPersonalDocumentTypeID, :documents)')
                    ->bindValue('documents', $documents);
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

    public function updatePersonalDocumentOwnership($foreignTableOld, $foreignTableOldID, $foreignTableNew, $foreignTableNewID)
    {
        $data = ['foreignTableOld' => $foreignTableOld, 'foreignTableOldID' => $foreignTableOldID, 'foreignTableNew' => $foreignTableNew, 'foreignTableNewID' => $foreignTableNewID];
        $sql = "UPDATE gibbonPersonalDocument 
                SET foreignTable=:foreignTableNew, foreignTableID=:foreignTableNewID 
                WHERE foreignTable=:foreignTableOld AND foreignTableID=:foreignTableOldID";

        return $this->db()->update($sql, $data);
    }

    public function deletePersonalDocuments($foreignTable, $foreignTableID)
    {
        $data = ['foreignTable' => $foreignTable, 'foreignTableID' => $foreignTableID];
        $sql = "DELETE FROM gibbonPersonalDocument 
                WHERE foreignTable=:foreignTable AND foreignTableID=:foreignTableID";

        return $this->db()->delete($sql, $data);
    }

    public function getPersonalDocumentDataByUser($gibbonPersonalDocumentTypeID, $gibbonPersonID)
    {
        $data = ['gibbonPersonalDocumentTypeID' => $gibbonPersonalDocumentTypeID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonPersonalDocumentID, filePath FROM gibbonPersonalDocument WHERE gibbonPersonalDocumentTypeID=:gibbonPersonalDocumentTypeID AND  foreignTable='gibbonPerson' AND foreignTableID=:gibbonPersonID";

        return $this->db()->select($sql, $data)->fetch();
    }
}
