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
 * Time Element(s)
 *
 * @version	29th September 2016
 * @since	20th May 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class time extends element
{
	/**
	 * Constructor
	 *
	 * @version	29th September 2016
	 * @since	20th May 2016
	 * @param	string		$name
	 * @param	mixed		$value
	 * @param	Gibbon\core\view	$view
	 * @return 	void
	 */
	public function __construct($name = null, $value = null, view $view)
	{
		parent::__construct($name, $value, $view);
		$hour = 0;
		$minute = 0;
		if ($value == null || $value == '')
		{
			$hour = 'Hour';
			$minute = 'Minute';
		} else {
			$hour = substr($value, 0, 2);
			$minute = substr($value, 3, 2);
		}
		$hName = $name . '[hour]';
		$mName = $name . '[minute]';
		$this->hour = new select($hName, $hour, $view);
		$this->minute = new select($mName, $minute, $view);

		$this->hour->addOption($view->__('Hour'), '');		
		$this->minute->addOption($view->__('Minute'), '');	
		for ($i = 0;$i < 60;++$i) 
		{
			if ($i <= 24) $this->hour->addOption(sprintf('%02d', $i));
			$this->minute->addOption(sprintf('%02d', $i));
		}
		if ($name !== null)
			$this->name = $name;
		if ($value !== null)
			$this->value = $value;
		else
			$this->value = null;
		$this->description = 'hh:mm';
		$this->element->name = 'time';

	}
}
