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

use Gibbon\view ;

/**
 * H3 Element
 *
 * @version	14th June 2016
 * @since	14th June 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class h3 extends element
{
	/**
	 * Constructor
	 *
	 * @version	14th June 2016
	 * @since	14th June 2016
	 * @param	string		$name	Name
	 * @param	string		$value	Value
	 * @param	Gibbon\view	$view
	 * @return 	void
	 */
	public function __construct($name = NULL, $value = NULL, view $view = NULL)
	{
		$this->createDefaults();
		$this->name = $name ;
		$this->value = $this->title = $value ;
		$this->element->name = 'h3';
		if ($view instanceof view)
			$view->render('form.wrapper.h3', $this);
	}
}
