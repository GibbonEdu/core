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

/**
 * Password Element
 *
 * @version	17th June 2016
 * @since	20th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class password extends text
{
	/**
	 * Constructor
	 *
	 * @version	17th June 2016
	 * @since	20th April 2016
	 * @param	string		$name	Name
	 * @param	mixed		$value	Value
	 * @return 	void
	 */
	public function __construct($name = NULL, $value = NULL)
	{
		$this->createDefaults();
		if ($name !== NULL)
			$this->name = $name;
		if ($value !== NULL)
			$this->value = $value;
		$this->maxlength = NULL;
		$this->element->name = 'password';
		$this->validate = new \stdClass();  // or false
		$this->validate->Presence = true;  // or false
		$this->validate->Format = false;  // or false
		$this->validate->Numericality = false;   // or true
		$this->validate->numberMinimum = NULL;   // or a number
		$this->validate->pattern = "";	// REGEX pattern to match
		$this->validate->formatMessage = "";  // Prompt for format
		$this->validate->onlyOnSubmit = false;  // Prompt for format
		$this->required = false;
		$this->readOnly = false;
		$this->id = '_' . $this->name ;
	}
}
