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

namespace Gibbon\Data;

use Gibbon\Contracts\Database\Connection;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads and holds the config info for a custom Import Type
 */
class ImportType
{
    /**
     * Information about the overall Import Type
     */
    protected $details = [];

    /**
     * Permission information for user access
     */
    protected $access = [];

    /**
     * Values that can be used for sync & updates
     */
    protected $primaryKey;
    protected $uniqueKeys = [];
    protected $keyFields = [];
    protected $fields = [];

    /**
     * Holds the table fields and information for each field
     */
    protected $table = [];
    protected $tables = [];
    protected $tablesUsed = [];

    /**
     * Has the structure been checked against the database?
     */
    protected $validated = false;

    /**
     * Relational data: System-wide (for filters)
     * @var array
     */
    protected $useYearGroups = false;
    protected $yearGroups = [];

    protected $useLanguages = false;
    protected $languages = [];

    protected $useCountries = false;
    protected $countries = [];

    protected $usePhoneCodes = false;
    protected $phoneCodes = [];

    protected $useCustomFields = false;
    protected $customFields = [];

    protected $useSerializedFields = false;

    /**
     * Constructor
     *
     * @param   array   importType information
     * @param   Object  PDO Connection
     */
    public function __construct($data, Connection $pdo = null, $validateStructure = true)
    {
        if (isset($data['details'])) {
            $this->details = $data['details'];
        }

        if (isset($data['access'])) {
            $this->access = $data['access'];
        }

        if (isset($data['primaryKey'])) {
            $this->primaryKey = $data['primaryKey'];
        }

        if (isset($data['uniqueKeys'])) {
            $this->uniqueKeys = $data['uniqueKeys'];

            //Grab the unique fields used in all keys
            foreach ($this->uniqueKeys as $key) {
                if (is_array($key) && count($key) > 1) {
                    $this->keyFields = array_merge($this->keyFields, $key);
                } else {
                    $this->keyFields[] = $key;
                }
            }

            $this->keyFields = array_unique(array_reduce($this->uniqueKeys, function ($group, $item) {
                $keys = is_array($item)? $item : [$item];
                return array_merge($group, $keys);
            }, []));
        }

        if (isset($data['tables']) && is_array($data['tables'])) {
            // Handle multiple tables in one file
            $this->fields = $data['fields'];
            $this->tables = $data['tables'];
        } elseif (isset($data['table'])) {
            // Convert single table into an array
            $this->fields = $data['table'];
            $this->tables[$this->details['table']] = [
                'primaryKey' => $data['primaryKey'] ?? '',
                'uniqueKeys' => $data['uniqueKeys'] ?? [],
                'fields'     => array_keys($data['table']) ?? [],
            ];
        }

        if (!empty($this->tables)) {

            foreach ($this->tables as $tableName => $table) {
                $this->tablesUsed[] = $tableName;

                $this->switchTable($tableName);

                // Add relational tables to the tablesUsed array so they're locked
                foreach ($this->table as $fieldName => $field) {
                    if ($this->isFieldRelational($fieldName)) {
                        $relationship = $this->getField($fieldName, 'relationship');
                        if (!in_array($relationship['table'], $this->tablesUsed)) {
                            $this->tablesUsed[] = $relationship['table'];
                        }
                    }

                    // Check the filters so we know if extra data is nessesary
                    $filter = $this->getField($fieldName, 'filter');
                    if ($filter == 'yearlist') {
                        $this->useYearGroups = true;
                    }
                    if ($filter == 'language') {
                        $this->useLanguages = true;
                    }
                    if ($filter == 'country') {
                        $this->useCountries = true;
                    }
                    if ($filter == 'phonecode') {
                        $this->usePhoneCodes = true;
                    }
                    if ($filter == 'customfield') {
                        $this->useCustomFields = true;
                    }

                    if (!empty($this->getField($fieldName, 'serialize'))) {
                        $this->useSerializedFields = true;
                    }
                }
            }

            $this->tablesUsed = array_unique($this->tablesUsed);
        }

        if ($pdo != null) {
            foreach ($this->tables as $tableName => $table) {
                $this->switchTable($tableName);
                $this->validated = true;

                if ($validateStructure == true) {
                    $this->validated &= $this->validateWithDatabase($pdo);
                    $this->loadRelationalData($pdo);
                } else {
                    $data = array('tableName' => $tableName);
                    $sql = "SHOW TABLES LIKE :tableName";
                    $this->validated &= !empty($pdo->selectOne($sql, $data));
                }
            }

            $this->loadAccessData($pdo);
        }

        if (empty($this->tables) || empty($this->details)) {
            return null;
        }
    }

