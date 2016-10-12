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

use Gibbon\security ;
use Gibbon\helper ;
use Gibbon\trans ;

/**
 * Course Record
 *
 * @version	11th May 2016
 * @since	2nd May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class course extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonCourse';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonCourseID';
	
	/**
	 * @var	object	$schoolYear School Year
	 */
	public $schoolYear;
	
	/**
	 * @var	object	$department	Department
	 */
	public $department;
	
	/**
	 * @var	array	$courseClasses	Course Class List
	 */
	public $courseClasses;
	
	/**
	 * @var	array	$units	Units
	 */
	public $units;
	
	/**
	 * Unique Test
	 *
	 * @version	2nd May 2016
	 * @since	2nd May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		return false ;
	}
	
	/**
	 * can Delete
	 *
	 * @version	2nd May 2016
	 * @since	2nd May 2016
	 * @return	boolean
	 */
	public function canDelete()
	{
		return false ;
	}
	
	/**
	 * get Record
	 *
	 * @version	11th May 2016
	 * @since	5th May 2016
	 * @param	integer		$id	
	 * @return	mixed	false or Record Object
	 */
	public function find($id)
	{
		if (parent::find($id)) {
			$this->schoolYear = new schoolYear($this->view, $this->record->gibbonSchoolYearID);
			$this->department = new department($this->view, $this->record->gibbonDepartmentID);
			$this->getCourseClasses($id);
			$this->getUnits($id);
			return $this->record;
		}
		return false ;
	}
	
	/**
	 * get Classes
	 *
	 * @version	5th May 2016
	 * @since	5th May 2016
	 * @param	integer		$id	Course ID
	 * @return	void
	 */
	public function getCourseClasses($id)
	{
		$sql = 'SELECT `gibbonCourseClassID` FROM `gibbonCourseClass` WHERE `gibbonCourseID` = ' . intval($id) . " ORDER BY `name`" ;
		$this->executeQuery(array(), $sql);
		$x = $this->result->fetchAll(\PDO::FETCH_CLASS);
		$this->courseClasses = array();
		foreach($x as $w)
			$this->courseClasses[$w->gibbonCourseClassID] = new courseClass($this->view, $w->gibbonCourseClassID);
	}
	
	/**
	 * get Units
	 *
	 * @version	5th May 2016
	 * @since	5th May 2016
	 * @param	integer		$id	Course ID
	 * @return	void
	 */
	public function getUnits($id)
	{
		$sql = 'SELECT `gibbonUnitID` FROM `gibbonUnit` WHERE `gibbonCourseID` = ' . intval($id) ;
		$this->executeQuery(array(), $sql);
		$x = $this->result->fetchAll(\PDO::FETCH_CLASS);
		$this->units = array();
		foreach($x as $w)
			$this->units[$w->gibbonUnitID] = new unit($this->view, $w->gibbonUnitID);
	}
	
	/**
	 * get Class
	 *
	 * @version	5th May 2016
	 * @since	5th May 2016
	 * @param	integer		$id	CourseClassID
	 * @return	mixed		false or courseClass Ojbect
	 */
	public function getClass($id)
	{
		if (isset($this->classes[$id]))
			return $this->classes[$id];
		return false ;
	}
	
	/**
	 * get Unit
	 *
	 * @version	5th May 2016
	 * @since	5th May 2016
	 * @param	integer		$id	UnitID
	 * @return	mixed		false or unit Ojbect
	 */
	public function getUnit($id)
	{
		if (isset($this->units[$id]))
			return $this->units[$id];
		return false ;
	}
}
