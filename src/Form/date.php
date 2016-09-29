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

use Gibbon\core\view;

/**
 * Date Element
 *
 * @version	29th September 2016
 * @since	20th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class date extends text
{
	use \Gibbon\core\functions\dateFunctions ;

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
		$this->name = $name;
		if (empty($value))
			$this->value = null;
		else
		{
			$sess = $this->view->session ;
			if (is_int($value))
				$this->value = date($sess->isEmpty("i18n.dateFormatPHP") ? "d/m/Y" : $sess->get("i18n.dateFormatPHP"), $value); 
			elseif (is_string($value)) 
				$this->value = $this->dateConvertBack($value); 
		}
		$this->element->name = 'date';
		$this->setDate();
	}

	/**
	 * Set Date
	 *
	 * @version	30th June 2016
	 * @since	24th June 2016
	 * @param	string		$message
	 * @param	string		$after		The value must be later than this option.
	 * @param	string		$before		The value must be earlier than this option.
	 * @return	void
	 */
	public function setDate($message = 'Date Format Incorrect', $after = '', $before = '')
	{
		$session = $this->view->session;
		$this->setID();
		$this->setMaxLength(10);
		$this->getValidate()->Date = true ;
		$this->description = $this->validate->dateFormat = $session->isEmpty("i18n.dateFormat") ? "dd/mm/yyyy" : $session->get("i18n.dateFormat") ;
		$this->validate->dateMessage = $session->isEmpty("i18n.dateFormat") ? "dd/mm/yyyy" : $session->get("i18n.dateFormat") ;
		$this->validate->dateBefore = $before ;
		$this->validate->dateAfter = $after ;
		if (empty($this->placholder))
			$this->placeholder = $session->isEmpty("i18n.dateFormat") ? "dd/mm/yyyy" : $session->get("i18n.dateFormat");
		$this->setFormat($session->isEmpty("i18n.dateFormatRegEx") ? "^(0[1-9]|[12][0-9]|3[01])[- \/.](0[1-9]|1[012])[- \/.](19|20)\d\d$": $session->get("i18n.dateFormatRegEx"), "Use " . $session->isEmpty("i18n.dateFormat") ? "dd/mm/yyyy" : $session->get("i18n.dateFormat"));
	}
}
