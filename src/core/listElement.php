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
namespace Gibbon\core ;

use Gibbon\core\view ;

/**
 * List Element
 *
 * @version	25th August 2016
 * @since	21st June 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Core
 */
class listElement
{
	/**
	 * @var	string	$type
	 */
	protected 	$type ;

	/**
	 * @var	array	$messages
	 */
	protected 	$messages ;

	/**
	 * @var	Gibbon\view	$view
	 */
	private		$view ;
	
	/**
	 * set Type
	 * @version	6th July 2016
	 * @since	21st June 2016
	 * @param	string	$type
	 * @param	string	$listClass
	 * @return	Gibbon\listElement
	 */
	public function setType($type, $listClass = null)
	{
		$this->type = 'ul';
		$this->listClass = $listClass ;
		switch (strtolower($type))
		{
			case 'ol':
				$this->type = 'ol';
				break;
			case 'dl':
				$this->type = 'dl';
				break;
			default:
				$this->type = 'ul';
		}
		return $this ;
	}
	
	/**
	 * Add List Element
	 *
	 * @version	21st June 2016
	 * @since	21st June 2016
	 * @param	string	$message
	 * @param	array	$param		None Translated sprintf for message.
	 * @param	string	$liClass
	 * @return	Gibbon\listElement
	 */
	public function addListElement($message, $params = array(), $liClass = null)
	{
		if (empty($this->messages)) $this->messages = array();
		$this->messages[] = array($message, $params, $liClass);
		return $this;
	}
	
	/**
	 * render List
	 *
	 * @version	1st July 2016
	 * @since	21st June 2016
	 * @param	Gibbon\view		$view
	 * @param	boolean			$return
	 * @return	string/void
	 */
	public function renderList(view $view, $return = false)
	{
		if ($return)
			return $view->renderReturn('default.list', $this);
		$view->render('default.list', $this);
	}
	
	/**
	 * get
	 *
	 * @version	6th July 2016
	 * @since	21st June 2016
	 * @param	string	$name
	 * @return	void
	 */
	public function get($name)
	{
		if (isset($this->$name))
			return $this->$name ;
		return null ;
	}
	
	/**
	 * Add Header
	 *
	 * @version	6th July 2016
	 * @since	6th July 2016
	 * @param	string		$header
	 * @return	Gibbon\listElement
	 */
	public function addHeader($header)
	{
		$this->listHeader = $header ;
		return $this;
	}
	
	/**
	 * Add List Element
	 *
	 * @version	21st June 2016
	 * @since	21st June 2016
	 * @param	string	$message
	 * @param	array	$param		None Translated sprintf for message.
	 * @param	string	$liClass
	 * @return	Gibbon\listElement
	 */
	public function addListLink($message, $style, $href, $liClass = null)
	{
		$link = $this->view->convertGetArraytoURL($href);
		if (! empty($style))
			$style = ' style="'.$style.'"';
		$this->addListElement('%1$s'.$message.'%2$s', array("<a ".$style." href='" . $link . "'>", "</a>"), $liClass);
		return $this;
	}
	
	/**
	 * Add Header
	 *
	 * @version	12th August 2016
	 * @since	12th August 2016
	 * @param	Gibbon\view		$view
	 * @return	Gibbon\listElement
	 */
	public function __construct(view $view)
	{
		$this->view = $view ;
		return $this;
	}
	
	/**
	 * is Empty
	 *
	 * @version	25th August 2016
	 * @since	25th August 2016
	 * @param	string	$name
	 * @return	boolean
	 */
	public function isEmpty($name)
	{
		$x = $this->get($name);
		if (empty($x)) return true ;
		return false ;
	}
}
