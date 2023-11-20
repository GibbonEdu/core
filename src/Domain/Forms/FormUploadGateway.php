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

namespace Gibbon\Domain\Forms;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class FormUploadGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFormUpload';
    private static $primaryKey = 'gibbonFormUploadID';
    private static $searchableColumns = ['gibbonFormUpload.name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryAllDocumentsByContext(QueryCriteria $criteria, $gibbonFormID, $foreignTable, $foreignTableID)
    {
        $status = $this->db()->selectOne("SELECT status FROM {$foreignTable} WHERE {$foreignTable}ID=:foreignTableID", ['foreignTableID' => $foreignTableID]);

        $select = $this
            ->newSelect()
            ->cols(['gibbonFormField.fieldGroup', "GROUP_CONCAT(gibbonFormField.options SEPARATOR ',') as options"])
            ->from('gibbonFormField')
            ->innerJoin('gibbonFormPage', 'gibbonFormPage.gibbonFormPageID=gibbonFormField.gibbonFormPageID')
            ->where('gibbonFormField.fieldGroup="RequiredDocuments"')
            ->where('gibbonFormPage.gibbonFormID=:gibbonFormID', ['gibbonFormID' => $gibbonFormID])
            ->groupBy(['gibbonFormField.fieldGroup']);

        $documents = $this->runSelect($select)->fetchKeyPair();

        foreach ($documents as $fieldGroup => $options) {
            $options = array_map('trim', explode(',', $options));

            if ($fieldGroup == 'RequiredDocuments' && !empty($options)) {
                foreach ($options as $i => $option) {
                    $query = empty($query)? $this->newQuery() : $this->unionAllWithCriteria($query, $criteria);
                    
                    $query->cols(["'Required Documents' AS type", "'Student' as target", ":option{$i} as name", 'gibbonFormUpload.gibbonFormUploadID AS id', 'gibbonFormUpload.path', 'gibbonFormField.required', 'gibbonFormUpload.timestamp'])
                        ->from('gibbonFormField')
                        ->innerJoin('gibbonFormPage', 'gibbonFormPage.gibbonFormPageID=gibbonFormField.gibbonFormPageID')
                        ->leftJoin('gibbonFormUpload', "gibbonFormUpload.gibbonFormFieldID=gibbonFormField.gibbonFormFieldID AND gibbonFormUpload.foreignTable=:foreignTable AND gibbonFormUpload.foreignTableID=:foreignTableID AND (gibbonFormUpload.name=:option{$i} OR gibbonFormUpload.name=SUBSTRING(:option{$i},1,90))")
                        ->where('gibbonFormField.fieldGroup="RequiredDocuments"')
                        ->where('gibbonFormPage.gibbonFormID=:gibbonFormID', ['gibbonFormID' => $gibbonFormID])
                        ->bindValue('foreignTable', $foreignTable)
                        ->bindValue('foreignTableID', $foreignTableID)
                        ->bindValue("option{$i}", $option);
                }
            }
        }

        // Check for orphaned documents if a document type is deleted
        $query = empty($query)? $this->newQuery() : $this->unionAllWithCriteria($query, $criteria);
        $query->distinct()
            ->from('gibbonFormUpload')
            ->cols(["'Unknown' AS type", "'Student' as target", "gibbonFormUpload.name", 'gibbonFormUpload.gibbonFormUploadID AS id', 'gibbonFormUpload.path', '"N" as required', 'gibbonFormUpload.timestamp'])
            ->leftJoin('gibbonFormField', "gibbonFormUpload.gibbonFormFieldID=gibbonFormField.gibbonFormFieldID AND  gibbonFormUpload.foreignTable=:foreignTable AND gibbonFormUpload.foreignTableID=:foreignTableID")
            ->where('(gibbonFormField.gibbonFormFieldID IS NULL OR gibbonFormField.options NOT LIKE CONCAT("%",gibbonFormUpload.name,"%"))')
            ->where('gibbonFormUpload.foreignTable=:foreignTable')
            ->where('gibbonFormUpload.foreignTableID=:foreignTableID')
            ->bindValue('foreignTable', $foreignTable)
            ->bindValue('foreignTableID', $foreignTableID);

        if ($status == 'Accepted') {

            $query = empty($query)? $this->newQuery() : $this->unionAllWithCriteria($query, $criteria);
            $query->distinct()
                ->from('gibbonPersonalDocument')
                ->cols(["'Personal Documents' AS type", "'Student' as target", 'gibbonPersonalDocumentType.name', 'gibbonPersonalDocument.gibbonPersonalDocumentID AS id',  'gibbonPersonalDocument.filePath AS path', '"N" as required', 'gibbonPersonalDocument.timestamp'])
                ->innerJoin('gibbonPersonalDocumentType', 'gibbonPersonalDocumentType.gibbonPersonalDocumentTypeID=gibbonPersonalDocument.gibbonPersonalDocumentTypeID')
                ->innerJoin('gibbonAdmissionsApplication', "JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.result, '$.gibbonPersonIDStudent'))=gibbonPersonalDocument.foreignTableID")
                ->where("gibbonPersonalDocument.foreignTable='gibbonPerson' AND gibbonPersonalDocumentType.activePersonStudent=1")
                ->where('gibbonAdmissionsApplication.gibbonAdmissionsApplicationID=:foreignTableID', ['foreignTableID' => $foreignTableID])
                ->where(":foreignTable='gibbonAdmissionsApplication'")
                ->where("gibbonPersonalDocumentType.fields LIKE '%filePath%'");

            $this->unionAllWithCriteria($query, $criteria)
                ->distinct()
                ->from('gibbonPersonalDocument')
                ->cols(["'Personal Documents' AS type", "'Parent' as target", 'gibbonPersonalDocumentType.name', 'gibbonPersonalDocument.gibbonPersonalDocumentID AS id',  'gibbonPersonalDocument.filePath AS path', '"N" as required', 'gibbonPersonalDocument.timestamp'])
                ->innerJoin('gibbonPersonalDocumentType', 'gibbonPersonalDocumentType.gibbonPersonalDocumentTypeID=gibbonPersonalDocument.gibbonPersonalDocumentTypeID')
                ->innerJoin('gibbonAdmissionsApplication', "JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.result, '$.gibbonPersonIDStudent'))=gibbonPersonalDocument.foreignTableID")
                ->where("gibbonPersonalDocument.foreignTable='gibbonPerson' AND gibbonPersonalDocumentType.activePersonParent=1")
                ->where('gibbonAdmissionsApplication.gibbonAdmissionsApplicationID=:foreignTableID', ['foreignTableID' => $foreignTableID])
                ->where(":foreignTable='gibbonAdmissionsApplication'")
                ->where("gibbonPersonalDocumentType.fields LIKE '%filePath%'");

        } else {
            $query = empty($query)? $this->newQuery() : $this->unionAllWithCriteria($query, $criteria);
            $query->cols(["'Personal Documents' AS type", "'Student' as target", 'gibbonPersonalDocumentType.name', 'gibbonPersonalDocument.gibbonPersonalDocumentID AS id',  'gibbonPersonalDocument.filePath AS path', 'gibbonFormField.required', 'gibbonPersonalDocument.timestamp'])
                ->from('gibbonFormField')
                ->innerJoin('gibbonFormPage', 'gibbonFormPage.gibbonFormPageID=gibbonFormField.gibbonFormPageID')
                ->innerJoin('gibbonPersonalDocumentType', 'gibbonFormField.fieldName="studentDocuments" AND gibbonPersonalDocumentType.activePersonStudent=1')
                ->leftJoin('gibbonPersonalDocument', 'gibbonPersonalDocumentType.gibbonPersonalDocumentTypeID=gibbonPersonalDocument.gibbonPersonalDocumentTypeID AND gibbonPersonalDocument.foreignTable=:foreignTable AND gibbonPersonalDocument.foreignTableID=:foreignTableID')
                ->where('gibbonFormPage.gibbonFormID=:gibbonFormID', ['gibbonFormID' => $gibbonFormID])
                ->where("gibbonPersonalDocumentType.fields LIKE '%filePath%' and gibbonPersonalDocumentType.activeApplicationForm=1")
                ->where('gibbonFormField.fieldGroup="PersonalDocuments"')
                ->bindValue('foreignTable', $foreignTable)
                ->bindValue('foreignTableID', $foreignTableID);

            $this->unionAllWithCriteria($query, $criteria)
                ->cols(["'Personal Documents' AS type", "'Parent 1' as target", 'gibbonPersonalDocumentType.name', 'gibbonPersonalDocument.gibbonPersonalDocumentID AS id', 'gibbonPersonalDocument.filePath AS path', 'gibbonFormField.required', 'gibbonPersonalDocument.timestamp'])
                ->from('gibbonFormField')
                ->innerJoin('gibbonFormPage', 'gibbonFormPage.gibbonFormPageID=gibbonFormField.gibbonFormPageID')
                ->innerJoin('gibbonPersonalDocumentType', 'gibbonFormField.fieldName="parent1Documents" AND gibbonPersonalDocumentType.activePersonParent=1')
                ->leftJoin('gibbonPersonalDocument', 'gibbonPersonalDocumentType.gibbonPersonalDocumentTypeID=gibbonPersonalDocument.gibbonPersonalDocumentTypeID AND gibbonPersonalDocument.foreignTable=:foreignTableP1 AND gibbonPersonalDocument.foreignTableID=:foreignTableID')
                ->where('gibbonFormPage.gibbonFormID=:gibbonFormID', ['gibbonFormID' => $gibbonFormID])
                ->where("gibbonPersonalDocumentType.fields LIKE '%filePath%' and gibbonPersonalDocumentType.activeApplicationForm=1")
                ->where('gibbonFormField.fieldGroup="PersonalDocuments"')
                ->bindValue('foreignTableP1', $foreignTable.'Parent1')
                ->bindValue('foreignTableID', $foreignTableID);

            $this->unionAllWithCriteria($query, $criteria)
                ->cols(["'Personal Documents' AS type", "'Parent 2' as target", 'gibbonPersonalDocumentType.name', 'gibbonPersonalDocument.gibbonPersonalDocumentID AS id',  'gibbonPersonalDocument.filePath AS path', 'gibbonFormField.required', 'gibbonPersonalDocument.timestamp'])
                ->from('gibbonFormField')
                ->innerJoin('gibbonFormPage', 'gibbonFormPage.gibbonFormPageID=gibbonFormField.gibbonFormPageID')
                ->innerJoin('gibbonPersonalDocumentType', 'gibbonFormField.fieldName="parent2Documents" AND gibbonPersonalDocumentType.activePersonParent=1')
                ->leftJoin('gibbonPersonalDocument', 'gibbonPersonalDocumentType.gibbonPersonalDocumentTypeID=gibbonPersonalDocument.gibbonPersonalDocumentTypeID AND gibbonPersonalDocument.foreignTable=:foreignTableP2 AND gibbonPersonalDocument.foreignTableID=:foreignTableID')
                ->where('gibbonFormPage.gibbonFormID=:gibbonFormID', ['gibbonFormID' => $gibbonFormID])
                ->where("gibbonPersonalDocumentType.fields LIKE '%filePath%' and gibbonPersonalDocumentType.activeApplicationForm=1")
                ->where('gibbonFormField.fieldGroup="PersonalDocuments"')
                ->bindValue('foreignTableP2', $foreignTable.'Parent2')
                ->bindValue('foreignTableID', $foreignTableID);
        }

        return $this->runQuery($query, $criteria);
    }

    public function getUploadByContext($gibbonFormID, $foreignTable, $foreignTableID, $name)
    {
        $select = $this
            ->newSelect()
            ->cols(['gibbonFormUpload.gibbonFormUploadID', 'gibbonFormUpload.path'])
            ->from($this->getTableName())
            ->where('gibbonFormUpload.gibbonFormID=:gibbonFormID', ['gibbonFormID' => $gibbonFormID])
            ->where('gibbonFormUpload.foreignTable=:foreignTable', ['foreignTable' => $foreignTable])
            ->where('gibbonFormUpload.foreignTableID=:foreignTableID', ['foreignTableID' => $foreignTableID])
            ->where('gibbonFormUpload.name=:name', ['name' => $name]);

        return $this->runSelect($select)->fetch();
    }

    public function selectAllUploadsByContext($gibbonFormID, $foreignTable, $foreignTableID)
    {
        $select = $this
            ->newSelect()
            ->cols(['gibbonFormUpload.name', 'gibbonFormUpload.path'])
            ->from($this->getTableName())
            ->where('gibbonFormUpload.gibbonFormID=:gibbonFormID', ['gibbonFormID' => $gibbonFormID])
            ->where('gibbonFormUpload.foreignTable=:foreignTable', ['foreignTable' => $foreignTable])
            ->where('gibbonFormUpload.foreignTableID=:foreignTableID', ['foreignTableID' => $foreignTableID]);

        return $this->runSelect($select);
    }
}
