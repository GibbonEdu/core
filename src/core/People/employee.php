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

namespace Gibbon\People;

use Gibbon\Record\person ;
use Gibbon\Record\staff ;
use Gibbon\Record\schoolYear ;
use Gibbon\People\user ;
use stdClass ;

/**
 * Employee
 *
 * @version	1st October 2016
 * @since	26th September 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	People
 */
class employee extends user
{
	/**
	 * @var		Gibbon\Record\staff
	 */
	protected $staff ;

	/**
	 * @var		stdClass
	 */
	protected $planner ;

	/**
	 * @var		stdClass
	 */
	protected $timetable ;

	/**
	 * @var		stdClass
	 */
	protected $rollGroups ;
	
	/**
	 * all Staff
	 *
	 * @version	29th September 2016
	 * @since	26th September 2016
	 * @param	string		$status		Status to load.
	 * @return	array		Gibbon\People\employee 
	 */
	public function allStaff($status = 'Full')
	{
		return $this->findAllStaffByStatus($status);
	}

	/**
	 * find All Staff by Status
	 *
	 * @version	29th September 2016
	 * @since	29th September 2016
	 * @param	string		$status		Status to load.
	 * @return	array		Gibbon\People\employee 
	 */
	public function findAllStaffByStatus($status = 'Full')
	{
		$sql = "SELECT `gibbonPerson`.*
			FROM `gibbonPerson`
			JOIN `gibbonStaff` ON `gibbonPerson`.`gibbonPersonID` = `gibbonStaff`.`gibbonPersonID`
			WHERE `status` = :status
			ORDER BY `surname`,`preferredName`" ;
		$x = $this->findAll($sql, array('status' => $status));
		return $x;
	}
	
