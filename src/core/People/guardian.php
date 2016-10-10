<?php
/**
 * Person Record
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
 * @version	24th April 2016
 * @since	24th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
*/
/**
 */
namespace Gibbon\People;

use Gibbon\core\view ;
use Gibbon\core\tabs ;
use Gibbon\Record\person ;
use Module\Timetable\Functions\functions as timeTableFunctions ;
use stdClass ;

/**
 * Person Record Class
 *
 * @version	8th September 2016
 * @since	8th September 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage Person
 */
class guardian extends person
{
	use \Gibbon\People\user ;

	/**
	 * Gibbon\core\view
	 */
	protected $view ;

	/**
	 * Gibbon\Record\alertLevel
	 */
	protected $alertLevel ;

	/**
	 * Gibbon\Record\schoolYear
	 */
	protected $schoolYear ;

	/**
	 * Gibbon\Record\unit
	 */
	protected $unit ;

	/**
	 * Gibbon\Record\like
	 */
	protected $like ;
	
	/**
	 * Construct
	 *
	 * @version 8th September 2016
	 * @since	8th September 2016
	 * @param	Gibbon\core\view
	 * @param	integer		$id  (of Person)
	 * @return	stdClass	Record
	 */
	public function __construct(view $view, $id = null)
	{
		return parent::__construct($view, $id);
	}

	/**
	 * get Parent Dashboard Contents
	 *
	 * Gets the contents of a single parent dashboard, for the student specified
	 * @version 8th September 2016
	 * @since	8th September 2016
	 * @param	integer		$personID  (of Person)
	 * @return	string
	 */
	public function getParentDashboardContents($personID)
	{
		$return = false;
		$this->alert = $this->getAlertLevel()->getAlert(002);
		$entryCount = 0;	
	
		$this->getPlanner($personID);
		
		$this->getGrades($personID);
		
		$this->getDeadlines($personID);
		
		$this->getTimetable($personID);

		$this->getActivities($personID);
		
		$this->getHooks($personID);
		if (! $this->planner->status && ! $this->grades->status && ! $this->deadlines->status && ! $this->timetable->status && ! $this->activities->status && count($this->hooks->content) < 1) {
			$return .= $this->view->returnMessage('There are no records to display.', 'warning');
		} else {
			
			$tabs = new tabs($this->view);

			if ($this->planner->status || $this->grades->status || $this->deadlines->status) 
				$tabs->addTab($this->planner->content . $this->grades->content . $this->deadlines->content, $this->view->__('Learning Overview'));

			if ($this->timetable->status) 
				$tabs->addTab($this->timetable->content, $this->view->__('Timetable'));

			if ($this->activities->status) 
				$tabs->addTab($this->activities->content, $this->view->__('Activities'));

			foreach ($this->hooks->content as $hook)
				$tabs->addTab($this->view->getRecord('hook')->includeHook('/modules/'.$hook['sourceModuleName'].'/'.$hook['sourceModuleInclude']), $this->view->__($hook['name']));

			$return .= $tabs->renderTabs($personID, '');
		}
	
		return $return;
	}

	/**
	 * get Alert Level
	 *
	 * @version 8th September 2016
	 * @since	8th September 2016
	 * @return	Gibbon\Record\alertLevel
	 */
	private function getAlertLevel()
	{
		if ($this->alertLevel instanceof alertLevel)
			return $this->alertLevel;
		$this->alertLevel = $this->view->getRecord('alertLevel');
		return $this->alertLevel ;
	}

	/**
	 * get School Year
	 *
	 * @version 8th September 2016
	 * @since	8th September 2016
	 * @return	Gibbon\Record\schoolYear
	 */
	private function getSchoolYear()
	{
		if ($this->schoolYear instanceof schoolYear)
			return $this->schoolYear;
		$this->schoolYear = $this->view->getRecord('schoolYear');
		return $this->schoolYear ;
	}

	/**
	 * get Security
	 *
	 * @version 8th September 2016
	 * @since	8th September 2016
	 * @return	Gibbon\core\security
	 */
	private function getSecurity()
	{
		return $this->view->getSecurity() ;
	}

