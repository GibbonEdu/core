<?php
/**
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
 * @package		Gibbon
 * @subpackage	Record
*/
/**
 */
namespace Gibbon\Record ;

use Gibbon\core\view ;
use Gibbon\core\helper ;
use Gibbon\core\logger ;
use Gibbon\core\fileManager ;

/**
 * Record Manager
 *
 * Provides a framework and default methods to manage SQL records with individual Tables and sub Tables.
 * @version	11th September 2016
 * @since	20th April 2016
 */
abstract class record implements recordInterface
{
	use \Gibbon\core\functions\developmentFunctions ;
	/**
	 * @var	sqlConnection	$pdo	Gibbon SQL
	 */
	public $pdo ;
	
	/**
	 * @var	config	$config		Gibbon Config
	 */
	public $config ;
	
	/**
	 * @var	session	$session	Gibbon Session
	 */
	public $session ;
	
	/**
	 * @var	view	$view	Gibbon View
	 */
	protected $view ;
	
	/**
	 * @var	stdClass	$record		db Record
	 */
	protected $record ;
	
	/**
	 * @var	PDO Statement	$result	
	 */
	protected $result ;
	
	/**
	 * @var	string	$error	 Last Error Message
	 */
	protected $error ;
	
	/**
	 * @var	boolean	$success	Last Query Success
	 */
	protected $success ;
	
	/**
	 * @var	integer	$rowCount	Query Count
	 */
	protected $rowCount ;
	
	/**
	 * @var	string	$lastQuery	 Last Query Executed
	 */
	protected $lastQuery	;
	
	/**
	 * @var	boolean	$change		Has data changed
	 */
	protected $change	;
	
	/**
	 * @var	array	$post	Post Data
	 */
	protected $post	;
	
	/**
	 * @var	boolean		$default
	 */
	protected $default = false;
	
	/**
	 * @var	string	
	 */
	protected $where;
	
	/**
	 * @var	array	
	 */
	protected $whereData;
	
	/**
	 * @var	string	
	 */
	protected $joinString;
	
	/**
	 * @var	array	
	 */
	protected $select = array();
	
	/**
	 * @var	boolean
	 */
	protected $distinct = false ;
	
	/**
	 * @var	boolean
	 */
	public $fieldValid = false ;
	
	/**
	 * Constructor
	 *
	 * @version	5th May 2016
	 * @since	30th April 2016
	 * @param	view		$view
	 * @param	integer		$id 
	 * @return	void
	 */
	public function __construct(view $view, $id = 0 )
	{
		$this->pdo = $view->pdo;
		$this->session = $view->session;
		$this->config = $view->config ;
		$this->view = $view ;
		if (empty($this->identifier))
			$this->identifier = $this->table . 'ID';
		$this->pdo->setSQLVariables($this->table, $this->identifier);
		if (intval($id) > 0)
			$this->find($id);
		else
			$this->defaultRecord();
	}

	/**
	 * find
	 *
	 * Will return false is no record or more than one record is found.
	 * @version	15th May 2016
	 * @since	30th April 2016
	 * @param	integer		$id Identifier
	 * @return	mixed		false or stdClass
	 */
	public function find($id)
	{
		$this->pdo->setSQLVariables($this->table, $this->identifier);
		$this->record = $this->pdo->get($id) ;
		$this->error = $this->pdo->getError();
		$this->change = false ;
		$this->result = $this->pdo->getResult();
		$this->rowCount = $this->pdo->getQuerySuccess() ? $this->result->rowCount() : 0;
		if ($this->record && $this->rowCount === 1) {
			$this->success = true;
			$this->error = null;
		} elseif ($this->rowCount() !== 1) {
			$this->error = 'The identifier did not find a record.';
			$this->success = false;
			$this->record = false;
		}
		return $this->record ;
	}

