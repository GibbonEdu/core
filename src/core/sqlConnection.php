<?php
/*
 * sql Connection 
 *
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
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	core
*/
/**
 * Namespace Gibbon
 */
namespace Gibbon\core ;

use Gibbon\core\logger ;
use PDO ;
/**
 * sql Connection
 *
 * @version	11th September 2016
 * @since	8th April 2016
 * @author	Craig Rayner
 */
class sqlConnection
{
	/**
	 * PDO Object
	 */
	protected	$pdo = NULL ;
	
	/**
	 * Connection Success
	 */
	private		$success = false ;
	
	/**
	 * Query Success
	 */
	private		$querySuccess = false ;
	
	/**
	 * PDOStatement 
	 */
	private		$result = NULL ;
	
	/**
	 * array
	 */
	private		$data = array() ;
	
	/**
	 * String
	 */
	private		$query = NULL ;
	
	/**
	 * String
	 */
	private		$error = NULL ;
	
	/**
	 * String
	 */
	private		$table = NULL ;
	
	/**
	 * String
	 */
	private		$identifier = NULL ;
	
	/**
	 * String
	 */
	private		$where = NULL ;
	
	/**
	 * String
	 */
	private		$version ;

	/**
	 * Construct
	 *
	 * @version	16th August 2016
	 * @since	8th April 2016
	 * @param	boolean		$connect Execute Connection
	 * @param	string		$message Overwrite default Error Message
	 * @param	Gibbon\config
	 * @return	Object		Gibbon\sqlConnection
	 */
	public function __construct($connect = true, $message = null, config $config = null)
	{	
		if (! $config instanceof config)
			$config = new config();
		if ($connect)
			return $this->generateConnection($config->get('dbHost'), $config->get('dbName'), $config->get('dbUser'), $config->get('dbPWord'), $message);
		return $this ;
	}

	/**
	 * generate Connection
	 *
	 * @version	16th August 2016
	 * @since	17th April 2016
	 * @param	string		$dbHost Server Address:port
	 * @param	string		$dbName Database Name
	 * @param	string		$dbUser Database User Name
	 * @param	string		$dbPWord Database Password
	 * @param	string		$message Overwrite default Error Message
	 * @return	Object		Gibbon\sqlConnection
	 */
	 private function generateConnection($dbHost, $dbName, $dbUser, $dbPWord, $message = NULL)
	 {
		if ($this->pdo instanceof PDO)
			return $this ;
		$this->pdo = NULL;
		$this->error = NULL;
		$this->success = true;
		try {
			$this->pdo = new PDO("mysql:host=".$dbHost.";dbname=".$dbName.";charset=utf8", $dbUser, $dbPWord);
			$this->setSQLMode();
		} catch ( PDOException $e) 
		{
			if ($message === NULL)
				$message = $e->getMessage();
			$this->success = false;
			$this->error = $message;
			fileAnObject(array('Failed to generate Connection', 'Error', 'PDO', array('error' => $message, 'raw-error'=>$e->getMessage())), 'SQL Failure');
		}
		$this->result = NULL;
		$this->query = NULL;
		
		//remove for production
		$session = new session();
		$session->plus('SQLConnection');
		$caller = debug_backtrace(false);
		if (mb_strpos($caller[1]['file'], 'src/controller/gibbon.php') !== false)
			return $this;
		$message = 'sql Connection: '.$caller[0]['line'].': '.$caller[0]['file']."\n".$caller[1]['line'].': '.$caller[1]['file']."\n".$caller[2]['line'].': '.$caller[2]['file'];
		logger::__($message, 'Debug', 'SQL', array(), $this);
		// ################################################
		return $this;
	}

	/**
	 * get Connection
	 *
	 * Only required for backwards compatibilty in Gibbon.
	 * @version	8th April 2016
	 * @since	8th April 2016
	 * @return	Object		PDO Connection
	 */
	public function getConnection()
	{
		if ($this->pdo === NULL)
			dump($this, true, true);
		return $this->pdo;
	}

	/**
	 * get Connection Success
	 *
	 * @version	12th April 2016
	 * @since	12th April 2016
	 * @return	boolean		Was the database connection successful
	 */
	public function getSuccess()
	{
		return $this->success;
	}

	/**
	 * get Error
	 *
	 * @version	19th April 2016
	 * @since	19th April 2016
	 * @return	string		Error Message
	 */
	public function getError()
	{
		return $this->error ;
	}

	/**
	 * get Query Success
	 *
	 * @version	12th April 2016
	 * @since	12th April 2016
	 * @return	boolean		Was the query execution successful
	 */
	public function getQuerySuccess()
	{
		return $this->querySuccess;
	}

