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
 * Option Group Element
 *
 * @version	26th September 2016
 * @since	15th July 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Form
 */
class optGroup extends element
{
	/**
	 * Constructor
	 *
	 * @version	26th September 2016
	 * @since	15th July 2016
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
		else 
			$this->value = NULL;
		$this->element = new \stdClass();
		$this->element->name = 'optGroup';
		$this->id = $this->setID() ;
		$this->options = array();
		$this->checkAll = false ;;
		$this->optionType = 'radio';
		$this->emptyMessage = 'No selection available!';
		$this->script = '';
		$this->optClass = '';
		return $this ;
	}

	/**
	 * add Option
	 *
	 * @version	15th July 2016
	 * @since	15th July 2016
	 * @param	string	$name	
	 * @param	mixed	$value
	 * @param	string	$nameDisplay
	 * @param	boolean	$checked
	 * @return 	stdClass
	 */
	public function addOption($name, $value, $nameDisplay = null, $checked = false)
	{
		if (empty($this->options)) $this->options = array();
		$option = new \stdClass();
		$option->name = $name;
		$option->value = $value;
		$option->nameDisplay = $nameDisplay;
		$option->checked = $checked;
		$option->class = '';
		$this->options[$name] = $option;
		return $option ;
	}

	/**
	 * add Check All
	 *
	 * @version	20th August 2016
	 * @since	20th August 2016
	 * @return 	this
	 */
	public function addCheckAll()
	{
		if ($this->checkAll)
			return $this ;
		$this->checkAll = true;
		$w = $this->addOption('checkAll', '', $this->__( "All") .  " / " . $this->__( "None"));
		$w->class = 'checkAll';
		return $this ;
	}
}
