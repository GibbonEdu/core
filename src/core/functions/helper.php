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
die();
use Gibbon\core\view ;

/**
 * Helper
 *
 * @version	12th August 2016
 * @since	20th April 2016
 * @package	Gibbon
 * @subpackage	Core
 * @author	Craig Rayner
 */
class helper
{

	/**
	 * @var Gibbon\core\sqlConnection
	 */
	static $pdo;
	
	/**
	 * @var Gibbon\core\view
	 */
	static $view;
	
	/**
	 * @var string
	 */
	static $rowNum = 'even';
	
	/**
	 * get pdo
	 *
	 * @version	22nd June 2016
	 * @since	24th April 2016
	 * @return	Gibbon\sqlConnection
	 */
	static public function getPDO()
	{
		if (self::$pdo instanceof sqlConnection)
			return self::$pdo;
		if (isset(self::$view->pdo) && self::$view->pdo instanceof sqlConnection)
			self::$pdo = self::$view->pdo ;
		else
			self::$pdo = new sqlConnection() ;
		return self::$pdo;
	}

	/**
	 * Gibbon\session
	 */
	static $session;
	
	/**
	 * get Session
	 *
	 * @version	22nd June 2016
	 * @since	24th April 2016
	 * @return	Gibbon\sqlConnection
	 */
	static public function getSession()
	{
		if (self::$session instanceof session)
			return self::$session;
		if (isset(self::$view->session) && self::$view->session instanceof session)
			self::$session = self::$view->session ;
		else
			self::$session = new session();
		return self::$session;
	}

	/**
	 * Gibbon\config
	 */
	static $config;
	
	/**
	 * get Session
	 *
	 * @version	24th April 2016
	 * @since	24th April 2016
	 * @return	Gibbon\config
	 */
	static public function getConfig()
	{
		if (self::$config instanceof config)
			return self::$config;
		if (isset(self::$view->config) && self::$view->config instanceof config)
			self::$config = self::$view->config;
		else
			self::$config = new config();
		return self::$config ;
	}

	/**
	 * format Name
	 *
	 * @version	22nd April 2016
	 * @since	22nd April 2016
	 * @param	string		$title	Title
	 * @param	string		$preferredName	Preferred Name
	 * @param	string		$surename	Surname
	 * @param	string		$roleCategory	Role Category
	 * @param	boolean		$reverse	Reverse
	 * @param	boolean		$informal	Informal
	 * @return	string
	 */
	static public function formatName( $title, $preferredName, $surname, $roleCategory, $reverse = false, $informal = false ) {
		$output=false ;
	
		if ($roleCategory=="Staff" OR $roleCategory=="Other") {
			if ($informal==FALSE) {
				if ($reverse==TRUE) {
					$output=$title . " " . $surname . ", " . strtoupper(substr($preferredName,0,1)) . "." ;
				}
				else {
					$output=$title . " " . strtoupper(substr($preferredName,0,1)) . ". " . $surname ;
				}
			}
			else {
				if ($reverse==TRUE) {
					$output=$surname . ", " . $preferredName ;
				}
				else {
					$output=$preferredName . " " . $surname ;
				}
			}
		}
		else if ($roleCategory=="Parent") {
			if ($informal==FALSE) {
				if ($reverse==TRUE) {
					$output=$title . " " . $surname . ", " . $preferredName ;
				}
				else {
					$output=$title . " " . $preferredName . " " . $surname ;
				}
			}
			else {
				if ($reverse==TRUE) {
					$output=$surname . ", " . $preferredName ;
				}
				else {
					$output=$preferredName . " " . $surname ;
				}
			}
		}
		else if ($roleCategory=="Student") {
			if ($reverse==TRUE) {
				$output=$surname. ", " . $preferredName ;
			}
			else {
				$output=$preferredName. " " . $surname ;
			}
	
		}
	
		return $output ;
	}

	/**
	 * set Notification
	 *
	 * Sets a system-wide notification
	 * @version	24th April 2016
	 * @since	copied from functions.php
	 * @param	integer		Persion ID
	 * @param	string		Notice
	 * @param	string		Module Name
	 * @param	string		Action Link
	 * @return	string
	 */
	static public function setNotification($personID, $text, $moduleName, $actionLink) {
		
		$nObj = new \Gibbon\Record\notification(new view());
		$nObj->set($personID, $text, $moduleName, $actionLink);
	}