	/**
	 * get Smart Workflow Help
	 * 
	 * @version	30th September 2016
	 * @since	copied from functions.php
	 * @param	integer		$step	Workflow Step
	 * @return	string		HTML Output
	 */
	public function getSmartWorkflowHelp($step = 0) {

		$output = '' ;
		$numbers = array(1=>'One', 2=>'Two', 3=>'Three', 4=>'Four', 5=>'Five');
		$this->staff = $this->getStaff($this->session->get('gibbonPersonID'));
		if ($this->staff->getSuccess()) {
			$row = $this->staff->returnRecord() ;
			if ($row->smartWorkflowHelp == "Y") {
				
				$output = $this->view->render('staff.start');
				$el = new stdClass();
				$el->step = $step ;
							
				$el->stepString = $numbers[1];
				$el->title = array('Create %1$s Outcomes', array("<br/>"));
				if ($step==1) {
					$output .= $this->view->render('staff.step.is', $el);
				}
				else {
					$el->href = $this->view->convertGetArraytoURL(array('q' => '/modules/Planner/outcomes.php'));
					$output .= $this->view->render('staff.step.not', $el);
				}
				
				$el->stepString = $numbers[2];
				$el->title = array('Plan & Deploy %1$s Smart Units', array("<br/>"));
				if ($step==2) {
					$output .= $this->view->render('staff.step.is', $el);
				}
				else {
					$el->href = $this->view->convertGetArraytoURL(array('q' => '/modules/Planner/units.php'));
					$output .= $this->view->render('staff.step.not', $el);
				}
				
				$el->stepString = $numbers[3];
				$el->title = array('Share, Teach %1$s & Interact', array("<br/>"));
				if ($step==3) {
					$output .= $this->view->render('staff.step.is', $el);
				}
				else {
					$el->href = $this->view->convertGetArraytoURL(array('q' => '/modules/Planner/planner.php'));
					$output .= $this->view->render('staff.step.not', $el);
				}
				
				$el->stepString = $numbers[4];
				$el->title = array('Assign & Collect %1$s Work', array("<br/>"));
				if ($step==4) {
					$output .= $this->view->render('staff.step.is', $el);
				}
				else {
					$el->href = $this->view->convertGetArraytoURL(array('q' => '/modules/Planner/planner_deadlines.php'));
					$output .= $this->view->render('staff.step.not', $el);
				}
				
				$el->stepString = $numbers[5];
				$el->title = array('Assess & Give %1$s Feedback', array("<br/>"));
				if ($step==5) {
					$output .= $this->view->render('staff.step.is', $el);
				}
				else {
					$el->href = $this->view->convertGetArraytoURL(array('q' => '/modules/Markbook/markbook_view.php'));
					$output .= $this->view->render('staff.step.not', $el);
				}



				?></tr><?php
				if (! empty($step)) {
					?><tr>
                    	<td style='text-align: justify; font-size: 125%; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 15px 4px' colspan=5><?php
							switch ($step) {
								case 1:
									$el->details = array('%1$sOutcomes%2$s provide a way to plan and track what is being taught in school, and so are a great place to get started.%3$s%4$sClick on the "Add" button (below this message, on the right) to add a new outcome, which can either be school-wide, or attached to a particular department.', array('<strong>', '</strong>', '<br />', '<br />'));
									$el->note = 'You need to be in a department, with the correct permissions, in order to be able to do this.';
									$output .= $this->render('staff.step.details', $el);
									break;
								case 2:
									$el->details = array('%1$sSmart Units%2$s support you in the design of course content, and can be quickly turned into individual lesson plans using intuitive drag and drop. Smart Units cut planning time dramatically, and support ongoing improvement and reuse of content.%3$s%4$sChoose a course, using the dropdown menu on the right, and then click on the "Add" button (below this message, on the right) to add a new unit. Once your master unit is complete, deploy it to a class to create your lesson plans.', array('<strong>', '</strong>', '<br />', '<br />'));
									$el->note = 'You need to be in a department, with the correct permissions, in order to be able to do this.';
									$output .= $this->render('staff.step.details', $el);
									break;
								case 3:
									$el->details = array('%1$sPlanner%2$s supports online lesson plans which can be shared with students, parents and other teachers. Create your lesson by hand, or automatically via %5$sSmart Units%6$s. Lesson plans facilitate sharing of course content, homework assignment and submission, text chat, and attendance taking.%3$s%4$sChoose a date or class, using the menu on the right, and then click on the "Add" button (below this message, on the right) to add a new unit.', array('<strong>', '</strong>', '<br />', '<br />', "<a href='" . GIBBON_URL . "index.php?q=/modules/Planner/units.php'>", "</a>"));
									$el->note = 'You need to have classes assigned to you, with the correct permissions, in order to be able to do this.';
									$output .= $this->render('staff.step.details', $el);
									break;
								case 4:
									$el->details = array('%1$sHomework + Deadlines%2$s allows teachers and students to see upcoming deadlines, cleanly displayed in one place. Click on an entry to view the details for that piece of homework, and the lesson it is attached to.%3$s%4$sHomework can be assigned using the %5$sPlanner%6$s, which also allows teachers to view all submitted work, and records late and incomplete work.', array('<strong>', '</strong>', '<br />', '<br />', "<a href='" . GIBBON_URL . "index.php?q=/modules/Planner/planner.php'>", "</a>"));
									$el->note = 'You need to have classes assigned to you, with the correct permissions, in order to be able to do this.';
									$output .= $this->render('staff.step.details', $el);
									break;
								case 5:
									$el->details = array('%1$sMarkbook%2$s provides an organised way to assess, record and report on student progress. Use grade scales, rubrics, comments and file uploads to keep students and parents up to date. Link markbooks to the %5$sPlanner%6$s, and see student work as you are marking it.%3$s%4$sChoose a class from the menu on the right, and then click on the "Add" button (below this message, on the right) to create a new markbook column.', array('<strong>', '</strong>', '<br />', '<br />', "<a href='" . GIBBON_URL . "index.php?q=/modules/Planner/planner.php'>", "</a>"));
									$el->note = 'You need to have classes assigned to you, with the correct permissions, in order to be able to do this.';
									$output .= $this->render('staff.step.details', $el);
									break;
							}
						?></td>
                     </tr><?php
				}
						
						

				?></table>
                <div style='text-align: right; font-size: 90%; padding: 0 7px'>
                	<?php $this->view->getLink('', array('href'=>'#', 'title'=> $this->view->__('Dismiss Smart Workflow Help'), 'onclick'=>'$("#smartWorkflowHelp").fadeOut(1000); $.ajax({ url: "' . GIBBON_URL . 'index_SmartWorkflowHelpAjax.php"})'), 'Dismiss Smart Workflow Help'); ?>
                </div>
				</div><?php
			}
		}
	}

	/**
	 * get Staff
	 *
	 * @version	30th September 2016
	 * @since	30th September 2016
	 * @param	integer		$personID
	 * @return	Gibbon\Record\staff
	 */
	public function getStaff($personID = null)
	{
		if (is_null($personID)) $personID = $this->record->personID;
		$data = array("gibbonPersonID" => $personID);
		$this->staff = new staff($this->view);
		$this->staff->findOneBy($data);
		return $this->staff;
	}
	
