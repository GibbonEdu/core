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

namespace Gibbon\Data;

use Gibbon\Contracts\Database\Connection;
use ParseCsv\Csv as ParseCSV;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

/**
 * Extended Import class
 */
class Importer
{
    const COLUMN_DATA_SKIP = -1;
    const COLUMN_DATA_CUSTOM = -2;
    const COLUMN_DATA_FUNCTION = -3;
    const COLUMN_DATA_LINKED = -4;
    const COLUMN_DATA_HIDDEN = -5;

    const ERROR_IMPORT_FILE = 200;
    const ERROR_REQUIRED_FIELD_MISSING = 205;
    const ERROR_INVALID_FIELD_VALUE = 206;
    const ERROR_DATABASE_GENERIC = 208;
    const ERROR_DATABASE_FAILED_INSERT = 209;
    const ERROR_DATABASE_FAILED_UPDATE = 210;
    const ERROR_NON_UNIQUE_KEY =212;
    const ERROR_RELATIONAL_FIELD_MISMATCH = 213;
    const ERROR_INVALID_HAS_SPACES = 214;

    const WARNING_DUPLICATE_KEY = 101;
    const WARNING_RECORD_NOT_FOUND = 102;

    public $fieldDelimiter = ',';
    public $stringEnclosure = '"';
    public $maxLineLength = 100000;

    public $mode;
    public $syncField;
    public $syncColumn;

    public $outputData = [];

    /**
     * File handler for line-by-line CSV read
     */
    private $csvFileHandler;

    /**
     * Array of header names from first CSV line
     */
    private $importHeaders;

    /**
     * Array of raw parsed CSV records
     */
    private $importData;

    /**
     * Array of validated, database-friendly records
     */
    private $tableData = [];
    private $tableFields = [];

    private $cachedData = [];
    private $serializeData = [];

    /**
     * Errors
     */
    private $importLog = ['error' => [], 'warning' => [], 'message' => []];
    private $rowErrors = [];

    /**
     * ID of the last error message
     */
    private $errorID = 0;

    /**
     * Current counts for database operations
     */
    private $databaseResults = [
        'inserts' => 0,
        'inserts_skipped' => 0,
        'updates' => 0,
        'updates_skipped' => 0,
        'duplicates' => 0
    ];

    /**
     * Valid import MIME types
     */
    private $csvMimeTypes = [
        'text/csv', 'text/xml', 'text/comma-separated-values', 'text/x-comma-separated-values', 'application/vnd.ms-excel', 'application/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/vnd.oasis.opendocument.spreadsheet', 'application/octet-stream',
    ];

    /**
     * Gibbon\Contracts\Database\Connection
     */
    private $pdo ;

    private $headerRow;
    private $firstRow;

    /**
     * Enables the output of the SQL queries when an error is encountered. Change this manually.
     *
     * @var bool
     */
    private $debug = false;

