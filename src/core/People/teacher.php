<?php
/**
 * Teacher Record
 *
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
 * @version	13th August 2016
 * @since	13th August 2016
 * @author	Craig Rayner
 * @package	Gibbon
*/
/**
 */
namespace Gibbon\People ;

use Gibbon\core\view ;
use Gibbon\People\employee ;

/**
 * Person Record Class
 *
 * @version	13th August 2016
 * @since	13th August 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class teacher extends employee
{
	/**
	 * Constructor
	 *
	 * @version	13th August 2016
	 * @since	3th August 2016
	 * @param	view		$view
	 * @param	integer		$id 
	 * @return	void
	 */
	public function __construct(view $view, $id = 0)
	{
		parent::__construct($view, $id);
		$this->validTeacher = false ;
		if ($this->getSuccess() && $id > 0 && $this->record->gibbonPersonID == $id)
			$this->validTeacher = true;
	}
}
