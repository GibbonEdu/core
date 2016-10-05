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

/**
 * Finder
 *
 * @version	29th May 2016
 * @since	29th May 2016
 * @package	Gibbon
 * @subpackage	Core
 * @author	Craig Rayner
 */
class finder
{
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
	 * @var	Gibbon\view	$view	Gibbon View
	 */
	public $view ;
	
	/**
	 * @var	element	$el		Render Parameters
	 */
	public $el ;

	/**
	 * Constructor
	 *
	 * @version	6th July 2016
	 * @since	29th May 2016
	 * @params	Gibbon\view		$view
	 * @return	Gibbon\finder 
	 */
	public function __construct(view $view)
	{
		$this->pdo = $view->pdo;
		$this->session = $view->session;
		$this->config = $view->config;
		$this->view = $view ;
		return $this ;
	}
	/**
	 * get Fast Finder
	 *
	 * @version	6th July 2016
	 * @since	copied from functions.php
	 * @return	HTML String
	 */
	public function getFastFinder()
	{
		//Show student and staff quick finder
		$this->el = $this->session->get("display.studentFastFinder") ;
		
		if (empty($this->el->refresh) || $this->el->refresh < 1)
		{
			$security = $this->view->getSecurity();
			if (empty($this->el)) $this->el = new \stdClass();
				
	
			$this->el->studentIsAccessible = $security->isActionAccessible("/modules/students/student_view.php") ;
			$this->el->staffIsAccessible = $security->isActionAccessible("/modules/Staff/staff_view.php") ;
			$this->el->classIsAccessible = false ;
			$this->el->highestActionClass = $security->getHighestGroupedAction("/modules/Planner/planner.php") ;
			if ($this->view->getSecurity()->isActionAccessible("/modules/Planner/planner.php") AND $this->el->highestActionClass!="Lesson Planner_viewMyChildrensClasses") 
				$this->el->classIsAccessible = true ;
	
			//Get list
			$dataList=array("gibbonRoleID"=>$this->session->get("gibbonRoleIDCurrent"));
			$sqlList="(SELECT DISTINCT concat(gibbonModule.name, '/', gibbonAction.entryURL) AS id, SUBSTRING_INDEX(gibbonAction.name, '_', 1) AS name, 'Action' AS type 
				FROM `gibbonModule`, gibbonAction, gibbonPermission 
				WHERE (active='Y') 
					AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) 
					AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) 
					AND (gibbonPermission.gibbonRoleID=:gibbonRoleID))" ;
			if ($this->el->staffIsAccessible) {
				$sqlList.=" UNION (SELECT gibbonPerson.gibbonPersonID AS id, concat(surname, ', ', preferredName) AS name, 'Staff' AS type 
					FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) 
					WHERE status='Full'
						AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') 
						AND (dateEnd IS NULL OR dateEnd>='" . date("Y-m-d") . "'))" ;
			}
			if ($this->el->studentIsAccessible) {
				$dataList["gibbonSchoolYearID"] = $this->session->get("gibbonSchoolYearID") ;
				$sqlList.=" UNION (SELECT gibbonPerson.gibbonPersonID AS id, concat(surname, ', ', preferredName, ' (', gibbonRollGroup.name, ')') AS name, 'Student' AS type 
					FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup 
					WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID 
						AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID 
						AND status='FULL' 
						AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') 
						AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') 
						AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID)" ;
			}
			if ($this->el->classIsAccessible) {
				if ($this->el->highestActionClass == "Lesson Planner_viewEditAllClasses" || $this->el->highestActionClass == "Lesson Planner_viewAllEditMyClasses") {
					$dataList["gibbonSchoolYearID2"] =$this->session->get("gibbonSchoolYearID");
					$sqlList.=" UNION (SELECT gibbonCourseClass.gibbonCourseClassID AS id, concat(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name, 'Class' AS type 
						FROM gibbonCourseClass 
							JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) 
						WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID2)" ;
				}
				else
				{
					$dataList["gibbonSchoolYearID3"] = $this->session->get("gibbonSchoolYearID") ;
					$dataList["gibbonPersonID"] = $this->session->get("gibbonPersonID") ;
					$sqlList.=" UNION (SELECT gibbonCourseClass.gibbonCourseClassID AS id, concat(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name, 'Class' AS type 
						FROM gibbonCourseClassPerson 
							JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
							JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) 
						WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID3 
							AND gibbonPersonID=:gibbonPersonID)" ;
				}
			}
			$sqlList.=" ORDER BY type, name" ;
	
			$resultList = $this->pdo->executeQuery($dataList, $sqlList);
			if (! $this->pdo->getQuerySuccess()) {
				$this->el->sqlError = $this->pdo->getError();
				$this->el->output = false ;
				$this->session->set("display.studentFastFinder", json_encode($this->el)) ;
				return $this->el ;
			}
			$this->el->studentCount = 0 ;
			$this->el->list = array() ;
			$id = $this->session->notEmpty('theme.settings.tokeninput.id') ?$this->session->get('theme.settings.tokeninput.id') : 'id';
			$name = $this->session->notEmpty('theme.settings.tokeninput.name') ?$this->session->get('theme.settings.tokeninput.name') : 'name';
			$x = 0;
			while ($rowList = $resultList->fetch()) {
				$this->el->list[$x][$id] = substr($rowList["type"],0,3) . "-" . $rowList["id"];
				$this->el->list[$x][$name] =  $this->view->htmlPrep($rowList["type"]) . " - " . $this->view->htmlPrep($rowList["name"]) ;
				$x++;
				if ($rowList["name"] == "Sound Alarm") { //Special lockdown entry
					if ($this->view->getSecurity()->isActionAccessible("/modules/System Admin/alarm.php")) {
						$this->el->list[$x][$id] = substr($rowList["type"],0,3) . "-" . $rowList["id"] ;
						$this->el->list[$x][$name] = $this->view->htmlPrep($rowList["type"]) . " - Lockdown" ;
						$x++;
					}
				}
				if ($rowList["type"] == "Student") {
					$this->el->studentCount++ ;
				}
			}
			$this->el->list = json_encode($this->el->list);
			$this->el->output = true ;
			$this->el->refresh = $this->view->getConfig()->get('cache', 15);
			if (empty($this->el->studentCount) || $this->el->studentCount < 0)
			{
				$this->el->output = false;
				$this->el->refresh = 0;
			}
		}
		else
			$this->el->refresh--;
		$this->session->set("display.studentFastFinder", $this->el) ;
		return $this->el ;
	}
}