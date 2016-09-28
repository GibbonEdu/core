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
 * @package		Gibbon
 * @subpackage	Record
 * @author	Craig Rayner
*/
/**
 */
namespace Gibbon\Record ;

use Gibbon\People\staff ;
/**
 * Space Record
 *
 * @version	28th September 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 */
class space extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonSpace';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonSpaceID';
	
	/**
	 * @var	array	$people	 List of linked Staff
	 */
	protected $people = array();
	
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
        $required = array('name','type','computer','projector','tv','dvd','hifi','speakers','iwb');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		if (! is_numeric($this->record->computerStudent))
				return $this->uniqueFailed('A necessary field is not the valid type.', 'Debug', $this->table, array('computer' => $this->record->computer)) ;
		if (! is_numeric($this->record->capacity))
				return $this->uniqueFailed('A necessary field is not the valid type.', 'Debug', $this->table, array('computer' => $this->record->computer)) ;
		$data = array('name' => $this->record->name, 'gibbonSpaceID' => $this->record->gibbonSpaceID);
		$sql = 'SELECT * 
			FROM `gibbonSpace` 
			WHERE `name` = :name 
				AND NOT `gibbonSpaceID` = :gibbonSpaceID';
		$v = clone $this;
		$roles = $v->findAll($sql, $data);
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', $this->table, array((array)$this->returnRecord())) ;
		return true ;
	}
	
	/**
	 * get People
	 *
	 * @version	28th September 2016
	 * @since	24th May 2016
	 * @return	boolean
	 */
	public function getStaff()
	{
		if (! empty($this->people)) return $this->people ;
		$data = array('gibbonSpaceID' => $this->record->gibbonSpaceID);
		$sql = "SELECT `surname`, `preferredName` 
			FROM `gibbonPerson` 
				JOIN `gibbonSpace` ON (`gibbonPerson`.`gibbonPersonID` = `gibbonSpace`.`gibbonPersonID1` OR `gibbonPerson`.`gibbonPersonID` = `gibbonSpace`.`gibbonPersonID2`) 
			WHERE `gibbonSpaceID`=:gibbonSpaceID
				AND `status` = 'Full' 
		ORDER BY `surname`, `preferredName`";
		$pObj = new staff($this->view);
		$this->people = $pObj->findAll($sql, $data, '_');
		unset($pObj);
		if (! is_array($this->people)) $this->people = array();
		return $this->people ;
	}

	/**
	 * Default Record
	 *
	 * @version	24th May 2016
	 * @since	24th May 2016
	 * @return	stdClass	Record
	 */
	public function defaultRecord()
	{
  		parent::defaultRecord();
		$this->record->computer = 'N';
		$this->record->projector = 'N';
		$this->record->tv = 'N';
		$this->record->dvd = 'N';
		$this->record->speakers = 'N';
		$this->record->iwb = 'N';
		$this->record->hifi = 'N';
		return $this->record ;
	}

	/**
	 * inject Post
	 *
	 * @version	24th May 2016
	 * @since	24th May 2016
	 * @return	boolean
	 */
	public function injectPost($data = null)
	{
		if (empty($_POST['gibbonPersonID1'])) $_POST['gibbonPersonID1'] = 'NULL';
		$gibbonPersonID2 = $_POST['gibbonPersonID2'];
		if (empty($_POST['gibbonPersonID2'])) $_POST['gibbonPersonID2'] = 'NULL';
		$_POST['phoneExternal'] = preg_replace('/[^0-9+]/', '', $_POST['phoneExternal']);

		return parent::injectPost();
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
}
