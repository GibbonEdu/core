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
namespace Gibbon\core\functions ;

/**
 * String Functions
 *
 * @version	18th September 2016
 * @since	18th September 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Trait
 */
trait stringFunctions
{
	/**
	 * html Preparation
	 *
	 * Encode string using htmlentities with the ENT_QUOTES option
	 * @version	17th September 2016
	 * @since	24th April 2016
	 * @param	string		$str 	String to Prepare
	 * @return	string	Prepared String
	 */
	public function htmlPrep($str) {
		return htmlentities($str, ENT_QUOTES, "UTF-8") ;
	}

	/**
	 * sanitise Anchor
	 *
	 * @version	18th September 2016
	 * @since	6th July 2016
	 * @params	string		$dirty
	 * @return	string		Clean
	 */
	public function sanitiseAnchor($dirty)
	{
		return str_replace(array(' ', '.'), '', filter_var($dirty, FILTER_SANITIZE_STRING));
	}
}
