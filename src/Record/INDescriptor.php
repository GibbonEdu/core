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
 * In Descriptor Record
 *
 * @version	9th July 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class INDescriptor extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonINDescriptor';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonINDescriptorID';
	
	/**
	 * Unique Test
	 *
	 * @version	21st May 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
        $required = array('name','nameShort','sequenceNumber');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		$data = array('name' => $this->record->name, 'nameShort' => $this->record->nameShort, 'sequenceNumber' => $this->record->sequenceNumber, $this->getIdentifierName() => $this->getIdentifier());
		$sql = 'SELECT * 
			FROM `gibbonINDescriptor` 
				WHERE (`name`=:name OR `nameShort` = :nameShort OR `sequenceNumber` = :sequenceNumber) 
					AND NOT `gibbonINDescriptorID` = :gibbonINDescriptorID';
		$v = clone $this;
		$roles = $v->findAll($sql, $data);
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', $this->table, array((array)$this->returnRecord())) ;
		return true ;
	}

	/**
	 * can Delete
	 *
	 * @version	9th July 2016
	 * @since	9th July 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}
}
