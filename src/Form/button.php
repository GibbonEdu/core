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
 * @version	17th September 2016
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
	 * @version	17th September 2016
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
		$this->row = new \stdClass();
		$this->col1 = new \stdClass();
		$this->col2 = new \stdClass();
		$this->span = new \stdClass();
		$this->validate = false;  // or false
		$this->required = false;
		$this->readOnly = false;
		$this->setID();
		$this->element->name = $this->element->type = 'button';
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
	}
}
