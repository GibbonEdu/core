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

use Gibbon\core\view ;

/**
 * String Replacement
 *
 * Unable to use string as a name as it is a reserved php word.
 * @version	18th September 2016
 * @since	2nd May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class stringReplacement extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonString';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonStringID';
	
	/**
	 * Default Record
	 *
	 * @version	18th September 2016
	 * @since	2nd May 2016
	 * @return	stdClass	Record
	 */
	public function defaultRecord()
	{
  		$this->record = new \stdClass();
		$this->record->gibbonStringID = NULL;
		$this->record->original = '';
		$this->record->replacement = '';
		$this->record->mode = 'Whole';
		$this->record->caseSensitive = 'N';
		$this->record->priority = 0;
		return $this->record ;
	}
	
	/**
	 * Unique Test
	 *
	 * @version	11th September 2016
	 * @since	2nd May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', 'String Replacement') ;
        $required = array('original','replacement','mode','caseSensitive');
		foreach ($required as $name) 
			if (! isset($this->record->$name))
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', 'String Replacement', array($name)) ;
		return true ; 
	}

	/**
	 * can Delete
	 *
	 * @version	29th June 2016
	 * @since	29th June 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}
}
