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
namespace Gibbon\Record ;

/**
 * Timetable Record
 *
 * @version	3rd June 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class TT extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonTT';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonTTID';
	
	/**
	 * @var	array	$ttDays	TimeTable Days
	 */
	protected $ttDays;
	
	/**
	 * @var	array	$courseClassPerson	Course Classes
	 */
	protected $courseClassPerson;
	
	/**
	 * @var	integer	$personID
	 */
	protected $personID;
	
	/**
	 * Unique Test
	 *
	 * @version	5th May 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		return false ;
	}

	/**
	 * can Delete
	 *
	 * @version	3rd June 2016
	 * @since	3rd June 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return false;
	}

	/**
	 * get Timetable Days
	 *
	 * @version	3rd June 2016
	 * @since	3rd June 2016
	 * @return	boolean		
	 */
	public function getTTDays()
	{
		if (! empty($this->ttDays))
			return $this->ttDays;
		$sql = 'SELECT `gibbonTTDayID` 
			FROM `gibbonTTDay` 
			WHERE `gibbonTTID` = :ttID' ;
		$this->executeQuery(array('ttID' => intval($this->record->gibbonTTID)), $sql);
		$x = $this->result->fetchAll(\PDO::FETCH_CLASS);
		$this->ttDays = array();
		foreach($x as $w)
			$this->ttDays[intval($w->gibbonTTDayID)] = new ttDay($this->view, $w->gibbonTTDayID);
		return $this->ttDays;
	}

	/**
	 * get Current Personal Timetables
	 *
	 * @version	24th August 2016
	 * @since	24th August 2016
	 * @param	integer		$personID
	 * @return	array
	 */
	public function getCurrentPersonalTimetables($personID)
	{
		//Find out which timetables I am involved in this year
		$this->startQuery()
			->startWhere('gibbonSchoolYearID', $this->session->get('gibbonSchoolYearID'))
			->andWhere('gibbonPersonID', $personID)
			->andWhere('active', 'Y')
			->startJoin('gibbonTTDay','gibbonTTID')
			->addJoin('gibbonTTDayRowClass', 'gibbonTTDayID', 'JOIN', 'gibbonTTDay')
			->addJoin('gibbonCourseClass', 'gibbonCourseClassID', 'JOIN', 'gibbonTTDayRowClass')
			->addJoin('gibbonCourseClassPerson', 'gibbonCourseClassID', 'JOIN', 'gibbonCourseClass')
			->setDistinct()
			->startSelect('gibbonTT.gibbonTTID')
			->addSelect('gibbonTT.name');

		$x = $this->findAllBy($this->getWhere());
		dump(count($x));
		dump($x);
		
		$data = array('personID' => $personID, 'schoolYearID' => $this->session->get('gibbonSchoolYearID'));
		$sql = "SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name 
			FROM gibbonTT 
				JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) 
				JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) 
				JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
				JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
			WHERE gibbonPersonID=:personID 
				AND gibbonSchoolYearID=:schoolYearID 
				AND active='Y' ";
		$x = $this->findAll($sql, $data);
		dump(count($x));
		dump($x, true);
	}

	/**
	 * get Current Personal Timetables
	 *
	 * @version	24th August 2016
	 * @since	24th August 2016
	 * @param	integer		$personID
	 * @return	array
	 */
	public function getCourseClassPerson($personID)
	{
		if ($this->personID == $personID && is_array($this->courseClassPerson) && ! empty($this->courseClassPerson))
			return $this->courseClassPerson ;
		$obj = new courseClassPerson($this->view);
		$obj->startQuery();
		$obj->startWhere('gibbonPersonID', $personID);
		$this->courseClassPerson = $obj->findAll($obj->getWhere());
		if ($obj->getSuccess())
			return $this->courseClassPerson;
		$this->courseClassPerson = array();
		return $this->courseClassPerson;
		
	}
}