	/**
	 * inject Post
	 *
	 * @version	15th August 2016
	 * @since	30th April 2016
	 * @param	array		$data  Defaults to $_POST
	 * @return	boolean
	 */
	public function injectPost($data = null)
	{
		$data = is_null($data) ? $_POST : $data;
		$data = ! is_array($data) && $data instanceof \stdClass ? (array) $data : $data ;
		if (isset($data[$this->identifier]) && $data[$this->identifier] === 'Add')
			unset($data[$this->identifier]);
		if (empty($data[$this->identifier]) && isset($_GET[$this->identifier]))
			$data[$this->identifier] = intval($_GET[$this->identifier]);
		if (empty($this->record))
			$this->defaultRecord();
		if (empty($this->cols))
		{
			$sql = 'SHOW COLUMNS FROM `'.$this->table.'`';
			$v = clone $this ;
			$this->cols = $v->findAll($sql, array(), null, 'Field');
			foreach($this->cols as $q=>$w)
				$this->cols[$q] = $w->returnRecord();
		}
		$this->post = array();
		$types = array();
		$fail = false ;
		$y = 0;
		foreach ($this->cols as $row) {
			if (isset($data[$row->Field]))
			{
				$this->post[$row->Field] = $this->validateField($row->Field, $data[$row->Field]);
				if (! $this->fieldValid) 
				{
					$fail = true ;
					logger::__('Field injection validation failure', 'Debug', 'Validation - '.$this->table, array($row, 'data'=>$data[$row->Field]));
				}
			}
			if (isset($data[$row->Field]) && empty($data[$row->Field]) && $row->Null == 'YES')
				$this->post[$row->Field] = null;
			$types[$row->Field] = $row;
		}
		if ($fail)
		{
			$this->error = 'The record was not injected correctly!';
			return false;
		}
		if (isset($this->post[$this->identifier]) && intval($this->post[$this->identifier]) < 1)
			unset($this->post[$this->identifier]);
		$change = false ;
		foreach($this->post as $q=>$w)
		{
			if (empty($this->record->$q) || $this->record->$q != $w)
				$change = true;
			$type = $types[$q]->Type;
			switch ($type) {
				case "date":
					if (! empty($w))
						$this->record->$q = helper::dateConvert($w) ;
					else
						$this->record->$q = null ;
					break ;
				default:
					if (empty($w) && $types[$q]->Null === 'YES')
						$this->record->$q = null ;
					else 
						$this->record->$q = $w ;
			}
		}
		$this->change = $change ;
		if (! $change) {
			$this->error = "Your record was not changed.";
			$this->view->insertMessage('Your record was not changed.', 'info');
			return true ;
		}
		return true ;
	}

	/**
	 * write Record
	 *
	 * @version	11th September 2016
	 * @since	30th April 2016
	 * @param	array	$fields		Limit the write to these fields
	 * @param	boolean	$forceInsert	Use the Identifier set as an INsert, not an update.
	 * @return	mixed	Object or False	
	 */
	public function writeRecord($fields = array(), $forceInsert = false)
	{
		$this->pdo->setSQLVariables($this->table, $this->identifier);
		if (empty($fields))
			$set = $this->record;
		else
		{
			if (! in_array($this->identifier, $fields))
				$fields[] = $this->identifier;
			$set = new \stdClass();
			foreach($fields as $name)
				$set->$name = $this->record->$name;
		}
		$ok = $this->pdo->set($set, $forceInsert);
		$this->insert = $this->pdo->insert;
		if ($this->insert && $this->record instanceof \stdClass)
			$this->record->{$this->identifier} = $this->pdo->lastInsertId();
		return $ok;
	}

	/**
	 * delete Record
	 *
	 * @version	19th June 2016
	 * @since	30th April 2016
	 * @param	integer		$id Record ID
	 * @return	boolean		Deleted Correctly
	 */
	public function deleteRecord($id)
	{
		if ($this->canDelete())
		{
			$this->pdo->setSQLVariables($this->table, $this->identifier);
			if ($this->pdo->delete($id))
			{
				$this->record = null ;
				return true;
			}
			$this->view->insertMessage($this->pdo->getError());
		}
		return false;
	}

	/**
	 * inject Record
	 *
	 * @version	1st August 2016
	 * @since	4th May 2016
	 * @param	stdClass	$record
	 * @return	void
	 */
	public function injectRecord( \stdClass $record)
	{
		if (empty($this->record))
		{
			$this->record = $record ;
			return ;
		}
		$record = (array) $record;
		foreach($record as $name => $value)
			$this->record->$name = $value;
	}

	/**
	 * return Record
	 *
	 * @version	4th May 2016
	 * @since	4th May 2016
	 * @return	void
	 */
	public function returnRecord()
	{
		return $this->record ;
	}

