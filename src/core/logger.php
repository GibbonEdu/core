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
*/
/**
 */
namespace Gibbon\core;

use Monolog\Logger as monoLogger ;
use MySQLHandler\MySQLHandler ;
use Gibbon\core\sqlConnection AS PDO;
use Gibbon\core\session ;
use Gibbon\core\trans ;
use Gibbon\core\view ;

/**
 * Logger Manager
 *
 * @version	12th August 2016
 * @since	22nd April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Core
 */
class logger
{
	/**
	 * @var	PDO
	 */
	static $pdo ;

	/**
	 * Log Message
	 *
	 * Debug is ignored unless installType = Development.
	 * @version	4th July 2016
	 * @since	8th June 2016
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
	 * @return	void
	 */
	public static function __($message, $level = 'Debug', $channel = 'Gibbon', $additional = array(), PDO $pdo = null)
	{

		$session = new session();
		
		if ($session->get('version') < '13.0.00')
			return ;

		if ($level == 'Debug' && $session->get('installType') !== 'Development')
			return ;

		$pdo = self::getPDO($pdo);
		
		if (self::sqlFail($pdo)) return ;

		$ip = $_SERVER['REMOTE_ADDR'];
		if (empty($additional)) $additional = array();
		$serialisedArray = serialize($additional);

		switch (strtoupper($level))
		{
			case 'DEBUG':
				$levelCode = monoLogger::DEBUG ;
				break;
			case 'INFO':
				$levelCode = monoLogger::INFO ;
				break;
			case 'NOTICE':
				$levelCode = monoLogger::NOTICE ;
				break;
			case 'WARNING':
				$levelCode = monoLogger::WARNING ;
				break;
			case 'ERROR':
				$levelCode = monoLogger::ERROR ;
				break;
			case 'CRITICAL':
				$levelCode = monoLogger::CRITICAL ;
				break;
			case 'ALERT':
				$levelCode = monoLogger::ALERT ;
				break;
			case 'EMERGENCY':
				$levelCode = monoLogger::EMERGENCY ;
				break;
			default:
				return ;
		}
		//Create MysqlHandler
		$mySQLHandler = new MySQLHandler($pdo->getConnection(), "gibbonLog", array('gibbonLogID', 'module', 'person', 'schoolYear', 'levelName', 'serialisedArray', 'ip'), $levelCode);
		
		//Create logger
		$logger = new monoLogger($channel);
		$logger->pushHandler($mySQLHandler);

		//Now you can use the logger, and further attach additional information
		$details = array('gibbonLogID'=>null, 'module' => $session->get("module"), 'person' => $session->get("officialName"), 
			'schoolYear' => $session->get("gibbonSchoolYearName"), 'levelName' => $level, 'serialisedArray'=>$serialisedArray, 'ip'=>$ip);

		$level = 'add'.$level;
		if (is_array($message))
			$message = vsprintf($message[0], $message[1]);

		$logger->$level($message, $details);
	}
	
	public static function getLog($gibbonSchoolYearID, $gibbonModuleID=null, $gibbonPersonID=null, $title=null, $startDate=null, $endDate=null, $ip=null, $array=null) 
	{
		$pdo = new sqlConnection();
		if($gibbonSchoolYearID == null || $gibbonSchoolYearID == "") {
			return null;
		}
		$dataLog = array("gibbonSchoolYearID"=>$gibbonSchoolYearID);
		$where="";
	
		if(is_array($array) && $array!=null && $array!="" && !empty($array)) {
			$valNum = 0;
			foreach($array as $key => $val){
				$keyName = 'key' . $valNum;
				$dataLog[$keyName] = $key;
				$valName = 'val' . $valNum;
				$dataLog[$valName] = $val;
				$where.=" AND serialisedArray LIKE CONCAT('%', :". $keyName .", '%;%', :". $valName .", '%')";
				$valNum++;
			}
		}
	
		if($gibbonModuleID!=null && $gibbonModuleID!="")  {
			$dataLog['gibbonModuleID'] = $gibbonModuleID;
			$where.=" AND gibbonModuleID=:gibbonModuleID";
		}
	
		if($gibbonPersonID!=null && $gibbonPersonID!="")  {
			$dataLog['gibbonPersonID'] = $gibbonPersonID;
			$where.=" AND gibbonPersonID=:gibbonPersonID";
		}
	
		if($title!=null)  {
			$dataLog['title'] = $title;
			$where.=" AND title=:title";
		}
	
		if($startDate!=null && $endDate==null) {
			$startDate = str_replace('/', '-', $startDate);
			$startDate = date("Y-m-d", strtotime($startDate));
			$dataLog['startDate'] = $startDate;
			$where.=" AND timestamp>=:startDate";
		}
		else if($startDate==null && $endDate!=null) {
			$endDate = str_replace('/', '-', $endDate);
			$endDate = date("Y-m-d 23:59:59", strtotime($endDate)) + date("H:i:s");
			$dataLog['endDate'] = $endDate;
			$where.=" AND timestamp<=:endDate";
		}
		elseif($startDate!=null && $endDate!=null)  {
			$startDate = str_replace('/', '-', $startDate);
			$startDate = date("Y-m-d", strtotime($startDate));
			$dataLog['startDate'] = $startDate;
			$endDate = str_replace('/', '-', $endDate);
			$endDate = date("Y-m-d 23:59:59", strtotime($endDate));
			$dataLog['endDate'] = $endDate;
			$where.=" AND timestamp>=:startDate AND timestamp<=:endDate";
		}
	
		if($ip!=null || $ip!="")  {
			$dataLog['ip'] = $ip;
			$where.=" AND ip=:ip";
		}

		$sqlLog="SELECT * FROM gibbonLog 
			WHERE gibbonSchoolYearID=:gibbonSchoolYearID " . $where . " 
			ORDER BY timestamp DESC" ;
		$resultLog = $pdo->executeQuery($dataLog, $sqlLog);
		if (! $pdo->getQuerySuccess()) return null;

		return $resultLog;
	}

	/**
	 * test SQL Ready (Fail)
	 *
	 * @version	22nd June 2016
	 * @since	22nd June 2016
	 * @param	Gibbon\sqlConnection
	 * @return	boolean	true on fail
	 */
	public static function sqlFail($pdo)
	{
		$result = $pdo->executeQuery(array(), "SHOW COLUMNS FROM `gibbonLog` WHERE `Field` = 'channel'");
		$x = $result->fetch();
		if (isset($x['Field']))
			return false;
		return true ;
	}

	/**
	 * get PDO
	 *
	 * @version	7th July 2016
	 * @since	7th July 2016
	 * @param	Gibbon\sqlConnection
	 * @return	Gibbon\sqlConnection
	 */
	public static function getPDO(PDO $pdo = null)
	{
		if (self::$pdo instanceof PDO)
			return self::$pdo ;
		if ($pdo instanceof PDO)
			return self::$pdo = $pdo;
		if (! $pdo instanceof PDO)
			return self::$pdo = new PDO();
		return self::$pdo ;
	}

}