	/**
	 * get Unit
	 *
	 * @version 8th September 2016
	 * @since	8th September 2016
	 * @return	Gibbon\Record\unit
	 */
	private function getUnit()
	{
		if ($this->unit instanceof unit)
			return $this->unit;
		$this->unit = $this->view->getRecord('unit');
		return $this->unit ;
	}

	/**
	 * get Like
	 *
	 * @version 8th September 2016
	 * @since	8th September 2016
	 * @return	Gibbon\Record\like
	 */
	private function getLike()
	{
		if ($this->like instanceof like)
			return $this->like;
		$this->like = $this->view->getRecord('like');
		return $this->like ;
	}

	/**
	 * get Planner
	 *
	 * @version 10th October 2016
	 * @since	10th October 2016
	 * @param	integer		$personID
	 * @return	void
	 */
	private function getPlanner($personID)
	{
		if (! empty($this->planner) && $personID == $this->planner->personID) return ;
	
		$this->planner = new stdClass();
		$this->planner->status = false;
		$this->planner->classes = false;
		$this->planner->content = '';
		$this->planner->personID = $personID ;
		
		//PREPARE PLANNER SUMMARY
		$plannerOutput = "<span style='font-size: 85%; font-weight: bold'>".$this->view->__("Today's Classes")."</span> . <span style='font-size: 70%'><a href='".GIBBON_URL.'index.php?q=/modules/Planner/planner.php&search='.$personID."'>".$this->view->__('View Planner').'</a></span>';
	
		$classes = false;
		$date = date('Y-m-d');
		if ($this->getSchoolYear()->isSchoolOpen($date) && $this->getSecurity()->isActionAccessible('/modules/Planner/planner.php') && $this->session->notEmpty('username')) {
			$data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'date' => $date, 'gibbonPersonID' => $personID, 'date2' => $date, 
				'gibbonPersonID2' => $personID, 'studentLeft' => 'Student - Left', 'teacherLeft' => 'Teacher - Left');
			$sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, 
					gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, 
					viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, 
					gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime 
				FROM gibbonPlannerEntry 
					JOIN gibbonCourseClass ON gibbonPlannerEntry.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
					JOIN gibbonCourseClassPerson ON gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID
					JOIN gibbonCourse ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
					LEFT JOIN gibbonPlannerEntryStudentHomework ON gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID = gibbonPlannerEntry.gibbonPlannerEntryID 
						AND gibbonPlannerEntryStudentHomework.gibbonPersonID = gibbonCourseClassPerson.gibbonPersonID
				WHERE gibbonSchoolYearID = :gibbonSchoolYearID 
					AND date=:date 
					AND gibbonCourseClassPerson.gibbonPersonID = :gibbonPersonID 
					AND NOT role = :studentLeft 
					AND NOT role = :teacherLeft) 
				UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, 
						gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, 
						viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, 
						NULL AS myHomeworkDueDateTime 
					FROM gibbonPlannerEntry
						JOIN gibbonCourseClass ON gibbonPlannerEntry.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
						JOIN gibbonPlannerEntryGuest ON gibbonPlannerEntryGuest.gibbonPlannerEntryID = gibbonPlannerEntry.gibbonPlannerEntryID
						JOIN gibbonCourse ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
					WHERE date=:date2 
						AND gibbonPlannerEntryGuest.gibbonPersonID = :gibbonPersonID2) 
				ORDER BY date, timeStart";
			$result = $this->view->getRecord('plannerEntry')->findAll($sql, $data);
			if (! $this->view->getRecord('plannerEntry')->getSuccess())
				$plannerOutput .= $this->returnMessage($this->view->getRecord('plannerEntry')->getError());
			if (count($result) > 0) {
				$classes = true;
				$plannerOutput .= $this->renderReturn('guardian.planner.start');
	
				foreach($result as $row) {
					$row->date = $date;
					$row->unit = $this->view->getRecord('unit')->getUnit($row->getField('gibbonUnitID'), $row->getField('gibbonHookID'), $row->getField('gibbonCourseClassID'));
					$el->likesGiven = $this->getLike()->countLikesByContextAndGiver('Planner', 'gibbonPlannerEntryID', $row->getField('gibbonPlannerEntryID'), $this->session->get('gibbonPersonID'));
					$el->personID = $personID ;
					$plannerOutput .= $this->renderReturn('guardian.planner.member', $row);
				}
				$plannerOutput .= '</tbody>
				</table>';
			}
		}
		if (! $classes) {
			$plannerOutput .= $this->view->returnMessage('There are no records to display.');
		}
		$this->planner->status = true ;
		$this->planner->classes = $classes ;
		$this->planner->content = $plannerOutput ;
	}

	/**
	 * get Grades
	 *
	 * @version 10th October 2016
	 * @since	10th October 2016
	 * @param	integer		$personID
	 * @return	void
	 */
	private function getGrades($personID)
	{
		if (! empty($this->grades) && $personID == $this->grades->personID) return ;

		$this->grades = new stdClass() ;
		$this->grades->status = false ;
		$this->grades->content = '' ;
		$this->grades->personID = $personID ;
		
		//PREPARE RECENT GRADES
		$gradesOutput = "<div style='margin-top: 20px'><span style='font-size: 85%; font-weight: bold'>".$this->view->__('Recent Grades')."</span> . <span style='font-size: 70%'><a href='".GIBBON_URL.'index.php?q=/modules/Markbook/markbook_view.php&search='.$personID."'>".$this->view->__('View Markbook').'</a></span></div>';
		$grades = false;
	
		//Get alternative header names
		$attainmentAlternativeName = $this->config->getSettingByScope('Markbook', 'attainmentAlternativeName');
		$attainmentAlternativeNameAbrev = $this->config->getSettingByScope('Markbook', 'attainmentAlternativeNameAbrev');
		$effortAlternativeName = $this->config->getSettingByScope('Markbook', 'effortAlternativeName');
		$effortAlternativeNameAbrev = $this->config->getSettingByScope('Markbook', 'effortAlternativeNameAbrev');
	
		$dataEntry = array('schoolYearID' => $this->session->get('gibbonSchoolYearID'), 'personID' => $personID, 'yes1' => 'Y', 'yes2' => 'Y', 'date' => date('Y-m-d'));
		$sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, 
				gibbonMarkbookEntry.comment AS comment 
			FROM gibbonMarkbookEntry 
				JOIN gibbonMarkbookColumn ON gibbonMarkbookEntry.gibbonMarkbookColumnID = gibbonMarkbookColumn.gibbonMarkbookColumnID
				JOIN gibbonCourseClass ON gibbonMarkbookColumn.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
				JOIN gibbonCourse ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
			WHERE gibbonSchoolYearID = :schoolYearID 
				AND gibbonPersonIDStudent = :personID 
				AND complete = :yes1 
				AND completeDate <= :date
				AND viewableParents = :yes2 
			ORDER BY completeDate DESC LIMIT 0, 3";
		$resultEntry = $this->view->getRecord('markbookEntry')->findAll($sqlEntry, $dataEntry);
		if (! $this->view->getRecord('markbookEntry')->getSuccess())
			$this->returnDisplay($this->view->getRecord('markbookEntry')->getError());
		if (count($resultEntry) > 0) {
			$showParentAttainmentWarning = $this->config->getSettingByScope('Markbook', 'showParentAttainmentWarning');
			$showParentEffortWarning = $this->config->getSettingByScope('Markbook', 'showParentEffortWarning');
			$grades = true; 
			$gradesOutput .= $this->renderReturn('guardian.grades.start');
	
			foreach($resultEntry as $rowEntry) {
				$rowEntry->alert = $this->alert;
				$gradesOutput .= $this->renderReturn('guardian.grades.member', $rowEntry);
			}
	
			$gradesOutput .= '</table>';
		}
		if ($grades) {
			$gradesOutput .= $this->view->returnMessage('There are no records to display.');
		}
		$this->grades->status = $grades ;
		$this->grades->content = $gradesOutput ;
	}

	/**
	 * get Deadlines
	 *
	 * @version 10th October 2016
	 * @since	10th October 2016
	 * @param	integer		$personID
	 * @return	void
	 */
	private function getDeadlines($personID)
	{
		if (! empty($this->deadlines) && $personID == $this->deadlines->personID) return ;

		$this->deadlines = new stdClass();
		$this->deadlines->status = false;
		$this->deadlines->content = '';
		$this->deadlines->personID = $personID;
		
		//PREPARE UPCOMING DEADLINES
		$deadlinesOutput = "<div style='margin-top: 20px'><span style='font-size: 85%; font-weight: bold'>".$this->view->__('Upcoming Deadlines')."</span> . <span style='font-size: 70%'><a href='".GIBBON_URL.'index.php?q=/modules/Planner/planner_deadlines.php&search='.$personID."'>".$this->view->__('View All Deadlines').'</a></span></div>';
		$deadlines = false;
	
		$data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $personID);
		$sql = "(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, 
				gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, 
				viewableParents, homework, homeworkDueDateTime, role 
			FROM gibbonPlannerEntry 
				JOIN gibbonCourseClass ON gibbonPlannerEntry.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
				JOIN gibbonCourseClassPerson ON gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID
				JOIN gibbonCourse ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
			WHERE gibbonSchoolYearID = :gibbonSchoolYearID 
				AND gibbonCourseClassPerson.gibbonPersonID = :gibbonPersonID
				AND NOT role = 'Student - Left'
				AND NOT role = 'Teacher - Left'
				AND homework = 'Y' 
				AND (role = 'Teacher' OR (role = 'Student' AND viewableStudents = 'Y'))
				AND homeworkDueDateTime > '".date('Y-m-d H:i:s')."' 
				AND ((date < '".date('Y-m-d')."') OR (date = '".date('Y-m-d')."' AND timeEnd <= '".date('H:i:s')."')))
			UNION (SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, 
					gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents,
					'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role 
				FROM gibbonPlannerEntry 
					JOIN gibbonCourseClass ON gibbonPlannerEntry.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
					JOIN gibbonCourseClassPerson ON gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID
					JOIN gibbonCourse ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
					JOIN gibbonPlannerEntryStudentHomework ON gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID = gibbonPlannerEntry.gibbonPlannerEntryID
						AND gibbonPlannerEntryStudentHomework.gibbonPersonID = gibbonCourseClassPerson.gibbonPersonID
				WHERE gibbonSchoolYearID = :gibbonSchoolYearID
					AND gibbonCourseClassPerson.gibbonPersonID = :gibbonPersonID
					AND NOT role = 'Student - Left'
					AND NOT role = 'Teacher - Left'
					AND (role = 'Teacher' OR (role = 'Student' AND viewableStudents = 'Y'))
					AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime > '".date('Y-m-d H:i:s')."' 
					AND ((date < '".date('Y-m-d')."') OR (date = '".date('Y-m-d')."' AND timeEnd <= '".date('H:i:s')."')))
			ORDER BY homeworkDueDateTime, type";
		$result = $this->view->getRecord('plannerEntry')->findAll($sql, $data);
		if (! $this->view->getRecord('plannerEntry')->getSuccess())
			$deadlinesOutput .= $this->view->returnMessage($this->view->getRecord('plannerEntry')->getError());
	
		if (count($result) > 0) {
			$deadlines = true;
			$deadlinesOutput .= "<ol style='margin-left: 15px'>";
			foreach($result as $w) {
				$row = (array)$w->returnRecord();
				$diff = (strtotime(substr($row['homeworkDueDateTime'], 0, 10)) - strtotime(date('Y-m-d'))) / 86400;
				$style = "style='padding-right: 3px;'";
				if ($diff < 2) {
					$style = "style='padding-right: 3px; border-right: 10px solid #CC0000; '";
				} elseif ($diff < 4) {
					$style = "style='padding-right: 3px; border-right: 10px solid #D87718; '";
				}
				$deadlinesOutput .= "<li $style>";
				$deadlinesOutput .= "<a href='".GIBBON_URL.'index.php?q=/modules/Planner/planner_view_full.php&search='.$personID.'&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&viewBy=date&date=$date&width=1000&height=550'>".$row['course'].'.'.$row['class'].'</a> ';
				$deadlinesOutput .= "<span style='font-style: italic'>".$this->view->__('Due at %1$s on %2$s', array(substr($row['homeworkDueDateTime'], 11, 5), $this->view->dateConvertBack(substr($row['homeworkDueDateTime'], 0, 10))));
				$deadlinesOutput .= '</li>';
			}
			$deadlinesOutput .= '</ol>';
		}
	
		if ($deadlines === false) 
			$deadlinesOutput .= $this->view->returnMessage('There are no records to display.');
		else
			$this->deadlines->status = true;
		$this->deadlines->content = $deadlinesOutput;
	}

	/**
	 * get Timetable
	 *
	 * @version 10th October 2016
	 * @since	10th October 2016
	 * @param	integer		$personID
	 * @return	void
	 */
	private function getTimetable($personID)
	{
		if (! empty($this->timetable) && $personID == $this->timetable->personID) return ;
	
		$this->timetable = new stdClass();
		$this->timetable->status = false;
		$this->timetable->content = '';
		$this->timetable->personID = $personID;
	
		//PREPARE TIMETABLE
		$this->timetable->status = false;
		if ($this->getSecurity()->isActionAccessible('/modules/Timetable/tt_view.php')) {
			$date = date('Y-m-d');
			if (isset($_POST['ttDate'])) {
				$date = $this->view->dateConvert($_POST['ttDate']);
			}
			$params = '';
			if (! $this->planner->status || ! $this->grades->status || $this->deadlines->status) 
				$params = '&tab=1';
			$ttf = new timeTableFunctions($this->view);
			$timetableOutputTemp = $ttf->renderTT($personID, null, null, $this->view->dateConvertToTimestamp($date), '', $params, 'narrow');
			if ($timetableOutputTemp !== false) {
				$this->timetable->status = true;
				$this->timetable->content = $timetableOutputTemp ;
			}
		}
	}	

	/**
	 * get Activities
	 *
	 * @version 10th October 2016
	 * @since	10th October 2016
	 * @param	integer		$personID
	 * @return	void
	 */
	private function getActivities($personID)
	{
		if (! empty($this->activities) && $personID == $this->activities->personID) return ;
	
		$this->activities = new stdClass();
		$this->activities->status = false;
		$this->activities->content = '';
		$this->activities->personID = $personID;
	
		//PREPARE ACTIVITIES
		$activitiesOutput = false;
		if (!($this->getSecurity()->isActionAccessible('/modules/Activities/activities_view.php'))) {
			$activitiesOutput .= $this->view->returnMessage('Your request failed because you do not have access to this action.');
		} else {
			$this->activities->status = true;

			$activitiesOutput .= $this->view->linkTopReturn(array('', array('q' => '/modules/Activities/activities_view.php', 'prompt' => 'View Available Activities')));
			$activitiesOutput .= $this->view->h4('Activities', array(), true);
	
			$dateType = $this->config->getSettingByScope('Activities', 'dateType');
			if ($dateType == 'Term') {
				$maxPerTerm = $this->config->getSettingByScope('Activities', 'maxPerTerm');
			}
			$dataYears = array('gibbonPersonID' => $personID);
			$sqlYears = "SELECT * 
				FROM gibbonStudentEnrolment 
					JOIN gibbonSchoolYear ON gibbonStudentEnrolment.gibbonSchoolYearID = gibbonSchoolYear.gibbonSchoolYearID
				WHERE gibbonSchoolYear.status = 'Current' 
					AND gibbonPersonID = :gibbonPersonID 
				ORDER BY sequenceNumber DESC";
			$resultYears = $this->view->getRecord('studentEnrolment')->findAll($sqlYears, $dataYears);
	
			if (count($resultYears) < 1) {
				$activitiesOutput .= $this->view->returnMessage('There are no records to display.');
			} else {
				$yearCount = 0;
				foreach($resultYears as $rowYears) {
					$yearCount++;
					$data = array('gibbonPersonID' => $personID, 'gibbonSchoolYearID' => $rowYears->getField('gibbonSchoolYearID'));
					$sql = "SELECT gibbonActivity.*, gibbonActivityStudent.status, NULL AS role 
						FROM gibbonActivity 
							JOIN gibbonActivityStudent ON gibbonActivity.gibbonActivityID = gibbonActivityStudent.gibbonActivityID
						WHERE gibbonActivityStudent.gibbonPersonID = :gibbonPersonID 
							AND gibbonSchoolYearID = :gibbonSchoolYearID 
							AND active = 'Y' 
						ORDER BY name";
					$result = $this->view->getRecord('activity')->findAll($sql, $data);
	
					if (count($result) < 1) {
						$activitiesOutput .= $this->view->returnMessage('There are no records to display.');
					} else {
						$activitiesOutput .= $this->renderReturn('guardian.activity.start');
						foreach($result as $row) {
							$activitiesOutput .= $this->renderReturn('guardian.activity.member', $row);
						}
						$activitiesOutput .= '</tbody>
						</table>';
					}
				}
			}
		}
	}

	/**
	 * get Hooks
	 *
	 * @version 10th October 2016
	 * @since	10th October 2016
	 * @param	integer		$personID
	 * @return	void
	 */
	private function getHooks($personID)
	{
		if (! empty($this->hooks) && $personID == $this->hooks->personID) return ;
	
		$this->hooks = new stdClass();
		$this->hooks->status = false ;
		$this->hooks->content = array() ;
		$this->hooks->personID = $personID ;
		
		//GET HOOKS INTO DASHBOARD
		$hooks = array();
		$resultHooks = $this->view->getRecord('hook')->findAllByType('Parental Dashboard');
		
		if (count($resultHooks) > 0) {
			$count = 0;
			foreach($resultHooks as $rowHooks) {
				$options = unserialize($rowHooks->getField('options'));
				//Check for permission to hook
				$dataHook = array('gibbonRoleIDCurrent' => $this->session->get('gibbonSchoolYearID'), 'sourceModuleName' => $options['sourceModuleName'], 'sourceModuleName2' => $options['sourceModuleName'], 'sourceModuleAction' => $options['sourceModuleAction'], 'type' => 'Parental Dashboard');
				$sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action
					FROM gibbonHook 
						JOIN gibbonModule ON gibbonHook.gibbonModuleID = gibbonModule.gibbonModuleID
						JOIN gibbonAction ON gibbonAction.gibbonModuleID = gibbonModule.gibbonModuleID
						JOIN gibbonPermission ON gibbonPermission.gibbonActionID = gibbonAction.gibbonActionID
					WHERE gibbonAction.gibbonModuleID = (SELECT gibbonModuleID 
							FROM gibbonModule 
							WHERE gibbonPermission.gibbonRoleID = :gibbonRoleIDCurrent
								AND name = :sourceModuleName)
						AND gibbonHook.type = :type
						AND gibbonAction.name = :sourceModuleAction
						AND gibbonModule.name = :sourceModuleName2
					ORDER BY name";
				$resultHook = $this->view->getRecord('hook')->findAll($sqlHook, $dataHook);
				if (count($resultHook) == 1) {
					$rowHook = (array)reset($resultHook);
					$hooks[$count]['name'] = $rowHooks['name'];
					$hooks[$count]['sourceModuleName'] = $rowHook['module'];
					$hooks[$count]['sourceModuleInclude'] = $options['sourceModuleInclude'];
					++$count;
				}
			}
			$this->hooks->content = $hooks ;
			$this->hooks->status = true ;
		}
	}	
}