	/**
	 * Execute Query
	 *
	 * @version	12th August 2016
	 * @since	12th April 2016
	 * @param	array		$data Data Information
	 * @param	string		$query SQL Query
	 * @param	string		$message Overwrite default Error Message
	 * Special Circumstances
	 * NULL = Do not generate any error message.
	 * _ (underscore) Generate Default Error Message
	 * @return	Object	PDO Result
	 */
	public function executeQuery($data, $query, $message = NULL)
	{

		$config = new config();
		$prefix = substr($config->get('dbPrefix'), 0, 6);
		$this->generateConnection($config->get('dbHost'), $config->get('dbName'), $config->get('dbUser'), $config->get('dbPWord'), $message);
		$this->querySuccess = true ;
		$this->error = NULL;
		if (empty($data)) $data  = array();
		$this->data = $data ;
		$this->query = str_replace('#_', $prefix, $query);
		try {
			if (! $this->result instanceof PDOStatement || $this->result->queryString != $this->query)
				$this->result = $this->getConnection()->prepare($this->query);
			$this->lastQuery = $this->query ;
			$this->result->execute($this->data);
		} catch ( PDOException $e) 
		{
			$trace = $e->getTrace();
			$this->error = $trace[0]['line'].': '.$trace[0]['file'] . '  ' . $e->getMessage();
			$this->querySuccess = false;
			if (! is_null($message))
				echo str_replace(array('_', '{message}'), array("<div class='error'>\n" . $this->error . "\n</div>\n", $this->error), $message).'<br/>A detailed error is placed in the temporary directory.';
			fileAnObject(array('Failed to execute a query', 'Error', 'PDO', array('error'=>$this->error, 'query' => $query, 'data' => $data)), 'SQL Failure');
			$this->result = null;
			return $this->result ;
		} catch ( \Exception $e) 
		{
			$trace = $e->getTrace();
			if (isset($trace[0]['line']) && isset($trace[0]['file']))
				$this->error = $trace[0]['line'].': '.$trace[0]['file'] . '  ' . $e->getMessage();
			else
				$this->error = $e->getMessage();
			$this->querySuccess = false;
			if ($message !== NULL)
				echo str_replace(array('_', '{message}'), array("<div class='error'>\n" . $this->error . "\n</div>\n", $this->error), $message);
			fileAnObject(array('Failed to execute a query', 'Error', 'PDO', array('error'=>$this->error, 'query' => $query, 'data' => $data)), 'SQL Failure');
			$this->result = NULL;
			return $this->result ;
		}

		return $this->result ;
	}

	/**
	 * get Result
	 *
	 * @version	14th April 2016
	 * @since	14th April 2016
	 * @return	Object		PDO Result 
	 */
	public function getResult()
	{
		return $this->result ;
	}

	/**
	 * get Version
	 *
	 * @version	16th April 2016
	 * @since	16th April 2016
	 * @return	string		MySQL Version
	 */
	public function getVersion()
	{
		return $this->pdo->query('SELECT VERSION()')->fetchColumn();
	}

	/**
	 * Install Bypass
	 *
	 * @version	17th April 2016
	 * @since	17th April 2016
	 * @param	string		$dbHost Server Address:port
	 * @param	string		$dbName Database Name
	 * @param	string		$dbUser Database User Name
	 * @param	string		$dbPWord Database Password
	 * @param	string		$message Overwrite default Error Message
	 * @return	Object		Gibbon\sqlConnection
	 */
	public function installBypass($dbHost, $dbName, $dbUser, $dbPWord, $message = NULL)
	{
		$dbNameClean = "`" . str_replace("`", "``", $dbName) . "`";
		$this->success = true;
		$this->pdo = NULL;
		try
		{
			$this->pdo = new PDO("mysql:host=".$dbHost.";charset=utf8", $dbUser, $dbPWord );
			$this->setSQLMode();
		}
		catch( PDOException $e)
		{
			if ($message === NULL)
				$message = $e->getMessage();
			echo $message;
			$this->success = false;
			$this->error = $e->getMessage();
			logger::__('Failed to connect to the database', 'Error', 'PDO', array('error'=>$this->error));
			return $this ;
		}
		$result = $this->pdo->query("CREATE DATABASE IF NOT EXISTS ".$dbNameClean." DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_unicode_ci");
		$result = $this->pdo->query("USE ". $dbNameClean);
		return $this;
	}
	 
