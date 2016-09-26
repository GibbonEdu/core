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
namespace Gibbon\Form;

use Gibbon\core\view ;

/**
 * Notice Element
 *
 * @version	25th September 2016
 * @since	15th June 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class notice extends element
{
	/**
	 * Constructor
	 *
	 * @version	25th September 2016
	 * @since	15th June 2016
	 * @param	string		$name
	 * @param	mixed		$value
	 * @param	Gibbon\core\view	$view
	 * @return 	void
	 */
	public function __construct($name = null, $value = null, view $view)
	{
		parent::__construct($name, $value, $view);
		$this->name = $name ;
		$this->value = $value ;
		$this->element->name = 'notice';
		$this->title = null;
		$this->titleParameters = array();
		$this->valueParameters = array();
	}
}
