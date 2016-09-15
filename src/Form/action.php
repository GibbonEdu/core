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
use Gibbon\core\session ;

/**
 * Action Element
 *
 * @version	6th September 2016
 * @since	21st April 2016
 * @author	Craig Rayner

 * @package	Gibbon
 * @subpackage	Form
*/
class action extends hidden
{
	/**
	 * view Manager
	 *
	 * @version	6th September 2016
	 * @since	20th April 2016
	 * @param	string		$pageName  Page Name (Target)
	 * @raram	Gibbon\view	$view
	 * @return 	void
	 */
	public function __construct($pageName, view $view)
	{
		parent::__construct();
		$this->name = 'action';
		$this->value = str_replace('//', '/', GIBBON_ROOT . 'src/' . str_replace(array('//', '\\', 'src/', GIBBON_ROOT), array('/', '/', '', ''), $pageName));
		$view->render('form.hidden', $this);
		new token($this->value, $view);
		$session = new session();
		$this->setID('action');
		new address($session->get('address'), $view);
	}
}
