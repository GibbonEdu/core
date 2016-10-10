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
 * Role Record
 *
 * @version	22nd July 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class role extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonRole';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonRoleID';
	
	/**
	 * Unique Test
	 *
	 * @version	22nd July 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', 'Role') ;
        $required = array('category','name','nameShort','description','futureYearsLogin','pastYearsLogin');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', 'Role', array($name)) ;
		$data = array('name' => $this->record->name, 'nameShort' => $this->record->nameShort, 'gibbonRoleID' => $this->record->gibbonRoleID);
		$sql = 'SELECT * 
			FROM `gibbonRole` 
			WHERE (`name` = :name OR `nameShort` = :nameShort) 
				AND NOT `gibbonRoleID` = :gibbonRoleID';
		$v = clone $this;
		$roles = $v->findAll($sql, $data);
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', 'Role', array((array)$this->returnRecord())) ;
		return true ;
	}

	/**
	 * can Delete
	 *
	 * @version	19th June 2016
	 * @since	19th June 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}

	/**
	 * default Record
	 *
	 * @version	22nd July 2016
	 * @since	22nd July 2016
	 * @return	void
	 */
	public function defaultRecord()
	{
		parent::defaultRecord();
		$this->record->type = 'additional';
		$this->record->category = '';
	}

	/**
	 * return Duplicate Record
	 *
	 * @version	22nd July 2016
	 * @since	22nd July 2016
	 * @return	void
	 */
	public function returnDuplicateRecord()
	{
		$record = clone $this->record;
		$record->description = '';
		$record->name = '';
		$record->nameShort = '';
		return $record ;
		
	}
}