	/**
	 * get Field
	 *
	 * @version	20th June 2016
	 * @since	4th May 2016
	 * @param	string		$fieldName
	 * @return	mixed		Field Value
	 */
	public function getField($fieldName)
	{
		if ($this->record instanceof \stdClass && isset($this->record->$fieldName))
			return	$this->record->$fieldName;
		return null ;
	}

	/**
	 * execute Query
	 *
	 * @version	18th June 2016
	 * @since	4th May 2016
	 * @param	array		$data
	 * @param	string		$query
	 * @param	string		$errorMessage
	 * @return	mixed		false or PDO Statement
	 */
	public function executeQuery($data, $query, $errorMessage = null)
	{
		$this->lastQuery = $query;
		$this->result = $this->pdo->executeQuery($data, $query, $errorMessage);
		$this->success = $this->pdo->getQuerySuccess();
		$this->error = $this->pdo->getError();
		$this->rowCount();
		return $this->result ;
	}

	/**
	 * next
	 *
	 * @version	7th July 2016
	 * @since	4th May 2016
	 * @return	stdClass
	 */
	public function next()
	{
		if ($this->result instanceof \PDOStatement)
			return $this->record = $this->result->fetchObject();
		return $this->record = false;
	}

	/**
	 * Row Count
	 *
	 * @version	18th June 2016
	 * @since	4th May 2016
	 * @return	integer		Row Count
	 */
	public function rowCount()
	{
		if (isset($this->result))
			return $this->rowCount = $this->result->rowCount();
		return 0 ;
	}

	/**
	 * Default Record
	 *
	 * @version	6th August 2016
	 * @since	4th May 2016
	 * @return	stdClass	Record
	 */
	public function defaultRecord()
	{
  		$this->record = new \stdClass();
		$this->change = false ;
		$this->default = true;
		$xx = $this->pdo->query("SHOW COLUMNS FROM `".$this->table."`");
		if (empty($xx))
			return $this->record;
		foreach($xx->fetchAll() as $rr) {
			$name = $rr['Field'];
			$this->record->$name = $rr['Default'];
			if ($rr['Null'] == 'NO' && is_null($rr['Default']))
			{
				if (strpos($rr['Type'], 'int') === 0)
					$this->record->$name = 0;
				if (strpos($rr['Type'], 'varchar') === 0)
					$this->record->$name = '';
				if (strpos($rr['Type'], 'mediumtext') === 0)
					$this->record->$name = '';
				if (strpos($rr['Type'], 'text') === 0)
					$this->record->$name = '';
			}
			if (strpos($rr['Type'], 'timestamp') === 0 && $rr['Default'] === 'CURRENT_TIMESTAMP')
				$this->record->$name = date('Y-m-d H:i:s');
		}
		$this->success = true ;
		return $this->record ;
	}

	/**
	 * find All
	 *
	 * @version	12th August 2016
	 * @since	6th May 2016
	 * @param	string	$findAllQuery	Query
	 * @param	string	$findAllData	Data
	 * @param	string	$errorMessage	Non default Error Message
	 * @param	string	$index			Array Index field (default is primary key of table.)
	 * @return	array	All Records in the Table
	 */
	public function findAll($findAllQuery = null, $findAllData = array(), $errorMessage = null, $index = null)
	{
		$result = $this->result ;
		if (! is_null($findAllQuery))
			$this->executeQuery($findAllData, $findAllQuery, $errorMessage);
		else
			$this->executeQuery(array(), "SELECT * FROM `".$this->table."`", $errorMessage);
		$records = array();
		if (! $this->success || is_null($this->result))
			return $records ; 
		if (is_null($index))
			$index = $this->identifier;
		while($record = $this->next())
		{
			if (empty($record->$index)) {
				$x = (array) $record;
				$index = key($x);
			}
			$records[$record->$index] = clone $this;
			$records[$record->$index]->injectRecord($record);
		}
		$this->result = $result ;
		return $records;
	}

	/**
	 * get Error
	 *
	 * @version	7th May 2016
	 * @since	7th May 2016
	 * @return	string
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * get Success
	 *
	 * @version	7th May 2016
	 * @since	7th May 2016
	 * @return	string
	 */
	public function getSuccess()
	{
		return (bool) $this->success;
	}

