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
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage Person
*/
/**
 */
namespace Gibbon\People;

use Gibbon\core\view ;
use Gibbon\core\tabs ;
use Gibbon\Record\person ;
use Gibbon\Record\family ;
use Gibbon\Form\token ;
use stdClass ;

/**
 * Student 
 *
 * @version	11th October 2016
 * @since	13th August 2016
 * @author	Craig Rayner
 */
class student extends person
{
	use \Gibbon\People\user ;
	
	/**
	 * @var	Gibbon\Record\studentEnrolment
	 */
	protected $enrolment ;

	/**
	 * @var	Gibbon\Record\yearGroup
	 */
	protected $yearGroup ;

	/**
	 * @var	Gibbon\Record\rollGroup
	 */
	protected $rollGroup ;

	/**
	 * @var	array of Gibbon\Record\family
	 */
	protected $families ;

	/**
	 * @var	array of Gibbon\Person\teacher
	 */
	protected $teachers ;

	/**
	 * @var	Gibbon\Person\house
	 */
	protected $house ;

	/**
	 * @var	array
	 */
	protected $familyAdults ;

	/**
	 * @var	array of Gibbon\Record\schoolYear
	 */
	protected $classOf ;

	/**
	 * @var	Gibbon\Record\personMedical
	 */
	protected $medical ;

	/**
	 * @var	array of Gibbon\Record\personMedicalCondition
	 */
	protected $medicalConditions ;

	/**
	 * @var	array of Gibbon\Record\studentNoteCategory
	 */
	protected $noteCategories ;

	/**
	 * @var	array of Gibbon\Record\studentNote
	 */
	protected $notes ;

	/**
	 * @var	Gibbon\Record\studentNote
	 */
	protected $note ;

	/**
	 * @var		stdClass
	 */
	protected $timetable ;

	/**
	 * Constructor
	 *
	 * @version	13th August 2016
	 * @since	3th August 2016
	 * @param	view		$view
	 * @param	integer		$id 
	 * @return	void
	 */
	public function __construct(view $view, $id = 0 )
	{
		parent::__construct($view, $id);
		$this->validStudent = false ;
		if ($this->getSuccess() && $id > 0 && $this->record->gibbonPersonID == $id)
			$this->validStudent = true;
	}

	/**
	 * get Student Enrolment
	 *
	 * @version	11th October 2016
	 * @since	13th August 2016
	 * @return	Gibbon\Record\studentEnrolment
	 */
	public function getEnrolment($schoolYearID = null)
	{
		if (isset($this->validEnrolment) && $this->validEnrolment)
			return $this->enrolment;
		$schoolYearID = is_null($schoolYearID) ? $this->session->get('gibbonSchoolYearID') : $schoolYearID ;
		$this->enrolment = $this->view->getRecord('studentEnrolment');
		$this->validEnrolment = false;
		if ($this->record->status == 'Full' && (is_null($this->record->dateStart) || $this->record->dateStart <= date('Y-m-d')) && (is_null($this->record->dateEnd) || $this->record->dateEnd <= date('Y-m-d')) )
		{
			$xx = $this->enrolment->findOneBy(array('gibbonPersonID' => $this->record->gibbonPersonID, 'gibbonSchoolYearID' => $schoolYearID));
		}
		if ($this->enrolment->getSuccess() && $this->enrolment->rowCount() === 1)
			$this->validEnrolment = true ;
		return $this->enrolment ;
	}
	
	/**
	 * get Year Group
	 *
	 * @version	11th October 2016
	 * @since	13th August 2016
	 * @param	string		$name
	 * @return	mixed		Gibbon\Record\yearGroup | field Value
	 */
	public function getYearGroup($name = null)
	{
		if (isset($this->validYearGroup) && $this->validYearGroup)
			if (is_null($name))
				return $this->yearGroup;
			else
				return $this->yearGroup->getField($name);
				
		$this->getEnrolment();
		$this->validYearGroup = false ;
		$this->yearGroup = $this->view->getRecord('yearGroup');
		if ($this->validEnrolment)
			$this->yearGroup->find($this->enrolment->getField('gibbonYearGroupID'));
		if ($this->yearGroup->getSuccess() && $this->yearGroup->rowCount() === 1)
			$this->validYearGroup = true;
		if (is_null($name))
			return $this->yearGroup;
		else
			return $this->yearGroup->getField($name);
	}
	
