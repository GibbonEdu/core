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
 * Checkbox Element
 *
 * @version	19th September 2016
 * @since	23rd April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class checkbox extends element
{
	/**
	 * Constructor
	 *
	 * @version	19th September 2016
	 * @since	23rd April 2016
	 * @param	string		$name
	 * @param	mixed		$value
	 * @param	Gibbon\core\view	$view
	 * @return 	void
	 */
	public function __construct($name = null, $value = null, view $view)
	{
		parent::__construct($name, $value, $view);
		if ($name !== NULL) $this->name = $name;
		if ($value !== NULL) $this->value = $this->view->htmlPrep($value) ;
		$this->checked = false;
		$this->element->name = 'checkbox';
		$this->required = false;
		$this->setID();
	}

	/**
	 * set Checked
	 *
	 * @version	22nd July 2016
	 * @since	22nd July 2016
	 * @return 	void
	 */
	public function setChecked()
	{
		$this->checked = true ;
	}
	
	/**
	 * On Change Submit
	 *
	 * @version	6th September 2016
	 * @since	6th September 2016
	 * @return 	void
	 */
	public function onClickSubmit()
	{
		$this->additional .= ' onClick="this.form.submit()"';
	}
	
	/**
	 * render Return
	 *
	 * @version	19th September 2016
	 * @since	19th September 2016
	 * @return 	html
	 */
	public function renderReturn()
	{
		return $this->view->renderReturn('form.checkbox', $this);
	}
}