	/**
	 * set Field
	 *
	 * @version	8th September 2016
	 * @since	4th May 2016
	 * @param	string		$fieldName
	 * @param	mixed		$fieldValue
	 * @param	boolean		$test  Test Content
	 * @return	Object		The current Object to allow chaining.
	 */
	public function setField($fieldName, $fieldValue, $test = true)
	{
		if (empty($this->record))
			$this->record = new \stdClass();
		if ($test)
			$fieldValue = $this->validateField($fieldName, $fieldValue);
		if (! $test || $this->fieldValid)
		{
			if ($fieldValue != $this->getField($fieldName))
			{
				$this->change = true ;
				$this->default = false ;
			}
			$this->record->$fieldName = $fieldValue ;
		}
		return $this ;
	}

    /**
     * Finds entities by a set of criteria.
     *
	 * @version	13th August 2016
	 * @version	8th May 2016
     * @param	array|string     $criteria
     * @param	array|null $orderBy
     * @param	int|null   $limit
     * @param	int|null   $offset
     * @return	object	
     */
    public function findBy($criteria, $orderBy = null, $limit = null, $offset = null)
    {
		$where = 'WHERE ';
		$index = 0;
		$data = array();
		$join = '';
		if (is_array($criteria))
		{
			foreach($criteria as $name=>$value) {
				$where .= "`". $name . "` = :" . $name.$index . " AND ";
				$data[$name.$index] = $value ;
				$index++;
			}
			$where = substr($where, 0, -5);
		} elseif (is_string($criteria))
		{
			$where .= $criteria; 
			$data = $this->getwhereData();
			$join = $this->getJoin();
		}
		$order = '';
		if (strlen($where) <= 6)
			$where = ''; 
		if (count($orderBy) > 0) {
			$order = ' ORDER BY ';
			foreach($orderBy as $name=>$direction) 
				$order .= "`".$name."` " . $direction . ", ";
			$order = substr($order, 0, -2); 
		}
		if (! is_null($limit))
		{
			$order .= ' LIMIT ';
			if (! is_null($offset))
				$order .= intval($offset).', ';
			$order .= intval($limit);
		}
		$query = 'SELECT '.$this->getSelect().' FROM `'. $this->table . '` ' . $join . "\n" . $where . "\n" . $order;

		$this->executeQuery($data, $query);
		if ($this->success) 
		{
			if ($this->rowCount() > 0) 
			{
				return $this->next();
			} 
			else
			{
				$this->record = new \stdClass();
				return null;
			}
		}
		else
		{
			$this->record = new \stdClass();
			return null;
		}
		return $this->record ;
    }

    /**
     * Finds One Entity by a set of criteria.
     *
	 * @version	11th May 2016
	 * @version	11th May 2016
     * @param	array|string      $criteria
     * @param	array|null $orderBy
     * @param	int|null   $limit
     * @param	int|null   $offset
     *
     * @return	object|false
     */
    public function findOneBy($criteria, $orderBy = null, $limit = null, $offset = null)
    {
		$this->findBy($criteria, $orderBy, $limit, $offset);
		if (intval($this->rowCount()) !== 1) 
			$this->record = false;
		return $this->record;
    }

    /**
     * get Identifier Name
     *
	 * @version	21st May 2016
	 * @version	21st May 2016
     * @return	string
     */
    public function getIdentifierName()
    {
		return $this->identifier;
    }

    /**
     * get Table Name
     *
	 * @version 29th June 2016
	 * @version	29th June 2016
     * @return	string
     */
    public function getTableName()
    {
		return $this->table;
    }

    /**
     * get Identifier
     *
	 * @version	21st May 2016
	 * @since	21st May 2016
     * @return	string
     */
    public function getIdentifier()
    {
		$name = $this->identifier;
		return $this->record->$name;
    }
	
	/** 
	 * unique Failed
	 *
	 * @version	4th July 2016
	 * @since	4th July 2016
	 * @param	string	$message	Message
	 * @param	string	$level		Log Level<br/>
	 	DEBUG (100): Detailed debug information.<br/>
		INFO (200): Interesting events. Examples: User logs in, SQL logs.<br/>
		NOTICE (250): Normal but significant events.<br/>
		WARNING (300): Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.<br/>
		ERROR (400): Runtime errors that do not require immediate action but should typically be logged and monitored.<br/>
		CRITICAL (500): Critical conditions. Example: Application component unavailable, unexpected exception.<br/>
		ALERT (550): Action must be taken immediately. Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up..<br/>
		EMERGENCY (600): Emergency: system is unusable.
	 * @param	string	$channel	Channel
	 * @param	array	$additional	Additonal Info
	 * @return	false
	 */
	public function uniqueFailed($message, $level = 'Debug', $channel = 'Gibbon', $additional = array())
	{
		logger::__($message, $level, $channel, $additional, $this->view->pdo);
		return false ;
	}

