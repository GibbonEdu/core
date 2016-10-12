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
 * @package	Gibbon
 * @subpackage	Module
*/
/**
 */
namespace Module\Timetable\Functions ;

use Gibbon\core\moduleFunctions as mfBase ;
use stdClass ;

/**
 * Timetable Functions
 *
 * @version	5th October 2016
 * @since	copied from Timetable Module
 * 
 */
class functions extends mfBase
{
	use \Gibbon\core\functions\dateFunctions,
		\Gibbon\core\functions\arrayFunctions ;
	/**
	 * @var	stdClass		Timetable Details
	 */
	protected $td;
	/**
	 * render TT
	 *
	 * TIMETABLE FOR INDIVIUDAL<br/>
	 * $this->td->narrow can be "full", "narrow", or "trim" (between narrow and full)
	 * @version	5th October 2016
	 * @since	copied from Timetable Module
	 * @param	integer	$personID
	 * @param	integer	$TTID
	 * @param	string	title
	 * @param	integer	$startDayStamp
	 * @param	string	$q
	 * @param	string	$params
	 * @param	string	$narrow
	 * @param	boolean	$edit
	 * @return	string or false
	 */
	function renderTT($personID, $TTID, $title = '', $startDayStamp = '', $q = '', $params = '', $narrow = 'full', $edit = false)
	{
		$this->td = new stdClass();
		$this->td->zCount = 0;
		$this->td->personID = $personID ;
		$this->td->TTID = $TTID ;
		$this->td->title = $title ;
		$this->td->startDayStamp = $startDayStamp;
		$this->td->q = $q ;
		$this->td->params = $params ;
		$this->td->narrow = $narrow ;
		$this->td->edit = $edit ;
		$module = $this->session->get('module');
		$this->session->set('module', 'Timetable');

		$proceed = false;

		ob_start();
	
		if ($this->view->getSecurity()->isActionAccessible('/modules/Timetable/tt.php', 'View Timetable by Person_allYears')) {
			$proceed = true;
		} else {
			if ($this->session->get('gibbonSchoolYearIDCurrent') == $this->session->get('gibbonSchoolYearID')) {
				$proceed = true;
			}
		}
	
		if (! $proceed) {
			$this->displayMessage('You do not have permission to access this timetable at this time.');
		} else {
			$self = false;
			if ($personID == $this->session->get('gibbonPersonID') && ! $this->td->edit) {
				$self = true;

				//Update display choices
				$person = $this->view->getRecord('person');
				$person->find($this->session->get('gibbonPersonID'));
				$person->setField('viewCalendarSchool', ($this->session->get('viewCalendar.School') ? 'Y' : 'N'));
				$person->setField('viewCalendarPersonal', ($this->session->get('viewCalendar.Personal') ? 'Y' : 'N'));
				$person->setField('viewCalendarSpaceBooking', ($this->session->get('viewCalendar.SpaceBooking') ? 'Y' : 'N'));
				if (! $person->writeRecord(array('viewCalendarSchool', 'viewCalendarPersonal', 'viewCalendarSpaceBooking')))
					$this->view->displayMessage($person->getError());
			}
	
			$blank = true;
			$this->td->startDayStamp = $this->td->startDayStamp = $this->td->startDayStamp == '' ? time() : $this->td->startDayStamp ;
	
			$ttObj = $this->view->getRecord("TT");
			//Find out which timetables I am involved in this year
			$data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $personID);
			$sql = "SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name 
				FROM gibbonTT 
					JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) 
					JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) 
					JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
					JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
				WHERE gibbonPersonID=:gibbonPersonID 
					AND gibbonSchoolYearID=:gibbonSchoolYearID 
					AND active='Y' ";
			$result = $ttObj->findAll($sql, $data);
			if (! $ttObj->getSuccess())
				$this->displayMessage($ttObj->getError());
	
			//If I am not involved in any timetables display all within the year
			if (count($result) == 0) {
				$data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'));
				$sql = "SELECT gibbonTT.gibbonTTID, gibbonTT.name 
					FROM gibbonTT 
					WHERE gibbonSchoolYearID=:gibbonSchoolYearID 
						AND active='Y' ";
				$result = $ttObj->findAll($sql, $data);
				if (! $ttObj->getSuccess())
					$this->displayMessage($ttObj->getError());
			}
	
			$this->td->result = $result;
			$this->td->data = $data;
			$this->td->sql = $sql;
			//link to other TTs
			if (count($result) > 1) {
				$this->view->render('Timetable.otherTT', $this->td);
				if (! empty($TTID)) {
					$data = array('schoolYearID' => $this->session->get('gibbonSchoolYearID'), 'gibbonTTID' => $TTID, 'personID' => $personID);
					$sql = "SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name 
						FROM gibbonTT 
						JOIN gibbonTTDay ON gibbonTT.gibbonTTID = gibbonTTDay.gibbonTTID
						JOIN gibbonTTDayRowClass ON gibbonTTDayRowClass.gibbonTTDayID = gibbonTTDay.gibbonTTDayID
						JOIN gibbonCourseClass ON gibbonTTDayRowClass.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID 
						JOIN gibbonCourseClassPerson ON gibbonCourseClassPerson.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
					WHERE gibbonPersonID = :personID
						AND gibbonSchoolYearID = :schoolYearID 
						AND gibbonTT.gibbonTTID = :gibbonTTID";
				}
				$result = $ttObj->findAll($sql, $data);
				if (! $ttObj->getSuccess())
					$this->displayMessage($ttObj->getError());
			}
	
			//Display first TT
			if (count($result) > 0) {
				$row = reset($result);
				$this->td->record = $row->returnRecord();
				$this->view->render('Timetable.headerTable', $this->td);
	
				//Check which days are school days
				$this->td->daysInWeek = 0;
				$this->td->days = array();
				$this->td->timeStart = '';
				$this->td->timeEnd = '';
				$this->td->days = $this->view->getRecord('daysOfWeek')->getSchoolDays(true);
				$this->td->daysInWeek = count($this->td->days) ;
				foreach ($this->td->days as $day) {
					if (empty($this->td->timeStart) || empty($this->td->timeEnd)) {
						$this->td->timeStart = $day['schoolStart'];
						$this->td->timeEnd = $day['schoolEnd'];
					} else {
						if ($day['schoolStart'] < $this->td->timeStart) {
							$this->td->timeStart = $day['schoolStart'];
						}
						if ($day['schoolEnd'] > $this->td->timeEnd) {
							$this->td->timeEnd = $day['schoolEnd'];
						}
					}
				}
				
				//Count back to first dayOfWeek before specified calendar date
				$w = intval(date('w', $this->td->startDayStamp));
				$day = reset($this->td->days);
				$this->td->dateCorrectionOffSet = $day['sequenceNumber'];
				$this->td->startDayStamp = strtotime(date('Ymd', $this->td->startDayStamp). ' -'.$w.' Days');
				while (date('D', $this->td->startDayStamp) != $day['nameShort'])
					$this->td->startDayStamp = strtotime(date('Ymd', $this->td->startDayStamp). ' +1 Day');

				//Count forward to the end of the week
				$this->td->endDayStamp = strtotime(date('Ymd', $this->td->startDayStamp). ' +'.($this->td->daysInWeek - 1).' Days');
	
				$this->td->schoolCalendarAlpha = 0.85;
				$this->td->ttAlpha = 1.0;
	
				if ($this->session->get('viewCalendar.School') || $this->session->get('viewCalendar.Personal') || $this->session->get('viewCalendar.SpaceBooking')) {
					$this->td->ttAlpha = 0.75;
				}
	
				//Get school calendar array
				$allDay = false;
				$eventsSchool = false;
				if ($self && $this->session->get('viewCalendar.School')) {
					if ($this->session->notEmpty('calendarFeed')) {
						$eventsSchool = $this->getCalendarEvents($this->session->get('calendarFeed'), $this->td->startDayStamp, $this->td->endDayStamp);
					}
					//Any all days?
					if ($eventsSchool != false) {
						foreach ($eventsSchool as $event) {
							if ($event[1] == 'All Day') {
								$allDay = true;
							}
						}
					}
				}
	
				//Get personal calendar array
				$eventsPersonal = false;
				if ($self && $this->session->get('viewCalendar.Personal')) {
					if ($this->session->notEmpty('calendarFeedPersonal') != '') {
						$eventsPersonal = $this->getCalendarEvents($this->session->get('calendarFeedPersonal'), $this->td->startDayStamp, $this->td->endDayStamp);
					}
					//Any all days?
					if ($eventsPersonal != false) {
						foreach ($eventsPersonal as $event) {
							if ($event[1] == 'All Day') {
								$allDay = true;
							}
						}
					}
				}
	
				$this->td->spaceBookingAvailable = $this->view->getSecurity()->isActionAccessible('/modules/Timetable/spaceBooking_manage.php');
				$eventsSpaceBooking = false;
				if ($this->td->spaceBookingAvailable) {
					//Get space booking array
					if ($self && $this->session->get('viewCalendar.SpaceBooking')) {
						$eventsSpaceBooking = $this->getSpaceBookingEvents($this->td->startDayStamp, $this->session->get('gibbonPersonID'));
					}
				}
	
				//Count up max number of all day events in a day
				$eventsCombined = false;
				$this->td->maxAllDays = 0;
				if ($allDay == true) {
					if ($eventsPersonal != false and $eventsSchool != false) {
						$eventsCombined = array_merge($eventsSchool, $eventsPersonal);
					} elseif ($eventsSchool != false) {
						$eventsCombined = $eventsSchool;
					} elseif ($eventsPersonal != false) {
						$eventsCombined = $eventsPersonal;
					}
	
					$eventsCombined = $this->msort($eventsCombined, 2, true);
	
					$currentAllDays = 0;
					$lastDate = '';
					$currentDate = '';
					foreach ($eventsCombined as $event) {
						if ($event[1] == 'All Day') {
							$currentDate = date('Y-m-d', $event[2]);
							if ($lastDate != $currentDate) {
								$currentAllDays = 0;
							}
							++$currentAllDays;
	
							if ($currentAllDays > $this->td->maxAllDays) {
								$this->td->maxAllDays = $currentAllDays;
							}
	
							$lastDate = $currentDate;
						}
					}
				}
	
				//Max diff time for week based on timetables
				$dataDiff = array('date1' => date('Y-m-d', ($this->td->startDayStamp + (86400 * 0))), 'date2' => date('Y-m-d', ($this->td->endDayStamp + (86400 * 1))), 'gibbonTTID' => $row->getField('gibbonTTID'));
				$sqlDiff = 'SELECT DISTINCT gibbonTTColumn.gibbonTTColumnID 
					FROM gibbonTTDay 
					JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) 
					JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) 
					WHERE (date >= :date1 
						AND date <= :date2) 
						AND gibbonTTID = :gibbonTTID';
				$resultDiff = $this->view->getRecord('TTDay')->findAll($sqlDiff, $dataDiff);
				if (! $this->view->getRecord('TTDay')->getSuccess())
					$this->displayMessage($this->view->getRecord('TTDay')->getError());
				
				foreach($resultDiff as $rowDiff) {
					$dataDiffDay = array('gibbonTTColumnID' => $rowDiff->getField('gibbonTTColumnID'));
					$sqlDiffDay = 'SELECT * 
						FROM `gibbonTTColumnRow` 
						WHERE `gibbonTTColumnID` = :gibbonTTColumnID 
						ORDER BY `timeStart`';
					$resultDiffDay = $this->view->getRecord('TTColumnRow')->findAll($sqlDiffDay, $dataDiffDay);
					if (! $this->view->getRecord('TTColumnRow')->getSuccess())
						$this->displayMessage($this->view->getRecord('TTColumnRow')->getError());
					foreach ($resultDiffDay as $rowDiffDay) {
						if ($rowDiffDay->getField('timeStart') < $this->td->timeStart) {
							$this->td->timeStart = $rowDiffDay->getField('timeStart');
						}
						if ($rowDiffDay->getField('timeEnd') > $this->td->timeEnd) {
							$this->td->timeEnd = $rowDiffDay->getField('timeEnd');
						}
					}
				}
	
				//Max diff time for week based on special days timing change
				$dataDiff = array('date1' => date('Y-m-d', ($this->td->startDayStamp + (86400 * 0))), 'date2' => date('Y-m-d', ($this->td->startDayStamp + (86400 * 6))));
				$sqlDiff = "SELECT * 
					FROM gibbonSchoolYearSpecialDay 
					WHERE date >= :date1 
						AND date <= :date2 
						AND type = 'Timing Change' 
						AND NOT schoolStart IS NULL 
						AND NOT schoolEnd IS NULL";
				$resultDiff = $this->view->getRecord('schoolYearSpecialDay')->findALL($sqlDiff, $dataDiff);
				if (! $this->view->getRecord('schoolYearSpecialDay')->getSuccess())
					$this->displayMessage($this->view->getRecord('schoolYearSpecialDay')->getError());
				foreach($resultDiff as $rowDiff) {
					if ($rowDiff->getField('schoolStart') < $this->td->timeStart) {
						$this->td->timeStart = $rowDiff->getField('schoolStart');
					}
					if ($rowDiff->getField('schoolEnd') > $this->td->timeEnd) {
						$this->td->timeEnd = $rowDiff->getField('schoolEnd');
					}
				}
	
				//Max diff based on school calendar events
				if ($self && $eventsSchool !== false) {
					foreach ($eventsSchool as $event) {
						if (date('Y-m-d', $event[2]) <= date('Y-m-d', ($this->td->startDayStamp + (86400 * 6)))) {
							if ($event[1] != 'All Day') {
								if (date('H:i:s', $event[2]) < $this->td->timeStart) {
									$this->td->timeStart = date('H:i:s', $event[2]);
								}
								if (date('H:i:s', $event[3]) > $this->td->timeEnd) {
									$this->td->timeEnd = date('H:i:s', $event[3]);
								}
								if (date('Y-m-d', $event[2]) != date('Y-m-d', $event[3])) {
									$this->td->timeEnd = '23:59:59';
								}
							}
						}
					}
				}
	
				//Max diff based on personal calendar events
				if ($self && $eventsPersonal !== false) {
					foreach ($eventsPersonal as $event) {
						if (date('Y-m-d', $event[2]) <= date('Y-m-d', ($this->td->startDayStamp + (86400 * 6)))) {
							if ($event[1] != 'All Day') {
								if (date('H:i:s', $event[2]) < $this->td->timeStart) {
									$this->td->timeStart = date('H:i:s', $event[2]);
								}
								if (date('H:i:s', $event[3]) > $this->td->timeEnd) {
									$this->td->timeEnd = date('H:i:s', $event[3]);
								}
								if (date('Y-m-d', $event[2]) != date('Y-m-d', $event[3])) {
									$this->td->timeEnd = '23:59:59';
								}
							}
						}
					}
				}
	
				//Max diff based on space booking events
				if ($self && $eventsSpaceBooking !== false) {
					foreach ($eventsSpaceBooking as $event) {
						if ($event[3] <= date('Y-m-d', ($this->td->startDayStamp + (86400 * 6)))) {
							if ($event[4] < $this->td->timeStart) {
								$this->td->timeStart = $event[4];
							}
							if ($event[5] > $this->td->timeEnd) {
								$this->td->timeEnd = $event[5];
							}
						}
					}
				}
	
				//Final calc
				$this->td->diffTime = strtotime($this->td->timeEnd) - strtotime($this->td->timeStart);
	
				if ($this->td->narrow == 'trim') {
					$this->td->width = (ceil(640 / $this->td->daysInWeek) - 20).'px';
				} elseif ($this->td->narrow == 'narrow') {
					$this->td->width = (ceil(515 / $this->td->daysInWeek) - 20).'px';
				} else {
					$this->td->width = (ceil(690 / $this->td->daysInWeek) - 20).'px';
				}
	
				$count = 0;
				
				$this->view->injectModuleCSS('Timetable');
				
				$this->view->render('Timetable.miniStart', $this->td);
				$this->view->render('Timetable.calendarControls', $this->td);
				$this->view->render('Timetable.calendarWeekHeader', $this->td);
	
	

	
				//Space for all day events
				if (($eventsSchool || $eventsPersonal ) && $allDay && ! is_null($eventsCombined)) 
					$this->view->render('Timetable.calendarAllDayEvents', $this->td);

				$this->view->render('Timetable.calendarWeekDetailsStart', $this->td);
				
				//Run through days of the week
				foreach ($this->td->days as $day) {
					if ($day['schoolDay'] == 'Y') {
						$dateCorrection = ($day['sequenceNumber'] - $this->td->dateCorrectionOffSet);

						//Check to see if day is term time
						$isDayInTerm = false;
						$dataTerm = array('schoolYearID' => $this->session->get('gibbonSchoolYearID'));
						$sqlTerm = 'SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay 
							FROM gibbonSchoolYearTerm, gibbonSchoolYear 
							WHERE gibbonSchoolYearTerm.gibbonSchoolYearID = gibbonSchoolYear.gibbonSchoolYearID 
								AND gibbonSchoolYear.gibbonSchoolYearID = :schoolYearID';
						$resultTerm = $this->view->getRecord('schoolYear')->findAll($sqlTerm, $dataTerm);
						if (! $this->view->getRecord('schoolYear')->getSuccess())
							$this->displayMessage($this->view->getRecord('schoolYear')->getError());

						foreach($resultTerm as $rowTerm) {
							if (date('Y-m-d', ($this->td->startDayStamp + (86400 * $dateCorrection))) >= $rowTerm->getField('firstDay') and date('Y-m-d', ($this->td->startDayStamp + (86400 * $dateCorrection))) <= $rowTerm->getField('lastDay')) {
								$isDayInTerm = true;
							}
						}

						if ($isDayInTerm) {
							//Check for school closure day
							$dataClosure = array('date' => date('Y-m-d', ($this->td->startDayStamp + (86400 * $dateCorrection))));
							$sqlClosure = 'SELECT * 
								FROM gibbonSchoolYearSpecialDay 
								WHERE date = :date';
							$resultClosure = $this->view->getRecord('schoolYearSpecialDay')->findAll($sqlClosure, $dataClosure);
							if (! $this->view->getRecord('schoolYearSpecialDay')->getSuccess())
								$this->displayMessage($this->view->getRecord('schoolYearSpecialDay')->getError());

							if (count($resultClosure) == 1) {
								$xx = reset($resultClosure);
								$rowClosure = (array) $xx->returnRecord();
								if ($rowClosure['type'] == 'School Closure') {
									$this->renderTTDay($row->getField('gibbonTTID'), false, $this->td->startDayStamp, $dateCorrection, $this->td->daysInWeek, $personID, $this->td->timeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $this->td->diffTime, $this->td->maxAllDays, $this->td->narrow, '', '', $this->td->edit);
								} elseif ($rowClosure['type'] == 'Timing Change') {
									$this->renderTTDay($row->getField('gibbonTTID'), true, $this->td->startDayStamp, $dateCorrection, $this->td->daysInWeek, $personID, $this->td->timeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $this->td->diffTime, $this->td->maxAllDays, $this->td->narrow, $rowClosure['schoolStart'], $rowClosure['schoolEnd'], $this->td->edit);
								}
							} else {
								$this->renderTTDay($row->getField('gibbonTTID'), true, $this->td->startDayStamp, $dateCorrection, $this->td->daysInWeek, $personID, $this->td->timeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $this->td->diffTime, $this->td->maxAllDays, $this->td->narrow, '', '', $this->td->edit);
							}
						} else {
							$this->renderTTDay($row->getField('gibbonTTID'), false, $this->td->startDayStamp, $dateCorrection, $this->td->daysInWeek, $personID, $this->td->timeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $this->td->diffTime, $this->td->maxAllDays, $this->td->narrow, '', '', $this->td->edit);
						}

						if (! $this->td->validDay) {
							?><td style='text-align: center; vertical-align: top; font-size: 11px'></td><?php
						}
					}
				}
				?></tr>
                </tbody>
                </table><?php
			}
		}
	
		$this->session->set('module', $module);

		$output = ob_get_contents();
		if (! empty($output))
			ob_end_clean();
		else
			return false ;
		return $output;
	}

	/**
	 * render TT Day
	 * 
	 * @version	7th October 2016
	 * @since	copied from Timetable Module
	 * @param	integer	$personID
	 * @param	integer	$TTID
	 * @param	string	$title
	 * @param	integer	$startDayStamp
	 * @param	string	$q
	 * @param	string	$params
	 * @param	string	$narrow
	 * @param	boolean	$this->td->edit
	 * @return	void
	 */
	public function renderTTDay($TTID, $schoolOpen, $startDayStamp, $count, $daysInWeek, $personID, $gridTimeStart, $eventsSchool, $eventsPersonal, $eventsSpaceBooking, $diffTime, $maxAllDays, $narrow, $specialDayStart = '', $specialDayEnd = '', $edit = false)
	{
		$this->td->schoolCalendarAlpha = 0.85;
		$this->td->ttAlpha = 1.0;
		$this->td->validDay = false;
		$this->td->diffTime = $diffTime ;
	
		if ($this->session->get('viewCalendar.School') || $this->session->get('viewCalendar.Personal') || $this->session->get('viewCalendar.SpaceBooking')) 
			$this->td->ttAlpha = 0.75;
	
		$this->td->date = date('Y-m-d', ($this->td->startDayStamp + (86400 * $count)));
		$self = false;
		if ($personID == $this->session->get('gibbonPersonID') && ! $this->td->edit) {
			$self = true;
			$roleCategory = $this->view->getSecurity()->getRoleCategory($this->session->get('gibbonRoleIDCurrent'));
		}
	
		if ($this->td->narrow == 'trim') {
			$this->td->width = (ceil(640 / $daysInWeek) - 20).'px';
		} elseif ($this->td->narrow == 'narrow') {
			$this->td->width = (ceil(515 / $daysInWeek) - 20).'px';
		} else {
			$this->td->width = (ceil(690 / $daysInWeek) - 20).'px';
		}
	
		$blank = true;
	
		$this->td->zCount = 0;
		$allDay = 0;
	
		if (! $schoolOpen) {
			$this->view->render('Timetable.dayColumn.start', $this->td);
			$this->view->render('Timetable.dayColumn.schoolClosedStart', $this->td);
	
			$this->td->zCount = 1;
	
			//Draw periods from school calendar
			if ($eventsSchool !== false) {
				$this->td->height = 0;
				$this->td->top = 0;
				$dayTimeStart = '';
				foreach ($eventsSchool as $event) {
					if (date('Y-m-d', $event[2]) == date('Y-m-d', ($this->td->startDayStamp + (86400 * $count)))) {
						if ($event[1] == 'All Day') {
							$this->td->label = $event[0];
							$this->td->title = '';
							if (strlen($this->td->label) > 20) {
								$this->td->label = substr($this->td->label, 0, 20).'...';
								$this->td->title = "title='".$event[0]."'";
							}
							$this->td->height = '30px';
							$this->td->top = (($maxAllDays * -31) - 8 + ($allDay * 30)).'px';
							$this->view->render('Timetable.dayColumn.schoolCalendar', $this->td);
							++$allDay;
						} else {
							$this->td->label = $event[0];
							$this->td->title = "title='".date('H:i', $event[2]).' to '.date('H:i', $event[3])."'";
							$this->td->height = ceil(($event[3] - $event[2]) / 60).'px';
							$charCut = 20;
							if (height < 20) {
								$charCut = 12;
							}
							if (strlen($this->td->label) > $charCut) {
								$this->td->label = substr($this->td->label, 0, $charCut).'...';
								$this->td->title = "title='".$event[0].' ('.date('H:i', $event[2]).' to '.date('H:i', $event[3]).")'";
							}
							$this->td->top = (ceil(($event[2] - strtotime(date('Y-m-d', $this->td->startDayStamp + (86400 * $count)).' '.$gridTimeStart)) / 60 )).'px';
							$this->td->event = $event ;
							$this->view->render('Timetable.dayColumn.schoolCalendar', $this->td);
						}
						++$this->td->zCount;
					}
				}
			}
	
			//Draw periods from personal calendar
			if ($eventsPersonal !== false) {
				$this->td->height = 0;
				$this->td->top = 0;
				$bg = "rgba(103,153,207,".$this->td->schoolCalendarAlpha.")";
				foreach ($eventsPersonal as $event) {
					if (date('Y-m-d', $event[2]) == date('Y-m-d', ($this->td->startDayStamp + (86400 * $count)))) {
						if ($event[1] == 'All Day') {
							$this->td->label = $event[0];
							$this->td->title = '';
							if (strlen($this->td->label) > 20) {
								$this->td->label = substr($this->td->label, 0, 20).'...';
								$this->td->title = "title='".$event[0]."'";
							}
							$this->td->height = '30px';
							$this->td->top = (($maxAllDays * -31) - 8 + ($allDay * 30)).'px';
							$this->view->render('Timetable.dayColumn.personalCalendar', $this->td);
							++$allDay;
						} else {
							$this->td->label = $event[0];
							$this->td->title = "title='".date('H:i', $event[2]).' to '.date('H:i', $event[3])."'";
							$this->td->height = ceil(($event[3] - $event[2]) / 60).'px';
							$charCut = 20;
							if (height < 20) {
								$charCut = 12;
							}
							if (strlen($this->td->label) > $charCut) {
								$this->td->label = substr($this->td->label, 0, $charCut).'...';
								$this->td->title = "title='".$event[0].' ('.date('H:i', $event[2]).' to '.date('H:i', $event[3]).")'";
							}
							$this->td->top = (ceil(($event[2] - strtotime(date('Y-m-d', $this->td->startDayStamp + (86400 * $count)).' '.$gridTimeStart)) / 60 )).'px';
							$this->td->event = $event ;
							$this->view->render('Timetable.dayColumn.personalCalendar', $this->td);
						}
						++$this->td->zCount;
					}
				}
			}
			?></div>
            </td><?php
		} else {
			//Make array of space changes
			$spaceChanges = array();
			$dataSpaceChange = array('date' => date('Y-m-d', ($this->td->startDayStamp + (86400 * $count))));
			$sqlSpaceChange = 'SELECT gibbonTTSpaceChange.*, gibbonSpace.name AS space, phoneInternal 
				FROM gibbonTTSpaceChange 
					LEFT JOIN gibbonSpace ON gibbonTTSpaceChange.gibbonSpaceID = gibbonSpace.gibbonSpaceID
				WHERE date = :date';
			$resultSpaceChange = $this->view->getRecord('TTSpaceChange')->findAll($sqlSpaceChange, $dataSpaceChange);
			foreach($resultSpaceChange as $rowSpaceChange) {
				$spaceChanges[$rowSpaceChange->getField('gibbonTTDayRowClassID')][0] = $rowSpaceChange['space'];
				$spaceChanges[$rowSpaceChange->getField('gibbonTTDayRowClassID')][1] = $rowSpaceChange['phoneInternal'];
			}
	
			//Get day start and end!
			$dayTimeStart = '';
			$dayTimeEnd = '';
			$dataDiff = array('date' => date('Y-m-d', ($this->td->startDayStamp + (86400 * $count))), 'gibbonTTID' => $TTID);
			$sqlDiff = 'SELECT timeStart, timeEnd 
				FROM gibbonTTDay 
					JOIN gibbonTTDayDate ON gibbonTTDay.gibbonTTDayID = gibbonTTDayDate.gibbonTTDayID 
					JOIN gibbonTTColumn ON gibbonTTDay.gibbonTTColumnID = gibbonTTColumn.gibbonTTColumnID
					JOIN gibbonTTColumnRow ON gibbonTTColumn.gibbonTTColumnID = gibbonTTColumnRow.gibbonTTColumnID
				WHERE date = :date 
					AND gibbonTTID = :gibbonTTID';
			$resultDiff = $this->view->getRecord('TTDay')->findAll($sqlDiff, $dataDiff);
			if (! $this->view->getRecord('TTDay')->getSuccess())
				$this->view->displayMessage($this->view->getRecord('TTDay')->getError());

			foreach($resultDiff as $rowDiff) {
				$dayTimeStart = $dayTimeStart == '' ? $rowDiff->getField('timeStart') : $dayTimeStart ;
				$dayTimeStart = $rowDiff->getField('timeStart') < $dayTimeStart ? $rowDiff->getField('timeStart') : $dayTimeStart ;

				$dayTimeEnd = $dayTimeEnd == '' ? $rowDiff->getField('timeEnd') : $dayTimeEnd ;
				$dayTimeEnd = $rowDiff->getField('timeEnd') > $dayTimeEnd ? $rowDiff->getField('timeEnd') : $dayTimeEnd ;
			}
			if (! empty($specialDayStart)) {
				$dayTimeStart = $specialDayStart;
			}
			if ($specialDayEnd != '') {
				$dayTimeEnd = $specialDayEnd;
			}

			$dayDiffTime = strtotime($dayTimeEnd) - strtotime($dayTimeStart);
	
			$startPad = strtotime($dayTimeStart) - strtotime($gridTimeStart);
	
			$this->view->render('Timetable.dayColumn.start', $this->td);

			$dataDay = array('gibbonTTID' => $TTID, 'date' => date('Y-m-d', ($this->td->startDayStamp + (86400 * $count))));
			$sqlDay = 'SELECT gibbonTTDay.gibbonTTDayID 
				FROM gibbonTTDayDate 
					JOIN gibbonTTDay ON gibbonTTDayDate.gibbonTTDayID = gibbonTTDay.gibbonTTDayID
				WHERE gibbonTTID = :gibbonTTID 
					AND date = :date';
			$resultDay = $this->view->getRecord('TTDayDate')->findAll($sqlDay, $dataDay);
			if (! $this->view->getRecord('TTDayDate')->getSuccess())
				$this->view->displayMessage($this->view->getRecord('TTDayDate')->getError());
	
			if (count($resultDay) == 1) {
				$rowDay = reset($resultDay);
				$this->td->zCount = 0;
				$this->view->render('Timetable.dayColumn.openStart', $this->td);
	
				//Draw outline of the day
				$dataPeriods = array('gibbonTTDayID' => $rowDay->getField('gibbonTTDayID'), 'date' => date('Y-m-d', ($this->td->startDayStamp + (86400 * $count))));
				$sqlPeriods = 'SELECT gibbonTTColumnRow.name, timeStart, timeEnd, type, date 
					FROM gibbonTTDay 
						JOIN gibbonTTDayDate ON gibbonTTDay.gibbonTTDayID = gibbonTTDayDate.gibbonTTDayID
						JOIN gibbonTTColumn ON gibbonTTDay.gibbonTTColumnID = gibbonTTColumn.gibbonTTColumnID
						JOIN gibbonTTColumnRow ON gibbonTTColumnRow.gibbonTTColumnID = gibbonTTColumn.gibbonTTColumnID
					WHERE gibbonTTDayDate.gibbonTTDayID = :gibbonTTDayID 
						AND date=:date 
					ORDER BY timeStart, timeEnd';
				$resultPeriods = $this->view->getRecord('TTDay')->findAll($sqlPeriods, $dataPeriods);
				if (! $this->view->getRecord('TTDay')->getSuccess())
					$this->view->displayMessage($this->view->getRecord('TTDay')->getError());

				foreach($resultPeriods as $rowPeriods) {
					$isSlotInTime = false;
					if ($rowPeriods->getField('timeStart')<= $dayTimeStart and $rowPeriods->getField('timeEnd') > $dayTimeStart) {
						$isSlotInTime = true;
					} elseif ($rowPeriods->getField('timeStart')>= $dayTimeStart and $rowPeriods->getField('timeEnd') <= $dayTimeEnd) {
						$isSlotInTime = true;
					} elseif ($rowPeriods->getField('timeStart')< $dayTimeEnd and $rowPeriods->getField('timeEnd') >= $dayTimeEnd) {
						$isSlotInTime = true;
					}
	
					if ($isSlotInTime == true) {
						$this->td->effectiveStart = $rowPeriods->getField('timeStart');
						$this->td->effectiveEnd = $rowPeriods->getField('timeEnd');
						if ($dayTimeStart > $rowPeriods->getField('timeStart')) {
							$this->td->effectiveStart = $dayTimeStart;
						}
						if ($dayTimeEnd < $rowPeriods->getField('timeEnd')) {
							$this->td->effectiveEnd = $dayTimeEnd;
						}
	
						$this->td->height = ceil((strtotime($this->td->effectiveEnd) - strtotime($this->td->effectiveStart)) / 60).'px';
						$this->td->top = ceil(((strtotime($this->td->effectiveStart) - strtotime($dayTimeStart)) + $startPad) / 60).'px';

						$this->td->title = '';
						if ($rowPeriods->getField('type') != 'Lesson' and $this->td->height > 15 and $this->td->height < 30) {
							$this->td->title = "title='".substr($this->td->effectiveStart, 0, 5).' - '.substr($this->td->effectiveEnd, 0, 5)."'";
						} elseif ($rowPeriods->getField('type') != 'Lesson' and $this->td->height <= 15) {
							$this->td->title = "title='".$rowPeriods->getField('name').' ('.substr($this->td->effectiveStart, 0, 5).'-'.substr($this->td->effectiveEnd, 0, 5).")'";
						}
						$this->td->class = 'ttGeneric';
						if ((date('H:i:s') > $this->td->effectiveStart) && (date('H:i:s') < $this->td->effectiveEnd) && $rowPeriods->getField('date') == date('Y-m-d')) 
							$this->td->class = 'ttCurrent';

						$style = '';
						if ($rowPeriods->getField('type') == 'Lesson') {
							$this->td->class = 'ttLesson';
						}
						
						$x = (array) $rowPeriods->returnRecord();
						foreach($x as $q=>$w)
							$this->td->$q = $w ;
						
						$this->view->render('Timetable.dayColumn.content', $this->td);
						++$this->td->zCount;
					}
				}
	
				//Draw periods from TT
				$dataPeriods = array('gibbonTTDayID' => $rowDay->getField('gibbonTTDayID'), 'gibbonPersonID' => $personID);
				$sqlPeriods = "SELECT gibbonTTDayID, gibbonTTDayRowClassID, gibbonTTColumnRow.gibbonTTColumnRowID, 
						gibbonCourseClass.gibbonCourseClassID, gibbonTTColumnRow.name, gibbonCourse.gibbonCourseID, 
						gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, timeStart, timeEnd, 
						phoneInternal, gibbonSpace.name AS roomName 
					FROM gibbonCourse 
						JOIN gibbonCourseClass ON gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID
						JOIN gibbonCourseClassPerson ON gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID
						JOIN gibbonTTDayRowClass ON gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID
						JOIN gibbonTTColumnRow ON gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID
						LEFT JOIN gibbonSpace ON gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID
					WHERE gibbonTTDayID = :gibbonTTDayID 
						AND gibbonCourseClassPerson.gibbonPersonID = :gibbonPersonID 
						AND NOT role LIKE '% - Left' 
					ORDER BY timeStart, timeEnd";
				$resultPeriods = $this->view->getRecord('course')->findAll($sqlPeriods, $dataPeriods);
				if (! $this->view->getRecord('course')->getSuccess())
					$this->view->displayMessage($this->view->getRecord('course')->getError());
				foreach($resultPeriods as $rowPeriods) {
					$isSlotInTime = false;
					if ($rowPeriods->getField('timeStart')<= $dayTimeStart && $rowPeriods->getField('timeEnd') > $dayTimeStart) {
						$isSlotInTime = true;
					} elseif ($rowPeriods->getField('timeStart')>= $dayTimeStart && $rowPeriods->getField('timeEnd') <= $dayTimeEnd) {
						$isSlotInTime = true;
					} elseif ($rowPeriods->getField('timeStart')< $dayTimeEnd && $rowPeriods->getField('timeEnd') >= $dayTimeEnd) {
						$isSlotInTime = true;
					}
	
					if ($isSlotInTime == true) {
						//Check for an exception for the current user
						$dataException = array('gibbonPersonID' => $personID, 'TTDayRowClassID' => $rowPeriods->getField('gibbonTTDayRowClassID'));
						$sqlException = 'SELECT * 
							FROM gibbonTTDayRowClassException 
							WHERE gibbonTTDayRowClassID = :TTDayRowClassID 
								AND gibbonPersonID=:gibbonPersonID';
						$resultException = $this->view->getRecord('TTDayRowClassException')->findAll($sqlException, $dataException);
						if (! $this->view->getRecord('TTDayRowClassException')->getSuccess())
							$this->view->displayMessage($this->view->getRecord('TTDayRowClassException')->getError());
						if (count($resultException) < 1) {
							$this->td->effectiveStart = $rowPeriods->getField('timeStart');
							$this->td->effectiveEnd = $rowPeriods->getField('timeEnd');
							if ($dayTimeStart > $rowPeriods->getField('timeStart')) {
								$this->td->effectiveStart = $dayTimeStart;
							}
							if ($dayTimeEnd < $rowPeriods->getField('timeEnd')) {
								$this->td->effectiveEnd = $dayTimeEnd;
							}
	
							$blank = false;
							if ($this->td->narrow == 'trim') {
								$this->td->width = (ceil(640 / $daysInWeek) - 20).'px';
							} elseif ($this->td->narrow == 'narrow') {
								$this->td->width = (ceil(515 / $daysInWeek) - 20).'px';
							} else {
								$this->td->width = (ceil(690 / $daysInWeek) - 20).'px';
							}
							$this->td->height = ceil((strtotime($this->td->effectiveEnd) - strtotime($this->td->effectiveStart)) / 60).'px';
							$this->td->top = (ceil((strtotime($this->td->effectiveStart) - strtotime($dayTimeStart)) / 60 + ($startPad / 60))).'px';
							$this->td->title = "title='";
							if ($this->td->height < 45) {
								$this->td->title .= $this->view->__('Time:').' '.substr($this->td->effectiveStart, 0, 5).' - '.substr($this->td->effectiveEnd, 0, 5).' | ';
								$this->td->title .= $this->view->__('Timeslot:').' '.$rowPeriods->getField('name').' | ';
							}
							if ($rowPeriods->getField('roomName') != '') {
								if ($this->td->height < 60) {
									if (isset($spaceChanges[$rowPeriods->getField('gibbonTTDayRowClassID')][0]) == false) {
										$this->td->title .= $this->view->__('Room:').' '.$rowPeriods->getField('roomName').' | ';
									} else {
										$this->td->title .= $this->view->__('Room:').' '.$spaceChanges[$rowPeriods->getField('gibbonTTDayRowClassID')][0].' | ';
									}
								}
								if ($rowPeriods->getField('phoneInternal') != '') {
									if (isset($spaceChanges[$rowPeriods->getField('gibbonTTDayRowClassID')][0]) == false) {
										$this->td->title .= $this->view->__('Phone:').' '.$rowPeriods->getField('phoneInternal').' | ';
									} else {
										$this->td->title .= $this->view->__('Phone:').' '.$spaceChanges[$rowPeriods->getField('gibbonTTDayRowClassID')][1].' | ';
									}
								}
							}
							$this->td->title = substr($this->td->title, 0, -3);
							$this->td->title .= "'";
							$this->td->class2 = 'ttPeriod';
	
							if ((date('H:i:s') > $this->td->effectiveStart) and (date('H:i:s') < $this->td->effectiveEnd) and $this->td->date == date('Y-m-d')) {
								$this->td->class2 = 'ttPeriodCurrent';
							}
							$x = (array)$rowPeriods->returnRecord();
							foreach($x as $q=>$w)
								$this->td->$q = $w;
							//Create div to represent period
							$this->view->render('Timetable.dayColumn.period', $this->td);
							++$this->td->zCount;
	
							if ($this->td->narrow == 'full' || $this->td->narrow == 'trim') {
								if (! $this->td->edit) {
									//Add planner link icons for staff looking at own TT.
										if ($self && $roleCategory == 'Staff') {
											if ($this->td->height >= 30) {
												$dataPlan = array('gibbonCourseClassID' => $rowPeriods->getField('gibbonCourseClassID'), 'date' => $this->td->date, 'timeStart' => $rowPeriods->getField('timeStart'), 'timeEnd' => $rowPeriods->getField('timeEnd'));
												$sqlPlan = 'SELECT name, gibbonPlannerEntryID FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd GROUP BY name';
												$resultPlan = $this->view->getRecord('plannerEntry')->findAll($sqlPlan, $dataPlan);
												if (! $this->view->getRecord('plannerEntry')->getSuccess())
													$this->view->displayMessage($this->view->getRecord('plannerEntry')->getError());
												$this->td->plan = $resultPlan ;
												$this->view->render('Timetable.dayColumn.staffPlannerLinks', $this->td);
												++$this->td->zCount;
											}
										}
										//Add planner link icons for any one else's TT
										else {
											//Check for lesson plan
											$bgImg = 'none';
	
											$dataPlan = array('gibbonCourseClassID' => $rowPeriods->getField('gibbonCourseClassID'), 'date' => $this->td->date, 
												'timeStart' => $rowPeriods->getField('timeStart'), 'timeEnd' => $rowPeriods->getField('timeEnd'));
											$sqlPlan = 'SELECT name, gibbonPlannerEntryID 
												FROM gibbonPlannerEntry 
												WHERE gibbonCourseClassID = :gibbonCourseClassID 
													AND date = :date 
													AND timeStart = :timeStart 
													AND timeEnd = :timeEnd 
												GROUP BY name';
											$resultPlan = $this->view->getRecord('plannerEntry')->findAll($sqlPlan, $dataPlan);
											if (! $this->view->getRecord('plannerEntry')->getSuccess())
												$this->view->displayMessage($this->view->getRecord('plannerEntry')->getError());
											$this->td->plan = $resultPlan ;
											$this->view->render('Timetable.dayColumn.otherPlannerLinks', $this->td);
											++$this->td->zCount;
										}
								}
								//Show exception editing
								elseif ($this->td->edit) {
									$this->view->render('Timetable.dayColumn.attendanceLinks', $this->td);
									++$this->td->zCount;
								}
							}
						}
					}
				}
	
				//Draw periods from school calendar
				if ($eventsSchool !== false) {
					$this->td->height = 0;
					$this->td->top = 0;
					foreach ($eventsSchool as $event) {
						if (date('Y-m-d', $event[2]) == date('Y-m-d', ($this->td->startDayStamp + (86400 * $count)))) {
							if ($event[1] == 'All Day') {
								$this->td->label = $event[0];
								$this->td->title = '';
								if (strlen($this->td->label) > 20) {
									$this->td->label = substr($this->td->label, 0, 20).'...';
									$this->td->title = "title='".$event[0]."'";
								}
								$this->td->height = '30px';
								$this->td->top = (($maxAllDays * -31) - 8 + ($allDay * 30)).'px';
								$this->td->event = $event;
								$this->view->render('Timetable.dayColumn.schoolCalendar', $this->td);
								++$allDay;
							} else {
								$this->td->label = $event[0];
								$this->td->title = "title='".date('H:i', $event[2]).' to '.date('H:i', $event[3])."'";
								$this->td->height = ceil(($event[3] - $event[2]) / 60).'px';
								$charCut = 20;
								if ($this->td->height < 20) {
									$charCut = 12;
								}
								if (strlen($this->td->label) > $charCut) {
									$this->td->label = substr($this->td->label, 0, $charCut).'...';
									$this->td->title = "title='".$event[0].' ('.date('H:i', $event[2]).' to '.date('H:i', $event[3]).")'";
								}
								$this->td->top = (ceil(($event[2] - strtotime(date('Y-m-d', $this->td->startDayStamp + (86400 * $count)).' '.$gridTimeStart)) / 60 )).'px';
								$this->td->event = $event;
								$this->view->render('Timetable.dayColumn.schoolCalendar', $this->td);
							}
							++$this->td->zCount;
						}
					}
				}
	
				//Draw periods from personal calendar
				if ($eventsPersonal !== false) {
					$this->td->height = 0;
					$this->td->top = 0;
					$bg = 'rgba(103,153,207,'.$this->td->schoolCalendarAlpha.')';
					foreach ($eventsPersonal as $event) {
						if (date('Y-m-d', $event[2]) == date('Y-m-d', ($this->td->startDayStamp + (86400 * $count)))) {
							if ($event[1] == 'All Day') {
								$this->td->label = $event[0];
								$this->td->title = '';
								if (strlen($this->td->label) > 20) {
									$this->td->label = substr($this->td->label, 0, 20).'...';
									$this->td->title = "title='".$event[0]."'";
								}
								$this->td->height = '30px';
								$this->td->top = (($maxAllDays * -31) - 8 + ($allDay * 30)).'px';
								$this->td->event = $event;
								$this->view->render('Timetable.dayColumn.personalCalendar', $this->td);
								++$allDay;
							} else {
								$this->td->label = $event[0];
								$this->td->title = "title='".date('H:i', $event[2]).' to '.date('H:i', $event[3])."'";
								$this->td->height = ceil(($event[3] - $event[2]) / 60).'px';
								$charCut = 20;
								if ($this->td->height < 20) {
									$charCut = 12;
								}
								if (strlen($this->td->label) > $charCut) {
									$this->td->label = substr($this->td->label, 0, $charCut).'...';
									$this->td->title = "title='".$event[0].' ('.date('H:i', $event[2]).' to '.date('H:i', $event[3]).")'";
								}
								$this->td->top = (ceil(($event[2] - strtotime(date('Y-m-d', $this->td->startDayStamp + (86400 * $count)).' '.$gridTimeStart)) / 60 )).'px';
								$this->td->event = $event;
								$this->view->render('Timetable.dayColumn.personalCalendar', $this->td);
							}
							++$this->td->zCount;
						}
					}
				}
	
				//Draw space bookings
				if ($eventsSpaceBooking !== false) {
					$this->td->height = 0;
					$this->td->top = 0;
					foreach ($eventsSpaceBooking as $event) {
						if ($event[3] == date('Y-m-d', ($this->td->startDayStamp + (86400 * $count)))) {
							$this->td->height = ceil((strtotime(date('Y-m-d', ($this->td->startDayStamp + (86400 * $count))).' '.$event[5]) - strtotime(date('Y-m-d', ($this->td->startDayStamp + (86400 * $count))).' '.$event[4])) / 60).'px';
							$this->td->top = (ceil((strtotime($event[3].' '.$event[4]) - strtotime(date('Y-m-d', $this->td->startDayStamp + (86400 * $count)).' '.$dayTimeStart)) / 60 + ($startPad / 60))).'px';
							if ($this->td->height < 45) {
								$this->td->label = $event[1];
								$this->td->title = "title='".substr($event[4], 0, 5).'-'.substr($event[5], 0, 5)."'";
							} else {
								$this->td->label = $event[1]."<br/><span style='font-weight: normal'>(".substr($event[4], 0, 5).'-'.substr($event[5], 0, 5).')<br/></span>';
								$this->td->title = '';
							}
							$this->td->event = $event;
							$this->view->render('Timetable.dayColumn.spaceBookingCalendar', $this->td);
							++$this->td->zCount;
						}
					}
				}
			?></div><?php
			}
		}
		$this->td->timeStart = $gridTimeStart;
		if ($this->td->zCount > 0) $this->td->validDay = true;
		return ;
	}

	/**
	 * get Calendar Events
	 * 
	 * Returns events from a Google Calendar XML field, between the time and date specified
	 * @version	8th October 2016
	 * @since	8th October 2016
	 * @param	string		$xml
	 * @param	integer		$startDayStamp
	 * @param	integer		$endDayStamp
	 * @return	array		Events
	 */
	function getCalendarEvents($xml, $startDayStamp, $endDayStamp)
	{
		$googleOAuth = $this->view->config->getSettingByScope('System', 'googleOAuth');
	
		if ($googleOAuth == 'Y' && $this->session->notEmpty('googleAPIAccessToken')) {
			$eventsSchool = array();
			$start = date("Y-m-d\TH:i:s", strtotime(date('Y-m-d', $startDayStamp)));
			$end = date("Y-m-d\TH:i:s", (strtotime(date('Y-m-d', $endDayStamp)) + 86399));
	
			require_once GIBBON_ROOT. 'vendor/autoload.php';  //new autoloader..
	
			$client = new \Google_Client();
			$client->setAccessToken($this->session->get('googleAPIAccessToken'));
	
			if ($client->isAccessTokenExpired()) { //Need to refresh the token
				//Get API details
				$googleClientName = $this->view->config->getSettingByScope('System', 'googleClientName');
				$googleClientID = $this->view->config->getSettingByScope('System', 'googleClientID');
				$googleClientSecret = $this->view->config->getSettingByScope('System', 'googleClientSecret');
				$googleRedirectUri = $this->view->config->getSettingByScope('System', 'googleRedirectUri');
				$googleDeveloperKey = $this->view->config->getSettingByScope('System', 'googleDeveloperKey');
	
				//Re-establish $client
				$client->setApplicationName($googleClientName); // Set your application name
				$client->setScopes(array('https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/plus.me', 'https://www.googleapis.com/auth/calendar')); // set scope during user login
				$client->setClientId($googleClientID); // paste the client id which you get from google API Console
				$client->setClientSecret($googleClientSecret); // set the client secret
				$client->setRedirectUri($googleRedirectUri); // paste the redirect URI where you given in APi Console. You will get the Access Token here during login success
				$client->setDeveloperKey($googleDeveloperKey); // Developer key
				$client->setAccessType('offline');
				if ($this->session->isEmpty('googleAPIRefreshToken')) {
					$this->view->displayMessage('Your request failed to authenticate with Google.');
					return false;
				}
				else
				{
					$client->refreshToken($this->session->get('googleAPIRefreshToken'));
					$this->session->set('googleAPIAccessToken', $client->getAccessToken());
				}
			}
	
			$getFail = false;
			$calendarListEntry = array();
			try {
				$service = new \Google_Service_Calendar($client);
				$optParams = array('timeMin' => $start.'+00:00', 'timeMax' => $end.'+00:00', 'singleEvents' => true);
				$calendarListEntry = $service->events->listEvents($xml, $optParams);
			} catch (Exception $e) {
				$getFail = true;
			}
	
			if ($getFail) {
				$eventsSchool = false;
			} else {
				$count = 0;
				foreach ($calendarListEntry as $entry) {
					$multiDay = false;
					if (substr($entry['start']['dateTime'], 0, 10) != substr($entry['end']['dateTime'], 0, 10)) {
						$multiDay = true;
					}
					if ($entry['start']['dateTime'] == '') {
						if ((strtotime($entry['end']['date']) - strtotime($entry['start']['date'])) / (60 * 60 * 24) > 1) {
							$multiDay = true;
						}
					}
	
					if ($multiDay) { //This event spans multiple days
						if ($entry['start']['date'] != $entry['start']['end']) {
							$days = (strtotime($entry['end']['date']) - strtotime($entry['start']['date'])) / (60 * 60 * 24);
						} elseif (substr($entry['start']['dateTime'], 0, 10) != substr($entry['end']['dateTime'], 0, 10)) {
							$days = (strtotime(substr($entry['end']['dateTime'], 0, 10)) - strtotime(substr($entry['start']['dateTime'], 0, 10))) / (60 * 60 * 24);
							++$days; //A hack for events that span multiple days with times set
						}
						for ($i = 0; $i < $days; ++$i) {
							//WHAT
							$eventsSchool[$count][0] = $entry['summary'];
	
							//WHEN - treat events that span multiple days, but have times set, the same as those without time set
							$eventsSchool[$count][1] = 'All Day';
							$eventsSchool[$count][2] = strtotime($entry['start']['date']) + ($i * 60 * 60 * 24);
							$eventsSchool[$count][3] = null;
	
							//WHERE
							$eventsSchool[$count][4] = $entry['location'];
	
							//LINK
							$eventsSchool[$count][5] = $entry['htmlLink'];
	
							++$count;
						}
					} else {  //This event falls on a single day
						//WHAT
						$eventsSchool[$count][0] = $entry['summary'];
	
						//WHEN
						if ($entry['start']['dateTime'] != '') { //Part of day
							$eventsSchool[$count][1] = 'Specified Time';
							$eventsSchool[$count][2] = strtotime(substr($entry['start']['dateTime'], 0, 10).' '.substr($entry['start']['dateTime'], 11, 8));
							$eventsSchool[$count][3] = strtotime(substr($entry['end']['dateTime'], 0, 10).' '.substr($entry['end']['dateTime'], 11, 8));
						} else { //All day
							$eventsSchool[$count][1] = 'All Day';
							$eventsSchool[$count][2] = strtotime($entry['start']['date']);
							$eventsSchool[$count][3] = null;
						}
						//WHERE
						$eventsSchool[$count][4] = $entry['location'];
	
						//LINK
						$eventsSchool[$count][5] = $entry['htmlLink'];
	
						++$count;
					}
				}
			}
		} else {
			$eventsSchool = false;
		}
	
		return $eventsSchool;
	}

	/**
	 * get Space Booking Events
	 *
	 * Returns space bookings for the specified user for the 7 days on/after $startDayStamp, or for all users for the 7 days on/after $startDayStamp if no user specified
	 * @version	8th Ocyober 2016
	 * @since	8th Ocyober 2016
	 * @param	integer		$startDayStamp
	 * @param	integer		$personID
	 * @return	array / false
	 */
	public function getSpaceBookingEvents($startDayStamp, $personID = 0)
	{
		$return = false;

		if (! empty($personID)) {
			$dataSpaceBooking = array('gibbonPersonID1' => $personID, 'gibbonPersonID2' => $personID, 
				'foreignKey'=>'gibbonSpaceID', 'foreignKey2' => 'gibbonLibraryItemID', 'date1' => date('Y-m-d', $startDayStamp), 
				'date2' => date('Y-m-d', ($startDayStamp + (7 * 24 * 60 * 60))), 'date3' => date('Y-m-d', $startDayStamp), 
				'date4' => date('Y-m-d', ($startDayStamp + (7 * 24 * 60 * 60))));
			$sqlSpaceBooking = "(SELECT gibbonTTSpaceBooking.*, name
				FROM gibbonTTSpaceBooking 
					JOIN gibbonSpace ON gibbonTTSpaceBooking.foreignKeyID = gibbonSpace.gibbonSpaceID
					JOIN gibbonPerson ON gibbonTTSpaceBooking.gibbonPersonID = gibbonPerson.gibbonPersonID
				WHERE foreignKey = :foreignKey
					AND gibbonTTSpaceBooking.gibbonPersonID = :gibbonPersonID1 
					AND date >= :date1 
					AND date <= :date2)
				UNION (SELECT gibbonTTSpaceBooking.*, name
					FROM gibbonTTSpaceBooking 
						JOIN gibbonLibraryItem ON gibbonTTSpaceBooking.foreignKeyID = gibbonLibraryItem.gibbonLibraryItemID
						JOIN gibbonPerson ON gibbonTTSpaceBooking.gibbonPersonID = gibbonPerson.gibbonPersonID
					WHERE foreignKey = :foreignKey2
						AND gibbonTTSpaceBooking.gibbonPersonID = :gibbonPersonID2 
						AND date >= :date3
						AND date <= :date4)
				ORDER BY date, timeStart, name";
		} else {
			$dataSpaceBooking = array('gibbonPersonID1' => $personID, 'gibbonPersonID2' => $personID, 
				'foreignKey'=>'gibbonSpaceID', 'foreignKey2' => 'gibbonLibraryItemID', 'date1' => date('Y-m-d', $startDayStamp), 
				'date2' => date('Y-m-d', ($startDayStamp + (7 * 24 * 60 * 60))), 'date3' => date('Y-m-d', $startDayStamp), 
				'date4' => date('Y-m-d', ($startDayStamp + (7 * 24 * 60 * 60))));
			$sqlSpaceBooking = "(SELECT gibbonTTSpaceBooking.*, name, title, surname, preferredName 
				FROM gibbonTTSpaceBooking 
					JOIN gibbonSpace ON gibbonTTSpaceBooking.foreignKeyID = gibbonSpace.gibbonSpaceID
					JOIN gibbonPerson ON gibbonTTSpaceBooking.gibbonPersonID = gibbonPerson.gibbonPersonID
				WHERE foreignKey = :foreignKey 
					AND date >= :date1 
					AND date <= :date2)
				UNION (SELECT gibbonTTSpaceBooking.*, name, title, surname, preferredName 
					FROM gibbonTTSpaceBooking 
						JOIN gibbonLibraryItem ON gibbonTTSpaceBooking.foreignKeyID = gibbonLibraryItem.gibbonLibraryItemID
						JOIN gibbonPerson ON gibbonTTSpaceBooking.gibbonPersonID = gibbonPerson.gibbonPersonID
					WHERE foreignKey = :foreignKey2 
						AND date >= :date3
						AND date <= :date4)
					ORDER BY date, timeStart, name";
		}
		$resultSpaceBooking = $this->view->getRecord('TTSpaceBooking')->findAll($sqlSpaceBooking, $dataSpaceBooking);

		if (count($resultSpaceBooking) > 0) {
			$return = array();
			foreach ($resultSpaceBooking as $w) {
				$rowSpaceBooking = $w->returnRecord();
				$count = $rowSpaceBooking->gibbonTTSpaceBookingID;
				$return[$count][0] = $rowSpaceBooking->gibbonTTSpaceBookingID;
				$return[$count][1] = $rowSpaceBooking->name;
				$return[$count][2] = $rowSpaceBooking->gibbonPersonID;
				$return[$count][3] = $rowSpaceBooking->date;
				$return[$count][4] = $rowSpaceBooking->timeStart;
				$return[$count][5] = $rowSpaceBooking->timeEnd;
				$this->view->getRecord('person');
				$this->view->getRecord('person')->find($rowSpaceBooking->gibbonPersonID);
				$return[$count][6] = $this->view->getRecord('person')->formatName();
			}
		}
	
		return $return;
	}
}