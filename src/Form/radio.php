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
 * Radio Element
 *
 * @version	18th September 2016
 * @since	17th May 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class radio extends select
{
	/**
	 * Constructor
	 *
	 * @version	18th September 2016
	 * @since	17th May 2016
	 * @param	string		$name
	 * @param	mixed		$value
	 * @param	Gibbon\core\view	$view
	 * @return 	void
	 */
	public function __construct($name = null, $value = null, view $view)
	{
		parent::__construct($name, $value, $view);
		if ($name !== NULL) $this->name = $name;
		if ($value !== NULL) $this->value = $value ;
		$this->hideDisplay = false ;
		$this->required = false;
		$this->element->name = 'radio';
		$this->checked = false ;
	}

	/**
	 * add Option
	 *
	 * @version	9th August 2016
	 * @since	9th August 2016
	 * @param	mised	$display Value
	 * @param	mixed	$value
	 * @param	string	$class	Option Class
	 * @return  stdClass
	 */
	public function addOption($display, $value = null, $class = null)
	{
		$option = parent::addOption($display, $value, $class);
		$option->id = '_' . $this->name . '_' . $value;
		return $option ;
	}

	/**
	 * set Checked
	 *
	 * @version	18th September 2016
	 * @since	18th September 2016
	 * @return  void
	 */
	public function setChecked()
	{
		$this->checked = true ;
	}
}
