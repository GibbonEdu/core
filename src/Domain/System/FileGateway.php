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

namespace Gibbon\Domain\System;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;

/**
 * File Gateway
 *
 * @version v31
 * @since   v31
 */
class FileGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFile';
    private static $primaryKey = 'gibbonFileID';
    private static $searchableColumns = ['fileName', 'filePath'];

    private ?array $textColumnsByTable = null;

    public function queryUploadedFiles(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->cols([
                'gibbonFile.gibbonPersonIDOwner AS gibbonPersonID',
                'gibbonPerson.title',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'COUNT(gibbonFile.gibbonFileID) AS fileCount',
                'SUM(gibbonFile.fileSize) AS totalSize',
            ])
            ->from('gibbonFile')
            ->leftJoin('gibbonFilePointer', 'gibbonFile.gibbonFileID = gibbonFilePointer.gibbonFileID')
            ->innerJoin('gibbonPerson', 'gibbonFile.gibbonPersonIDOwner = gibbonPerson.gibbonPersonID')
            ->where('gibbonFilePointer.gibbonFilePointerID IS NULL')
            ->groupBy(['gibbonFile.gibbonPersonIDOwner']);

        return $this->runQuery($query, $criteria);
    }
    
    public function queryUploadedFilesByPerson(QueryCriteria $criteria, string $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->cols([
                'gibbonFile.gibbonFileID', 'gibbonFile.filePath', 'gibbonFile.fileName', 'gibbonFile.fileExtension',
                'gibbonFile.fileSize', 'gibbonFile.mimeType', 'gibbonFile.uploadedAt', 'gibbonFile.isUsed',
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname',
            ])
            ->from('gibbonFile')
            ->leftJoin('gibbonFilePointer', 'gibbonFile.gibbonFileID = gibbonFilePointer.gibbonFileID')
            ->innerJoin('gibbonPerson', 'gibbonFile.gibbonPersonIDOwner = gibbonPerson.gibbonPersonID')
            ->where('gibbonFilePointer.gibbonFilePointerID IS NULL')
            ->where('gibbonFile.gibbonPersonIDOwner = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        return $this->runQuery($query, $criteria);
    }

    public function selectFilesByForeignRecord($foreignTable, $foreignTableID)
    {
        $data = ['foreignTable' => $foreignTable, 'foreignTableID' => $foreignTableID];
        $sql = "SELECT gibbonFile.*
                FROM gibbonFile
                JOIN gibbonFilePointer ON (gibbonFile.gibbonFileID = gibbonFilePointer.gibbonFileID)
                WHERE gibbonFilePointer.foreignTable = :foreignTable
                AND gibbonFilePointer.foreignTableID = :foreignTableID
                ORDER BY gibbonFile.uploadedAt DESC";

        return $this->db()->select($sql, $data);
    }

    public function selectAllFileRecords()
    {
        $sql = "SELECT gibbonFileID, filePath, fileName, uploadedAt
                FROM gibbonFile";

        return $this->db()->select($sql);
    }

    public function getByFilePath(string $filePath)
    {
        $data = ['filePath' => $filePath];
        $sql = "SELECT gibbonFileID, filePath
                FROM gibbonFile
                WHERE filePath = :filePath
                LIMIT 1";

        return $this->db()->selectOne($sql, $data);
    }

    public function getOtherFileByPath(string $filePath, int|string $gibbonFileID)
    {
        $data = ['filePath' => $filePath, 'gibbonFileID' => $gibbonFileID];
        $sql = "SELECT gibbonFileID, filePath
                FROM gibbonFile
                WHERE filePath = :filePath
                AND gibbonFileID <> :gibbonFileID
                LIMIT 1";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectNoPointerFiles()
    {
        $sql = "SELECT gibbonFile.gibbonFileID, gibbonFile.filePath, gibbonFile.isUsed
                FROM gibbonFile
                LEFT JOIN gibbonFilePointer ON (gibbonFile.gibbonFileID = gibbonFilePointer.gibbonFileID)
                WHERE gibbonFilePointer.gibbonFilePointerID IS NULL";

        return $this->db()->select($sql);
    }

    public function selectTextColumns()
    {
        $sql = "SELECT col.TABLE_NAME, col.COLUMN_NAME
                FROM information_schema.COLUMNS AS col
                JOIN information_schema.TABLES AS tables ON (col.TABLE_SCHEMA = tables.TABLE_SCHEMA AND col.TABLE_NAME = tables.TABLE_NAME)
                WHERE col.TABLE_SCHEMA = DATABASE()
                AND tables.TABLE_TYPE = 'BASE TABLE'
                AND col.TABLE_NAME <> 'gibbonFile'
                AND col.DATA_TYPE IN ('text', 'mediumtext', 'longtext')
                ORDER BY col.TABLE_NAME, col.COLUMN_NAME";

        return $this->db()->select($sql);
    }

    public function isFilePathInText(string $filePath): bool
    {
        if ($filePath === '' || str_contains($filePath, "\0") || str_contains($filePath, '..')) {
            return true;
        }

        $tables = $this->getTextColumnsByTable();
        if ($tables === []) {
            return true;
        }

        $pattern = '%'.addcslashes($filePath, '%_\\').'%';
        foreach ($tables as $table => $columns) {
            $data = [];
            $where = [];
            foreach ($columns as $index => $column) {
                $data['filePath'.$index] = $pattern;
                $where[] = "`{$column}` LIKE :filePath{$index} ESCAPE '\\\\'";
            }

            $sql = "SELECT 1 AS found FROM `{$table}` WHERE ".implode(' OR ', $where).' LIMIT 1';
            if (!empty($this->db()->selectOne($sql, $data))) {
                return true;
            }
        }

        return false;
    }

    private function getTextColumnsByTable(): array
    {
        if ($this->textColumnsByTable !== null) {
            return $this->textColumnsByTable;
        }

        $this->textColumnsByTable = [];
        foreach ($this->selectTextColumns()->fetchAll() as $column) {
            $table = $column['TABLE_NAME'];
            $name = $column['COLUMN_NAME'];
            if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                continue;
            }
            $this->textColumnsByTable[$table][] = $name;
        }

        return $this->textColumnsByTable;
    }

    public function markAsUnused(string $gibbonFileID): bool
    {
        $data = ['gibbonFileID' => $gibbonFileID];
        $sql = "UPDATE gibbonFile
                SET isUsed = 'N'
                WHERE gibbonFileID = :gibbonFileID";

        return $this->db()->update($sql, $data);
    }
}
