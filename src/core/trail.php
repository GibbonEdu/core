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
namespace Gibbon\core;

use Gibbon\core\trans ;
use Gibbon\core\module ;
use Gibbon\core\view ;

/**
 * Trail 
 *
 * @version	31st May 2016
 * @since	26th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class trail
{
	/**
	 * @var	Gibbon\trail	
	 */
	private $view;

	/**
	 * Constructor
	 *
     * @version	31st May 2016
	 * @since	26th April 2016
	 * @param	string		$address	Address
	 * @param	Gibbon\view	$view
	 * @return	void
	 */
	public function __construct($address, view $view)
	{
		$this->view = $view;
		$this->addTrail(trans::__('Home'), array());
		$module = module::getModuleName($address, $this->view);
		if (strpos($address, '/plugins/') === false)
			if (! empty($module))
				$this->addTrail($module, array('q'=>'/modules/' . $module . "/" . module::getModuleEntry($address, $this->view)) );
		$this->trailEnd = 'Not Set';
	}

	/**
	 * add Trail Element
	 *
     * @version	20th May 2016
	 * @since	26th April 2016
	 * @param	string		$prompt	Displayed on Trail
	 * @param	string/array		$link	Relative Link starting /index.php?... or Array of $_GET
	 * @return	void
	 */
	public function addTrail($prompt, $link)
	{
		if (empty($this->trailHead)) $this->trailHead = array();
		if (is_array($link)) {
			$x = $link;
			$link = '/index.php?';
			foreach($x as $name=>$value)
			{
				$link .= $name ."=".$value."&";	
			}
			$link = rtrim($link, '&');
			$link = rtrim($link, '?');
		}
		$this->trailHead[trans::__($prompt)] = $link;
	}

	/**
	 * render
	 *
     * @version	26th April 2016
	 * @since	26th April 2016
	 * @param	Gibbon\view	$view
	 * @param	string		$viewName	ViewName
	 * @return	void
	 */
	public function render(view $view, $viewName = 'default.trail')
	{
		$this->view->render($viewName, $this);
	}

}

