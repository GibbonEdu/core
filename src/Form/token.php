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

use Gibbon\core\config ;
use Gibbon\core\view ;

/**
 * Token Element
 *
 * @version	6th September 2016
 * @since	21st April 2016
 * @author	Craig Rayner

 * @package	Gibbon
 * @subpackage	Form
*/
class token extends hidden
{
	/**
	 * Constructor
	 *
	 * @version	6th September 2016
	 * @since	20th April 2016
	 * @param	string		$pageName
	 * @param	Gibbon\view	$view
	 * @return 	void
	 */
	public function __construct($pageName, view $view = NULL)
	{
		parent::__construct();
		$this->name = '_token';
		$this->value = $this->generateToken($pageName);
		$this->setID('token');
		if ($view !== NULL)
			$view->render('form.hidden', $this);
	}

	/**
	 * view Manager
	 *
	 * @version	24th August 2016
	 * @since	20th April 2016
	 * @return 	void
	 */
	public function generateToken($pageName)
	{
		
		if (defined('GIBBON_UID'))
			$this->guid = GIBBON_UID;
		else
		{
			$config = new config();
			$this->guid = $config->get('guid');
		}
		$this->action = $this->generateAction($pageName);
		return md5($this->guid . $this->action);
	}

	/**
	 * view Manager
	 *
	 * @version	24th August 2016
	 * @since	20th April 2016
	 * @return 	void
	 */
	public function generateAction($pageName)
	{
		$this->action = str_replace('//', '/', GIBBON_ROOT . 'src/' . str_replace(array('//', '\\', 'src/', GIBBON_ROOT), array('/', '/', '', ''), $pageName));
		return $this->action ;
	}
}
