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
	 * get Week Number
	 *
	 * @version	21st April 2016
	 * @since	21st April 2016
	 * @param	string		Date
	 * @return	mixed		ModuleID or false
	 */
	public static function getWeekNumber($date) {
		$week=0 ;
		$session = new session();
		$pdo = self::getPDO();
		
		$dataWeek=array("gibbonSchoolYearID"=>$session->get("gibbonSchoolYearID"));
		$sqlWeek="SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber" ;
		$resultWeek=$pdo->executeQuery($dataWeek, $sqlWeek);
		while ($rowWeek=$resultWeek->fetch()) {
			$firstDayStamp = strtotime($rowWeek["firstDay"]) ;
			$lastDayStamp = strtotime($rowWeek["lastDay"]) ;
			while (date("D",$firstDayStamp)!="Mon") {
				$firstDayStamp=$firstDayStamp-86400 ;
			}
			$head=$firstDayStamp ;
			while ($head<=($date) AND $head<($lastDayStamp+86399)) {
				$head=$head+(86400*7) ;
				$week++ ;
			}
			if ($head<($lastDayStamp+86399)) {
				break ;
			}
		}
	
		if ($week<=0) {
			return false ;
		}
		else {
			return $week ;
		}
	}

	/**
	 * set Current School Year
	 *
	 * GET THE CURRENT YEAR AND SET IT AS A GLOBAL VARIABLE
	 * @version	27th July 2016
	 * @since	21st April 2016
	 * @return	void
	 */
	static public function setCurrentSchoolYear(view $view)
	{
		$syObj = new \Gibbon\Record\schoolYear($view);
		return $syObj->setCurrentSchoolYear();
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
	 * get Smart Workflow Help
	 * 
	 * @version	3rd May 2016
	 * @since	copied from functions.php
	 * @param	integer		$step	Workflow Step
	 * @return	string		HTML Output
	 */
	public static function getSmartWorkflowHelp($step = 0) {
		$output=false ;
		$pdo = self::getPDO();
		$session = self::getSession();
		
		$data=array("gibbonPersonID"=>$_SESSION["gibbonPersonID"]);
		$sql="SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID" ;
		$result=$pdo->executeQuery($data, $sql);
		if ($result->rowCount()==1) {
			$row=$result->fetch() ;
			if ($row["smartWorkflowHelp"]=="Y") {
				$output="<div id='smartWorkflowHelp' class='message' style='padding-top: 14px'>" ;
					$output.="<div style='padding: 0 7px'>" ;
						$output.="<span style='font-size: 175%'><i><strong>" .  $this->__('Smart Workflow') . "</strong></i> " .  $this->__('Getting Started') . "</span><br/>" ;
						$output.= $this->__("Designed and built by teachers, Gibbon's Smart Workflow takes care of the boring stuff, so you can get on with teaching.") . "<br/>" ;
					$output.="</div>" ;
					$output.="<table cellspacing='0' style='width: 100%; margin: 10px 0px; border-spacing: 4px;'>" ;
						$output.="<tr>" ;
							if ($step==1) {
								$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
									$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>" .  $this->__('One') . "</span><br/>" ;
									$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>" . sprintf( $this->__('Create %1$s Outcomes'), "<br/>") . "</span><br/></span>" ;
								$output.="</td>" ;
							}
							else {
								$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
									$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>" .  $this->__('One') . "</span><br/>" ;
									$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . GIBBON_URL . "index.php?q=/modules/Planner/outcomes.php'>" . sprintf( $this->__('Create %1$s Outcomes'), "<br/>") . "</span><br/></a>" ;
								$output.="</td>" ;
							}
							if ($step==2) {
								$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
									$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>" .  $this->__('Two') . "</span><br/>" ;
									$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>" . sprintf( $this->__('Plan & Deploy %1$s Smart Units'), "<br/>") . "</span><br/></span>" ;
								$output.="</td>" ;
							}
							else {
								$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
									$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>" .  $this->__('Two') . "</span><br/>" ;
									$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . GIBBON_URL . "index.php?q=/modules/Planner/units.php'>" . sprintf( $this->__('Plan & Deploy %1$s Smart Units'), "<br/>") . "</span><br/></a>" ;
								$output.="</td>" ;
							}
							if ($step==3) {
								$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
									$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>" .  $this->__('Three') . "</span><br/>" ;
									$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>" . sprintf( $this->__('Share, Teach %1$s & Interact'), "<br/>") . "</span><br/></span>" ;
								$output.="</td>" ;
							}
							else {
								$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
									$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>" .  $this->__('Three') . "</span><br/>" ;
									$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . GIBBON_URL . "index.php?q=/modules/Planner/planner.php'>" . sprintf( $this->__('Share, Teach %1$s & Interact'), "<br/>") . "</span><br/></a>" ;
								$output.="</td>" ;
							}
							if ($step==4) {
								$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
									$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>" .  $this->__('Four<') . "/span><br/>" ;
									$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>" . sprintf( $this->__('Assign & Collect %1$s Work'), "<br/>") . "</span><br/></span>" ;
								$output.="</td>" ;
							}
							else {
								$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
									$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>" .  $this->__('Four') . "</span><br/>" ;
								$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . GIBBON_URL . "index.php?q=/modules/Planner/planner_deadlines.php'>" . sprintf( $this->__('Assign & Collect %1$s Work'), "<br/>") . "</span><br/></a>" ;
								$output.="</td>" ;
							}
							if ($step==5) {
								$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
									$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>" .  $this->__('Five') . "</span><br/>" ;
									$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>" . sprintf( $this->__('Assess & Give %1$s Feedback'), "<br/>") . "</span><br/></span>" ;
								$output.="</td>" ;
							}
							else {
								$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
									$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>" .  $this->__('Five') . "</span><br/>" ;
								$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . GIBBON_URL . "index.php?q=/modules/Markbook/markbook_view.php'>" . sprintf( $this->__('Assess & Give %1$s Feedback'), "<br/>") . "</span><br/></a>" ;
								$output.="</td>" ;
							}
						$output.="</tr>" ;
						if ($step!="") {
							$output.="<tr>" ;
								$output.="<td style='text-align: justify; font-size: 125%; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 15px 4px' colspan=5>" ;
									if ($step==1) {
										$output.= $this->__('<strong>Outcomes</strong> provide a way to plan and track what is being taught in school, and so are a great place to get started.<br/><br/>Click on the "Add" button (below this message, on the right) to add a new outcome, which can either be school-wide, or attached to a particular department.') . "<br/>" ;
										$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'>" .  $this->__('<strong>Note</strong>: You need to be in a department, with the correct permissions, in order to be able to do this.') . " " . sprintf( $this->__('Please contact %1$s for help.'), "<a href='mailto:" . $session->get("organisationAdministratorEmail") . "'>" . $session->get("organisationAdministratorName") . "</a>") . "</div>" ;
									}
									else if ($step==2) {
										$output.= $this->__('<strong>Smart Units</strong> support you in the design of course content, and can be quickly turned into individual lesson plans using intuitive drag and drop. Smart Units cut planning time dramatically, and support ongoing improvement and reuse of content.<br/><br/>Choose a course, using the dropdown menu on the right, and then click on the "Add" button (below this message, on the right) to add a new unit. Once your master unit is complete, deploy it to a class to create your lesson plans.') . "<br/>" ;
										$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'>" .  $this->__('<strong>Note</strong>: You need to be in a department, with the correct permissions, in order to be able to do this.') . " " . sprintf( $this->__('Please contact %1$s for help.'), "<a href='mailto:" . $session->get("organisationAdministratorEmail") . "'>" . $session->get("organisationAdministratorName") . "</a>") . "</div>" ;
									}
									else if ($step==3) {
										$output.=sprintf( $this->__('<strong>Planner</strong> supports online lesson plans which can be shared with students, parents and other teachers. Create your lesson by hand, or automatically via %1$sSmart Units%2$s. Lesson plans facilitate sharing of course content, homework assignment and submission, text chat, and attendance taking.<br/><br/>Choose a date or class, using the menu on the right, and then click on the "Add" button (below this message, on the right) to add a new unit.'), "<a href='" . GIBBON_URL . "index.php?q=/modules/Planner/units.php'>", "</a>") . "<br/>" ;
										$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'>" .  $this->__('<strong>Note</strong>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this.') . " " . sprintf( $this->__('Please contact %1$s for help.'), "<a href='mailto:" . $session->get("organisationAdministratorEmail") . "'>" . $session->get("organisationAdministratorName") . "</a>") . "</div>" ;
									}
									else if ($step==4) {
										$output.=sprintf( $this->__('<strong>Homework + Deadlines</strong> allows teachers and students to see upcoming deadlines, cleanly displayed in one place. Click on an entry to view the details for that piece of homework, and the lesson it is attached to.<br/><br/>Homework can be assigned using the %1$sPlanner%2$s, which also allows teachers to view all submitted work, and records late and incomplete work.'), "<a href='" . GIBBON_URL . "index.php?q=/modules/Planner/planner.php'>", "</a>") . "<br/>" ;
										$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'>" .  $this->__('<strong>Note</strong>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this.') . " " . sprintf( $this->__('Please contact %1$s for help.'), "<a href='mailto:" . $session->get("organisationAdministratorEmail") . "'>" . $session->get("organisationAdministratorName") . "</a>") . "</div>" ;
									}
									else if ($step==5) {
										$output.=sprintf( $this->__('<strong>Markbook</strong> provides an organised way to assess, record and report on student progress. Use grade scales, rubrics, comments and file uploads to keep students and parents up to date. Link markbooks to the %1$sPlanner%2$s, and see student work as you are marking it.<br/><br/>Choose a class from the menu on the right, and then click on the "Add" button (below this message, on the right) to create a new markbook column.'), "<a href='" . GIBBON_URL . "index.php?q=/modules/Planner/planner.php'>", "</a>") . "<br/>" ;
										$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'>" .  $this->__('<strong>Note</strong>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this.') . " " . sprintf( $this->__('Please contact %1$s for help.'), "<a href='mailto:" . $session->get("organisationAdministratorEmail") . "'>" . $session->get("organisationAdministratorName") . "</a>") . "</div>" ;
									}
								$output.="</td>" ;
							$output.="</tr>" ;
						}
					$output.="</table>" ;
					$output.="<div style='text-align: right; font-size: 90%; padding: 0 7px'>" ;
						$output.="<a title='".  $this->__('Dismiss Smart Workflow Help') . "' onclick='$(\"#smartWorkflowHelp\").fadeOut(1000); $.ajax({ url: \"" . GIBBON_URL . "index_SmartWorkflowHelpAjax.php\"})' href='#'>" .  $this->__('Dismiss Smart Workflow Help') . "</a>" ;
					$output.="</div>" ;
				$output.="</div>" ;
			}
		}
	
		return $output ;
	}

	/**
	 * get Unit
	 * 
	 * Get information on a unit of work, inlcuding the possibility that it is a hooked unit
	 * @version	8th September 2016
	 * @since	copied from functions.php
	 * @param	integer		$unitID  Unit ID
	 * @param	integer		$hookID  Hook ID
	 * @param	integer		$courseClassID  Course Class ID
	 * @return	string		HTML Output
	 */
	static public function getUnit($unitID, $hookID, $courseClassID) {
		
		$obj = new \Gibbon\Record\unit($this->view);
		return $obj->getUnit($unitID, $hookID, $courseClassID);
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
	 * get Alert Bar
	 *
	 * @version	3rd May 2016
	 * @since	copied from functions.php
	 * @param	integer		$personID	Person ID
	 * @param	string		$$privacy 
	 * @param	string		$divExtras 
	 * @param	boolean		$div
	 * @param	boolean		$large
	 * @return	string		HTML
	 */
	public static function getAlertBar($personID, $privacy="", $divExtras="", $div = true, $large = false) {
		$output="" ;
		
		$pdo = self::getPDO();
		$session = self::getSession();
		$config = self::getConfig();
		$security = self::$view->getSecurity();
		
		$width="14" ;
		$height="13" ;
		$fontSize="12" ;
		$totalHeight="16" ;
		if ($large) {
			$width="42" ;
			$height="35" ;
			$fontSize="24" ;
			$totalHeight="45" ;
		}
	
		$aObj = new \Gibbon\Record\alertLevel(self::$view);
		$highestAction = $security->getHighestGroupedAction("/modules/Students/student_view_details.php") ;
		if ($highestAction=="View Student Profile_full") {
			if ($div) {
				$output.="<div $divExtras style='width: 83px; text-align: right; height: " . $totalHeight . "px; padding: 3px 0px; margin: auto'><strong>" ;
			}
	
			//Individual Needs
			$obj = new \Gibbon\Record\INPersonDescriptor(self::$view);
			$w = $obj->findFirst("SELECT * 
				FROM `gibbonINPersonDescriptor` 
					JOIN `gibbonAlertLevel` ON `gibbonINPersonDescriptor`.`gibbonAlertLevelID` = `gibbonAlertLevel`.`gibbonAlertLevelID` 
				WHERE `gibbonPersonID` = :gibbonPersonID 
				ORDER BY `sequenceNumber` DESC",
				array("gibbonPersonID"=>$personID)
				);

			if (! is_null($w)) {
				if ($obj->rowCount()==1) 
					$title =  $this->__(array('Individual Need alert is set. Alert level of %1$s.', array($w->getField('name')))) ;
				else 
					$title =  $this->__(array('%1$s Individual Needs alerts are set. Maximum alert level of %2$s.', array($obj->rowCount(), $w->getField('name')))) ;

				$output.="<a style='font-size: " . $fontSize . "px; color: #" . $w->getField('color') . "; text-decoration: none' href='" . GIBBON_URL . "index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $personID . "&subpage=Individual Needs'><div title='$title' class='alertBar' style='float: right; text-align: center; vertical-align: middle; max-height: " . $height . "px; height: " . $height . "px; width: " . $width . "px; border-top: 4px solid #" . $highestColour . "; margin: -2px 0 0 2px; background-color: #" . $w->getField('colorBG') . "'>" .  $this->__('IN') . "</div></a>" ;
			}
	
			//Academic
			$alertLevelID="" ;
			$dataAlert=array("gibbonPersonIDStudent"=>$personID, "gibbonSchoolYearID"=>$session->get("gibbonSchoolYearID"));
			$sqlAlert="SELECT * 
				FROM gibbonMarkbookEntry 
					JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) 
					JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
					JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) 
				WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent 
					AND (attainmentConcern='Y' OR effortConcern='Y') 
					AND complete='Y' 
					AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$resultAlert=$pdo->executeQuery($dataAlert, $sqlAlert);
			if ( ! $pdo->getQuerySuccess()) $session->append("sidebarExtra", "<div class='error'>" . $pdo->getError() . "</div>" );
			if ($resultAlert->rowCount()>1 AND $resultAlert->rowCount()<=4) {
				$alertLevelID=003 ;
			}
			else if ($resultAlert->rowCount()>4 AND $resultAlert->rowCount()<=8) {
				$alertLevelID=002 ;
			}
			else if ($resultAlert->rowCount()>8) {
				$alertLevelID=001 ;
			}
			if (! empty($alertLevelID)) {
				$alert = $aObj->getAlert($alertLevelID) ;
				if ($alert!=FALSE) {
					$title=sprintf( $this->__('Student has a %1$s alert for academic concern in the current academic year.'),  $this->__($alert["name"])) ;
					$output.="<a style='font-size: " . $fontSize . "px; color: #" . $alert["color"] . "; text-decoration: none' href='" . GIBBON_URL . "index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $personID . "&subpage=Markbook&filter=" . $session->get("gibbonSchoolYearID") . "'><div title='$title' class='alertBar' style='max-height: " . $height . "px; height: " . $height . "px; width: " . $width . "px; border-top: 2px solid #" . $alert["color"] . "; background-color: #" . $alert["colorBG"] . "'>" .  $this->__('A') . "</div></a>" ;
				}
			}
	
			//Behaviour
			$alertLevelID="" ;
			$dataAlert=array("gibbonPersonID"=>$personID);
			$sqlAlert="SELECT * 
				FROM gibbonBehaviour 
				WHERE gibbonPersonID=:gibbonPersonID 
					AND type='Negative' 
					AND date>'" . date("Y-m-d", (time()-(24*60*60*60))) . "'" ;
			$resultAlert=$pdo->executeQuery($dataAlert, $sqlAlert);
			if ( ! $pdo->getQuerySuccess()) $session->append("sidebarExtra", "<div class='error'>" . $pdo->getError() . "</div>" );
			if ($resultAlert->rowCount()>1 AND $resultAlert->rowCount()<=4) {
				$alertLevelID=003 ;
			}
			else if ($resultAlert->rowCount()>4 AND $resultAlert->rowCount()<=8) {
				$alertLevelID=002 ;
			}
			else if ($resultAlert->rowCount()>8) {
				$alertLevelID=001 ;
			}
			if ($alertLevelID!="") {
				$alert = helper::getAlert($alertLevelID) ;
				if ($alert!=FALSE) {
					$title =  $this->__(array('Student has a %1$s alert for behaviour over the past 60 days.', array( $this->__($alert["name"])))) ;
					$output.="<a style='font-size: " . $fontSize . "px; color: #" . $alert["color"] . "; text-decoration: none' href='" . GIBBON_URL . "index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $personID . "&subpage=Behaviour'><div title='$title' class='alertBar' style='float: right; text-align: center; vertical-align: middle; max-height: " . $height . "px; height: " . $height . "px; width: " . $width . "px; border-top: 2px solid #" . $alert["color"] . ";  background-color: #" . $alert["colorBG"] . "'>" .  $this->__('B') . "</div></a>" ;
				}
			}
	
			//Medical
			$alert = self::getHighestMedicalRisk($personID, self::$view) ;
			if ($alert) {
				$title =  $this->__(array('Medical alerts are set, up to a maximum of %1$s', array($alert['name']))) ;
				$output .= "<a style='font-size: " . $fontSize . "px; color: #" . $alert['colour'] . "; text-decoration: none' href='" . GIBBON_URL . "index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $personID . "&subpage=Medical'>
				<div title='$title' class='alertBar' style='max-height: " . $height . "px; height: " . $height . "px; width: " . $width . "px; border-top: 4px solid #" . $alert['colour'] . "; background-color: #" . $alert['colourBG'] . "'><strong>" .  $this->__('M') . "</strong></div>
				</a>" ;
			}
	
			//Privacy
			$privacySetting = $config->getSettingByScope("User Admin", "privacy" ) ;
			if ($privacySetting=="Y" && ! empty($privacy)) {
				$alert=helper::getAlert(001) ;
				$title=sprintf( $this->__('Privacy is required: %1$s'), $privacy) ;
				$output.="<div title='$title' class='alertBar' style='font-size: " . $fontSize . "px; float: right; text-align: center; vertical-align: middle; max-height: " . $height . "px; height: " . $height . "px; width: " . $width . "px; border-top: 4px solid #" . $alert["color"] . "; color: #" . $alert["color"] . "; background-color: #" . $alert["colorBG"] . "'>" .  $this->__('P') . "</div>" ;
			}
	
			if ($div) {
				$output.="</div>" ;
			}
		}
	
		return $output ;
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
	 * get User Photo
	 *
	 * Gets a given user photo, or a blank if not available
	 * @version	12th August 2016
	 * @since	copied from functions.php
	 * @param	string		$path	Photo Path
	 * @param	string		$size	
	 * @return	string		HTML
	 */
	public static function getUserPhoto($path, $size) {
		$output = "" ;
		
		$sizeStyle = $size == 240 ? "style='width: 240px;'" : "style='width: 75px;'" ;

		if (empty($path) || ! file_exists(GIBBON_ROOT . $path)) 
			$output = "<img $sizeStyle class='user' src='" . GIBBON_URL . "themes/" . self::getSession()->get("theme.Name") . "/img/anonymous_" . $size . ".jpg'/><br/>" ;
		else 
			$output = "<img $sizeStyle class='user' src='" . GIBBON_URL . $path . "'/><br/>" ;

		return $output ;
	}

	/**
	 * days Until Next Birthday
	 *
	 * Accepts birthday in mysql date (YYYY-MM-DD) 
	 * @version	3rd May 2016
	 * @since	copied from functions.php
	 * @param	date		$birthday	Birth Date
	 * @return	integer		Days to next Birthday
	 */
	public static function daysUntilNextBirthday($birthday) {
		$dtz = new \DateTimeZone('UTC');
		$today = new \DateTime(date('Y-m-d', strtotime('now')), $dtz);
		$birthday = new \DateTime($birthday, $dtz);
		$x = $today->diff($birthday);
		$years = $x->y;
		if ($x->m + $x->d > 0) //  Check if the birthday is today.
			$years++;
		$interval = new \DateInterval('P'.$years.'Y');
		$birthday->add($interval);
		$d = $today->diff($birthday);
		
		return $d->days;
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

