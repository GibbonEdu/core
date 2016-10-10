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
 * House Record
 *
 * @version	13th July 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class house extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonHouse';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonHouseID';
	
	/**
	 * @var	boolean	$imageFail
	 */
	public $imageFail = false;
	
	/**
	 * Unique Test
	 *
	 * @version	5th May 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		return true ;
	}

	/**
	 * can Delete
	 *
	 * @version	2nd July 2016
	 * @since	2nd July 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}
	
	/**
	 * inject Post
	 *
	 * @version	13th July 2016
	 * @since	12th May 2016
	 * @return	boolean
	 */
	public function injectPost($data = null)
	{
		$this->imageFail = false ;
		$house = filter_var($_POST['name']);
		$_POST['logo'] = $this->managePhoto('file1', 'logo', $house.'Logo', array(480, 480));
		return parent::injectPost($data);
	}
}