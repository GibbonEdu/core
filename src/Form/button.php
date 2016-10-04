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
 * button Element
 *
 * @version	3rd October 2016
 * @since	27th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Form
*/
class button extends element
{
	/**
	 * Constructor
	 *
	 * @version	3rd October 2016
	 * @since	27th April 2016
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
		$this->validate = false;  // or false
		$this->required = false;
		$this->readOnly = false;
		$this->setID();
		$this->colour = 'grey';
		$this->element->name = 'button';
		$this->element->type = 'button';
	}
	
	/**
	 * On Change Submit
	 *
	 * @version	16th September 2016
	 * @since	16th September 2016
	 * @return 	void
	 */
	public function onClickSubmit()
	{
		$this->additional .= ' onClick="this.form.submit()"';
		$this->element->type = 'submit' ;
	}
	
	/**
	 * On Change Reset
	 *
	 * @version	16th September 2016
	 * @since	16th September 2016
	 * @return 	void
	 */
	public function onClickReset()
	{
		$this->additional .= ' onClick="this.form.reset()"';
		$this->element->type = 'reset' ;
	}
	
	/**
	 * set Button Colour
	 *
	 * @version	3rd October 2016
	 * @since	3rd October 2016
	 * @return 	void
	 */
	public function setButtonColour($colour)
	{
		$this->colour = 'grey';
		if (in_array($colour, array('red', 'green', 'blue', 'aqua', 'grey', 'orange')))
			$this->colour = $colour ;
	}
}
