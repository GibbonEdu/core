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


/**
 * sql Connection
 *
 * @version	18th April 2016
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
	 * Construct
	 *
	 * @version	12th April 2016
	 * @since	8th April 2016
	 * @param	string	error Message
	 * @return	Object	PDO Connection
	 */
	public function __construct($message = NULL)
	{	
		// Test for Config file. 
		if (file_exists(dirname(__FILE__). '/../../config.php'))
			include dirname(__FILE__). '/../../config.php';
		else
			return NULL;
		return $this->generateConnection($databaseServer, $databaseName, $databaseUsername, $databasePassword, $message);
	}

	/**
	 * generate Connection
	 *
	 * @version	17th April 2016
	 * @since	17th April 2016
	 * @param	string	Server Address:port
	 * @param	string	Database Name
	 * @param	string	User Name
	 * @param	string	Password
	 * @param	string	error Message
	 * @return	Object	PDO Connection
	 */
	 private function generateConnection($databaseServer, $databaseName, $databaseUsername, $databasePassword, $message = NULL)
	 {
		$this->pdo = NULL;
		$this->success = true;
		try
		{
			$this->pdo = new \PDO("mysql:host=".$databaseServer.";dbname=".$databaseName.";charset=utf8", $databaseUsername, $databasePassword );
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
			$this->setSQLMode();
		}
		catch( \PDOException $e)
		{
			if ($message === NULL)
				echo $e->getMessage();
			else
				echo $message;
			$this->success = false;
			$this->error = $e->getMessage();
		}
		return $this;
	}

	/**
	 * get Connection
	 *
	 * Only required for backwards compatibilty in Gibbon.
	 * @version	8th April 2016
	 * @since	8th April 2016
	 * @return	Object PDO COnnection
	 */
	public function getConnection()
	{
		return $this->pdo;
	}

	/**
	 * get Connection Success
	 *
	 * @version	12th April 2016
	 * @since	12th April 2016
	 * @return	Object PDO COnnection
	 */
	public function getSuccess()
	{
		return $this->success;
	}

	/**
	 * get Query Success
	 *
	 * @version	12th April 2016
	 * @since	12th April 2016
	 * @return	Object PDO COnnection
	 */
	public function getQuerySuccess()
	{
		return $this->querySuccess;
	}

	/**
	 * Execute Query
	 *
	 * @version	14th April 2016
	 * @since	12th April 2016
	 * @param	array	Data Information
	 * @param	string	SQL Query
	 * @param	string	Error
	 * @return	Object	PDO Result
	 */
	public function executeQuery($data, $query, $error = NULL)
	{

		$this->querySuccess = true ;
		$this->data = $data ;
		$this->query = $query ;
		try {
			$this->result = $this->getConnection()->prepare($query);
			$this->result->execute($data);
		}
		catch(PDOException $e) 
		{
			$this->error = $e->getMessage();
			$this->querySuccess = false;
			if ($error !== NULL)
				echo str_replace('{message}', $this->error, $error);
			$this->result = NULL;
		}

		return $this->result ;
	}

	/**
	 * get Result
	 *
	 * @version	14th April 2016
	 * @since	14th April 2016
	 * @return	Object	PDOStatement 
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
	 * @return	string	Version
	 */
	public function getVersion()
	{
		return $this->pdo->query('select version()')->fetchColumn();
	}

	/**
	 * Install Bypass
	 *
	 * @version	17th April 2016
	 * @since	17th April 2016
	 * @param	string	Server Address:port
	 * @param	string	Database Name
	 * @param	string	User Name
	 * @param	string	Password
	 * @param	string	error Message
	 * @return	Object	PDO Connection
	 */
	public function installBypass($databaseServer, $databaseName, $databaseUsername, $databasePassword, $message = NULL)
	{
		$databaseNameClean="`".str_replace("`","``",$databaseName)."`";
		$this->success = true;
		$this->pdo = NULL;
		try
		{
			$this->pdo = new \PDO("mysql:host=".$databaseServer.";charset=utf8", $databaseUsername, $databasePassword );
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
			$this->setSQLMode();
		}
		catch( \PDOException $e)
		{
			if ($message === NULL)
				echo $e->getMessage();
			else
				echo $message;
			$this->success = false;
			$this->error = $e->getMessage();
			return $this ;
		}
		$result = $this->executeQuery(array(), "CREATE DATABASE IF NOT EXISTS ".$databaseNameClean." DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_unicode_ci");
		if ($this->querySuccess)
			$result = $this->executeQuery(array(), "USE ". $databaseNameClean);
		return $this;
	}
	 
	/**
	 * set SQL Mode
	 *
	 * @version	18th April 2016
	 * @since	18th April 2016
	 * @return	void
	 */
	private function setSQLMode()
	{
		$version = $this->getVersion();
		if ($version > '5.7')  //Default for 5.7.x is STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_AUTO_CREATE_USER
			$result = $this->pdo->prepare("SET SESSION `sql_mode` = ''");
		elseif ($version > '5.6')  // Default for 5.6.x is NO_ENGINE_SUBSTITUTION
			$result = $this->pdo->prepare("SET SESSION `sql_mode` = ''");
		else // Default for < 5.6 is ''
			$result = $this->pdo->prepare("SET SESSION `sql_mode` = ''");
		$result->execute(array());
	}
}

/**
 * Print an Object Alias (Dump)
 *
 * @version 16th February 2015
 * @since OLD
 * @param mixed The object to be printed
 * @param boolean Stop execution after printing object.
 * @param boolean Full dump of Trace Stack
 * @return void
 */
function dump($object, $stop = false, $full = false) 
{
	$caller = debug_backtrace();
	echo "<pre>\n";
	echo $caller[0]['line'].': '.$caller[0]['file'];
	echo "\n</pre>\n";
	echo "<pre>\n";
	print_r($object);
	if ($full) 
		print_r($caller);
	echo "\n</pre>\n";
	if ($stop) 
		trigger_error('Object Print Stop', E_USER_ERROR);
	return ;
}

?>