	/**
	 * multiDimension Array Sort
	 *
	 * @version	24th April 2016
	 * @since	copied from functions.php
	 * @param	array		$array Data to be sorted
	 * @param	string		$id id Name
	 * @param	boolean		$sort_ascending	Ascending
	 * @return	array		Sorted Array	
	 */
	static public function msort($array, $id="id", $sort_ascending=true)
	{

		$temp_array=array();
		while(count($array)>0) {
			$lowest_id=0;
			$index=0;
			foreach ($array as $item) {
				if (isset($item[$id])) {
					if ($array[$lowest_id][$id]) {
						if (strtolower($item[$id]) < strtolower($array[$lowest_id][$id])) {
							$lowest_id=$index;
						}
					}
				}
				$index++;
			}
			$temp_array[]=$array[$lowest_id];
			$array=array_merge(array_slice($array, 0,$lowest_id), array_slice($array, $lowest_id+1));
		}
		if ($sort_ascending) {
			return $temp_array;
		} else {
			return array_reverse($temp_array);
		}
	}
	
	/**
	 * get Max Upload
	 *
	 * Print out, preformatted indicator of max file upload size
	 * @version	26th April 2016
	 * @since	copied from functions.php
	 * @param	boolean		$multiple
	 * @return	HTML String
	 */
	public static function getMaxUpload($multiple = false) {
		$output="" ;
		$post=substr(ini_get("post_max_size"),0,(strlen(ini_get("post_max_size"))-1)) ;
		$file=substr(ini_get("upload_max_filesize"),0,(strlen(ini_get("upload_max_filesize"))-1)) ;
	
		$output.="<div style='margin-top: 10px; font-style: italic; color: #c00'>" ;
		if ($multiple) {
			if ($post < $file) {
				$output.=sprintf( $this->__( 'Maximum size for all files: %1$sMB'), $post) . "<br/>" ;
			}
			else {
				$output.=sprintf( $this->__( 'Maximum size for all files: %1$sMB'), $file) . "<br/>" ;
			}
		}
		else {
			if ($post < $file) {
				$output.=sprintf( $this->__( 'Maximum file size: %1$sMB'), $post) . "<br/>" ;
			}
			else {
				$output.=sprintf( $this->__( 'Maximum file size: %1$sMB'), $file) . "<br/>" ;
			}
		}
		$output.="</div>" ;
	
		return $output ;
	}

	/**
	 * count Likes By Context
	 *
	 * @version	3rd May 2016
	 * @since	copied from functions.php
	 * @param	string		$moduleName	Module Name
	 * @param	string		$contextKeyName	Context Key Name
	 * @param	mixed		$contextKeyValue	Context Key Value
	 * @return	mixed		Count or false
	 */
	public static function countLikesByContext($moduleName, $contextKeyName, $contextKeyValue) {
		$pdo = self::getPDO();
		
		$data=array("moduleName"=>$moduleName, "contextKeyName"=>$contextKeyName, "contextKeyValue"=>$contextKeyValue);
		$sql="SELECT DISTINCT gibbonSchoolYearID, gibbonModuleID, contextKeyName, contextKeyValue, gibbonPersonIDGiver 
			FROM gibbonLike 
			WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:moduleName) 
				AND contextKeyName=:contextKeyName 
				AND contextKeyValue=:contextKeyValue" ;
		$result=$pdo->executeQuery($data, $sql);
		if ( ! $pdo->getQuerySuccess()) {
			return $false ;
		}