	/**
	 * set SQL Mode
	 *
	 * @version	9th September 2016
	 * @since	18th April 2016
	 * @return	void
	 */
	private function setSQLMode()
	{
		$config = new config();
		$setting = $config->get('setting');
		if (isset($setting['System']['installtype']) && $setting['System']['installtype'] == 'Production')
			$this->getConnection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);		// Production
		else
			$this->getConnection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);   //Testing and Development
		$this->getConnection()->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$this->getConnection()->setAttribute(PDO::ATTR_AUTOCOMMIT, true); 
		$this->version = $this->getVersion();
		if ($this->version > '5.7')  //Default for 5.7.x is STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_AUTO_CREATE_USER
			$result = $this->getConnection()->prepare("SET SESSION `sql_mode` = ''");
		elseif ($this->version > '5.6')  // Default for 5.6.x is NO_ENGINE_SUBSTITUTION
			$result = $this->getConnection()->prepare("SET SESSION `sql_mode` = ''");
		else // Default for < 5.6 is ''
			$result = $this->getConnection()->prepare("SET SESSION `sql_mode` = ''");
		$result->execute(array());
	}

	/**
	 * Last Insert ID
	 *
	 * @version	29th April 2016
	 * @since	29th April 2016
	 * @return	void
	 */
	public function lastInsertId()
	{
		return $this->pdo->lastInsertId();
	}

	/**
	 * set SQL Variables
	 *
	 * @version	30th April 2016
	 * @since	30th April 2016
	 * @param	string		$table The full db Table Name
	 * @param	string		$identifier The db table identifier name
	 * @param	string		$where Overwrite standard where this this statement
	 * @return	void
	 */
	public function setSQLVariables($table, $identifier, $where = NULL)
	{
		$this->table = $table ;
		$this->identifier = $identifier ;
		$this->where = $where ;
	}

	/**
	 * set
	 *
	 * @version	11th September 2016
	 * @since	30th April 2016
	 * @param	object		$fields Field Values
	 * @param	boolean		$forceInsert	Use the Identifier set as an INsert, not an update.
	 * @return	boolean		Good/Fail
	 */
	public function set($fields, $forceInsert = false)
	{
		$this->querySuccess = false;
		$this->insert = false;
		if (is_null($this->table) or is_null($this->identifier))
		{
			$this->error = 'The SQL Class table and identifier have not been correctly set.';
			$this->querySuccess = false ;
			return false ;
		}
		$identifier = $this->identifier ;
		if (! $forceInsert && isset($fields->$identifier) && intval($fields->$identifier) > 0)
			return $this->update($fields);
		else
		{
			if (! $forceInsert)
				unset($fields->$identifier);
			return $this->insert($fields, $forceInsert);
		}
	}

	/**
	 * get
	 *
	 * @version	21st May 2016
	 * @since	30th April 2016
	 * @param	integer		$id Identifier Value
	 * @param	string		$oName Object Name 
	 * Used too inset result into this Object
	 * @param	string		$where Overwrite standard where clause
	 * @todo	Where Clause overwrite needs work.
	 * @return	mixed		Object/false
	 */
	public function get($id, $oName = NULL, $where = NULL)
	{
		$id = intval($id);
		if (is_null($this->table) or is_null($this->identifier))
		{
			$this->error = 'The SQL Class table and identifier have not been correctly set.';
			$this->querySuccess = false ;
			return false ;
		}
		$sql = "SELECT * FROM `".$this->table."` ";
		if ($where === NULL)
			$this->where = " WHERE `".$this->identifier."` = " . intval($id);
		else
			$this->where = $where ;
		$sql .= $this->where;
		$result = $this->executeQuery(array(), $sql);
		if (! $this->querySuccess) {
			return false ;
		}
		if ($result->rowCount()!=1) {
			$this->error = sprintf("The specified record from %s cannot be found.", $this->table) ;
			$this->querySuccess = false ;
			logger::__("The specified record cannot be found.", 'Warning', 'PDO', array('table'=>$this->table));
			return false;
		} 

		if ($oName === NULL)
			return $result->fetchObject();	
		else	
			return $result->fetchObject( $oName );	
	}

	/**
	 * update
	 *
	 * @version	5th July 2016
	 * @since	30th April 2016
	 * @param	object		$fields Field Values
	 * @return	boolean		Good/Fail
	 */
	private function update($fields)
	{
		$sql = "UPDATE `".$this->table."`
			SET ";
		$data = array();
		$identifier = $this->identifier ;
		foreach ((array)$fields as $name=>$value)
		{
			if ($name != $this->identifier) 
			{
				if (is_null($value))
				{
					$sql .= " `".$name."` = NULL,";
				}
				else
				{
					$sql .= " `".$name."` = :".$name.",";
					$data[':'.$name] = $value;
				}
			}
		}
		$sql = rtrim($sql, ',');
		$sql .= " WHERE `".$this->identifier."` = :" . $this->identifier;
		$data[':'.$this->identifier] = $fields->$identifier;
		$this->lastQuery = $sql ;
		$result = $this->executeQuery($data, $sql);
		return $this->querySuccess;
	}

	/**
	 * insert
	 *
	 * @version	11th September 2016
	 * @since	30th April 2016
	 * @param	object		$fields Field Values
	 * @param	boolean	$forceInsert	Use the Identifier set as the Insert.
	 * @return	boolean		Good/Fail
	 */
	private function insert($fields, $forceInsert = false)
	{
		$values = '';
		$columns = '';
		$data = array();
		foreach ((array)$fields as $name=>$value)
		{
			if ($name != $this->identifier || $forceInsert) 
			{
				if (is_null($values))
				{
					$values .= ' NULL,';
				}
				else
				{
					$values .= " :".$name.",";
					$data[':'.$name] = $value;
				}
				$columns .= "`".$name."`,";
			}
		}
		$columns = rtrim($columns, ',');
		$values = rtrim($values, ',');
		$sql = "INSERT INTO `".$this->table."` (".$columns.") VALUES (".$values.")";
		$this->lastQuery = $sql ;
		$this->executeQuery($data, $sql);
		$this->insert = true;
		return $this->querySuccess;
	}

	/**
	 * delete
	 *
	 * @version	30th April 2016
	 * @since	30th April 2016
	 * @param	integer		$id  Identifier Value
	 * @return	boolean		Good/Fail
	 */
	public function delete($id)
	{
		if (is_null($this->table) or is_null($this->identifier))
		{
			$this->error = 'The SQL Class table and identifier have not been correctly set.';
			$this->querySuccess = false ;
			return false ;
		}
		$sql = "DELETE FROM `".$this->table."` ";
		$this->where = " WHERE `".$this->identifier."` = " . intval($id);
		$sql .= $this->where;
		$this->lastQuery = $sql ;
		$result = $this->executeQuery(array(), $sql);
		if (! $this->querySuccess) {
			return false ;
		}
		if ($result->rowCount() != 1){
			$this->error = 'Delete failed to delete the correct record from ' . $this->table . '!';
			logger::__('Delete failed to delete the correct record from ' . $this->table . '!', 'Warning', 'PDO');
			$this->querySuccess = false;
			return false ;
		}
		return true ;
	}

	/**
	 * Query
	 *
	 * @version	2nd May 2016
	 * @since	2nd May 2016
	 * @param	string		$query SQL Query
	 * @param	string		$message Overwrite default Error Message
	 * Special Circumstances
	 * NULL = Do not generate any error message.
	 * _ (underscore) Generate Default Error Message
	 * @return	Object	PDO Result
	 */
	public function query($query, $message = NULL)
	{
		return $this->executeQuery(array(), $query, $message);
	}

	/**
	 * get Record from ID
	 *
	 * Uses an array to capture ID Name and Value. Table Name  = IDName less the ID.
	 * @version	30th April 2016
	 * @since	30th April 2016
	 * @param	array		$id Identifier Name=>Value
	 * @param	string		$oName  Class Name
	 * @return	mixed		Object/false
	 */
	public function getRecordFromID($id, $oName = NULL)
	{
		$identifier = trim(key($id));
		$table = substr($identifier, 0, -2);
		
		$sql = "SELECT * FROM `".$table."` ";
		$sql .= " WHERE `".$identifier."` = :" . $identifier;
		$result = $this->executeQuery($id, $sql);
		if (! $this->querySuccess) {
			return false ;
		}
		if ($result->rowCount()!=1) {
			$this->error = "The specified record cannot be found." ;
			$this->querySuccess = false ;
			return false;
		} 

		if ($oName === NULL)
			return $result->fetchObject();	
		else	
			return $result->fetchObject( $oName );	
	}

	/**
	 * get Enum
	 *
	 * @version	6th September 2016
	 * @since	19th May 2016
	 * @param	string		$table	Table Name
	 * @param	string		$field	Field Name
	 * @return	array		Enumerated Details
	 */
	public function getEnum($table, $field)
	{
		$x = array();
		$config = new config();
		$data = array('dbTable' => $table, 'dbName' => $config->get('dbName'), 'dbField' => $field);
		$sql = "SELECT COLUMN_TYPE
  			FROM INFORMATION_SCHEMA.COLUMNS
 			WHERE table_name = :dbTable
  				AND table_schema = :dbName
  				AND column_name LIKE :dbField";
		$this->result = $this->executeQuery($data, $sql);
		$enum = substr($this->result->fetchColumn(), 5, -1);
		$x = explode(',', $enum);
		foreach($x as $q=>$w)
		{
			$x[$q] = trim($x[$q], "'");
			$x[$q] = trim($x[$q], "'");
		}
		return $x;
	}
	public function beginTransaction()
	{
		$this->pdo->beginTransaction();
	}
	public function commit()
	{
		$this->pdo->commit();
	}
}