    /**
     * Manage Photo
     *
	 * @version	9th September 2016
	 * @version	13th July 2016
	 * @params	string		$file	
	 * @params	string		$name
	 * @params	string		$fileName	
	 * @params	array		$imageLimits
     * @return	string
     */
    protected function managePhoto($file, $name, $fileName, array $imageLimits)
	{
		if (! empty($_FILES[$file])) {
			$fm1 = new fileManager($this->view);
			if (! $fm1->fileManage($file, $fileName)) $this->imageFail = true ;
			if (! $fm1->validImage($imageLimits)) $this->imageFail = true ;
			$_POST[$name] = empty($fm1->fileName) ? $_POST['logo'] : $fm1->fileName ; 
			return $_POST[$name];
		}
	}


	/**
	 * set Field
	 *
	 * @version	9th September 2016
	 * @since	26th July 2016
	 * @param	string		$fieldName
	 * @param	mixed		$fieldValue
	 * @return	Object		The current Object to allow chaining.
	 */
    protected function validateField($fieldName, $fieldValue)
	{
		if (empty($this->cols))
		{
			$sql = 'SHOW COLUMNS FROM `'.$this->table.'`';
			$v = clone $this ;
			$this->cols = $v->findAll($sql, array(), null, 'Field');
			foreach($this->cols as $q=>$w)
				$this->cols[$q] = $w->returnRecord();
		}
		$this->fieldValid = true;
		if (isset($this->cols[$fieldName]))
			$column = $this->cols[$fieldName];
		else
		{
			//Orphan Field.
			$this->record->$fieldName = $fieldValue ;
			return $fieldValue ;
		}
		$x = $column->Type;
		if (is_null($fieldValue) && $column->Null == 'Yes')
			return null ;
		if (strpos($x, '(') !== false)
			$x = substr($x, 0, strpos($x, '('));
		switch ($x)
		{
			case 'blob':
				break ;
			case 'boolean':
				$fieldValue = filter_var($fieldValue, FILTER_VALIDATE_BOOLEAN) ;
				break;
			case 'tinyint':
			case 'int':
				$fieldValue = ltrim($fieldValue, '0');
				$fieldValue = intval($fieldValue);
				$fieldValue = filter_var($fieldValue, FILTER_VALIDATE_INT) ;
				break;
			case 'float':
				$fieldValue = filter_var($fieldValue, FILTER_VALIDATE_FLOAT);
				break;
			case 'timestamp':
			case 'mediumtext':
			case 'text':
			case 'tinytext':
			case 'varchar':
				$fieldValue = filter_var($fieldValue, FILTER_DEFAULT) ;
				break;
			case 'enum':
				$enum = mb_substr($column->Type, mb_strpos($column->Type, '(') + 1);
				$enum = str_replace(array(',', "'", '"'), array('|'), $enum);
				$enum = rtrim($enum, ')');
				$enum = trim($enum, "'");
				$enum = trim($enum, '"');
				$options = explode('|', $enum);
				if (! in_array($fieldValue, $options)) $fieldValue = false ;
				break;
			case 'date':
				$fieldValue = helper::dateConvert($fieldValue);
				$options =array("options" => array("regexp" => $this->session->get('i18n.dateFormatRegEx')));
				if (empty($fieldValue) || $fieldValue === '0000-00-00' && $column->Null == 'YES')
					$fieldValue =  null;
				elseif (empty($fieldValue) || $fieldValue === '0000-00-00' && $column->Null == 'NO')
					$fieldValue = false;
				break;
			case 'time':
			    $dateTime = \DateTime::createFromFormat('Y-m-d H:i', '2016-01-01 '.$fieldValue['hour'].':'.$fieldValue['minute']);
				$errors = \DateTime::getLastErrors();
				if (! empty($errors['warning_count'])) {
					$fieldValue = false;
					logger::__('Time Validation failure', 'Debug', 'Validation', array('type' => $row->Type, 'field' => $row->Field, 'errors' => $errors));
				} elseif ($fieldValue['hour'] == '' || $fieldValue['minute'] == '')
				{
					if ($column->Null == 'YES')
						$fieldValue = null;
					else
						$fieldValue = false;
				}
				else 
					$fieldValue = $fieldValue['hour'].':'.$fieldValue['minute'];
				break;
			case 'datetime':
			    $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $fieldValue);
				$errors = \DateTime::getLastErrors();
				if (! empty($errors['warning_count'])) {
					$fieldValue = false;
					logger::__('Date Time Validation failure', 'Debug', 'Validation', array('type' => $row->Type, 'field' => $row->Field, 'errors' => $errors));
				}
				break;
			default:
				dump('Field Type has not been defined: '.$x);
				dump($fieldValue, true);
		}
		if (false === $fieldValue)
			$this->fieldValid = false ;
		return $fieldValue ;
	}

    /**
     * blank Record
     *
	 * @version	31st July 2016
	 * @since	31st July 2016
     * @return	string
     */
    public function blankRecord()
    {
		return $this->record = new \stdClass();
    }

	/**
	 * get ID
	 *
	 * @version	12th August 2016
	 * @since	12th August 2016
	 * @return	integer
	 */
	public function getID()
	{
		$x = $this->isEmpty($this->identifier) ? 0 : $this->getField($this->identifier) ;
		return $x ;
	}

	/**
	 * find First
	 *
	 * @version	12th August 2016
	 * @since	12th August 2016
	 * @param	string	$findAllQuery	Query
	 * @param	string	$findAllData	Data
	 * @param	string	$errorMessage	Non default Error Message
	 * @param	string	$index			Array Index field (default is primary key of table.)
	 * @return	recordInterface/null	
	 */
	public function findFirst($findAllQuery = null, $findAllData = array(), $errorMessage = null, $index = null)
	{
		$w = $this->findAll($findAllQuery, $findAllData, $errorMessage, $index);
		if (is_array($w) and count($w) > 0)
			return reset($w) ;
		return null;
	}

	/**
	 * or Where
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @param	string	$name		
	 * @param	mixed	$value
	 * @param	string	$linkType
	 * @return	recordInterface	
	 */
	public function orWhere($name, $value, $linkType = '=')
	{
		if (empty($this->where))
			$this->where = '';
		$this->where .= '`'.str_replace('.', '`.`', $name).'` '.$linkType . ' ' . $this->valueSubstitute($value) . ' OR ';
		return $this;
	}

	/**
	 * and Where
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @param	string	$name		
	 * @param	mixed	$value
	 * @param	string	$linkType
	 * @return	recordInterface	
	 */
	public function andWhere($name, $value, $linkType = '=')
	{
		if (empty($this->where))
			$this->where = '';
		$this->where .= '`'.str_replace('.', '`.`', $name).'` '.$linkType . ' ' . $this->valueSubstitute($value) . "\n AND ";
		return $this;
	}

	/**
	 * start Or Where
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @param	string	$name		
	 * @param	mixed	$value
	 * @param	string	$linkType
	 * @return	recordInterface	
	 */
	public function startOrWhere($name, $value, $linkType = '=')
	{
		if (empty($this->where))
			$this->where = '';
		$this->where .= '(';
		$this->orWhere($name, $value, $linkType);
		return $this;
	}


	/**
	 * end Or Where
	 *
	 * @version	20th August 2016
	 * @since	13th August 2016
	 * @param	string	$name		
	 * @param	mixed	$value
	 * @param	string	$linkType
	 * @return	recordInterface	
	 */
	public function endOrWhere($name = null, $value = null, $linkType = '=')
	{
		if (! is_null($name))
			$this->orWhere($name, $value, $linkType);
		$this->where = substr($this->where, 0, -4) . ")\n AND ";
		return $this;
	}

	/**
	 * get Where
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @return	stdClass
	 */
	public function getWhere()
	{
		return rtrim($this->where, ' AND');
	}

	/**
	 * value Substitute
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @return	string		Substitute Value 
	 */
	protected function valueSubstitute($value)
	{
		if (is_null($value)) return '';
		if (empty($this->whereData)) 
			$this->whereData = array();
		$x = ':value' . count($this->whereData);
		$this->whereData[$x] = $value;
		return $x ;
	}

	/**
	 * and Where Data
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @return	stdClass
	 */
	public function getWhereData()
	{
		return $this->whereData ;
	}

	/**
	 * start Where
	 *
	 * @version	15th August 2016
	 * @since	13th August 2016
	 * @param	string	$name		
	 * @param	mixed	$value
	 * @param	string	$linkType
	 * @return	recordInterface	
	 */
	public function startWhere($name = null, $value = null, $linkType = '=')
	{
		$this->where = '';
		$this->whereData = array();
		if (! is_null($name))
			$this->andWhere($name, $value, $linkType);
		return $this;
	}

	/**
	 * start Join
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @param	string	$name		
	 * @param	mixed	$value
	 * @param	string	$linkType
	 * @return	recordInterface	
	 */
	public function startJoin($table2, $field, $joinType = 'JOIN', $table1 = null, $field1 = null)
	{
		$this->joinString = '' ;
		$this->addJoin($table2, $field, $joinType, $table1, $field1);
		return $this;
	}

	/**
	 * start Join
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @param	string	$name		
	 * @param	mixed	$value
	 * @param	string	$linkType
	 * @return	recordInterface	
	 */
	public function addJoin($table2, $field, $joinType = 'JOIN', $table1 = null, $field1 = null)
	{
		$this->joinString .= $joinType . ' `' . $table2 . '` ON `' . $table2 . '`.`' . $field . '` = `';
		$x = is_null($table1) ? $this->table . '`.`' : $table1 . '`.`' ;
		$this->joinString .= $x;
		$field1 = is_null($field1) && ! is_null($table1) ? $field : $field1;
		$x = is_null($field1) ? $field . '` ' : $field1 . '` ' ;
		$this->joinString .= $x;
		return $this;
	}

	/**
	 * and join
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @return	stdClass
	 */
	public function getJoin()
	{
		return $this->joinString ;
	}

	/**
	 * start Query
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @return	recordInterface
	 */
	public function startQuery()
	{
		$this->joinString = '';
		$this->where = '';
		$this->whereData = array();
		return $this;
	}

    /**
     * Finds All Entities by a set of criteria.
     *
	 * @version	20th August 2016
	 * @version	18th August 2016
     * @param	array|string      $criteria
     * @param	array|null $orderBy
     * @param	int|null   $limit
     * @param	int|null   $offset
     *
     * @return	array
     */
    public function findAllBy($criteria, $orderBy = null, $limit = null, $offset = null)
    {
		$this->findBy($criteria, $orderBy, $limit, $offset);
		if (! $this->getSuccess() || intval($this->rowCount()) == 0) 
			return array();
		$records = array();
		$records[] = $this->record;
		while (false !== $this->next())
		{
			$records[] = $this->record;
		}
		return $records;
    }

    /**
     * start Select
     *
	 * @version	20th August 2016
	 * @version	20th August 2016
     * @return	this
     */
    public function startSelect($name)
	{
		$this->select = array();
		return $this->addSelect($name);
    }

    /**
     * add Select
     *
	 * @version	20th August 2016
	 * @version	20th August 2016
     * @return	this
     */
    public function addSelect($name)
	{
		$this->select[] = $name ;
		return $this ;
    }

    /**
     * get Select
     *
	 * @version	20th August 2016
	 * @version	20th August 2016
     * @return	string
     */
    public function getSelect()
	{
		if (! is_array($this->select) || count($this->select) === 0)
			return '`'.$this->table.'`.*';
		$select = '';
		foreach($this->select as $name)
		{
			$w = explode('.', $name);
			if (count($w) == 2) 
			{
				$table = $w[0];
				$field = $w[1];
			} 
			else 
			{
				$table = $this->table;
				$field = $w[0];
			}
			if (! empty($table) && ! empty($field))
				$select .= '`'.$table.'`.';
			if (! empty($field))
			{
				if ($field == '*')
					$select .= '*, ';
				else
					$select .= '`'.$field.'`, ';
			}
		}
		if (empty($select)) return '`'.$this->table.'`.*';
		if ($this->distinct)
			$select = 'DISTINCT ' . $select;
		return trim($select, ', ');
    }

    /**
     * set Distinct
     *
	 * @version	24th August 2016
	 * @version	24th August 2016
	 * @param	boolean		$on
     * @return	this
     */
    public function setDistinct($on = true)
	{
		$this->distinct = $on ;
		return $this ;
    }

	/**
	 * is Empty
	 *
	 * @version	25th August 2016
	 * @since	25th August 2016
	 * @param	string		$name of Field
	 * @return	boolean
	 */
	public function isEmpty($name)
	{
		$x = $this->getField($name);
		if (empty($x)) return true;
		return false ;
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

