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
 * School Year Term Record
 *
 * @version	28th September 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class schoolYearTerm extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonSchoolYearTerm';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonSchoolYearTermID';
	
	/**
	 * @var	array	$schoolYearSpecialDays
	 */
	public $schoolYearSpecialDays;
	
	/**
	 * @var	Gibbon\Record\schoolYear	$schoolYear
	 */
	public $schoolYear;
	
	/**
	 * @var	Gibbon\Record\schoolYearSpecialDay	$currentSpecialDay
	 */
	public $currentSpecialDay;
	
	/**
	 * Unique Test
	 *
	 * @version	28th September 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$required = array('name', 'nameShort', 'sequenceNumber', 'firstDay', 'lastDay');
		foreach ($required as $name) 
			if (empty($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		$data=array("sequenceNumber"=>$this->record->sequenceNumber, "schoolYearID"=>$this->record->gibbonSchoolYearID, "schoolYearTermID"=>$this->record->gibbonSchoolYearTermID); 
		$sql="SELECT * 
			FROM `gibbonSchoolYearTerm` 
			WHERE `sequenceNumber` = :sequenceNumber 
				AND `gibbonSchoolYearID` = :schoolYearID
				AND NOT `gibbonSchoolYearTermID` = :schoolYearTermID" ;
		$v = clone $this;
		$roles = $v->findAll($sql, $data);
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', $this->table, array((array)$this->returnRecord())) ;
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
		if (parent::find($id)) {
			$this->getSchoolYearSpecialDays($id);
			$this->schoolYear = new schoolYear($this->view);
			$this->schoolYear->findBy( array('gibbonSchoolYearID'=>$this->record->gibbonSchoolYearID));
			return $this->record;
		}
		return false ;
	}
	
	/**
	 * get School Year Term Special Days
	 *
	 * @version	16th May 2016
	 * @since	16th May 2016
	 * @param	integer		$id	
	 * @return	void
	 */
	public function getSchoolYearSpecialDays($id)
	{
		$sql = 'SELECT `gibbonSchoolYearSpecialDayID` 
			FROM `gibbonSchoolYearSpecialDay` 
			WHERE `gibbonSchoolYearTermID` = ' . intval($id) . ' 
			ORDER BY `date` ASC' ;
		$this->executeQuery(array(), $sql);
		$x = $this->result->fetchAll(\PDO::FETCH_CLASS);
		$this->specialDays = array();
		$this->schoolYearSpecialDays = array();
		foreach($x as $w) 
		{
			$this->schoolYearSpecialDays[$w->gibbonSchoolYearSpecialDayID] = new schoolYearSpecialDay($this->view, $w->gibbonSchoolYearSpecialDayID);
			$this->specialDays[$this->schoolYearSpecialDays[$w->gibbonSchoolYearSpecialDayID]->getField('date')] = $w->gibbonSchoolYearSpecialDayID;
		}
	}
	
	/**
	 * delete School Year Term
	 *
	 * @version	19th May 2016
	 * @since	19th May 2016
	 * @param	integer		$id	
	 * @return	boolean
	 */
	public function deleteRecord($id)
	{
		foreach($this->schoolYearSpecialDays as $id=>$record)
			if (! $record->deleteRecord($id)) return false ;
		return parent::deleteRecord($this->record->gibbonSchoolYearTermID);
	}

	/**
	 * can Delete
	 *
	 * @version	25th May 2016
	 * @since	25th May 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}
	
	/**
	 * get School Year Special Days
	 *
	 * @version	7th September 2016
	 * @since	7th September 2016
	 * @return	timestamp/null
	 */
	public function getSpecialDayStamp()
	{
		if (! empty($this->specialDays))	
			$obj = $this->schoolYearSpecialDays[array_shift($this->specialDays)];
		else
			return null ;
		if (isset($obj) && $obj instanceof schoolYearSpecialDay) 
		{
			list($Year, $Month, $Day) = explode('-', $obj->getField('date'));
			$stamp = mktime(0, 0, 0, $Month, $Day, $Year);
		}
		else
			return null;
		$this->currentSpecialDay = $obj ;
		return $stamp;
	}
}