	/**
	 * get Roll Group
	 *
	 * @version	11th October 2016
	 * @since	13th August 2016
	 * @param	string		$name
	 * @return	mixed		Gibbon\Record\rollGroup | field Value
	 */
	public function getRollGroup($name = null)
	{
		if (isset($this->validRollGroup) && $this->validRollGroup)
			if (is_null($name))
				return $this->rollGroup;
			else
				return $this->rollGroup->getField($name);
				
		$this->getEnrolment();
		$this->validRollGroup = false ;
		$this->rollGroup = $this->view->getRecord('rollGroup');
		if ($this->validEnrolment)
			$this->rollGroup->find($this->enrolment->getField('gibbonRollGroupID'));
		if ($this->rollGroup->getSuccess() && $this->rollGroup->rowCount() === 1)
			$this->validRollGroup = true;
		if (is_null($name))
			return $this->rollGroup;
		else
			return $this->rollGroup->getField($name);
	}
	
	/**
	 * get Family
	 *
	 * @version	15th August 2016
	 * @since	13th August 2016
	 * @param	integer		$personID
	 * @return	array	 of Gibbon\Record\family  
	 */
	public function getFamily($personID = null)
	{
		if (isset($this->validFamilies) && $this->validFamilies)
			return $this->families ;
				
		$this->validFamilies = false ;
		$sql = 'SELECT `gibbonFamily`.* 
			FROM `gibbonFamily`
				JOIN `gibbonFamilyChild` ON `gibbonFamilyChild`.`gibbonFamilyID` = `gibbonFamily`.`gibbonFamilyID`
			WHERE `gibbonFamilyChild`.`gibbonPersonID` = :personID';
		$data = array('personID' => $this->record->gibbonPersonID);
		$family = new family($this->view);
		$this->families = $family->findAll($sql, $data);
		if ($family->getSuccess() && count($this->families) > 0)
		{
			$this->validFamilies = true ;
			return $this->families;
		}
		return array() ;
	}
	
	/**
	 * get Family Adults
	 *
	 * @version	15th August 2016
	 * @since	13th August 2016
	 * @param	Gibbon\Record\family
	 * @return	array of Gibbon\Record\familyAdult
	 */
	public function getFamilyAdults(family $family)
	{
		if (isset($this->validFamilyAdults) && $this->validFamilyAdults)
		{
			$adult = reset($this->familyAdults);
			if ($adult->getField('gibbonFamilyID') === $family->getField('gibbonFamilyID'))
				return $this->familyAdults;
		}
		$this->validFamilyAdults = false ;

		$this->getFamily();
		$adult = $this->view->getRecord('familyAdult');
		$sql = 'SELECT * 
			FROM `gibbonFamilyAdult` 
				JOIN `gibbonPerson` ON `gibbonFamilyAdult`.`gibbonPersonID` = `gibbonPerson`.`gibbonPersonID` 
			WHERE `gibbonFamilyID` = :familyID 
			ORDER BY `contactPriority`, `surname`, `preferredName`';

		$this->familyAdults = $adult->findAll($sql, array('familyID' => $family->getField('gibbonFamilyID')));

		if ($family->getSuccess() && $family->rowCount() > 0)
			$this->validFamilyAdults = true;
		else
			$this->familyAdults = array();

		$this->childDataAccess = 'N';

		if (array_key_exists($this->session->get('gibbonPersonID'), $this->familyAdults)
			&& ! empty($this->familyAdults[$this->session->get('gibbonPersonID')]->getField('childDataAccess')))
				$this->childDataAccess = $this->familyAdults[$this->session->get('gibbonPersonID')]->getField('childDataAccess');

		return $this->familyAdults ;
	}
	