    /**
     * Constructor
     *
     * @param    Gibbon\Contracts\Database\Connection
     */
    public function __construct(Connection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    public function __set($name, $value)
    {
        throw new \Exception('Trying to access a read-only property.');
    }

    /**
     * Validates the supplied MIME Type with a list of valid types
     *
     * @param  string  MIME Type
     * @return  bool
     */
    public function isValidMimeType($fileMimeType)
    {
        return in_array($fileMimeType, $this->csvMimeTypes);
    }

    /**
     * Open CSV File
     *
     * @param  string  Full File Path
     * @return  bool  true on success
     */
    public function openCSVFile($csvFile)
    {
        ini_set("auto_detect_line_endings", true);
        $this->csvFileHandler = fopen($csvFile, "r");
        return ($this->csvFileHandler !== false);
    }

    /**
     * Close CSV File
     */
    public function closeCSVFile()
    {
        fclose($this->csvFileHandler);
    }

    /**
     * Get CSV Line
     *
     * @return  array  Next parsed CSV line, based on current handler
     */
    public function getCSVLine()
    {
        return fgetcsv($this->csvFileHandler, $this->maxLineLength, $this->fieldDelimiter, $this->stringEnclosure);
    }

    /**
     * Read CSV String
     *
     * @param  string  CSV Data
     * @return  bool  true on successful CSV parse
     */
    public function readCSVString($csvString)
    {
        $csv = new ParseCSV();
        $csv->heading = true;
        $csv->delimiter = $this->fieldDelimiter;
        $csv->enclosure = $this->stringEnclosure;

        $csv->parse($csvString);

        $this->importHeaders = $csv->titles ?? [];
        $this->importData = $csv->data ?? [];

        $this->importLog['error'] = $csv->error_info;
        unset($csv);

        foreach ($this->importLog['error'] as $error) {
            $this->rowErrors[$error['row']] = 1;
        }

        return (!empty($this->importHeaders) && count($this->importData) > 0 && count($this->rowErrors) == 0);
    }


    public function readFileIntoCSV()
    {
        $data = '';

        $fileType = mb_substr($_FILES['file']['name'], mb_strpos($_FILES['file']['name'], '.')+1);
        $fileType = mb_strtolower($fileType);
        $mimeType = $_FILES['file']['type'];

        if ($fileType == 'csv') {
            $opts = array('http' => array('header' => "Accept-Charset: utf-8;q=0.7,*;q=0.7\r\n"."Content-Type: text/html; charset =utf-8\r\n"));
            $context = stream_context_create($opts);

            $data = file_get_contents($_FILES['file']['tmp_name'], false, $context);
            if (mb_check_encoding($data, 'UTF-8') == false) {
                $data = mb_convert_encoding($data, 'UTF-8');
            }

            // Grab the header & first row for Step 1
            if ($this->openCSVFile($_FILES['file']['tmp_name'])) {
                $this->headerRow = $this->getCSVLine();
                $this->firstRow = $this->getCSVLine();
                $this->closeCSVFile();
            } else {
                $this->errorID = Importer::ERROR_IMPORT_FILE;
                return false;
            }
        } elseif ($fileType == 'xlsx' || $fileType == 'xls' || $fileType == 'xml' || $fileType == 'ods') {
            $filePath = $_FILES['file']['tmp_name'];

            // Try to use the best reader if available, otherwise catch any read errors
            try {
                if ($fileType == 'xml') {
                    $objReader = IOFactory::createReader('Xml');
                    $spreadsheet = $objReader->load($filePath);
                } else {
                    $spreadsheet = IOFactory::load($filePath);
                }
            } catch (ReaderException $e) {
                $this->errorID = Importer::ERROR_IMPORT_FILE;
                return false;
            }

            $objWorksheet = $spreadsheet->getActiveSheet();
            $lastColumn = $objWorksheet->getHighestColumn();

            // Grab the header & first row for Step 1
            foreach ($objWorksheet->getRowIterator(0, 2) as $rowIndex => $row) {
                $array = $objWorksheet->rangeToArray('A'.$rowIndex.':'.$lastColumn.$rowIndex, null, true, true, false);

                if ($rowIndex == 1) {
                    $this->headerRow = $array[0];
                } elseif ($rowIndex == 2) {
                    $this->firstRow = $array[0];
                }
            }

            $objWriter = IOFactory::createWriter($spreadsheet, 'Csv');

            // Export back to CSV
            ob_start();
            $objWriter->save('php://output');
            $data = ob_get_clean();
        }

        return $data;
    }

    /**
     * Iterate over the imported records, validating and building table data for each one
     *
     * @param  Object  Import Type
     * @param  array  Column Order
     * @param  array  Custom user-provided values
     * @return  bool  true if build succeeded
     */
    public function buildTableData($importType, $columnOrder, $customValues = [])
    {
        if (empty($this->importData)) {
            return false;
        }

        $this->tableData = [];
        $rowIndex = 0;

        foreach ($this->importData as $rowNum => $row) {
            $fields = [];
            $fieldCount = 0;
            $partialFail = false;
            $tableFields = $importType->getTableFields();

            foreach ($importType->getAllFields() as $fieldName) {
                $columnIndex = $columnOrder[$fieldCount];
                $value = $importType->getField($fieldName, 'value', null);

                if (!in_array($fieldName, $tableFields)) {
                    // Skip fields not used by this table
                    $fieldCount++;
                    continue;
                }

                if ($columnIndex == Importer::COLUMN_DATA_SKIP) {
                    // Skip marked columns
                    $fieldCount++;
                    continue;
                } elseif ($columnIndex == Importer::COLUMN_DATA_CUSTOM) {
                    // Get the custom text value provided by the user (from Step 2)
                    $value = (isset($customValues[$fieldCount]))? $customValues[$fieldCount] : null;
                } elseif ($columnIndex == Importer::COLUMN_DATA_FUNCTION) {
                    // Run a user_func based on the function name defined for that field
                    $value = $importType->doImportFunction($fieldName);
                } elseif ($columnIndex == Importer::COLUMN_DATA_LINKED) {
                    // Grab another field value for linked fields. Fields with values must always precede the linked field.
                    // Can grab cached linked values if they're in a table that preceded this one.
                    if ($importType->isFieldLinked($fieldName)) {
                        $linkedFieldName = $importType->getField($fieldName, 'linked');
                        $value = $fields[$linkedFieldName] ?? $this->cachedData[$rowIndex][$linkedFieldName] ?? null;
                    }
                } elseif ($columnIndex >= 0) {
                    // Use the column index to grab to associated CSV value
                    // Get the associative key from the CSV headers using the current index
                    $columnKey = (isset($this->importHeaders[$columnIndex]))? $this->importHeaders[$columnIndex] : -1;
                    $value = (isset($row[$columnKey]))? $row[$columnKey] : null;
                }

                // Filter
                $value = $importType->filterFieldValue($fieldName, $value);
                $filter = $importType->getField($fieldName, 'filter');

                // Validate the value
                if ($importType->validateFieldValue($fieldName, $value) === false) {
                    $type = $importType->getField($fieldName, 'type');

                    if ($filter == 'nospaces') {
                        $this->log($rowNum, Importer::ERROR_INVALID_HAS_SPACES, $fieldName, $fieldCount, array('value' => $value));
                    } else {
                        $expectation = (!empty($type))? $importType->readableFieldType($fieldName) : $filter;
                        $this->log($rowNum, Importer::ERROR_INVALID_FIELD_VALUE, $fieldName, $fieldCount, array('value' => $value, 'expectation' => $expectation));
                    }

                    $partialFail = true;
                }

                // Handle relational table data
                if ($importType->isFieldRelational($fieldName) && !empty($this->cachedData[$rowIndex][$fieldName])) {
                    // Grab existing cached relational data, to prevent multiple identical queries in multi-table imports
                    $value = $this->cachedData[$rowIndex][$fieldName];
                } elseif ($importType->isFieldRelational($fieldName)) {
                    // Otherwise build a query to grab the relational data.
                    $join = $on = '';
                    extract($importType->getField($fieldName, 'relationship'));

                    $table = $this->escapeIdentifier($table);
                    $join = $this->escapeIdentifier($join);

                    // Handle table joins
                    $tableJoin = '';
                    if (!empty($join) && !empty($on)) {
                        if (is_array($on) && count($on) == 2) {
                            $tableJoin = "JOIN {$join} ON ({$join}.{$on[0]}={$table}.{$on[1]})";
                        }
                    }

                    // Handle relational fields with CSV data
                    $values = $filter == 'csv' ? array_map('trim', explode(',', $value)) : [$value];
                    $relationalValue = [];

                    foreach ($values as $value) {
                        $fieldNameKey = $this->escapeParameter($fieldName);
                        if (is_array($field) && count($field) > 0) {
                            // Multi-key relationships
                            $relationalField = $this->escapeIdentifier($field[0]);
                            $relationalData = array($fieldNameKey => $value);
                            $relationalSQL = "SELECT {$table}.{$key} FROM {$table} {$tableJoin} WHERE {$relationalField}=:{$fieldNameKey}";

                            for ($i=1; $i<count($field); $i++) {
                                // Relational field from within current import data
                                $relationalField = $field[$i];
                                if (isset($fields[$relationalField])) {
                                    $relationalFieldKey = $this->escapeParameter($relationalField);
                                    $relationalData[$relationalFieldKey] = $fields[$relationalField];
                                    $relationalSQL .= " AND ".$this->escapeIdentifier($relationalField)."=:{$relationalFieldKey}";
                                }
                            }
                        } elseif (stripos($field, '|') !== false) {
                            // Single key/value relationship, multiple optional fields as field1|field2|field3
                            $relationalData = [$fieldNameKey => $value];
                            $relationalFields = [];
                            foreach (explode('|', $field) as $i => $relationalField)  {
                                $relationalField = $this->escapeIdentifier($relationalField);
                                $relationalFields[] = "{$relationalField}=:{$fieldNameKey}";
                            }
                            $relationalSQL = "SELECT {$table}.{$key} FROM {$table} {$tableJoin} WHERE ".implode(" OR ", $relationalFields);
                        } else {
                            // Single key/value relationship
                            $relationalField = $this->escapeIdentifier($field);
                            $relationalData = array($fieldNameKey => $value);
                            $relationalSQL = "SELECT {$table}.{$key} FROM {$table} {$tableJoin} WHERE {$relationalField}=:{$fieldNameKey}";
                        }

                        $result = $this->pdo->select($relationalSQL, $relationalData);

                        if ($result->rowCount() > 0) {
                            $relationalValue[] = $result->fetchColumn(0);
                        } else {
                            // Missing relation for required field? Or missing a relation when value is provided? (excluding linked fields)
                            if ((!empty($value) && !$importType->isFieldLinked($fieldName)) || $importType->isFieldRequired($fieldName)) {
                                $field = (is_array($field))? implode(', ', $field) : $field;
                                $this->log(
                                    $rowNum,
                                    Importer::ERROR_RELATIONAL_FIELD_MISMATCH,
                                    $fieldName,
                                    $fieldCount,
                                    array('name' => $importType->getField($fieldName, 'name'), 'value' => $value, 'field' => $field, 'table' => $table)
                                );
                                $this->debugLog($rowNum, $relationalSQL, $relationalData, 'relational');

                                $partialFail = true;
                            }
                        }
                    }

                    $value = !empty($relationalValue) ? implode(',', $relationalValue) : null;
                }

                // Required field is empty?
                if ((!isset($value) || $value === null) && $importType->isFieldRequired($fieldName)) {
                    $this->log($rowNum, Importer::ERROR_REQUIRED_FIELD_MISSING, $fieldName, $fieldCount);
                    $partialFail = true;
                }

                // Do we serialize this data?
                $serialize = $importType->getField($fieldName, 'serialize');
                if (!empty($serialize)) {
                    if ($serialize == $fieldName) {
                        // Is this the field we're serializing? Grab the array
                        $value = json_encode($this->serializeData[$serialize] ?? []);
                        $fields[$fieldName] = $value;
                    } else {
                        // Otherwise collect values in an array
                        $customField = $importType->getField($fieldName, 'customField');
                        if (empty($customField)) $customField = $importType->getField($fieldName, 'name');
                        $this->serializeData[$serialize][$customField] = $value;
                    }
                } else {
                    // Add the field to the field set for this row
                    $fields[$fieldName] = $value;
                }

                $fieldCount++;
            }

            // Add the primary key if we're syncing with a database ID
            if ($this->syncField == true) {
                if (isset($row[$this->syncColumn]) && !empty($row[$this->syncColumn])) {
                    $fields[$importType->getPrimaryKey()] = $row[$this->syncColumn];
                } else {
                    $this->log($rowNum, Importer::ERROR_REQUIRED_FIELD_MISSING, $importType->getPrimaryKey(), $this->syncColumn);
                    $partialFail = true;
                }
            }

            // Salt & hash passwords
            if (isset($fields['passwordStrong'])) {
                if (!isset($this->outputData['passwords'])) {
                    $this->outputData['passwords'] = [];
                }
                $this->outputData['passwords'][] = ['username' => $fields['username'], 'password' => $fields['passwordStrong']];

                $salt = getSalt() ;
                $value = $fields['passwordStrong'];
                $fields['passwordStrong'] = hash("sha256", $salt.$value);
                $fields['passwordStrongSalt'] = $salt;
            }

            if (!empty($fields) && $partialFail == false) {
                $this->tableData[$rowIndex] = $fields;

                // Merge & cache the table data so multi-table imports can skip additional relational data checks
                $this->cachedData[$rowIndex] = array_merge($this->cachedData[$rowIndex] ?? [], $fields);

                $rowIndex++;
            }
        }

        if (count($this->tableData) > 0 && isset($this->tableData[0])) {
            $this->tableFields = array_keys($this->tableData[0]);
        }

        return (!empty($this->tableData) && $this->getErrorCount() == 0);
    }

    /**
     * Iterate over the table data and INSERT or UPDATE the database, checking for existing records
     *
     * @param  Object  Import Type
     * @param  bool  Update the database?
     * @return  bool  true if import succeeded
     */
    public function importIntoDatabase($importType, $liveRun = true)
    {
        if (empty($this->tableData) || count($this->tableData) < 1) {
            return false;
        }

        $tableName = $this->escapeIdentifier($importType->getDetail('table'));
        $primaryKey = $importType->getPrimaryKey();

        // Setup the query string for keys
        $sqlKeyQueryString = $this->getKeyQueryString($importType);

        $partialFail = false;
        foreach ($this->tableData as $rowNum => $row) {

            // Ensure we have valid key(s)
            $uniqueKeyDiff = array_diff($importType->getUniqueKeyFields(), array_keys($row));
            if (!empty($importType->getUniqueKeyFields()) && $uniqueKeyDiff != false) {
                $this->log($rowNum, Importer::ERROR_REQUIRED_FIELD_MISSING, implode(', ', $uniqueKeyDiff));
                $partialFail = true;
                continue;
            }

            // Find existing record(s)
            $data = [];
            // Add the unique keys
            foreach ($importType->getUniqueKeyFields() as $keyField) {
                $keyParam = $this->escapeParameter($keyField);
                $data[$keyParam] = $row[$keyField];
            }
            // Add the primary key if database IDs is enabled
            if ($this->syncField == true) {
                $data[$primaryKey] = $row[$primaryKey];
            }

            $result = $this->pdo->select($sqlKeyQueryString, $data);
            $keyRow = $result->fetch();

            if (!$this->pdo->getQuerySuccess()) {
                $this->log($rowNum, Importer::ERROR_DATABASE_GENERIC);
                $this->debugLog($rowNum, $sqlKeyQueryString, $data, 'find row');
                $partialFail = true;
                continue;
            }

            // Build the data and query field=:value associations
            $sqlFields = [];
            $sqlData = [];

            foreach ($row as $fieldName => $fieldData) {
                if ($importType->isFieldReadOnly($fieldName) || ($this->mode == 'update' && $fieldName == $primaryKey)) {
                    continue;
                } else {
                    $fieldNameParam = $this->escapeParameter($fieldName);
                    $sqlFields[] = $this->escapeIdentifier($fieldName) . "=:" . $fieldNameParam;
                    $sqlData[$fieldNameParam] = $fieldData;
                }

                // Handle merging existing custom field data with partial custom field imports
                if ($importType->isUsingCustomFields() && $fieldName == 'fields') {
                    if (isset($keyRow['fields']) && !empty($keyRow['fields'])) {
                        $existingFields = json_decode($keyRow['fields'], true) ?? [];
                        $newFields = json_decode($fieldData, true) ?? [];
                        $sqlData['fields'] = json_encode(array_merge($existingFields, $newFields));
                    }
                }
            }

            $sqlFieldString = implode(", ", $sqlFields);
            $updateNonUnique = $importType->getDetail('updateNonUnique');

            // Handle Existing Records
            if ($result->rowCount() == 1 || ($result->rowCount() > 0 && $updateNonUnique == true)) {

                $primaryKeyValues = [$keyRow[$primaryKey]];
                if ($updateNonUnique == true && $result->rowCount() > 1) {
                    $primaryKeyValues += $result->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_UNIQUE, 0);
                }

                foreach ($primaryKeyValues as $primaryKeyValue) {
                    // Dont update records on INSERT ONLY mode
                    if ($this->mode == 'insert') {
                        $this->log($rowNum, Importer::WARNING_DUPLICATE_KEY, $primaryKey, $primaryKeyValue);
                        $this->debugLog($rowNum, $sqlKeyQueryString, $data, 'insert fail');
                        $this->databaseResults['updates_skipped'] += 1;
                        continue;
                    }

                    // If these IDs don't match, then one of the unique keys matched (eg: non-unique value with different database ID)
                    if ($this->syncField == true && $primaryKeyValue != $row[$primaryKey]) {
                        $this->log($rowNum, Importer::ERROR_NON_UNIQUE_KEY, $primaryKey, $row[$primaryKey], array('key' => $primaryKey, 'value' => intval($primaryKeyValue) ));
                        $this->debugLog($rowNum, $sqlKeyQueryString, $data, 'update fail');
                        $this->databaseResults['updates_skipped'] += 1;
                        continue;
                    }

                    $this->databaseResults['updates'] += 1;

                    $sqlData[$primaryKey] = $primaryKeyValue;
                    $sql="UPDATE {$tableName} SET " . $sqlFieldString . " WHERE ".$this->escapeIdentifier($primaryKey)."=:{$primaryKey}" ;

                    // Skip now so we dont change the database
                    if (!$liveRun) {
                        continue;
                    }

                    $this->pdo->update($sql, $sqlData);

                    if (!$this->pdo->getQuerySuccess()) {
                        $this->log($rowNum, Importer::ERROR_DATABASE_FAILED_UPDATE);
                        $this->debugLog($rowNum, $sql, $sqlData, 'update fail');
                        $partialFail = true;
                        continue;
                    }
                }
            }

            // Handle New Records
            elseif ($result->rowCount() == 0) {

                // Dont add records on UPDATE ONLY mode
                if ($this->mode == 'update') {
                    $this->log($rowNum, Importer::WARNING_RECORD_NOT_FOUND);
                    $this->debugLog($rowNum, $sqlKeyQueryString, $data, 'update fail');
                    $this->databaseResults['inserts_skipped'] += 1;
                    continue;
                }

                $this->databaseResults['inserts'] += 1;

                $sql = "INSERT INTO {$tableName} SET ".$sqlFieldString;

                // Skip now so we dont change the database
                if (!$liveRun) {
                    continue;
                }

                $this->pdo->insert($sql, $sqlData);

                if (!$this->pdo->getQuerySuccess()) {
                    $this->log($rowNum, Importer::ERROR_DATABASE_FAILED_INSERT);
                    $this->debugLog($rowNum, $sql, $sqlData, 'insert fail');
                    $partialFail = true;
                    continue;
                }
            } else {
                $primaryKeyValues = $result->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_UNIQUE, 0);
                $this->log($rowNum, Importer::ERROR_NON_UNIQUE_KEY, $primaryKey, -1, array('key' => $primaryKey, 'value' => implode(', ', $primaryKeyValues) ));
                $this->debugLog($rowNum, $sqlKeyQueryString, $data, 'non-unique');
                $partialFail = true;
            }
        }

