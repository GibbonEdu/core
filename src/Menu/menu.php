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
namespace Gibbon\Menu;

use Gibbon\core\view;

/**
 * Menu Core Class
 *
 * @version	4th May 2016
 * @since	24th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
abstract class menu implements menuInterface
{
	/**
	 * Gibbon\sqlConnection
	 */
	protected $pdo ;
	
	/**
	 * Gibbon\session
	 */
	protected $session ;
	
	/**
	 * Gibbon\config
	 */
	protected $config ;
	
	/**
	 * Gibbon\view
	 */
	protected $view ;
	
	/**
	 * string
	 */
	protected $menu = NULL ;
	
	/**
	 * Construct
	 *
	 * @version 4th May 2016
	 * @since	24th April 2016
	 * @param	Gibbon\view	$view
	 * @return	void
	 */
	public function __construct( view $view )
	{
		$this->view = $view ;
		$this->pdo = $view->pdo ;
		$this->session = $view->session;
		$this->config = $view->config ;
		$this->setMenu();
	}
}
?>