	/**
	 * get Staff Dashboard Contents
	 *
	 * Gets the contents of the staff dashboard for the member of staff specified
	 * @version	1st October 2016
	 * @since	Copied from functions
	 * @param	integer		$personID
	 * @return	void
	 */
	public function getStaffDashboardContents($personID)
	{
		$return = false;
	
		$this->getPlanner($personID);
		
		$this->getTimetable($personID);
			
		$this->getRollGroups($personID);
	
	
		//GET HOOKS INTO DASHBOARD
		$hooks = array();
		$resultHooks = $this->view->getRecord('hook')->findAllByType('Staff Dashboard');
		if (count($resultHooks) > 0) {
			$count = 0;
			foreach ($resultHooks  as $rowHooks ) {
				$options = unserialize($rowHooks['options']);
				//Check for permission to hook
				try {
					$dataHook = array('gibbonRoleIDCurrent' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'sourceModuleName' => $options['sourceModuleName']);
					$sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND name=:sourceModuleName) AND gibbonHook.type='Staff Dashboard'  AND gibbonAction.name='".$options['sourceModuleAction']."' AND gibbonModule.name='".$options['sourceModuleName']."' ORDER BY name";
					$resultHook = $connection2->prepare($sqlHook);
					$resultHook->execute($dataHook);
				} catch (PDOException $e) {
				}
				if ($resultHook->rowCount() == 1) {
					$rowHook = $resultHook->fetch();
					$hooks[$count]['name'] = $rowHooks['name'];
					$hooks[$count]['sourceModuleName'] = $rowHook['module'];
					$hooks[$count]['sourceModuleInclude'] = $options['sourceModuleInclude'];
					++$count;
				}
			}
		}
	
		if (! $this->planner->status && ! $this->timetable->status && count($hooks) < 1) {
			$return .= "<div class='warning'>";
			$return .= $this->view->__('There are no records to display.');
			$return .= '</div>';
		} else {
			$staffDashboardDefaultTab = $this->config->getSettingByScope('School Admin', 'staffDashboardDefaultTab');
			$staffDashboardDefaultTabCount = null;
	
			$return .= "<div id='".$personID."tabs' style='margin: 0 0'>";
			$return .= '<ul>';
			$tabCount = 1;
			if ($this->planner->status || $this->timetable->status) {
				$return .= "<li><a href='#tabs".$tabCount."'>".$this->view->__('Planner').'</a></li>';
				if ($staffDashboardDefaultTab == 'Planner')
					$staffDashboardDefaultTabCount = $tabCount;
				++$tabCount;
			}
			if (count($rollGroups) > 0) {
				foreach ($rollGroups as $rollGroup) {
					$return .= "<li><a href='#tabs".$tabCount."'>".$rollGroup[1].'</a></li>';
					++$tabCount;
					if ($this->view->getSecurity()->isActionAccessible('/modules/Behaviour/behaviour_view.php')) {
						$return .= "<li><a href='#tabs".$tabCount."'>".$rollGroup[1].' '.$this->view->__('Behaviour').'</a></li>';
						++$tabCount;
					}
				}
			}
	
			foreach ($hooks as $hook) {
				$return .= "<li><a href='#tabs".$tabCount."'>".$this->view->__($hook['name']).'</a></li>';
				if ($staffDashboardDefaultTab == $hook['name'])
					$staffDashboardDefaultTabCount = $tabCount;
				++$tabCount;
			}
			$return .= '</ul>';
	
			$tabCount = 1;
			if ($this->planner->status || $this->timetable->status) {
				$return .= "<div id='tabs".$tabCount."'>";
				$return .= $this->planner->content;
				$return .= $this->timetable->content;
				$return .= '</div>';
				++$tabCount;
			}
			if (count($rollGroups) > 0) {
				foreach ($rollGroups as $rollGroup) {
					$return .= "<div id='tabs".$tabCount."'>";
					$return .= $rollGroup[2];
					$return .= '</div>';
					++$tabCount;
	
					if ($this->view->getSecurity()->isActionAccessible('/modules/Behaviour/behaviour_view.php')) {
						$return .= "<div id='tabs".$tabCount."'>";
						$return .= $rollGroup[3];
						$return .= '</div>';
						++$tabCount;
					}
				}
			}
			foreach ($hooks as $hook) {
				$return .= "<div style='min-height: 100px' id='tabs".$tabCount."'>";
				$include = $_SESSION[$guid]['absolutePath'].'/modules/'.$hook['sourceModuleName'].'/'.$hook['sourceModuleInclude'];
				if (!file_exists($include)) {
					$return .= "<div class='error'>";
					$return .= $this->view->__('The selected page cannot be displayed due to a hook error.');
					$return .= '</div>';
				} else {
					$return .= include $include;
				}
				++$tabCount;
				$return .= '</div>';
			}
			$return .= '</div>';
		}
	