        return (!$partialFail);
    }

    protected function getKeyQueryString($importType)
    {
        $tableName = $this->escapeIdentifier($importType->getDetail('table'));
        $primaryKey = $importType->getPrimaryKey();
        $primaryKeyField = $this->escapeIdentifier($primaryKey);

        $sqlKeys = [];
        foreach ($importType->getUniqueKeys() as $uniqueKey) {

            $uniqueKey = is_array($uniqueKey) ? $uniqueKey : [$uniqueKey];

            if (count($uniqueKey) > 0) {
                // Handle multi-part unique keys (eg: school year AND course short name)
                $sqlKeysFields = [];
                foreach ($uniqueKey as $fieldName) {
                    // Skip key fields which dont exist in our imported data set
                    if (!in_array($fieldName, $this->tableFields)) {
                        continue;
                    }

                    $fieldNameField = $this->escapeIdentifier($fieldName);
                    $fieldNameParam = $this->escapeParameter($fieldName);
                    $sqlKeysFields[] = "({$fieldNameField}=:{$fieldNameParam} AND {$fieldNameField} IS NOT NULL)";
                }
                $sqlKeys[] = '('. implode(' AND ', $sqlKeysFields) .')';
            }
        }

        // Add the primary key if database IDs is enabled
        if ($this->syncField == true) {
            $sqlKeys[] = $primaryKeyField.'=:'.$primaryKey;
        }

        $sqlKeyString = implode(' OR ', $sqlKeys);

        if (empty($sqlKeyString)) {
            $sqlKeyString = "FALSE";
        }

        if ($importType->isUsingCustomFields()) {
            $primaryKeyField = $primaryKeyField.", fields";
        }

        return "SELECT {$tableName}.{$primaryKeyField} FROM {$tableName} WHERE ". $sqlKeyString ;
    }

    protected function escapeIdentifier($text)
    {
        return implode('.', array_map(function ($piece) {
            return '`' . str_replace('`', '``', $piece) . '`';
        }, explode('.', $text, 2)));
    }

    protected function escapeParameter($text)
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $text);
    }

    /**
     * Get Header Row
     *
     * @return  array     Row data
     */
    public function getHeaderRow()
    {
        return $this->headerRow;
    }

    /**
     * Get First Row
     *
     * @return  array     Row data
     */
    public function getFirstRow()
    {
        return $this->firstRow;
    }

    /**
     * Get Row Count
     *
     * @return  int    Count of rows imported from file
     */
    public function getRowCount()
    {
        return count($this->importData);
    }

    /**
     * Get Database Results
     *
     * @param  string $key
     * @return int    Current count of a database operation
     */
    public function getDatabaseResult($key)
    {
        return (isset($this->databaseResults[$key]))? $this->databaseResults[$key] : 'unknown';
    }

    /**
     * Get Logs
     *
     * @return  array  Errors logged with logError
     */
    public function getLogs()
    {
        return array_merge($this->importLog['message'], $this->importLog['warning'], $this->importLog['error']);
    }

    /**
     * Get Warning Count
     *
     * @return  int     Warning count
     */
    public function getWarningCount()
    {
        return count($this->importLog['warning']);
    }

    /**
     * Get Error Count
     *
     * @return  int    Error count
     */
    public function getErrorCount()
    {
        return count($this->importLog['error']);
    }

    /**
     * Get Error Row Count
     *
     * @return  int    Count of rows with errors
     */
    public function getErrorRowCount()
    {
        return count($this->rowErrors);
    }

    /**
     * Get Last Error
     *
     * @return  string  Translated error message
     */
    public function getLastError()
    {
        return $this->translateMessage($this->errorID);
    }

    /**
     * Log
     *
     * @param  int    Row Number
     * @param  int    Error ID
     * @param  string  Field Name
     * @param  string  Field Index
     * @param  array  Values to pass to String Format
     */
    protected function log($rowNum, $messageID, $fieldName = '', $fieldNum = -1, $args = [])
    {
        if ($messageID > 200) {
            $type = 'error';
        } elseif ($messageID > 100) {
            $type = 'warning';
        } else {
            $type = 'message';
        }

        $this->importLog[$type][] = array(
            'index'      => $rowNum,
            'row'        => $rowNum+2,
            'info'       => $this->translateMessage($messageID, $args),
            'field_name' => $fieldName,
            'field'      => $fieldNum,
            'type'       => $type
        );

        if ($type == 'error') {
            $this->rowErrors[$rowNum] = 1;
        }
    }

    protected function debugLog($rowNum, $sql, $data = [], $context = '')
    {
        if (!$this->debug) return;

        $this->importLog['error'][] = array(
            'index'      => $rowNum,
            'row'        => $rowNum+2,
            'info'       => $sql.'<br/><br/>'.json_encode($data, \JSON_PRETTY_PRINT),
            'field_name' => $context,
            'field'      => '',
            'type'       => 'error'
        );
    }

    /**
     * Error Message
     *
     * @param  int    Error ID
     * @return  string  Translated error message
     */
    protected function translateMessage($errorID, $args = [])
    {
        switch ($errorID) {
            // ERRORS
            case Importer::ERROR_IMPORT_FILE:
                return __('There was an error reading the file {value}.', $args);
                break;
            case Importer::ERROR_REQUIRED_FIELD_MISSING:
                return __('Missing value for a required field.');
                break;
            case Importer::ERROR_INVALID_FIELD_VALUE:
                return __('Invalid value: "{value}". Expected: {expectation}', $args);
                break;
            case Importer::ERROR_INVALID_HAS_SPACES:
                return __('Invalid value: "{value}". Contains invalid characters.', $args);
                break;
            case Importer::ERROR_NON_UNIQUE_KEY:
                return __('Non-unique values used by {key}: {value}', $args);
                break;
            case Importer::ERROR_DATABASE_GENERIC:
                return __('Your request failed due to a database error.');
                break;
            case Importer::ERROR_DATABASE_FAILED_INSERT:
                return __('Failed to insert or update database record.');
                break;
            case Importer::ERROR_DATABASE_FAILED_UPDATE:
                return __('Failed to insert or update database record.');
                break;
            case Importer::ERROR_RELATIONAL_FIELD_MISMATCH:
                return __('Each {name} value should match an existing {field} in {table}.', $args);
                break;

            // WARNINGS
            case Importer::WARNING_DUPLICATE_KEY:
                return __('A duplicate entry already exists for this record. Record skipped.');
                break;
            case Importer::WARNING_RECORD_NOT_FOUND:
                return __('A database entry for this record could not be found. Record skipped.');
                break;

            default:
                return __('An unknown error occured, so the import will be aborted.');
                break;
        }
    }

    /**
     * Inserts a record of an import into the database
     *
     * @param  string  gibbonPersonID
     * @param  string  Import Type name
     * @param  array  Results of the import
     * @param  array  Column order used
     * @return  bool
     */
    public function createImportLog($gibbonPersonID, $type, $results = [], $columnOrder = [])
    {
        $success = (($results['importSuccess'] && $results['buildSuccess'] && $results['databaseSuccess']) || $results['ignoreErrors']);

        $log = [
            'type'        => $type,
            'success'     => $success,
            'results'     => $results,
            'columnOrder' => $columnOrder,
        ];

        $data = [
            'gibbonPersonID'  => $gibbonPersonID,
            'title'           => 'Import - '.$type,
            'serialisedArray' => serialize($log),
            'ip'              => getIPAddress(),
        ];

        $sql = "INSERT INTO gibbonLog SET gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current'), gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='System Admin'), gibbonPersonID=:gibbonPersonID, title=:title, serialisedArray=:serialisedArray, ip=:ip";

        return $this->pdo->insert($sql, $data);
    }
}