    public static function getBaseDir(Connection $pdo)
    {
        $absolutePath = getSettingByScope($pdo->getConnection(), 'System', 'absolutePath');
        return rtrim($absolutePath, '/ ');
    }

    public static function getImportTypeDir(Connection $pdo)
    {
        return self::getBaseDir($pdo) . "/resources/imports";
    }

    public static function getCustomImportTypeDir(Connection $pdo)
    {
        $customFolder = getSettingByScope($pdo->getConnection(), 'Data Admin', 'importCustomFolderLocation');

        return self::getBaseDir($pdo).'/uploads/'.trim($customFolder, '/ ');
    }

    /**
     * Loads all YAML files from a folder and creates an importType object for each
     *
     * @param   Object  PDO Connection
     * @return  array   2D array of importType objects
     */
    public static function loadImportTypeList(Connection $pdo = null, $validateStructure = false)
    {
        $yaml = new Yaml();
        $importTypes = [];

        // Get the built-in import definitions
        $defaultFiles = glob(self::getImportTypeDir($pdo) . "/*.yml");

        // Create importType objects for each file
        foreach ($defaultFiles as $file) {
            $fileData = $yaml::parse(file_get_contents($file));

            if (isset($fileData['details']) && isset($fileData['details']['type'])) {
                $fileData['details']['grouping'] = (isset($fileData['access']['module']))? $fileData['access']['module'] : 'General';
                $importTypes[ $fileData['details']['type'] ] = new ImportType($fileData, $pdo, $validateStructure);
            }
        }

        // Get the user-defined custom definitions
        $customFiles = glob(self::getCustomImportTypeDir($pdo) . "/*.yml");

        if (is_dir(self::getCustomImportTypeDir($pdo))==false) {
            mkdir(self::getCustomImportTypeDir($pdo), 0755, true) ;
        }

        foreach ($customFiles as $file) {
            $fileData = $yaml::parse(file_get_contents($file));

            if (isset($fileData['details']) && isset($fileData['details']['type'])) {
                $fileData['details']['grouping'] = '* Custom Imports';
                $fileData['details']['custom'] = true;
                $importTypes[ $fileData['details']['type'] ] = new ImportType($fileData, $pdo, $validateStructure);
            }
        }

        uasort($importTypes, array('self', 'sortImportTypes'));

        return $importTypes;
    }

    protected static function sortImportTypes($a, $b)
    {
        if ($a->getDetail('grouping') != $b->getDetail('grouping')) {
            return $a->getDetail('grouping') <=> $b->getDetail('grouping');
        }

        if ($a->getDetail('category') != $b->getDetail('category')) {
            return $a->getDetail('category') <=> $b->getDetail('category');
        }

        if ($a->getDetail('name') != $b->getDetail('name')) {
            return $a->getDetail('name') <=> $b->getDetail('name');
        }

        return 0;
    }

    /**
     * Loads a YAML file and creates an importType object
     *
     * @param   string  Filename of the Import Type
     * @param   Object  PDO Conenction
     * @return  [importType]
     */
    public static function loadImportType($importTypeName, Connection $pdo = null)
    {
        // Check custom first, this allows for local overrides
        $path = self::getCustomImportTypeDir($pdo).'/'.$importTypeName.'.yml';
        if (!file_exists($path)) {
            // Next check the built-in import types folder
            $path = self::getImportTypeDir($pdo).'/'.$importTypeName.'.yml';

            // Finally fail if nothing is found
            if (!file_exists($path)) {
                return null;
            }
        }

        $yaml = new Yaml();
        $fileData = $yaml::parse(file_get_contents($path));

        return new importType($fileData, $pdo);
    }

    /**
     * Is Import Accessible
     *
     * @param   string  guid
     * @param   Object  PDO Conenction
     * @return  bool
     */
    public function isImportAccessible($guid, $connection2)
    {
        if ($this->getAccessDetail('protected') == false) {
            return true;
        }
        if ($connection2 == null) {
            return false;
        }

        return isActionAccessible($guid, $connection2, '/modules/' . $this->getAccessDetail('module').'/'.$this->getAccessDetail('entryURL'));
    }

    /**
     * Compares the importType structure with the database table to ensure imports will succeed
     *
     * @param   Connection  PDO
     * @return  bool    true if all fields match existing table columns
     */
    protected function validateWithDatabase(Connection $pdo)
    {
        try {
            $sql="SHOW COLUMNS FROM " . $this->getDetail('table');
            $result = $pdo->executeQuery([], $sql);
        } catch (\PDOException $e) {
            return false;
        }

        $columns = $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);

