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
 * Alert Level Record
 *
 * @version	7th September 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class alertLevel extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonAlertLevel';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonAlertLevelID';
	
	/**
	 * @var	array	$alerts
	 */
	protected $alerts = array();
	
	/**
	 * Unique Test
	 *
	 * @version	7th September 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
        $required = array('color','name','nameShort','colorBG','sequenceNumber');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
		$data = array('name' => $this->record->name, 'nameShort' => $this->record->nameShort, 'alertLevelID' => $this->record->gibbonAlertLevelID, 'sequenceNumber' => $this->record->sequenceNumber);
		$sql = 'SELECT * 
			FROM `gibbonAlertLevel` 
			WHERE (`name` = :name OR `nameShort` = :nameShort OR `sequenceNumber` = :sequenceNumber) 
				AND NOT `gibbonAlertLevelID` = :alertLevelID';
		$v = clone $this;
		$roles = $v->findAll($sql, $data);
		if (count($roles) > 0) return $this->uniqueFailed('Field values did not meet the requirements for uniqueness!', 'Debug', $this->table, array((array)$this->returnRecord())) ; 
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
	 * get Alert
	 *
	 * @version	15th August 2016
	 * @since	copied from functions.php
	 * @param	integer		$alertLevelID	Gibbon Alert ID
	 * @return	mixed		array || false
	 */			
	public function getAlert($alertLevelID)
	{
		$output = false ;
		if (isset($this->alerts[$alertLevelID]))
			return $this->alerts[$alertLevelID];
		if ($row = $this->find($alertLevelID)) {
			$output=array() ;
			$output["name"] 			= $this->view->__($row->name) ;
			$output["nameShort"]		= $row->nameShort ;
			$output["colour"]			= $row->color ;
			$output["colourBG"]			= $row->colorBG ;
			$output["color"]			= $row->color ;
			$output["colorBG"]			= $row->colorBG ;
			$output["description"]		= $this->view->__($row->description) ;
			$output["sequenceNumber"]	= $row->sequenceNumber ;
			$this->alerts[$alertLevelID] = $output ;
		}
		return $output ;
	}
	
	/**
	 * inject Post
	 *
	 * @version	7th September 2016
	 * @since	7th September 2016
	 * @return	boolean
	 */
	public function injectPost($data = null)
	{
		$data['colour'] = isset($data['colour']) ? strtoupper($data['colour']) : '';
		$data['colourBG'] = isset($data['colourBG']) ? strtoupper($data['colourBG']) : '';
		return parent::injectPost($data);
	}
}
