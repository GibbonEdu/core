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

use Gibbon\core\trans ;
use Gibbon\core\helper ;

/**
 * Select Element
 *
 * @version	5th August 2016
 * @since	20th April 2016
 * @author	Craig Rayner

 * @package	Gibbon
 * @subpackage	Form
*/
class select extends element
{
	/**
	 * Constructor
	 *
	 * @version	23rd April 2016
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
		else 
			$this->value = NULL;
		$this->element = new \stdClass();
		$this->element->name = 'select';
		$this->id = $this->setID() ;
		$this->options = array();
		$this->additional = '';
	}

	/**
	 * add Option
	 *
	 * @version	5th August 2016
	 * @since	20th April 2016
	 * @param	mised	$display Value
	 * @param	mixed	$value
	 * @param	string	$class	Option Class
	 * @return  stdClass
	 */
	public function addOption($display, $value = null, $class = null)
	{
		if (empty($this->options)) $this->options = array();
		if (is_null($value)) $value = $display ;
		if ($value == 'Please select...' && ! empty($this->options[$value]))
			return ;
		$option = new \StdClass ;
		$option->display = helper::htmlPrep($display);
		$option->value = $value;
		$option->class = $class;
		$this->options[$value] = $option;
		return $option ;
	}
	
	/**
	 * set Multple
	 *
	 * @version	1st July 2016
	 * @since	1st July 2016
	 * @return 	void
	 */
	public function setMultiple()
	{
		$this->multiple = true;
	}
	
	/**
	 * set Please Select
	 *
	 * @version	9th July 2016
	 * @since	8th July 2016
	 * @return 	void
	 */
	public function setPleaseSelect($message = 'Select something!')
	{
		$this->setExclusion('Please select...', $message);
		$this->validate->pleaseSelect = true;
		$this->pleaseSelect = true;
		if (empty($this->options))
			$this->addOption(trans::__('Please select...'), 'Please select...');
	}
	
	/**
	 * On Change Submit
	 *
	 * @version	9th July 2016
	 * @since	8th July 2016
	 * @return 	void
	 */
	public function onChangeSubmit()
	{
		$this->additional .= ' onChange="this.form.submit()"';
	}
	
	/**
	 * On Change Submit
	 *
	 * @version	25th July 2016
	 * @since	25th July 2016
	 * @param	string		$groupName	
	 * @return 	stdClass 	Option
	 */
	public function addOptGroup($groupName)
	{
		return $this->addOption('optgroup', trans::__($groupName));
	}
}
