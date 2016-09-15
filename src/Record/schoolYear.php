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

use Gibbon\core\session ;
use Gibbon\core\module as helper ;
use Gibbon\core\trans ;


/**
 * Unit Record
 *
 * @version	25th August 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage                     Record
 */
class schoolYear extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonSchoolYear';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonSchoolYearID';
	
	/**
	 * @var	array	$schoolYearTerms
	 */
	public $schoolYearTerms;
	
	/**
	 * @var	array	$rollGroups
	 */
	public $rollGroups;
	
	/**
	 * @var	array	$nextRollGroups
	 */
	public $nextRollGroups;
	
	/**
	 * @var	integer		Previous School Year ID
	 */
	public $previousSchoolYearID;
	
	/**
	 * @var	integer		Next School Year ID
	 */
	public $nextSchoolYearID;
	
	/**
	 * Unique Test
	 *
	 * @version	26th July 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$required = array('name', 'status', 'sequenceNumber', 'firstDay', 'lastDay');
		foreach ($required as $name) 
			if (empty($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		$sql = "SELECT `".$this->identifier."` 
			FROM `" . $this->table ."` 
			WHERE (`name` = '".$this->record->name."' OR `sequenceNumber` = '".$this->record->sequenceNumber."')
				AND `".$this->identifier."` != " .  intval($this->record->gibbonSchoolYearID);
		$this->executeQuery(array(), $sql);
		if ($this->rowCount > 0)
			return $this->uniqueFailed('Some fields failed to meet the requirements for uniqueness!', 'Debug', $this->table) ;
		if ($this->record->status != 'Current')
			return true;
		$sql = "SELECT `".$this->identifier."`
			FROM `" . $this->table ."` 
			WHERE `status` = 'Current'
				AND `".$this->identifier."` != " .  intval($this->record->gibbonSchoolYearID);
		$this->executeQuery(array(), $sql);
		if ($this->rowCount > 0)
			return $this->uniqueFailed('Only one school year can have a status of current!', 'Debug', $this->table) ;
		return true ;
	}
	
	/**
	 * find
	 *
	 * @version	16th May 2016
	 * @since	16th May 2016
	 * @param	integer		$id	
	 * @return	mixed	false or Record Object
	 */
	public function find($id)
	{
		if (parent::find($id) !== false) {
			$result = $this->result ;
			$this->schoolYearTerms = array();
			$this->getSchoolYearTerms($id);
			$this->result = $result;
			$this->rowCount();
			return $this->record;
		}
		return false ;
	}
	
	/**
	 * get School Year Terms
	 *
	 * @version	8th September 2016
	 * @since	16th May 2016
	 * @param	integer		$id	
	 * @return	void
	 */
	public function getSchoolYearTerms($id)
	{
		if (! empty($this->schoolYearTerms) && $id == $this->record->gibbonSchoolYearID)
			return $this->schoolYearTerms ;
		$sql = 'SELECT `gibbonSchoolYearTermID` 
			FROM `gibbonSchoolYearTerm` 
			WHERE `gibbonSchoolYearID` = :schoolYearID
			ORDER BY `sequenceNumber`'  ;
		$data = array('schoolYearID' => $id);
		$this->executeQuery($data, $sql);
		$x = $this->result->fetchAll(\PDO::FETCH_CLASS);
		$this->schoolYearTerms = array();
		foreach($x as $w)
			$this->schoolYearTerms[$w->gibbonSchoolYearTermID] = new schoolYearTerm($this->view, $w->gibbonSchoolYearTermID);
	}
	
	/**
	 * delete School Year
	 *
	 * @version	19th May 2016
	 * @since	19th May 2016
	 * @param	integer		$id	
	 * @return	void
	 */
	public function deleteRecord($id)
	{
		foreach($this->schoolYearTerms as $id=>$record)
			if (! $record->deleteRecord($id)) return false ;
		return parent::deleteRecord($this->record->gibbonSchoolYearID);
	}

	/**
	 * get Previous School Year ID
	 *
	 * Take a school year, and return the previous one, or false if none
	 * @version	9th September 2016
	 * @since	copied from functions.php
	 * @param	integer		$yearID
	 * @return	mixed		School Year ID or false	
	 */
	public function getPreviousSchoolYearID($yearID = null)
	{
		if (! empty($this->previousSchoolYearID) && is_null($yearID))
			return $this->previousSchoolYearID;
		if (! empty($this->previousYear) && is_null($yearID))
			return $this->previousSchoolYearID = $this->previousYear->getField('gibbonSchoolYearID');
		$output = false ;
	
		$yearID = is_null($yearID) ? $this->record->gibbonSchoolYearID : $yearID;
		$v = clone $this ;
		$w = $v->find($yearID);	
		$data = array("sequenceNumber" => $w->sequenceNumber);
		$sql = "SELECT `gibbonSchoolYearID` 
			FROM `gibbonSchoolYear` 
			WHERE `sequenceNumber` < :sequenceNumber 
			ORDER BY `sequenceNumber` DESC 
			LIMIT 1" ;
		$w = $v->findAll($sql, $data);
		if ($v->getSuccess() && count($w) == 1) 
		{
			$this->previousYear = reset($w);
			$output = $this->previousYear->getField('gibbonSchoolYearID');
		}
		else
			$this->previousYear = false;	
		
		return $this->previousSchoolYearID = $output ;
	}
	
	/**
	 * get Next School Year ID
	 *
	 * Take a school year, and return the next one, or false if none
	 * @version	9th September 2016
	 * @since	copied from functions.php
	 * @param	integer		$yearID
	 * @return	mixed		School Year ID or false	
	 */
	public function getNextSchoolYearID($yearID = null)
	{
		if (! empty($this->nextSchoolYearID) && is_null($yearID))
			return $this->nextSchoolYearID;
		if (! empty($this->nextYear) && is_null($yearID))
			return $this->nextSchoolYearID = $this->nextYear->getField('gibbonSchoolYearID');
		$output = false ;
		
		$yearID = is_null($yearID) ? $this->record->gibbonSchoolYearID : $yearID;
		$v = clone $this ;
		$w = $v->find($yearID);	
		$data = array("sequenceNumber" => $w->sequenceNumber);
		$sql = "SELECT * 
			FROM `gibbonSchoolYear` 
			WHERE `sequenceNumber` > :sequenceNumber 
			ORDER BY `sequenceNumber` ASC 
			LIMIT 1" ;
		$w = $v->findAll($sql, $data);

		if ($v->getSuccess() && count($w) == 1) 
		{
			$this->nextYear = reset($w);
			$output = $this->nextYear->getField('gibbonSchoolYearID');
		}
		else
			$this->nextYear = false;	

 		return $this->nextSchoolYearID = $output ;
	}

	/**
	 * Inject Post
	 *
	 * @version	7th September 2016
	 * @since	22nd May 2016
	 * @return	void
	 */
	public function injectPost($data = null)
	{
		return parent::injectPost($data);
	}
	
	/**
	 * get Roll Groups
	 *
	 * @version	8th September 2016
	 * @since	24th May 2016
	 * @return	array		Gibbon\Record\rollGroup
	 */
	public function getRollGroups()
	{
		if (! empty($this->rollGroups))
			return $this->rollGroups ;
		$sql = 'SELECT `gibbonRollGroupID` 
			FROM `gibbonRollGroup` 
			WHERE `gibbonSchoolYearID` = :schoolYearID  
			ORDER BY `name`';
		$v = clone $this;
		$x = $v->findAll($sql, array('schoolYearID'=>$this->record->gibbonSchoolYearID));
		$this->rollGroups = array();
		foreach($x as $q)
		{
			$w = $q->returnRecord();
			$this->rollGroups[intval($w->gibbonRollGroupID)] = new rollGroup($this->view, $w->gibbonRollGroupID);
		}
		return $this->rollGroups;
	}

	/**
	 * get Roll Group
	 *
	 * @version	25th May 2016
	 * @since	24th May 2016
	 * @param	integer		$id		Roll Group ID
	 * @return	Gibbon\Record\rollGroup
	 */
	public function getRollGroup($id)
	{
		$this->getRollGroups();
		if (isset($this->rollGroups[intval($id)]))
			return $this->rollGroups[intval($id)];
		
		if ($id === 'Add')
		{
			$rg = new rollGroup($this->view);
			$rg->setField('gibbonSchoolYearID', $this->record->gibbonSchoolYearID);
			return $rg ;
		}
		
		return false ;
	}

	/**
	 * can Delete
	 *
	 * @version	25th August 2016
	 * @since	25th May 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		if ($this->record->status == 'Current')
			return false ;
		$w = $this->getRollGroups();
		if (! empty($w))
			return false ;
		return true;
	}
	
	/**
	 * get Next Roll Groups
	 *
	 * @version	8th September 2016
	 * @since	25th May 2016
	 * @return	array		Gibbon\Record\rollGroup
	 */
	public function getNextRollGroups()
	{
		if (! empty($this->nextRollGroups))
			return $this->nextRollGroups ;
		$sql = 'SELECT `gibbonRollGroupID` 
			FROM `gibbonRollGroup` 
			WHERE `gibbonSchoolYearID` = :schoolYearID  
			ORDER BY `name`';
		$v = clone $this ;
		$x = $v->findAll($sql, array('schoolYearID'=>$this->getNextSchoolYearID()));
		$this->nextRollGroups = array();
		foreach($x as $q)
		{
			$w = $q->returnRecord();
			$this->nextRollGroups[intval($w->gibbonRollGroupID)] = new rollGroup($this->view, $w->gibbonRollGroupID);
		}
		return $this->nextRollGroups;
	}

	/**
	 * set Current School Year
	 *
	 * GET THE CURRENT YEAR AND SET IT AS A GLOBAL VARIABLE
	 * @version	27th July 2016
	 * @since	21st April 2016
	 * @return	void
	 */
	public function setCurrentSchoolYear() {
		
		$v = clone $this ;
		$year = $v->findOneBy(array('status' => 'Current'));
		
		//Check number of rows returned.
		//If it is not 1, show error
		if (! $v->getSuccess())  // find One By does all this test for us.
			die(\Gibbon\trans::__("Your request failed due to a database error.")) ;
		else {
			//Else get schoolYearID
			$this->session->set("gibbonSchoolYearID", $year->gibbonSchoolYearID) ;
			$this->session->set("gibbonSchoolYearName", $year->name) ;
			$this->session->set("gibbonSchoolYearSequenceNumber", $year->sequenceNumber) ;
			$this->session->set("gibbonSchoolYearFirstDay", $year->firstDay) ;
			$this->session->set("gibbonSchoolYearLastDay", $year->lastDay) ;
		}
	}

	/**
	 * is School Open
	 *
	 * Checks to see if a specified date (YYYY-MM-DD) is a day where school is open in the current academic year. There is an option to search all years
	 * @version	8th September 2016
	 * @since	21st April 2016
	 * @param	string		$date Date
	 * @param	boolean		$allYears allYears
	 * @return	mixed		Module ID or false
	 */
	public function isSchoolOpen($date, $allYears = false ) {
		
		//Set test variables
		$isInTerm = false ;
		$isSchoolDay = false ;
		$isSchoolOpen = false ;
		$session = new session();
		//Turn $date into UNIX timestamp and extract day of week
		$timestamp = helper::dateConvertToTimestamp($date) ;
		$dayOfWeek = date("D",$timestamp) ;
	
		//See if date falls into a school term
		$data = array();
		$sqlWhere = "" ;
		if (! $allYears) {
			$data[$session->get("gibbonSchoolYearID")] = $session->get("gibbonSchoolYearID") ;
			$sqlWhere = " AND gibbonSchoolYear.gibbonSchoolYearID=:" . $session->get("gibbonSchoolYearID") ;
		}

		$sql = "SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay
			FROM gibbonSchoolYearTerm, gibbonSchoolYear 
			WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID $sqlWhere" ;
		$result = $this->executeQuery($data, $sql . $sqlWhere);
		while ($row = $result->fetch()) {
			if ($date>=$row["firstDay"] AND $date<=$row["lastDay"]) {
				$isInTerm = true ;
			}
		}
	
		//See if date's day of week is a school day
		if ($isInTerm) {
			$data = array("nameShort"=>$dayOfWeek);
			$sql = "SELECT * FROM gibbonDaysOfWeek WHERE nameShort=:nameShort AND schoolDay='Y'" ;
			$result = $this->executeQuery($data, $sql);
			if ($result->rowCount()>0) {
				$isSchoolDay = true ;
			}
		}
	
		//See if there is a special day
		if ($isInTerm && $isSchoolDay) {
			$data = array("date"=>$date);
			$sql = "SELECT * FROM gibbonSchoolYearSpecialDay WHERE type='School Closure' AND date=:date" ;
			$result = $this->executeQuery($data, $sql);
			if ($result->rowCount()<1) {
				$isSchoolOpen = true ;
			}
		}
	
		return $isSchoolOpen ;
	}

	/**
	 * get Terms
	 *
	 * Gets terms in the specified school year
	 * @version	8th September 2016
	 * @since	copied from functions.php
	 * @param	integer		$schoolYearID School Year ID
	 * @param	boolean		$short Use Short Name
	 * @return	array/false
	 */
	public static function getTerms($schoolYearID, $short = false)
	{

		$output = false ;
		//Scan through year groups
		$rows = $this->getSchoolYearTerms($schoolYearID);
	
		foreach ($rows as $row)
		{
			$output .= $row->getID() . "," ;
			if ($short) {
				$output .= $row->getField('nameShort') . "," ;
			}
			else {
				$output .= $row->getField('name') . "," ;
			}
		}
		if ($output !== false) {
			$output = trim($output, ',') ;
			$output = explode(",", $output) ;
		}
		return $output ;
	}
}