		return $result->rowCount() ;
	}

	/**
	 * get Highest Medical Risk
	 *
	 * Returns the risk level of the highest-risk condition for an individual
	 * @version	12th August 2016
	 * @since	copied from functions.php
	 * @param	integer		$personID	Person ID
	 * @return	mixed		false or array
	 */
	public static function getHighestMedicalRisk($personID, view $view) {
		$output = false ;
	
		$obj = new \Gibbon\Record\alertLevel($view);
		$data = array("personID"=>$personID);
		$sql = "SELECT `gibbonAlertLevel`.* 
			FROM `gibbonAlertLevel` 
				JOIN `gibbonPersonMedicalCondition` ON `gibbonPersonMedicalCondition`.`gibbonAlertLevelID` = `gibbonAlertLevel`.`gibbonAlertLevelID`
				JOIN `gibbonPersonMedical` ON `gibbonPersonMedical`.`gibbonPersonMedicalID` = `gibbonPersonMedicalCondition`.`gibbonPersonMedicalID` 
			WHERE `gibbonPersonID` = :personID
			ORDER BY `gibbonAlertLevel`.`sequenceNumber` DESC" ;
		$medical = $obj->findFirst($sql, $data);
	
		if (! is_null($medical)) {
			$output = array() ;
			$output[0] = $medical->getField("gibbonAlertLevelID");
			$output[1] =  $this->__($medical->getField("name")) ;
			$output[2] = $medical->getField("nameShort") ;
			$output[3] = $medical->getField("color") ;
			$output[4] = $medical->getField("colorBG") ;
			$output['level'] = $medical->getField("gibbonAlertLevelID");
			$output['name'] =  $this->__($medical->getField("name")) ;
			$output['nameShort'] = $medical->getField("nameShort") ;
			$output['colour'] = $medical->getField("colour") ;
			$output['colourBG'] = $medical->getField("colourBG") ;
		}
	
		return $output ;
	}

	/**
	 * count Likes By Context And Giver
	 *
	 * @version	3rd May 2016
	 * @since	copied from functions.php
	 * @param	string		$moduleName	Module Name
	 * @param	string		$contextKeyName	Context Key Name
	 * @param	mixed		$contextKeyValue	Context Key Value
	 * @param	integer		$personIDGiver	Person ID Giver
	 * @param	integer		$personIDRecipient  Person ID Recipient
	 * @return	mixed		Count or false
	 */
	public static function countLikesByContextAndGiver($moduleName, $contextKeyName, $contextKeyValue, $personIDGiver, $personIDRecipient=NULL) {
	
		$obj = new \Gibbon\Record\like($this->getView());
		return $obj->countLikesByContextAndGiver($moduleName, $contextKeyName, $contextKeyValue, $personIDGiver, $personIDRecipient);
	}

	/**
	 * get Year Groups From ID List
	 *
	 * @version	3rd May 2016
	 * @since	copied from functions.php
	 * @param	string		$ids  Comma separated list of ID's
	 * @param	boolean		$vertical
	 * @param	boolean		$translated
	 * @return	mixed		Count or false
	 */
	public static function getYearGroupsFromIDList($ids, $vertical=false, $translated=true) {
		$output=FALSE ;
		
		$pdo = self::getPDO();
		$sqlYears="SELECT DISTINCT nameShort, sequenceNumber FROM gibbonYearGroup ORDER BY sequenceNumber" ;
		$resultYears=$pdo->query($sqlYears);

		$years=explode(",", $ids) ;
		if (count($years)>0 AND $years[0]!="") {
			if (count($years)==$resultYears->rowCount()) {
				$output="<em>All</em>" ;
			}
			else {
				$dataYears=array() ;
				$sqlYearsOr="" ;
				for ($i=0; $i<count($years); $i++) {
					if ($i==0) {
						$dataYears[$years[$i]]=$years[$i] ;
						$sqlYearsOr=$sqlYearsOr . " WHERE gibbonYearGroupID=:" . $years[$i] ;
					}
					else {
						$dataYears[$years[$i]]=$years[$i] ;
						$sqlYearsOr=$sqlYearsOr . " OR gibbonYearGroupID=:" . $years[$i] ;
					}
				}

				$sqlYears="SELECT DISTINCT nameShort, sequenceNumber FROM gibbonYearGroup $sqlYearsOr ORDER BY sequenceNumber" ;
				$resultYears=$pdo->executeQuery($dataYears, $sqlYears);

				$count3=0 ;
				while ($rowYears=$resultYears->fetch()) {
					if ($count3>0) {
						if ($vertical==false) {
							$output.=", " ;
						}
						else {
							$output.="<br/>" ;
						}
					}
					if ($translated==TRUE) {
						$output.= $this->__($rowYears["nameShort"]) ;
					}
					else {
						$output.=$rowYears["nameShort"] ;
					}
					$count3++ ;
				}
			}
		}
		else {
			$output="<em>None</em>" ;
		}
	
		return $output ;
	}

	/**
	 * get Terms
	 *
	 * Gets terms in the specified school year
	 * @version	3rd May 2016
	 * @since	copied from functions.php
	 * @param	integer		$gibbonSchoolYearID School Year ID
	 * @param	boolean		$short Use Short Name
	 * @return	mixed		Name or false
	 */
	public static function getTerms($gibbonSchoolYearID, $short = false) {
		$output = false ;
		$pdo = self::getPDO();
		//Scan through year groups
		$data = array("gibbonSchoolYearID"=>$gibbonSchoolYearID);
		$sql = "SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber" ;
		$result = $pdo->executeQuery($data, $sql);
	
		while ($row = $result->fetch()) {
			$output .= $row["gibbonSchoolYearTermID"] . "," ;
			if ($short) {
				$output.=$row["nameShort"] . "," ;
			}
			else {
				$output.=$row["name"] . "," ;
			}
		}
		if (! $output) {
			$output=substr($output,0,(strlen($output)-1)) ;
			$output=explode(",", $output) ;
		}
		return $output ;
	}

	/**
	 * get Age
	 *
	 * Gets age from date of birth, in days and months, from Unix timestamp
	 * @version	4th May 2016
	 * @since	copied from functions.php
	 * @param	integer		$stamp	Timestamp
	 * @param	boolean		$short Use Short Name
	 * @param	boolean		$yearsOnly	Return Years Only
	 * @return	string		
	 */
	public static function getAge($stamp, $short = false, $yearsOnly = false) {
		$output="" ;
		$session = self::getSession();
		$dtz = new \DateTimeZone($session->get('timezone'));
		$birthday = new \DateTime('now', $dtz);
		$birthday->setTimestamp($stamp);
		$today = new \DateTime('now', $dtz);
		$diff = $birthday->diff($today);
		$years = $diff->y;
		$months = $diff->m ;
		if ($short) {
			$output = $years .  $this->__("y") . ", " . $months .  $this->__("m") ;
		}
		else {
			$output = $years . " " .  $this->__("years") . ", " . $months . " " .  $this->__("months") ;
		}
		if ($yearsOnly) {
			$output = $years ;
		}
		return $output ;
	}

	/**
	 * get Row Num
	 *
	 * @version	17th May 2016
	 * @since	16th May 2016
	 * @param	string		$rowNum	Override RowNum
	 * @return	string		Row Num	
	 */
	public static function getRowNum($rowNum = NULL) {
		
		if (! empty($rowNum)) return $rowNum;
		if (self::$rowNum !== 'odd')
			self::$rowNum = 'odd';
		else
			self::$rowNum = 'even';
		return self::$rowNum ;
	}

	/**
	 * New Line 2 BR
	 *
	 * @version	18th May 2016
	 * @since	copied from functions.php
	 * @param	string		$string
	 * @return	string
	 */
	public static function nl2brr($string) {
		return preg_replace("/\r\n|\n|\r/", "<br/>", $string);
	}
	
	/**
	 * inject View
	 *
	 * @version	22nd June 2016
	 * @since	22nd June 2016
	 * @param	Gibbon\view		$view
	 * @return	void
	 */
	static public function injectView(view $view)
	{
		self::$view = $view;
		self::$pdo = $view->pdo;
		self::$session = $view->session ;
		self::$config = $view->config;
	}

	/**
	 * sanitise Anchor
	 *
	 * @version	6th July 2016
	 * @since	6th July 2016
	 * @params	string		$dirty
	 * @return	string		Clean
	 */
	public static function sanitiseAnchor($dirty)
	{
		return str_replace(array(' ', '.'), '', filter_var($dirty, FILTER_SANITIZE_STRING));
	}

	/**
	 * sanitise Anchor
	 *
	 * @version	6th July 2016
	 * @since	6th July 2016
	 * @params	string		$dirty
	 * @return	string		Clean
	 */
	public static function getView()
	{
		if (! self::$view instanceof \Gibbon\core\view)
			self::$view = new \Gibbon\core\view();
		return self::$view;
	}

}

