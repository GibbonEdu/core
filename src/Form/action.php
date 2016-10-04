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
use Gibbon\core\session ;

/**
 * Action Element
 *
 * @version	17th September 2016
 * @since	21st April 2016
 * @author	Craig Rayner

 * @package	Gibbon
 * @subpackage	Form
*/
class action extends hidden
{
	/**
	 * view Manager
	 *
	 * @version	17th September 2016
	 * @since	20th April 2016
	 * @param	string		$name
	 * @param	mixed		$value
	 * @param	Gibbon\view	$view
	 * @return 	void
	 */
	public function __construct($name = null, $value = null, view $view)
	{
		parent::__construct($name, $value, $view);
		$this->name = 'action';
		$this->value = str_replace('//', '/', GIBBON_ROOT . 'src/' . str_replace(array('//', '\\', 'src/', GIBBON_ROOT), array('/', '/', '', ''), $name));
		$this->id = '_action';
	}
}
