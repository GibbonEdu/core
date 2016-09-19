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
use Symfony\Component\Yaml\Yaml ;

/**
 * Start Form
 *
 * @version	19th May 2016
 * @since	19th May 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class startForm
{
	/**
	 * @var Object	Gibbon\view
	 */
	private $view ;

	/**
	 * @var	array	Defaults
	 */
	private $defaults ;

	/**
	 * @var	string	Theme
	 */
	private $theme ;

	/**
	 * Constructor
	 *
	 * @version	1st June 2016
	 * @since	19th May 2016
	 * @param	array		$get		Injected $_GET values
	 * @param	object		$view		Gibbon\view
	 * @param	string		$id			Form Id
	 * @param	string		$class		Form Class
	 * @return 	void
	 */
	public function __construct(array $get = array(), view $view = NULL, $id = 'TheForm', $class = NULL)
	{
		$this->getDefaults();
		$this->postTarget = $view->session->get("absoluteURL") . "/index.php?" ;
		foreach($get as $name=>$value)
			$this->postTarget .= $name.'='.$value.'&';
		$this->postTarget = rtrim($this->postTarget, '&');
		$this->postTarget = rtrim($this->postTarget, '?');
		$this->formClass = $this->getClass($class);
		$this->name = $this->id = $id;
		if ($view instanceof view) $this->view->render('form.startForm', $this);
	}
	
	/**
	 * get Class
	 *
	 * @version	1st June 2016
	 * @since	1st June 2016
	 * @param	string		$class
	 * @return	void
	 */
	private function getClass($class)
	{
		if (! is_null($class) )
			return $this->formClass = $class;
		$this->getView();
		if ($this->getDefaults() === false) return ;
		if (isset($this->defaults['form']['class']))
			$this->formClass = $this->defaults['form']['class'];
	}
	
	/**
	 * get Defaults
	 *
	 * @version	1st June 2016
	 * @since	1st June 2016
	 * @return	boolean
	 */
	private function getDefaults()
	{
		if (! empty($this->defaults))
			return true;
		$this->theme = $this->getView()->session->get('gibbonThemeName');
		if (empty($this->theme))
			return true;
		if (! file_exists($this->view->session->get('absolutePath').'/themes/'.$this->theme.'/default.yml'))
			return false;
		$this->defaults = Yaml::parse(file_get_contents($this->view->session->get('absolutePath').'/themes/'.$this->theme.'/default.yml'));
		$this->formAdditional = isset($this->defaults['form']['additional']) ? $this->defaults['form']['additional'] : NULL ;
		return true ;
	}
	
	/**
	 * get Defaults
	 *
	 * @version	1st June 2016
	 * @since	1st June 2016
	 * @return	Gibbon\view
	 */
	private function getView()
	{
		if (! $this->view instanceof view)
			$this->view = new view('default.blank');
		return $this->view;
	}
	
	/**
	 * theme Correction
	 *
	 * @version	1st June 2016
	 * @since	1st June 2016
	 * @param	string		$theme	Theme Name
	 * @return	void
	 */
	private function themeCorrection()
	{
		if (is_null($this->row))
		{	
			$this->row = new \stdClass();
			$this->row->element = new \stdClass();
		}
		switch ($this->theme)
		{
			case 'Bootstrap':
				$pattern = array("/width:( ){0,}(.*\d)px(;){0,1}/", "/style=/");
				if (empty($this->row->element)) $this->row->element = new \stdClass();
				$this->row->element->style = isset($this->row->element->style) ? trim(preg_replace($pattern, '', $this->row->element->style)) : NULL ;
				$this->row->element->style = isset($this->row->element->style) ? trim($this->row->element->style, '"') : NULL ;
				$this->row->element->style = isset($this->row->element->style) ? trim($this->row->element->style, "'") : NULL ;
				$this->row->element->style = isset($this->row->element->style) ? trim($this->row->element->style) : NULL ;
				if (empty($this->row->element->style)) unset($this->row->element->style);
				break;
		}
	}
	
	/**
	 * render
	 *
	 * @version	1st June 2016
	 * @since	1st June 2016
	 * @param	string		$target		View Template name
	 * @param	object		$row		Row Information
	 * @return	void
	 */
	public function render($target, $row = NULL)
	{
		$this->row = NULL;
		if (! is_null($row))
			$this->row = $row;
		$this->themeCorrection();
		$this->getView()->render($target, $this->row);
	}

	/**
	 * start Form COntent
	 *
	 * @version	1st June 2016
	 * @since	1st June 2016
	 * @param	object		$params
	 * @return	void
	 */
	public function startFormContent($params = NULL)
	{
		$el = new \stdClass();
		$el->tableClass = 'smallIntBorder fullWidth';
		$el->divClass = 'gibbon-form';
		if (isset($params->class))
		{
			$el->tableClass = $params->class;
			$el->divClass = $params->class;
		}
		$this->getView()->render('content.form.start', $el);
	}
}
