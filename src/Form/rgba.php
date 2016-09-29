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
 * RGBA Element
 *
 * @version	29th September 2016
 * @since	11th July 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class rgba extends element
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
		if ($name !== NULL)
			$this->name = $name;
		if ($value !== NULL)
			$this->value = $value;
		$this->element->name = "rgba";
		$this->setID();
		$this->setRGBA();
	}
	/**
	 * Set Colour
	 *
	 * @version	11th July 2016
	 * @since	11th July 2016
	 */
	public function setRGBA($message = 'Colour Format: 0-255,0-255,0-255,0.00-1 (e.g. 100,100,100,0,5 ) No spaces.')
	{
		$this->setFormat("^([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]),([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]),([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]),(1|0\.([0-9]{1,2}))$", $message);
		$this->setMaxLength(16);
	}
}