	/**
	 * check Child Data Access
	 *
	 * Is set to true when a valid Enrolment and the user is one of the adults in the family.
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @return	boolean
	 */
	public function checkChildDataAccess()
	{
		$this->getEnrolment();
		$this->getFamilyAdults($this->view->getRecord('family'));
		
		if ($this->validEnrolment && $this->validFamilyAdults && $this->childDataAccess == 'Y')
			return true ;

		return false ;
	}
	
	/**
	 * get Teachers
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @return	array of Gibbon\Person\teacher
	 */
	public function getTeachers()
	{
		if (isset($this->validTeachers) && $this->validTeachers)
			return $this->teachers;
		//Get list of teachers
		$this->validTeachers = false ;
		$tObj = new teacher($this->view);
		$data = array('personID' => $this->record->gibbonPersonID, 'schoolYearID' => $this->session->get('gibbonSchoolYearID'));
		$sql = "SELECT DISTINCT `teacher`.*
			FROM `gibbonPerson` AS `teacher` 
				JOIN `gibbonCourseClassPerson` AS `teacherClass` ON `teacherClass`.`gibbonPersonID` = `teacher`.`gibbonPersonID`
				JOIN `gibbonCourseClassPerson` AS `studentClass` ON `studentClass`.`gibbonCourseClassID` = `teacherClass`.`gibbonCourseClassID`
				JOIN `gibbonPerson` AS `student` ON `studentClass`.`gibbonPersonID` = `student`.`gibbonPersonID`
				JOIN `gibbonCourseClass` ON `studentClass`.`gibbonCourseClassID` = `gibbonCourseClass`.`gibbonCourseClassID`
				JOIN `gibbonCourse` ON `gibbonCourseClass`.`gibbonCourseID` = `gibbonCourse`.`gibbonCourseID`
			WHERE `teacher`.`status` = 'Full'
				AND `teacherClass`.`role` = 'Teacher' 
				AND `studentClass`.`role` = 'Student' 
				AND `student`.`gibbonPersonID` = :personID 
				AND `gibbonCourse`.`gibbonSchoolYearID` = :schoolYearID
			ORDER BY `teacher`.`preferredName`, `teacher`.`surname`, `teacher`.`email`";
		$this->teachers = $tObj->findAll($sql, $data);
		if ($tObj->getSuccess() && is_array($this->teachers))
			$this->validTeachers = true;
		else
			$this->teachers = array();
		return $this->teachers;
	}
	
	/**
	 * get classOf
	 *
	 * @version	15th August 2016
	 * @since	13th August 2016
	 * @return	array of Gibbon\Person\teacher
	 */
	public function getClassOf()
	{
		if (isset($this->validClassOf) && $this->validClassOf)
			return $this->classOf ;
		$this->validClassOf = false;
		$this->classOf = new schoolYear($this->view, $this->record->gibbonSchoolYearIDClassOf);
		if ($this->classOf->getSuccess() && $this->classOf->rowCount() == 1)
			$this->validClassOf = true;
		else
			$this->classOf = new schoolYear($this->view);
		return $this->classOf;
	}
	
	/**
	 * get House
	 *
	 * @version	14th August 2016
	 * @since	14th August 2016
	 * @param	string		$name
	 * @return	mixed		Gibbon\Record\house | field Value
	 */
	public function getHouse($name = null)
	{
		if (isset($this->validHouse) && $this->validHouse)
			if (is_null($name))
				return $this->house;
			else
				return $this->house->getField($name);
				
		$this->validHouse = false ;
		$this->house = new house($this->view, $this->record->gibbonHouseID);
		if ($this->house->getSuccess() && $this->house->rowCount() === 1)
			$this->validhouse = true;
		if (is_null($name))
			return $this->house;
		else
			return $this->house->getField($name);
	}
	