        $validatedFields = 0;
        foreach ($this->table as $fieldName => $field) {
            if ($this->isFieldReadOnly($fieldName)) {
                $this->setValueTypeByFilter($fieldName);
                $validatedFields++;
                continue;
            }

            $columnFieldName = stripos($fieldName, '.') !== false ? trim(strrchr($fieldName, '.'), '.') : $fieldName;

            if (isset($columns[$columnFieldName])) {
                foreach ($columns[$columnFieldName] as $columnName => $columnField) {
                    if ($columnName == 'Type') {
                        $this->parseTableValueType($fieldName, $columnField);
                    } else {
                        $this->setField($fieldName, mb_strtolower($columnName), $columnField);
                    }
                }
                $validatedFields++;
            } else {
                echo '<div class="error">Invalid field '. $fieldName .'</div>';
            }
        }

        return ($validatedFields == count($this->table));
    }

    /**
     * Load Access Data - for user permission checking, and category names
     *
     * @param   Connection $pdo
     */
    protected function loadAccessData(Connection $pdo)
    {
        if (empty($this->access['module']) || empty($this->access['action'])) {
            $this->access['protected'] = false;
            $this->details['category'] = 'Gibbon';
            return;
        }

        try {
            $data = array('module' => $this->access['module'], 'action' => $this->access['action'] );
            $sql = "SELECT gibbonAction.category, gibbonAction.entryURL
                    FROM gibbonAction
                    JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID)
                    WHERE gibbonModule.name=:module
                    AND gibbonAction.name=:action
                    ORDER BY gibbonAction.precedence ASC
                    LIMIT 1";
            $result = $pdo->executeQuery($data, $sql);
        } catch (\PDOException $e) {
        }

        if ($result->rowCount() > 0) {
            $action = $result->fetch();

            $this->access['protected'] = true;
            $this->access['entryURL'] = $action['entryURL'];

            if (empty($this->details['category'])) {
                $this->details['category'] = $action['category'];
            }
        }
    }

    /**
     * Load Relational Data
     *
     * @param   Connection $pdo
     */
    protected function loadRelationalData(Connection $pdo)
    {
        // Grab the year groups so we can translate Year Group Lists without a million queries
        if ($this->useYearGroups) {
            try {
                $sql="SELECT gibbonYearGroupID, nameShort FROM gibbonYearGroup ORDER BY sequenceNumber";
                $resultYearGroups = $pdo->executeQuery([], $sql);
            } catch (\PDOException $e) {
            }

            if ($resultYearGroups->rowCount() > 0) {
                while ($yearGroup = $resultYearGroups->fetch()) {
                    $this->yearGroups[ $yearGroup['nameShort'] ] = $yearGroup['gibbonYearGroupID'];
                }
            }
        }

        // Grab the Languages for system-wide relational data (filters)
        if ($this->useLanguages) {
            try {
                $sql="SELECT name FROM gibbonLanguage";
                $resultLanguages = $pdo->executeQuery([], $sql);
            } catch (\PDOException $e) {
            }

            if ($resultLanguages->rowCount() > 0) {
                while ($languages = $resultLanguages->fetch()) {
                    $this->languages[ $languages['name'] ] = $languages['name'];
                }
            }
        }

        // Grab the Countries for system-wide relational data (filters)
        if ($this->useCountries || $this->usePhoneCodes) {
            try {
                $sql="SELECT printable_name, iddCountryCode FROM gibbonCountry";
                $resultCountries = $pdo->executeQuery([], $sql);
            } catch (\PDOException $e) {
            }

            if ($resultCountries->rowCount() > 0) {
                while ($countries = $resultCountries->fetch()) {
                    if ($this->useCountries) {
                        $this->countries[ $countries['printable_name'] ] = $countries['printable_name'];
                    }
                    if ($this->usePhoneCodes) {
                        $this->phoneCodes[ $countries['iddCountryCode'] ] = $countries['iddCountryCode'];
                    }
                }
            }
        }

        // Grab the user-defined Custom Fields
        if ($this->useCustomFields) {
            try {
                $sql="SELECT gibbonPersonFieldID, name, type, options, required FROM gibbonPersonField where active = 'Y'";
                $resultCustomFields = $pdo->executeQuery([], $sql);
            } catch (\PDOException $e) {
            }

            if ($resultCustomFields->rowCount() > 0) {
                while ($fields = $resultCustomFields->fetch()) {
                    $this->customFields[ $fields['name'] ] = $fields;
                }

                foreach ($this->table as $fieldName => $field) {
                    $customFieldName = $this->getField($fieldName, 'name');
                    if (!isset($this->customFields[$customFieldName])) {
                        continue;
                    }

                    $type = $this->customFields[ $customFieldName ]['type'];
                    if ($type == 'varchar') {
                        $this->setField($fieldName, 'kind', 'char');
                        $this->setField($fieldName, 'type', 'varchar');
                        $this->setField($fieldName, 'length', $this->customFields[ $customFieldName ]['options']);
                    } elseif ($type == 'select') {
                        $this->setField($fieldName, 'kind', 'enum');
                        $this->setField($fieldName, 'type', 'enum');
                        $elements = explode(',', $this->customFields[ $customFieldName ]['options']);
                        $this->setField($fieldName, 'elements', $elements);
                        $this->setField($fieldName, 'length', count($elements));
                    } elseif ($type == 'text' || $type == 'date') {
                        $this->setField($fieldName, 'kind', $type);
                        $this->setField($fieldName, 'type', $type);
                    }

                    $this->setField($fieldName, 'customField', $this->customFields[ $customFieldName ]['gibbonPersonFieldID']);

                    $args = $this->getField($fieldName, 'args');
                    $args['required'] = ($this->customFields[ $customFieldName ]['required'] == 'Y');
                    $this->setField($fieldName, 'args', $args);
                }
            }
        }
    }

    /**
     * Split the SQL type eg: int(3) into a type name and length, etc.
     *
     * @param   string $fieldName
     * @param   string $columnField
     */
    protected function parseTableValueType($fieldName, $columnField)
    {
        // Split the info from inside the outer brackets, eg int(3)
        $firstBracket = mb_strpos($columnField, '(');
        $lastBracket = mb_strrpos($columnField, ')');

        $type = ($firstBracket !== false)? mb_substr($columnField, 0, $firstBracket) : $columnField;
        $details = ($firstBracket !== false)? mb_substr($columnField, $firstBracket+1, $lastBracket-$firstBracket-1) : '';

        // Cancel out if the type is not valid
        if (!isset($type)) {
            return;
        }

        $this->setField($fieldName, 'type', $type);

        if ($type == 'varchar' || $type == 'character') {
            $this->setField($fieldName, 'kind', 'char');
            $this->setField($fieldName, 'length', $details);
        } elseif ($type == 'text' || $type == 'mediumtext' || $type == 'longtext' || $type == 'blob') {
            $this->setField($fieldName, 'kind', 'text');
        } elseif ($type == 'integer' || $type == 'int' || $type == 'tinyint' || $type == 'smallint' || $type == 'mediumint' || $type == 'bigint') {
            $this->setField($fieldName, 'kind', 'integer');
            $this->setField($fieldName, 'length', $details);
        } elseif ($type == 'decimal' || $type == 'numeric' || $type == 'float' || $type == 'real') {
            $this->setField($fieldName, 'kind', 'decimal');
            $decimalParts = explode(',', $details);
            $this->setField($fieldName, 'length', $decimalParts[0] - $decimalParts[1]);
            $this->setField($fieldName, 'precision', $decimalParts[0]);
            $this->setField($fieldName, 'scale', $decimalParts[1]);
        } elseif ($type == 'enum') {
            // Grab the CSV enum elements as an array
            $elements = explode(',', str_replace("'", "", $details));
            $this->setField($fieldName, 'elements', $elements);
            $this->setField($fieldName, 'length', count($elements));

            if ($details == "'Y','N'" || $details == "'N','Y'") {
                $this->setField($fieldName, 'kind', 'yesno');
            } else {
                $this->setField($fieldName, 'kind', 'enum');
            }

            if (empty($this->getField($fieldName, 'desc'))) {
                $this->setField($fieldName, 'desc', implode(', ', $elements));
            }
        } else {
            $this->setField($fieldName, 'kind', $type);
        }

        if ($this->isFieldRelational($fieldName)) {
            $this->setField($fieldName, 'kind', 'char');
            $this->setField($fieldName, 'length', 50);
        }
    }

    protected function setValueTypeByFilter($fieldName)
    {
        $type = '';
        $kind = '';

        switch ($this->getField($fieldName, 'filter')) {
            case 'string':  $type = 'text'; $kind = 'text'; break;
            case 'date':    $type = 'date'; $kind = 'date'; break;
            case 'url':     $type = 'text'; $kind = 'text'; break;
            case 'email':   $type = 'text'; $kind = 'text'; break;
        }

        $this->setField($fieldName, 'type', $type);
        $this->setField($fieldName, 'kind', $kind);
    }

    /**
     * Switch the active table - one table is handled at a time.
     *
     * @param string $tableName
     */
    public function switchTable($tableName)
    {
        if (isset($this->tables[$tableName])) {
            // Intersect only the fields relative to this table
            $fields = array_flip($this->tables[$tableName]['fields']);
            $this->table = array_intersect_key($this->fields, $fields);

            $this->primaryKey = $this->tables[$tableName]['primaryKey'] ?? '';
            $this->uniqueKeys = $this->tables[$tableName]['uniqueKeys'] ?? [];
            $this->details['table'] = $tableName;

            $this->keyFields = array_unique(array_reduce($this->uniqueKeys, function ($group, $item) {
                $keys = is_array($item)? $item : [$item];
                return array_merge($group, $keys);
            }, []));
        }
    }

    public function getCurrentTable()
    {
        return $this->details['table'];
    }

    /**
     * Get Detail
     *
     * @param   string  key - name of the detail to retrieve
     * @param   string  default - an optional value to return if key doesn't exist
     * @return  var
     */
    public function getDetail($key, $default = "")
    {
        return (isset($this->details[$key]))? $this->details[$key] : $default;
    }

    /**
     * Get Access Detail
     *
     * @param   string  key - name of the access key to retrieve
     * @param   string  default - an optional value to return if key doesn't exist
     * @return  var
     */
    public function getAccessDetail($key, $default = "")
    {
        return (isset($this->access[$key]))? $this->access[$key] : $default;
    }

    /**
     * Get Primary Key
     *
     * @return  array   2D array of available keys to sync with
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Get Keys
     *
     * @return  array   2D array of available keys to sync with
     */
    public function getUniqueKeys()
    {
        return $this->uniqueKeys;
    }

    /**
     * Get Key Fields
     *
     * @return  array   2D array of available key fields
     */
    public function getUniqueKeyFields()
    {
        return (isset($this->keyFields))? $this->keyFields : [];
    }

    /**
     * Get the tables used in this import. All tables used must be locked.
     *
     * @return  array   2D array of table names used in this import
     */
    public function getTables()
    {
        return array_keys($this->tables);
    }

    public function getPrimaryTable()
    {
        return key($this->tables);
    }

    /**
     * Get Table Fields
     *
     * @return  array   2D array of table field names used in this import
     */
    public function getTableFields()
    {
        return (isset($this->table))? array_keys($this->table) : [];
    }

    /**
     * Get All Fields used in the import, regardless of table
     *
     * @return  array   2D array of table field names used in this import
     */
    public function getAllFields()
    {
        return (isset($this->fields))? array_keys($this->fields) : [];
    }

    /**
     * Get Field Information by Key
     *
     * @param   string  Field Name
     * @param   string  Key to retrieve
     * @param   string  Default value to return if key doesn't exist
     * @return  var
     */
    public function getField($fieldName, $key, $default = "")
    {
        if (isset($this->fields[$fieldName][$key])) {
            return $this->fields[$fieldName][$key];
        } elseif (isset($this->fields[$fieldName]['args'][$key])) {
            return $this->fields[$fieldName]['args'][$key];
        } else {
            return $default;
        }
    }

    /**
     * Set Field Information by Key
     *
     * @param   string  Field Name
     * @param   string  Key to retrieve
     * @param   string  Value to set
     */
    protected function setField($fieldName, $key, $value)
    {
        if (isset($this->fields[$fieldName])) {
            $this->fields[$fieldName][$key] = $value;
        } else {
            $this->fields[$fieldName] = array( $key => $value );
        }
    }

    /**
     * Compares the value type, length and properties with the expected values for the table column
     *
     * @param   string  Field name
     * @param   mixed   Value to validate
     * @return  bool    true if the value checks out
     */
    public function filterFieldValue($fieldName, $value)
    {
        $value = trim($value);
        $defaultValue = $this->getField($fieldName, 'null') == 'YES' ? null : '';
        
        $filter = $this->getField($fieldName, 'filter');
        $strvalue = mb_strtoupper($value);

        switch ($filter) {

            case 'html': // Filter valid tags? requires db connection, which we dont store :(
                break;

            case 'url':
                if (!empty($value)) {
                    $value = filter_var($value, FILTER_SANITIZE_URL);
                }
                break;

            case 'email':
                if (mb_strpos($value, ',') !== false || mb_strpos($value, '/') !== false || mb_strpos($value, ' ') !== false) {
                    $emails = preg_split('/[\s,\/]*/u', $value);
                    $value = (isset($emails[0]))? $emails[0] : '';
                }

                if (!empty($value)) {
                    $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                }
                break;

            case 'yesno': // Translate generic boolean values into Y or N, watch the === for TRUE/FALSE, otherwise it breaks!
                if ($strvalue == 'TRUE' || $strvalue == 'YES' || $strvalue == 'Y') {
                    $value = 'Y';
                } elseif ($value === false || $strvalue == 'FALSE' || $strvalue == 'NO' || $strvalue == 'N' || $strvalue == '') {
                    $value = 'N';
                }
                break;

            case 'date': // Handle various date formats
                if (!empty($value)) { // && preg_match('/(^\d{4}[-]\d{2}[-]\d{2}$)/u', $value) === false
                    $date = strtotime($value);
                    $value = date('Y-m-d', $date);
                }
                if (empty($value) || $value == '0000-00-00' || preg_match('/(^\d{4}[-]\d{2}[-]\d{2}$)/u', $value) === false) {
                    $value = null;
                }
                break;

            case 'time': // Handle various time formats
                if (!empty($value)) { // && preg_match('/(^\d{2}[:]\d{2}$)/u', $value) === false
                    $time = strtotime($value);
                    $value = date('H:i:s', $time);
                }
                if (empty($value) || $value == '00:00:00' || preg_match('/(^\d{2}[:]\d{2}$)/u', $value) === false) {
                    $value = null;
                }
                break;

            case 'timestamp':
                if (!empty($value)) {
                    $time = strtotime($value);
                    $value = date('Y-m-d H:i:s', $time);
                }
                if (empty($value) || $value == '0000-00-00 00:00:00' || preg_match('/(^\d{4}[-]\d{2}[-]\d{2}[ ]+\d{2}[:]\d{2}[:]\d{2}$)/u', $value) === false) {
                    $value = null;
                }

                break;

            case 'schoolyear': // Change school years formated as 2015-16 to 2015-2016
                if (preg_match('/(^\d{4}[-]\d{2}$)/u', $value) > 0) {
                    $value = mb_substr($value, 0, 5) . mb_substr($value, 0, 2) . mb_substr($value, 5, 2);
                }
                break;

            case 'gender':  // Handle various gender formats
                $strvalue = str_replace('.', '', $strvalue);
                if ($strvalue == 'M' || $strvalue == 'MALE' || $strvalue == 'MR') {
                    $value = 'M';
                } elseif ($strvalue == 'F' || $strvalue == 'FEMALE' || $strvalue == 'MS' || $strvalue == 'MRS' || $strvalue == 'MISS') {
                    $value = 'F';
                } elseif (empty($value)) {
                    $value = 'Unspecified';
                } else {
                    $value = 'Other';
                }
                break;

            case 'numeric':
                $value = !empty($value) ? preg_replace("/[^0-9]/u", '', $value) : $defaultValue;
                break;

            case 'phone':   // Handle phone numbers - strip all non-numeric chars
                $value = !empty($value) ? preg_replace("/[^0-9,\/]/u", '', $value) : $defaultValue;

                if (mb_strpos($value, ',') !== false || mb_strpos($value, '/') !== false || mb_strpos($value, ' ') !== false) {
                    $numbers = preg_split("/[,\/]*/u", $value);
                    $value = isset($numbers[0])? $numbers[0] : $defaultValue;
                }
                break;

            case 'phonecode':
                $value = preg_replace("/[^0-9]/u", '', $value);
                break;

            case 'phonetype': // Handle TIS phone types
                if (mb_stripos($value, 'Mobile') !== false || mb_stripos($value, 'Cellular') !== false) {
                    $value = 'Mobile';
                } elseif (mb_stripos($value, 'Home') !== false) {
                    $value = 'Home';
                } elseif (mb_stripos($value, 'Office') !== false || mb_stripos($value, 'Business') !== false) {
                    $value = 'Work';
                } else {
                    $value = 'Other';
                }
                break;

            case 'country':
                if ($strvalue == "MACAU") {
                    $value = 'Macao';
                }
                if ($strvalue == "HK") {
                    $value = 'Hong Kong';
                }
                if ($strvalue == "USA") {
                    $value = 'United States';
                }
                $value = ucfirst($value);
                break;

            case 'language': // Translate a few languages to gibbon-specific use
                if ($strvalue == "CANTONESE") {
                    $value = 'Chinese (Cantonese)';
                }
                if ($strvalue == "MANDARIN") {
                    $value = 'Chinese (Mandarin)';
                }
                if ($strvalue == "CHINESE") {
                    $value = 'Chinese (Mandarin)';
                }
                $value = ucfirst($value);
                break;

            case 'ethnicity':
                $value = ucfirst($value);
                break;

            case 'relation':
                if ($strvalue == "MOTHER") {
                    $value = 'Parent';
                } elseif ($strvalue == "FATHER") {
                    $value = 'Parent';
                } elseif ($strvalue == "SISTER") {
                    $value = 'Other Relation';
                } elseif ($strvalue == "BROTHER") {
                    $value = 'Other Relation';
                } else {
                    $value = 'Other';
                }
                break;

            case 'yearlist': // Handle incoming blackbaud Grade Level's Allowed, turn them into Year Group IDs
                if (!empty($value)) {
                    $yearGroupIDs = [];
                    $yearGroupNames = explode(',', $value);

                    foreach ($yearGroupNames as $gradeLevel) {
                        $gradeLevel = trim($gradeLevel);
                        if (isset($this->yearGroups[$gradeLevel])) {
                            $yearGroupIDs[] = $this->yearGroups[$gradeLevel];
                        } elseif ($key = array_search($gradeLevel, $this->yearGroups)) {
                            $yearGroupIDs[] = $this->yearGroups[$key];
                        }
                    }

                    $value = implode(',', $yearGroupIDs);
                }
                break;

            case 'status': // Transform positive values into Full and negative into Left
                if ($strvalue == 'FULL' || $strvalue == 'YES' || $strvalue == 'Y' || $value === '1') {
                    $value = 'Full';
                } elseif ($strvalue == 'LEFT' || $strvalue == 'NO' || $strvalue == 'N' || $value == '' || $value === '0') {
                    $value = 'Left';
                } elseif ($strvalue == 'EXPECTED') {
                    $value = 'Expected';
                } elseif ($strvalue == 'PENDING APPROVAL') {
                    $value = 'Pending Approval';
                }
                break;

            case 'csv':
                break;

            case 'customfield':
                break;

            case 'string':
            default:
                $value = strip_tags($value);
        }

        $kind = $this->getField($fieldName, 'kind');

        switch ($kind) {
            case 'integer': $value = !empty($value) ? intval($value) : $defaultValue; break;
            case 'decimal': $value = !empty($value) ? floatval($value) : $defaultValue; break;
            case 'boolean': $value = !empty($value) ? boolval($value) : $defaultValue; break;
        }

        if ($strvalue == 'NOT REQUIRED' || $value == 'N/A') {
            $value = '';
        }

        return $value;
    }

    /**
     * Compares the value type, legth and properties with the expected values for the table column
     *
     * @param   string  Field name
     * @param   var     Value to validate
     * @return  bool    true if the value checks out
     */
    public function validateFieldValue($fieldName, $value)
    {
        if (!$this->validated) {
            return false;
        }

        if ($this->isFieldRelational($fieldName)) {
            return true;
        }

        // Validate based on filter type (from args)
        $filter = $this->getField($fieldName, 'filter');

        switch ($filter) {

            case 'url':         if (!empty($value) && filter_var($value, FILTER_VALIDATE_URL) === false) {
                return false;
            } break;
            case 'email':       //if (!empty($value) && filter_var( $value, FILTER_VALIDATE_EMAIL) === false) return false;
                                break;

            case 'country':     if (!empty($value) && !isset($this->countries[ $value ])) {
                return false;
            } break;

            case 'language':    if (!empty($value) && !isset($this->languages[ $value ])) {
                return false;
            } break;

            case 'phonecode':   if (!empty($value) && !isset($this->phoneCodes[ $value ])) {
                return false;
            } break;

            case 'schoolyear':  if (preg_match('/(^\d{4}[-]\d{4}$)/u', $value) > 1) {
                return false;
            } break;

            case 'nospaces':    if (preg_match('/\s/u', $value) > 0) {
                return false;
            } break;

            default:            if (mb_substr($filter, 0, 1) == '/') {
                if (preg_match($filter, $value) == false) {
                    return false;
                }
            };
        }

        // Validate based on value type (from db)
        $kind = $this->getField($fieldName, 'kind');

        switch ($kind) {
            case 'char':    $length = $this->getField($fieldName, 'length');
                            if (mb_strlen($value) > $length) {
                                return false;
                            }
                            break;

            case 'text':    break;

            case 'integer': $value = intval($value);
                            $length = $this->getField($fieldName, 'length');
                            if (mb_strlen($value) > $length) {
                                return false;
                            }
                            break;

            case 'decimal': $value = floatval($value);
                            $length = $this->getField($fieldName, 'length');

                            if (mb_strpos($value, '.') !== false) {
                                $number = mb_strstr($value, '.', true);
                                if (mb_strlen($number) > $length) {
                                    return false;
                                }
                            } else {
                                if (mb_strlen($value) > $length) {
                                    return false;
                                }
                            }
                            break;

            case 'yesno':   if ($value != 'Y' && $value != 'N') {
                return false;
            }
                            break;

            case 'boolean': if (!is_bool($value)) {
                return false;
            }
                            break;

            case 'enum':    $elements = $this->getField($fieldName, 'elements');
                            if (!in_array($value, $elements)) {
                                return false;
                            }
                            break;
        }

        return $value;
    }

    /**
     * Has the ImportType been checked against the databate table for field validity?
     *
     * @return  bool true if the importType has been validated
     */
    public function isValid()
    {
        return $this->validated;
    }

    /**
     * Is Using Custom Fields
     *
     * @return  bool
     */
    public function isUsingCustomFields()
    {
        return $this->useCustomFields || $this->useSerializedFields;
    }

    /**
     * Is Field Relational
     *
     * @param   string  Field name
     * @return  bool true if marked as a required field
     */
    public function isFieldRelational($fieldName)
    {
        return (isset($this->fields[$fieldName]['relationship']) && !empty($this->fields[$fieldName]['relationship']));
    }

    /**
     * Is Field Linked to another field (for relational reference)
     *
     * @param   string  Field name
     * @return  bool true if marked as a linked field
     */
    public function isFieldLinked($fieldName)
    {
        return (isset($this->fields[$fieldName]['args']['linked']))? $this->fields[$fieldName]['args']['linked'] : false;
    }

    /**
     * Is Field Read Only (for relational reference)
     *
     * @param   string  Field name
     * @return  bool true if marked as a read only field
     */
    public function isFieldReadOnly($fieldName)
    {
        $readonly = $this->fields[$fieldName]['args']['readonly'] ?? false;
        return is_array($readonly) ? in_array($this->getCurrentTable(), $readonly): $readonly;
    }

    /**
     * Is Field Hidden
     *
     * @param   string  Field name
     * @return  bool true if marked as a hidden field (or is linked)
     */
    public function isFieldHidden($fieldName)
    {
        if ($this->isFieldLinked($fieldName)) {
            return true;
        }

        $hidden = $this->fields[$fieldName]['args']['hidden'] ?? false;
        return is_array($hidden) ? in_array($this->getCurrentTable(), $hidden): $hidden;
    }

    /**
     * Is Field Required
     *
     * @param   string  Field name
     * @return  bool true if marked as a required field
     */
    public function isFieldRequired($fieldName)
    {
        $required = $this->fields[$fieldName]['args']['required'] ?? false;
        return is_array($required) ? in_array($this->getCurrentTable(), $required): $required;
    }

    /**
     * Is Field Required
     *
     * @param   string  Field name
     * @return  bool true if marked as a required field
     */
    public function isFieldUniqueKey($fieldName)
    {
        return (in_array($fieldName, $this->keyFields)) ;
    }

    /**
     * Create a human friendly representation of the field value type
     *
     * @param   string  Field name
     * @return  string
     */
    public function readableFieldType($fieldName)
    {
        $filter = $this->getField($fieldName, 'filter');
        $kind = $this->getField($fieldName, 'kind');
        $length = $this->getField($fieldName, 'length');

        if ($this->isFieldRelational($fieldName)) {
            extract($this->getField($fieldName, 'relationship'));
            $field = is_array($field) ? current($field) : $field;

            $helpText = __('Each {name} value should match an existing {field} in {table}.', [
                'name' => $this->getField($fieldName, 'name'),
                'field' => $field,
                'table' => !empty($join) ? $join : $table,
            ]);

            return '<abbr title="'.$helpText.'">'.__('Text').' ('.$field.')</abbr>';
        }

        switch ($filter) {
            case 'email':
                return __('Email ({number} chars)', ['number' => $length]);

            case 'url':
                return __('URL ({number} chars)', ['number' => $length]);

            case 'numeric':
                return __('Number');
        }

        switch ($kind) {
            case 'char':
                return __('Text ({number} chars)', ['number' => $length]);

            case 'text':
                return $filter != 'string' ? __('Text').' ('.$filter.')' : __('Text');

            case 'integer':
                return __('Number ({number} digits)', ['number' => $length]);

            case 'decimal':
                $scale = $this->getField($fieldName, 'scale');
                $format = str_repeat('0', $length) .".". str_repeat('0', $scale);
                return __('Decimal ({number} format)', ['number' => $format]);

            case 'date':
                return __('Date');

            case 'yesno':
                return __('Y or N');

            case 'boolean':
                return __('True or False');

            case 'enum':
                $options = implode('<br/>', $this->getField($fieldName, 'elements'));
                return '<abbr title="'.$options.'">'.__('Options').'</abbr>';

            default:
                return __(ucfirst($kind));
        }
        
        return '';
    }

    /**
     * Returns the value of a dynamic function name supplied by the importType field
     *
     * @param   string  Field name
     * @return  var|NULL
     */
    public function doImportFunction($fieldName)
    {
        $method = $this->getField($fieldName, 'function');

        if (!empty($method) && method_exists($this, 'userFunc_'.$method)) {
            return call_user_func(array($this, 'userFunc_'.$method));
        } else {
            return null;
        }
    }

    /**
     * Custom function for run-time generation of passwords on import
     *
     * @return  string  Random password, based on default Gibbon function
     */
    protected function userFunc_generatePassword()
    {
        return randomPassword(10);
    }

    /**
     * Custom function for run-time generation of timestamps
     *
     * @return  string  current timestamp
     */
    protected function userFunc_timestamp()
    {
        return date('Y-m-d H:i:s', time());
    }
}
