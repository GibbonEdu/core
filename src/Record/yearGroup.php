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
 * Year Group Record
 *
 * @version	25th September 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class yearGroup extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonYearGroup';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonYearGroupID';
	
	/**
	 * Unique Test
	 *
	 * @version	25th September 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', 'yearGroup') ;
        $required = array('name', 'nameShort', 'sequenceNumber');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', 'yearGroup', array($name)) ;
		$data = array('name' => $this->record->name, 'nameShort' => $this->record->nameShort, 'sequenceNumber' => $this->record->sequenceNumber, 'gibbonYearGroupID' => $this->record->gibbonYearGroupID);
		$sql = 'SELECT * 
			FROM `gibbonYearGroup` 
			WHERE (`name` = :name OR `nameShort` = :nameShort OR `sequenceNumber` = :sequenceNumber) 
				AND NOT `gibbonYearGroupID` = :gibbonYearGroupID';
		$v = clone $this;
		$roles = $v->findAll($sql, $data);
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', 'yearGroup', array((array)$this->returnRecord())) ;
		return true ;
	}

	/**
	 * can Delete
	 *
	 * @version	11th July 2016
	 * @since	11th July 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}

	/**
	 * get Year Groups
	 *
	 * @version	15th July 2016
	 * @since	copied from functions.php
	 * @return	array		Sorted Array	
	 */
	public function getYearGroups( )
	{
		$output = false ;
		//Scan through year groups
		//SELECT NORMAL
		$sql="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
		$result = $this->findAll($sql);
		if (is_array($result))
			foreach($result as $row) {
				$output .= $row->getField('gibbonYearGroupID') . "," ;
				$output .= $row->getField('name') . "," ;
			}
	
		if ($output !== false) {
			$output=substr($output,0,(strlen($output)-1)) ;
			$output=explode(",", $output) ;
		}
		return $output ;
	}
	
	/**
	 * get Last year Group ID
	 *
	 * @version	26th July 2016
	 * @since	26th July 2016
	 * @return the last school year in the school, or false if none
	 */
	function getLastYearGroupID()
	{
		$output = false;
		$w = $this->findBy(array(), array('sequenceNumber' => 'DESC'));
		if ($this->getSuccess() && $this->rowCount() > 1) {
			$output = $w->gibbonYearGroupID;
		}
		return $output;
	}
}
