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
use Gibbon\Form\button ;

/**
 * button Element
 *
 * @version	17th September 2016
 * @since	29th June 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Form
 */
class buttons extends element
{
	/**
	 * Constructor
	 *
	 * @version	17th September 2016
	 * @since	29th June 2016
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
		$this->element->name = 'buttons';
		$this->row->class = 'g_buttons';
		$this->validate = false;  // or false
		$this->required = false;
		$this->readOnly = false;
	}

	/**
	 * add Button
	 *
	 * @version	29th June 2016
	 * @since	29th June 2016
	 * @param	string		$name	Name
	 * @param	string		$value	Value
	 * @param	string		$type	of Button
	 * @return 	Object		of Button
	 */
	public function addButton($name = NULL, $value = NULL, $type = 'button', $action = 'submit')
	{
		if (empty($this->buttons)) $this->buttons = array();
		$el = new button($name, $value, $this->view);
		$el->type = $type;
		switch ($action)
		{
			case 'submit':
				$el->element->type = 'submit';
				break;
			case 'reset':
				$el->element->type = 'reset';
				break;
			default:
				$el->element->type = 'button';
		}
		$this->buttons[] = $el;
		return $el ;
	}

	/**
	 * set Buttons Class
	 *
	 * @version	16th September 2016
	 * @since	16th September 2016
	 * @return 	void
	 */
	public function setButtonsClass()
	{
		if (empty($this->buttons)) return ;
		foreach($this->buttons as $q=>$w)
		{
			$this->buttons[$q]->element->class = $this->theme['buttons'][$w->type];
		}
	}
}