	/**
	 * get Adult Relationship
	 *
	 * @version	15th August 2016
	 * @since	15th August 2016
	 * @param	Gibbon\Record\familyAdult
	 * @param	string/array	Default response (Will be translated.)
	 * @return	string
	 */
	public function getAdultRelationship(familyAdult $adult, $default = '')
	{
		
		$obj = new familyRelationship($this->view);
		$obj->startQuery()
			->startWhere('gibbonPersonID1', $adult->getField('gibbonPersonID'))
			->andWhere('gibbonPersonID2', $this->record->gibbonPersonID)
			->andWhere('gibbonFamilyID', $adult->getField('gibbonFamilyID'));
		$relationship = $obj->findOneBy($obj->getWhere());

		if ($obj->getSuccess()) {
			return $obj->getField('relationship');
		} else {
			return $this->view->__($default);
		}
	}
	
	/**
	 * get Siblings
	 *
	 * @version	15th August 2016
	 * @since	15th August 2016
	 * @param	Gibbon\Record\family
	 * @return	array of Gibbon\Record\familyChild
	 */
	public function getSiblings(family $family)
	{
		$obj = new familyChild($this->view);
		$data = array('gibbonFamilyID' => $family->getField('gibbonFamilyID'), 'gibbonPersonID' => $this->record->gibbonPersonID);
		$sql = 'SELECT * 
			FROM `gibbonFamilyChild` 
				JOIN `gibbonPerson` ON `gibbonFamilyChild`.`gibbonPersonID` = `gibbonPerson`.`gibbonPersonID`
				JOIN `gibbonRole` ON `gibbonPerson`.`gibbonRoleIDPrimary` = `gibbonRole`.`gibbonRoleID`
			WHERE `gibbonFamilyID` = :gibbonFamilyID 
				AND NOT `gibbonPerson`.`gibbonPersonID` = :gibbonPersonID 
			ORDER BY `surname`, `preferredName`';
		$siblings = $obj->findAll($sql, $data);
		if ($obj->getSuccess() and count($siblings) > 0)
			return $siblings;
		return array();
		
	}
	
	/**
	 * get Medical
	 *
	 * @version	11th October 2016
	 * @since	15th August 2016
	 * @return	Gibbon\Record\personMedical
	 */
	public function getMedical()
	{
		if (isset($this->validMedical) && $this->validMedical)
			return $this->medical ;
		$this->validMedical = false;
		$this->medical = $this->view->getRecord('personMedical');
		$this->medical->startQuery();
		$this->medical->startWhere('gibbonPersonID', $this->record->gibbonPersonID);
		$this->medical->findOneBy($this->medical->getWhere());
		if ($this->medical->getSuccess() && $this->medical->rowCount() == 1)
			$this->validMedical = true;
		else
			$this->medical = $this->view->getRecord('personMedical');
		return $this->medical;
	}
	
	/**
	 * get Medical Conditions
	 *
	 * @version	15th August 2016
	 * @since	15th August 2016
	 * @return	array of Gibbon\Record\personMedical
	 */
	public function getMedicalConditions()
	{
		if (isset($this->validMedicalConditions) && $this->validMedicalConditions)
			return $this->medicalConditions ;
		$this->validMedicalConditions = false;
		$this->getMedical();
		if (! $this->validMedical)
			return array();
		
		$this->medicalConditions = $this->medical->getMedicalConditions();
		
		$this->validMedicalConditions = $this->medical->validMedicalConditions;

		return $this->medicalConditions;
	}
	
	/**
	 * get Highest Medical Risk
	 *
	 * @version	15th August 2016
	 * @since	15th August 2016
	 * @return	array|false
	 */
	public function getHighestMedicalRisk()
	{
		$this->getMedical();
		if ($this->validMedical)
			return $this->medical->getHighestMedicalRisk();
		return false ;
	}
	
