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
 * Colour Element
 *
 * @version	29th September 2016
 * @since	20th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class colour extends element
{
	/**
	 * Constructor
	 *
	 * @version	29th September 2016
	 * @since	20th April 2016
	 * @param	string		$name
	 * @param	mixed		$value
	 * @param	Gibbon\core\view	$view
	 * @return 	void
	 */
	public function __construct($name = null, $value = null, view $view)
	{
		parent::__construct($name, $value, $view);
		if ($name !== null)
			$this->name = $name;
		if ($value !== null)
			$this->value = $value;
		$this->element->name = "colour";
		$this->setID();
		$this->setColour();
	}
	/**
	 * Set Colour
	 *
	 * @version	9th July 2016
	 * @since	9th July 2016
	 */
	public function setColour($message = 'Colour must be 3 or 6 characters long. Only these characters accepted: 0-9, a-f or A-F')
	{
		$this->setFormat("^([0-9a-fA-F]{6})$|^([0-9a-fA-F]{3})$", $message);
	}
}