		$defaultTab = 0;
		if (isset($_GET['tab'])) {
			$defaultTab = $_GET['tab'];
		}
		else if (!is_null($staffDashboardDefaultTabCount)) {
			$defaultTab = $staffDashboardDefaultTabCount-1;
		}
	
		$return .= "<script type='text/javascript'>";
		$return .= '$(function() {';
		$return .= '$( "#'.$personID.'tabs" ).tabs({';
		$return .= 'active: '.$defaultTab.',';
		$return .= 'ajaxOptions: {';
		$return .= 'error: function( xhr, status, index, anchor ) {';
		$return .= '$( anchor.hash ).html(';
		$return .= "\"Couldn't load this tab.\" );";
		$return .= '}';
		$return .= '}';
		$return .= '});';
		$return .= '});';
		$return .= '</script>';
	
		return $return;
	}
	
	/**
	 * get Planner
	 *
	 * @version	1st October 2016
	 * @since	1st October 2016
	 * @param	integer		$personID
	 * @return	void
	 */
	public function getPlanner($personID)
	{
		if (! empty($this->planner)) return ;
	
		//GET PLANNER
		
		$this->planner = new stdClass();
		$this->planner->status = false;
		$this->planner->content = '';
		
		$planner = false;
		$date = date('Y-m-d');
		if ($this->view->getRecord('schoolYear')->isSchoolOpen($date) && $this->view->isActionAccessible('/modules/Planner/planner.php') && $this->session->notEmpty('username')) {
			$data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'date' => $date, 'gibbonPersonID' => $this->session->get('gibbonPersonID'), 'gibbonSchoolYearID2' => $this->session->get('gibbonSchoolYearID'), 'date2' => $date, 'gibbonPersonID2' => $this->session->get('gibbonPersonID'));
			$sql = "(SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND date=:date AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess,  role, date, summary, NULL AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart, course, class";

			$result = $this->view->getRecord('plannerEntry')->findAll($sql, $data, '_');
			$planner .= $this->h2("Today's Lessons", array(), false);
			if (count($result) < 1) {
				$planner .= $this->returnMessage('There are no records to display.', 'warning');
			} else {
				$planner .= $this->renderReturn('staff.planner.start');
	
				foreach($result as $row) {
					if (!($row->role == 'Student' and $row->viewableStudents == 'N')) {
						
						//Highlight class in progress
						if ((date('H:i:s') > $row->timeStart) && (date('H:i:s') < $row->timeEnd) && ($date) == date('Y-m-d')) 
							$row->rowNum = 'current';
						
						$row->unit = $this->view->getRecord('unit')->getUnit($row->gibbonUnitID, $row->gibbonHookID, $row->gibbonCourseClassID);
						if ($el->role != 'Teacher') 
							$el->likesGiven = $this->view->getRecord('like')->countLikesByContextAndGiver('Planner', 'gibbonPlannerEntryID', $el->gibbonPlannerEntryID, $this->session->get('gibbonPersonID'));

						
						$this->renderReturn('staff.planner.member', $row);
						
					}
				}
				$planner .= '</tbody>
				</table>';
			}
			$this->planner->status = true;
			$this->planner->content = $planner ;
		}
	}
	
	/**
	 * get Timetable
	 *
	 * @version	1st October 2016
	 * @since	1st October 2016
	 * @param	integer		$personID
	 * @return	void
	 */
	public function getTimetable($personID)
	{
		if (! empty($this->timetable)) return ;
		
		//GET TIMETABLE
		
		$this->timetable = new stdClass();
		$this->timetable->status = false;
		$this->timetable->content = '';

		if ($this->view->getSecurity()->isActionAccessible('/modules/Timetable/tt.php') && $this->session->notEmpty('username') && $this->view->getSecurity()->getRoleCategory($this->session->get('gibbonRoleIDCurrent')) == 'Staff') {
			$this->view->addScript('
<script type="text/javascript">
	$(document).ready(function(){
		$("#tt").load("'.GIBBON_URL.'index_tt_ajax.php",{"gibbonTTID": "'.@$_GET['gibbonTTID'].'", "ttDate": "'. @$_POST['ttDate'].'", "fromTT": "'.@$_POST['fromTT'].'", "personalCalendar": "'.@$_POST['personalCalendar'].'", "schoolCalendar": "'.@$_POST['schoolCalendar'].'", "spaceBookingCalendar": "'.@$_POST['spaceBookingCalendar'].'"});
	});
</script>
			');?>
			<?php
			$this->timetable->content = $this->view->renderReturn('staff.timetable.loading');
			$this->timetable->status = true ;
		}
	}

	/**
	 * get Roll Groups
	 *
	 * @version	1st October 2016
	 * @since	1st October 2016
	 * @param	integer		$personID
	 * @return	void
	 */
	public function getRollGroups($personID)
	{
		if (! empty($this->rollGroups)) return ;
		//GET ROLL GROUPS
		$this->rollGroups = new stdClass();
		$this->rollGroups->status = false;
		$this->rollGroups->content = '';

		$rollGroups = array();
		$dataRollGroups = array('gibbonPersonIDTutor' => $this->session->get('gibbonPersonID'), 'gibbonPersonIDTutor2' => $this->session->get('gibbonPersonID'), 'gibbonPersonIDTutor3' => $this->session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'));
		$sqlRollGroups = 'SELECT * 
			FROM `gibbonRollGroup` 
			WHERE (`gibbonPersonIDTutor` = :gibbonPersonIDTutor OR `gibbonPersonIDTutor2` = :gibbonPersonIDTutor2 OR `gibbonPersonIDTutor3` = :gibbonPersonIDTutor3) 
				AND `gibbonSchoolYearID` = :gibbonSchoolYearID';
		$resultRollGroups = $this->view->getRecord('rollGroup')->findAll($sqlRollGroups, $dataRollGroups, '_');
		$rollGroup = array();

		foreach($resultRollGroups as $w) {
			$rowRollGroups = $w->returnRecord();
			$id = $rowRollGroups->gibbonRollGroupID;
			$rollGroup[$id]['id'] = $rowRollGroups->gibbonRollGroupID;
			$rollGroup[$id]['nameShort'] = $rowRollGroups->nameShort;
	
			//Roll group table
			$rollGroup[$id]['table'] = $this->view->renderReturn('staff.rollGroups.table', $rowRollGroups);
	
			if ($this->view->getSecurity()->isActionAccessible('/modules/Behaviour/behaviour_view.php')) {
				//Behaviour
				$bh = '';
				$plural = 's';
				if ($resultRollGroups->rowCount() == 1) 
					$plural = '';

				$dataBehaviour = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'gibbonSchoolYearID2' => $this->session->get('gibbonSchoolYearID'), 'gibbonRollGroupID' => $rollGroups[$count][0]);
				$sqlBehaviour = 'SELECT gibbonBehaviour.*, student.surname AS surnameStudent, 
						student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, 
						creator.preferredName AS preferredNameCreator, creator.title 
					FROM gibbonBehaviour 
						JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) 
						JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) 
						JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) 
					WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID 
						AND gibbonBehaviour.gibbonSchoolYearID=:gibbonSchoolYearID2 
						AND gibbonRollGroupID=:gibbonRollGroupID 
					ORDER BY timestamp DESC';
				
				$rBh = $this->view->getRecord('behaviour')->findAll($sqlBehaviour, $dataBehaviour);
	
				if ($this->view->getSecurity()->isActionAccessible('/modules/Behaviour/behaviour_manage_add.php')) {
					$links = array();
					$links[] = array('add' => array('q'=>'/modules/Behaviour/behaviour_manage_add.php', 'gibbonPersonID'=>null, 'gibbonRollGroupID'=>null, 'gibbonYearGroupID'=>null, 'type'=>null));

					$policyLink = $this->view->config->getSettingByScope('Behaviour', 'policyLink');
					if ($policyLink != '') {
						$links[] = array('' => array('q' => $policyLink, 'prompt' => 'View Behaviour Policy'));
					}
					$bh .= $this->view->linkTopReturn($links);
				}
	
				if (count($rBh) < 1) {
					$bh .= $this->view->returnMessage('There are no records to display.');
				} else {
					$bh .= $this->view->renderReturn('student.behaviour.start');
	
					foreach($rBh as $row) {
						$bh .= $this->view->renderReturn('student.behaviour.member', $row);
					}
					$bh .= '
							</tbody>
						</table>
						';
				}
				$rollGroup[$id]['behaviour'] = $bh;
			}
		}
	}
}