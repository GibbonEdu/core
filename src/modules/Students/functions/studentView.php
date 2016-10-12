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
namespace Module\Students\functions ;

use Gibbon\core\listElement ;
use Gibbon\People\student ;

/**
 * Student View Functions
 *
 * @version	12th August 2016
 * @since	12th August 2016
 * @author	Craig Rayner
 */
class studentView
{
	/**
	 * @var View
	 */
	private $view ;
	
	/**
	 * @var Student
	 */
	private $student ;
	
	/**
	 * Construct
	 *
	 * @version	12th August 2016
	 * @since	12th August 2016
	 * @param	object		$student
	 * @return	this
	 */
	public function __construct(student $student)
	{
		$this->view = $student->getView() ;
		$this->student = $student ;
		return $this;
	}
	
	/**
	 * Side Bar Extra
	 *
	 * @version	12th August 2016
	 * @since	12th August 2016
	 * @param	Gibbon\People\student	$person
	 * @return	this
	 */
	public function sidebarExtra()
	{
		//Set sidebar
		$session = $this->view->session;
		
		$person = $this->student ;
		
		$session->clear('sidebarExtra');

        $personID = isset($_GET['gibbonPersonID']) ? $_GET['gibbonPersonID'] : 0;
		$subpage = isset($_GET['subpage']) ? $_GET['subpage'] : null ;
        $search = isset($_GET['search']) ? $_GET['search'] : null;
        $allStudents = isset($_GET['allStudents']) ? $_GET['allStudents'] : '';
        $sort = isset($_GET['sort']) ? $_GET['sort'] : '';
        $hook = isset($_GET['hook']) ? $_GET['hook'] : '';
        $module = isset($_GET['module']) ? $_GET['module'] : '';
        $action = isset($_GET['action']) ? $_GET['action'] : '';

		//Show alerts
		$alert = $this->view->getRecord('alertLevel')->getAlertBar($person->getID(), $person->getField('privacy'), '', false, true);
		$session->append('sidebarExtra', "<div class='alertBarHolder'>");
		if (empty($alert)) {
			$session->append('sidebarExtra', $this->view->strong('No Current Alerts', array(), true));
		} else {
			$session->append('sidebarExtra', $alert);
		}
		$session->append('sidebarExtra', '</div>');

		$session->append('sidebarExtra', $person->getUserPhoto($person->getField('image_240'), 240));

		//PERSONAL DATA MENU ITEMS
		
		$list = $this->view->startList('ul', 'moduleMenu')
			->addHeader($this->view->h4('Personal', array(), true));
		
		$style = $subpage == 'Overview' ? 'font-weight: bold' : '';
		$list->addListLink('Overview', $style, array('q'=>$_GET['q'], 'gibbonPersonID'=>$person->getID(), 'search'=>$search, 'allStudents'=>$allStudents, 'subpage'=>'Overview'));

		$style = $subpage == 'Personal' ? 'font-weight: bold' : '';
		$list->addListLink('Personal', $style, array('q'=>$_GET['q'], 'gibbonPersonID'=>$person->getID(), 'search'=>$search, 'allStudents'=>$allStudents, 'subpage'=>'Personal'));

		$style = $subpage == 'Family' ? 'font-weight: bold' : '';
		$list->addListLink('Family', $style, array('q'=>$_GET['q'], 'gibbonPersonID'=>$person->getID(), 'search'=>$search, 'allStudents'=>$allStudents, 'subpage'=>'Family'));

		$style = $subpage == 'Emergency Contacts' ? 'font-weight: bold' : '';
		$list->addListLink('Emergency Contacts', $style, array('q'=>$_GET['q'], 'gibbonPersonID'=>$person->getID(), 'search'=>$search, 'allStudents'=>$allStudents, 'subpage'=>'Emergency Contacts'));

		$style = $subpage == 'Medical' ? 'font-weight: bold' : '';
		$list->addListLink('Medical', $style, array('q'=>$_GET['q'], 'gibbonPersonID'=>$person->getID(), 'search'=>$search, 'allStudents'=>$allStudents, 'subpage'=>'Medical'));

        $enableStudentNotes = $this->view->config->getSettingByScope('Students', 'enableStudentNotes');
		if ($this->view->getSecurity()->isActionAccessible('/modules/Students/student_view_details_notes_add.php')) {
			if ($enableStudentNotes == 'Y') {
				$style = $subpage == 'Notes' ? 'font-weight: bold' : '';
				$list->addListLink('Notes', $style, array('q'=>$_GET['q'], 'gibbonPersonID'=>$person->getID(), 'search'=>$search, 'allStudents'=>$allStudents, 'subpage'=>'Notes'));
			}
		}
		$session->append('sidebarExtra', $list->renderList($this->view, true));

		//OTHER MENU ITEMS, DYANMICALLY ARRANGED TO MATCH CUSTOM TOP MENU
		//Get all modules, with the categories
		$mObj = $this->view->getRecord('module');
		$mainMenu = array();
		foreach($mObj->findAllActive() as $module) {
			$mainMenu[$module->getField('name')] = $module->getField('category');
		}
		$studentMenuCateogry = array();
		$studentMenuName = array();
		$studentMenuLink = array();
		$studentMenuCount = 0;

		//Store items in an array
		if ($this->view->getSecurity()->isActionAccessible('/modules/Markbook/markbook_view.php')) {
			$studentMenuCategory[$studentMenuCount] = $mainMenu['Markbook'];
			$studentMenuName[$studentMenuCount] = 'Markbook';
			$studentMenuLink[$studentMenuCount] = array('subpage' => $studentMenuName[$studentMenuCount]);
			++$studentMenuCount;
		}
		if ($this->view->getSecurity()->isActionAccessible('/modules/Formal Assessment/internalAssessment_view.php')) {
			$studentMenuCategory[$studentMenuCount] = $mainMenu['Formal Assessment'];
			$studentMenuName[$studentMenuCount] = 'Formal Assessment';
			$studentMenuLink[$studentMenuCount] = array('subpage' => $studentMenuName[$studentMenuCount]);
			++$studentMenuCount;
		}
		if ($this->view->getSecurity()->isActionAccessible('/modules/Formal Assessment/externalAssessment_details.php') || 
			$this->view->getSecurity()->isActionAccessible('/modules/Formal Assessment/externalAssessment_view.php')) {
			$studentMenuCategory[$studentMenuCount] = $mainMenu['Formal Assessment'];
			$studentMenuName[$studentMenuCount] = 'External Assessment';
			$studentMenuLink[$studentMenuCount] = array('subpage' => $studentMenuName[$studentMenuCount]);
			++$studentMenuCount;
		}

		if ($this->view->getSecurity()->isActionAccessible('/modules/Activities/report_activityChoices_byStudent.php')) {
			$studentMenuCategory[$studentMenuCount] = $mainMenu['Activities'];
			$studentMenuName[$studentMenuCount] = 'Activities';
			$studentMenuLink[$studentMenuCount] = array('subpage' => $studentMenuName[$studentMenuCount]);
			++$studentMenuCount;
		}
		if ($this->view->getSecurity()->isActionAccessible('/modules/Planner/planner_edit.php') || $this->view->getSecurity()->isActionAccessible('/modules/Planner/planner_view_full.php')) {
			$studentMenuCategory[$studentMenuCount] = $mainMenu['Planner'];
			$studentMenuName[$studentMenuCount] = 'Homework';
			$studentMenuLink[$studentMenuCount] = array('subpage' => $studentMenuName[$studentMenuCount]);
			++$studentMenuCount;
		}
		if ($this->view->getSecurity()->isActionAccessible('/modules/Individual Needs/in_view.php')) {
			$studentMenuCategory[$studentMenuCount] = $mainMenu['Individual Needs'];
			$studentMenuName[$studentMenuCount] = 'Individual Needs';
			$studentMenuLink[$studentMenuCount] = array('subpage' => $studentMenuName[$studentMenuCount]);
			++$studentMenuCount;
		}
		if ($this->view->getSecurity()->isActionAccessible('/modules/Library/report_studentBorrowingRecord.php')) {
			$studentMenuCategory[$studentMenuCount] = $mainMenu['Library'];
			$studentMenuName[$studentMenuCount] = 'Library Borrowing';
			$studentMenuLink[$studentMenuCount] = array('subpage' => $studentMenuName[$studentMenuCount]);
			++$studentMenuCount;
		}
		if ($this->view->getSecurity()->isActionAccessible('/modules/Timetable/tt_view.php')) {
			$studentMenuCategory[$studentMenuCount] = $mainMenu['Timetable'];
			$studentMenuName[$studentMenuCount] = 'Timetable';
			$studentMenuLink[$studentMenuCount] = array('subpage' => $studentMenuName[$studentMenuCount]);
			++$studentMenuCount;
		}
		if ($this->view->getSecurity()->isActionAccessible('/modules/Behaviour/behaviour_view.php')) {
			$studentMenuCategory[$studentMenuCount] = $mainMenu['Behaviour'];
			$studentMenuName[$studentMenuCount] = 'Behaviour';
			$studentMenuLink[$studentMenuCount] = array('subpage' => $studentMenuName[$studentMenuCount]);
			++$studentMenuCount;
		}
		if ($this->view->getSecurity()->isActionAccessible('/modules/Attendance/report_studentHistory.php')) {
			$studentMenuCategory[$studentMenuCount] = $mainMenu['Attendance'];
			$studentMenuName[$studentMenuCount] = 'School Attendance';
			$studentMenuLink[$studentMenuCount] = array('subpage' => $studentMenuName[$studentMenuCount]);
			++$studentMenuCount;
		}

		//Check for hooks, and slot them into array
		$hookRecords = $this->view->getRecord('hook')->findAllByType('Student Profile');

		if (count($hookRecords) > 0) {
			$hooks = array();
			foreach($hookRecords as $rowHooks) {
				$options = unserialize($rowHooks->getField('options'));
				//Check for permission to hook
				
				if ($rowHooks->isPermitted()) {
					$studentMenuCategory[$studentMenuCount] = $mainMenu[$options['sourceModuleName']];
					$studentMenuName[$studentMenuCount] = $rowHooks->getField('name');
					$studentMenuLink[$studentMenuCount] = array(	'subpage' => $studentMenuName[$studentMenuCount], 
																	'hook'=>$rowHooks->getField('name'), 
																	'module'=>$options['sourceModuleName'], 
																	'action'=>$options['sourceModuleAction'], 
																	'gibbonHookID'=>$rowHooks->getID()
																);
					++$studentMenuCount;
				}
			}
		}

		//Sort array
		array_multisort($studentMenuCategory, $studentMenuName, $studentMenuLink);

		//Spit out array
		$list = null;
		if (count($studentMenuCategory) > 0) {
			$categoryLast = '';
			foreach($studentMenuCategory as $i=>$category) {

				if (! empty($categoryLast) && $category != $categoryLast) {
					$session->append('sidebarExtra', $list->renderList($this->view, true));
					$list = null;
				}
				if ($category != $categoryLast) 
					$list = $this->view->startList('ul', 'moduleMenu')
						->addHeader($this->view->h4($category, array(), true));

				$style = $subpage == $studentMenuName[$i] ? 'font-weight: bold' : '';
				$linkGet = array_merge(array('q'=>$_GET['q'], 'gibbonPersonID'=>$person->getID(), 'search'=>$search, 'allStudents'=>$allStudents), $studentMenuLink[$i]);
				$list->addListLink($studentMenuName[$i], $style, $linkGet);

				$categoryLast = $category;
			}
			if (! is_null($list))
				$session->append('sidebarExtra', $list->renderList($this->view, true));
		}
	}
}