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
 * External Assessment Field Record
 *
 * @version	26th September 2016
 * @since	4th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class externalAssessmentField extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonExternalAssessmentField';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonExternalAssessmentFieldID';
	
	/**
	 * Unique Test
	 *
	 * @version	26th September 2016
	 * @since	4th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$required = array('gibbonExternalAssessmentID', 'name', 'category', 'order', 'gibbonScaleID') ;
		foreach($required as $name)
			if (empty($this->record->$name)) return $this->uniqueFailed('A necessary field was empty.', 'Debug',  $this->table, array($name)) ; ;
		return true ;
	}
	
	/**
	 * Can Delete
	 *
	 * @version	18th July 2016
	 * @since	18th July 2016
	 * @return	boolean
	 */
	public function canDelete()
	{
		return true ;
	}
	
	/**
	 * inject Post
	 *
	 * @version	19th July 2016
	 * @since	19th July 2016
	 * @param	array	$data
	 * @return	boolean
	 */
	public function injectPost($data = null)
	{
		if (empty($_POST['gibbonYearGroupID'])) $_POST['gibbonYearGroupID'] = array();
		$_POST['gibbonYearGroupIDList'] = implode(',', $_POST['gibbonYearGroupID']);
		return parent::injectPost() ;
	}
}