	/**
	 * get Note Categories
	 *
	 * @version	17th August 2016
	 * @since	17th August 2016
	 * @return	array
	 */
	public function getNoteCategories()
	{
		if (isset($this->validNoteCategories) && $this->validNoteCategories)
			return $this->noteCategories ;
		$this->validNoteCategories  = false ;
		$obj = new studentNoteCategory($this->view);
		$this->noteCategories = $obj->findAll('SELECT * 
			FROM `gibbonStudentNoteCategory` 
			WHERE `active` = "Y" 
			ORDER BY `name`');
		if ($obj->getSuccess() && count($this->noteCategories) > 0)
		{
				$this->validNoteCategories  = true ;
				$x = array();
				foreach($this->noteCategories as $q=>$w)
					$x[$q] = $w->getField('name');
				$this->noteCategories = $x ;
		}
		else
			$this->noteCategories = array();
		return $this->noteCategories;
	}
	
	/**
	 * get Notes
	 *
	 * @version	17th August 2016
	 * @since	17th August 2016
	 * @param	integer		$categoryID
	 * @return	array
	 */
	public function getNotes($categoryID = null)
	{
		if (isset($this->validNotes) && $this->validNotes)
			return $this->notes ;
		$this->validNotes  = false ;
		$obj = new studentNote($this->view);
		$x = '=';
		if (empty($categoryid))
		{
			$x = '>';
			$categoryID = 0;
		}
			
		$data = array('personID' => $this->record->gibbonPersonID, 'noteCategoryID' => $categoryID);
		$sql = 'SELECT * 
			FROM `gibbonStudentNote` 
			WHERE `gibbonPersonID` = :personID 
				AND `gibbonStudentNoteCategoryID` '.$x.' :noteCategoryID 
			ORDER BY `timestamp` DESC';
		$this->notes = $obj->findAll($sql, $data);
		if ($obj->getSuccess() && count($this->noteCategories) > 0)
			$this->validNotes  = true ;
		else
			$this->notes = array();
		return $this->notes;
	}
	
	/**
	 * get Note
	 *
	 * @version	17th August 2016
	 * @since	17th August 2016
	 * @param	integer		$noteID
	 * @return	Gibbon\Record\studentNote
	 */
	public function getNote($noteID)
	{
		if (isset($this->validNote) && $this->validNote)
			return $this->note;
		$this->validNote  = false ;
		$this->note = new studentNote($this->view, $noteID);
		if ($this->note->getSuccess())
			$this->validNote  = true ;

		return $this->note;
	}
	
	/**
	 * get Student Dashboard Contents
	 *
	 * @version	7th September 2016
	 * @since	7th September 2016
	 * @param	integer		$personID
	 * @return	string
	 */
	public function getStudentDashboardContents($personID)
	{
		$return = false;
	
		$this->getPlanner($personID);
		
		$this->getTimetable($personID);
		
		$this->getHooks($personID);
		
	
		if (! $this->planner->status && ! $this->timetable->status && count($hooks) < 1) {
			$this->view->returmMessage('There are no records to display.', 'warning');
		} else {
			
			$tabs = new tabs($this->view);
			
			if ($this->planner->status || $this->timetable->status) 
				$tabs->addTab($this->planner->content . $this->timetable->content, $this->view->__('Planner'));
				
			foreach ($this->hooks->content as $hook) 
				$tabs->addTab($this->view->getRecord('hook')->includeHookFile('/modules/'.$hook['sourceModuleName'].'/'.$hook['sourceModuleInclude']), $this->view->__($hook['name']));
				
			$return .= $tabs->renderTabs($personID, $this->view->__('Planner'));
	
		}
	
		return $return;
	}
	
	/**
	 * get Timetable
	 *
	 * @version	8th October 2016
	 * @since	8th October 2016
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

		if ($this->view->getSecurity()->isActionAccessible('/modules/Timetable/tt.php') && $this->session->notEmpty('username') && $this->view->getSecurity()->getRoleCategory($this->session->get('gibbonRoleIDCurrent')) == 'Student') {
			$tok = new token('/modules/Timetable/index_tt_ajax.php', null, $this->view);

			$school = isset($_POST['fromTT'])? (isset($_POST['schoolCalendar']) && $_POST['schoolCalendar'] == 'Y' ? 'Y' : 'N') : $this->view->session->get('viewCalendar.School') ;
			$personal = isset($_POST['fromTT'])? (isset($_POST['personalCalendar']) && $_POST['personalCalendar'] == 'Y' ? 'Y' : 'N') : $this->view->session->get('viewCalendar.Personal') ;
			$space = isset($_POST['fromTT'])? (isset($_POST['spaceBookingCalendar']) && $_POST['spaceBookingCalendar'] == 'Y' ? 'Y' : 'N') : $this->view->session->get('viewCalendar.SpaceBookinf') ;
			
			echo '
<script type="text/javascript">
// Student
	$(document).ready(function(){
		$("#tt").load("'.GIBBON_URL.'index.php?q=/modules/Timetable/index_tt_ajax.php",{"gibbonTTID": "'.@$_GET['gibbonTTID'].'", "ttDate": "'. @$_POST['ttDate'].'", "fromTT": "'.@$_POST['fromTT'].'", "personalCalendar": "'.$personal.'", "schoolCalendar": "'.$school.'", "spaceBookingCalendar": "'.$space.'", "divert": "true", "action": "'.$tok->generateAction('/modules/Timetable/index_tt_ajax.php').'", "_token": "'.$tok->generateToken('/modules/Timetable/index_tt_ajax.php').'"});
	});
</script>
			';?>
			<?php
			$this->timetable->content = $this->view->renderReturn('student.timetable.loading');
			$this->timetable->status = true ;
		}
	}
	
	/**
	 * get Planner
	 *
	 * @version	9th October 2016
	 * @since	8th October 2016
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
		//GET PLANNER
		$planner = false;
		$date = date('Y-m-d');
		if ($this->view->getRecord('schoolYear')->isSchoolOpen($date) && $this->view->getSecurity()->isActionAccessible('/modules/Planner/planner.php') && $this->session->notEmpty('username')) {
			$data = array('gibbonSchoolYearID' => $_SESSION['gibbonSchoolYearID'], 'date' => $date, 'gibbonPersonID' => $_SESSION['gibbonPersonID'], 'gibbonSchoolYearID2' => $_SESSION['gibbonSchoolYearID'], 'date2' => $date, 'gibbonPersonID2' => $_SESSION['gibbonPersonID'], 'role1' => 'Student - Left', 'role2' => 'Teacher - Left');
			$sql = "(SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, 
					gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, 
					gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, 
					homeworkCrowdAssess, role, date, summary, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime 
				FROM gibbonPlannerEntry 
					JOIN gibbonCourseClass ON gibbonPlannerEntry.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
					JOIN gibbonCourseClassPerson ON gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID 
					JOIN gibbonCourse ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID 
					LEFT JOIN gibbonPlannerEntryStudentHomework ON gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID = gibbonPlannerEntry.gibbonPlannerEntryID 
						AND gibbonPlannerEntryStudentHomework.gibbonPersonID = gibbonCourseClassPerson.gibbonPersonID
				WHERE gibbonSchoolYearID = :gibbonSchoolYearID 
					AND date = :date 
					AND gibbonCourseClassPerson.gibbonPersonID = :gibbonPersonID 
					AND NOT role = :role1 
					AND NOT role = :role2) 
				UNION (SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, 
						gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, 
						gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, 
						viewableParents, homework, homeworkSubmission, homeworkCrowdAssess,  role, date, summary, 
						NULL AS myHomeworkDueDateTime 
					FROM gibbonPlannerEntry 
						JOIN gibbonCourseClass ON gibbonPlannerEntry.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
						JOIN gibbonPlannerEntryGuest ON gibbonPlannerEntryGuest.gibbonPlannerEntryID = gibbonPlannerEntry.gibbonPlannerEntryID
						JOIN gibbonCourse ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
					WHERE gibbonSchoolYearID = :gibbonSchoolYearID2 
						AND date = :date2 
						AND gibbonPlannerEntryGuest.gibbonPersonID = :gibbonPersonID2) 
				ORDER BY date, timeStart, course, class";
			$result = $this->view->getRecord('plannerEntry')->findAll($sql, $data);
			if (! $this->view->getRecord('plannerEntry')->getSuccess())
				$planner .= $this->view->returnMessage($this->view->getRecord('plannerEntry')->getError());

			if ($result->rowCount() < 1) {
				$planner .= $this->view->returnMessage('There are no records to display.', 'warning');
			} else {
	
				$planner .= $this->view->renderReturn('student.planner.start');
				$count = 0;
				$rowNum = 'odd';
				foreach($result as $w) {
					$row = $w->returnRecord();
					$row->date = $date;
					$row->unit = $this->view->getRecord('unit')->getUnit($row->gibbonUnitID, $row->gibbonHookID, $row->gibbonCourseClassID);
					if ($row->role == 'Teacher') {
						$row->likesGiven = $this->view->getRecord('like')->countLikesByContext('Planner', 'gibbonPlannerEntryID', $row->gibbonPlannerEntryID);
					} else {
						$row->likesGiven = $this->view->getRecord('like')->countLikesByContextAndGiver('Planner', 'gibbonPlannerEntryID', $row->gibbonPlannerEntryID, $this->view->session->get('gibbonPersonID'));
					}

					if (!($row->role == 'Student' && $row->viewableStudents == 'N')) 
						$planner .= $this->view->renderReturn('student.planner.member', $row);
				}
				$planner .= '</tbody>
				</table>';
			}
		}
		$this->planner->content = $planner;
		if ($planner !== false)
			$this->planner->status = true ;
	}
	
	/**
	 * get Hooks
	 *
	 * @version	9th October 2016
	 * @since	9th October 2016
	 * @param	integer		$personID
	 * @return	void
	 */
	public function getHooks($personID)
	{
		if (! empty($this->hooks)) return ;
		
		//GET TIMETABLE
		
		$this->hooks = new stdClass();
		$this->hooks->status = false;
		$this->hooks->content = '';

		//GET HOOKS INTO DASHBOARD
		$hooks = array();
		$dataHooks = array();
		$sqlHooks = "SELECT * FROM gibbonHook WHERE type='Student Dashboard'";
		$resultHooks = $this->view->getRecord('hook')->findAll($sqlHooks, $dataHooks);
		if (count($resultHooks) > 0) {
			$count = 0;
			foreach($resultHooks as $rowHooks) {
				$options = unserialize($rowHooks->options);
				//Check for permission to hook
				$dataHook = array('gibbonRoleIDCurrent' => $this->view->session->get('gibbonRoleIDCurrent'), 'sourceModuleName' => $options['sourceModuleName'], 'type' =>'Student Dashboard',
					'actionName' => $options['sourceModuleAction'], 'moduleName' => $options['sourceModuleName']);
				$sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action 
					FROM gibbonHook 
						JOIN gibbonModule ON gibbonHook.gibbonModuleID = gibbonModule.gibbonModuleID
						JOIN gibbonAction ON gibbonAction.gibbonModuleID = gibbonModule.gibbonModuleID
						JOIN gibbonPermission ON gibbonPermission.gibbonActionID = gibbonAction.gibbonActionID
					WHERE gibbonAction.gibbonModuleID = (SELECT gibbonModuleID 
						FROM gibbonModule 
						WHERE name = :sourceModuleName) 
						AND gibbonPermission.gibbonRoleID = :gibbonRoleIDCurrent 
							AND gibbonHook.type = :type
							AND gibbonAction.name = :actionName
							AND gibbonModule.name = :moduleName
						ORDER BY name";
				$resultHook = $this->view->getRecord('hook')->findAll($sqlHooks, $dataHooks);
				if (count($resultHook) == 1) {
					$rowHook = reset($resultHook);
					$hooks[$count]['name'] = $rowHooks->name;
					$hooks[$count]['sourceModuleName'] = $rowHook->module;
					$hooks[$count]['sourceModuleInclude'] = $options['sourceModuleInclude'];
					++$count;
				}
			}
		}
		$this->hooks->content = $hooks;
		if (! empty($hooks)) $this->hooks->status = true ;
	}
	
	/**
	 * get View
	 *
	 * @version	11th October 2016
	 * @since	11th October 2016
	 * @return	Gibbon\core\view 
	 */
	public function getView()
	{
		return $this->view ;
	}
}