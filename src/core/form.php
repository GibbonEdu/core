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

use Symfony\Component\Yaml\Yaml ;
use Gibbon\Form\deleteForm ;
use Gibbon\Record\theme ;

/**
 * Configuration Manager
 *
 * @version	9th September 2016
 * @since	14th June 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Form
 */
class form
{
	/**
	 * @var	sqlConnection	$this->pdo	Gibbon SQL
	 */
	protected $pdo ;
	
	/**
	 * @var	config	$config		Gibbon Config
	 */
	protected $config ;
	
	/**
	 * @var	session	$session	Gibbon Session
	 */
	protected $session ;

	/**
	 * @var	session	$view	Gibbon View
	 */
	protected $view ;

	/**
	 * @var	string	$handler		Path to file to handle post
	 */
	protected $handler ;

	/**
	 * @var	array	$get		$_GET array to add to form method.
	 */
	protected $get ;

	/**
	 * @var	boolean	$divert		Divert post direct to handler.
	 */
	protected $divert ;

	/**
	 * @var	array	$theme			Theme Defaults
	 */
	protected $theme ;

	/**
	 * @var	array	$validation			Form Validation
	 */
	protected $validation ;

	/**
	 * @var	array	$elements		The elements in the form.
	 */
	protected $elements ;

	/**
	 * @var	string	$style			The form style name
	 */
	protected $style ;

	/**
	 * @var	string	$name			The form name
	 */
	protected $name ;

	/**
	 * @var	string	$id			The form ID
	 */
	protected $id ;

	/**
	 * @var	string	$class			The Form Class
	 */
	protected $class ;

	/**
	 * @var	string	$additional			The Form Additional
	 */
	protected $additional ;

	/**
	 * @var	integer	$wellCount		Number of wells started and not finished.		
	 */
	protected $wellCount = 0 ;

	/**
	 * @var	string	$enctype			
	 */
	protected $enctype = 'multipart/form-data';

	/**
	 * Constructor
	 *
	 * @version	6th September 2016
	 * @since	14th June 2016
	 * @params	Gibbon\view		$view
	 * @return	Gibbon\form
	 */
	public function __construct(view $view, $style = 'standard')
	{
		$this->pdo = $view->pdo;
		$this->session = $view->session;
		$this->config = $view->config;
		$this->view = $view ;
		$this->elements = array();
		$this->style = $style;
		$this->themeDefaults();
		$this->setName('TheForm');
		$this->method = 'post';
		return $this ;
	}

	/**
	 * set Handler
	 *
	 * @version	21st August 2016
	 * @since	14th June 2016
	 * @params	string		$handler	Path to action file.
	 * @params	array		$get		$_GET Parameters.	
	 * @params	boolean		$divert		Divert to file without stdOut.	
	 * @return	void
	 */
	public function setHandler($handler, $get = array(), $divert = false, $enctype = false)
	{
		$this->handler = $handler ;
		$this->table = new \stdClass();
		$handler = str_replace($this->session->get('absolutePath'), '', $handler);
		$get['q'] = '/'.ltrim($handler, '/');
		if ($get['q'] === '/' || is_null($handler))
			unset($get['q']);
		$this->get = $get;
		$this->divert = $divert ;
		if (!$enctype)
			$this->enctype = '';
	}

	/**
	 * add Element
	 *
	 * @version	2nd July 2016
	 * @since	14th June 2016
	 * @params	string		$type	Element Type
	 * @params	string		$name	Element Name
	 * @params	mixed		$value	Element Value
	 * @return	object		Form Element
	 */
	public function addElement($type, $name, $value = null)
	{
		if (! is_array($this->elements)) $this->elements = array();

		$className = '\\Gibbon\\Form\\'.$type;
		$element = new $className($name, $value);
		if (in_array($type, array('multiple'))) // add other element types here
			$element->form = $this;
		$this->elements[] = $element ;

		return $element ;
	}

	/**
	 * render Form
	 *
	 * @version	30th June 2016
	 * @since	14th June 2016
	 * @param	string		$style	Style with which to render the form
	 * @param	boolean		$insertSignOff	Insert the Sign Off
	 * @return	void
	 */
	public function renderForm($style = 'standard', $insertSignOff = true)
	{
		$this->style = $this->name = $style ;
		
		$this->insertSignOff = $insertSignOff;
		
		while ($this->wellCount > 0)
			$this->endWell();
		
		if ($this->view instanceof view)
			$this->view->render('form.style.'.$style, $this);
		flush();
	}

	/**
	 * get
	 *
	 * @version	14th June 2016
	 * @since	14th June 2016
	 * @params	string		$name	Form Element Name
	 * @return	mixed
	 */
	public function get($name)
	{
		if (isset($this->$name))
			return $this->$name;
		return null ;
	}

	/**
	 * theme Defaults
	 *
	 * @version	26th August 2016
	 * @since	14th June 2016
	 * @return	mixed
	 */
	public function themeDefaults()
	{
		$this->theme = array();
		if ($this->session->isEmpty('theme.Name'))
			$this->session->set('theme.Name', 'Default');
		if ($this->session->notEmpty("theme.settings"))
			$this->theme = $this->session->get("theme.settings");
		else
		{	if (file_exists(GIBBON_ROOT.'src/themes/Bootstrap/settings.yml'))
			{
				$this->theme = Yaml::parse(file_get_contents(GIBBON_ROOT.'src/themes/Bootstrap/settings.yml'));
			}
		}
		$this->validation = $this->theme['validation'];
		if (isset($this->theme['form'][$this->style]))
			$this->theme = $this->theme['form'][$this->style];
		$this->class = isset($this->theme['class']) ? $this->theme['class'] : '';
		$this->role = isset($this->theme['role']) ? $this->theme['role'] : 'form';
		$this->additional = isset($this->theme['additional']) ? $this->theme['additional'] : '' ;
	}
	
	/**
	 * Sign Off
	 *
	 * @version	14th June 2016
	 * @since	14th June 2016
	 * @return	void
	 */
	public function signOff()
	{
		if (! $this->insertSignOff) return ;
		if ($this->divert)
			new \Gibbon\Form\hidden('divert', true, $this->view);
		new \Gibbon\Form\action($this->handler, $this->view);
	}
	
	/**
	 * get Target
	 *
	 * @version	14th June 2016
	 * @since	14th June 2016
	 * @return	void
	 */
	public function getTarget()
	{
		$get = '';
		foreach ($this->get as $name=>$value)
			$get .= $name . '=' . $value . '&';
		$get = rtrim('?' . rtrim($get, '&'), '?');
		return rtrim(GIBBON_URL . 'index.php' . $get);
	}
	
	/**
	 * set Name
	 *
	 * @version	14th June 2016
	 * @since	14th June 2016
	 * @params	string		$name	Name
	 * @params	string		$id		Id Name
	 * @return	void
	 */
	public function setName($name, $id = null)
	{
		$this->name = $name;
		if (! is_null($id))
			$this->id = $id;
		else
			$this->id = $name ;
	}
	
	/**
	 * start Well
	 *
	 * @version	25th June 2016
	 * @since	15th June 2016
	 * @return	void
	 */
	public function startWell()
	{
		$this->addElement('raw', '', $this->theme['well']['start']);
		$this->wellCount++;
		return $this;
	}
	
	/**
	 * start Well
	 *
	 * @version	9th September 2016
	 * @since	15th June 2016
	 * @param	boolean		$injectSubmit
	 * @param	string		$submitValue
	 * @return	void
	 */
	public function endWell($injectSubmit = false, $submitValue = 'Submit')
	{
		
		if ($this->wellCount < 1) return ;
		if ($injectSubmit) $this->addElement('submitBtn', null, $submitValue);
		$this->addElement('raw', '', $this->theme['well']['end']);
		$this->wellCount--;
		return $this ;
	}
	
	/**
	 * set Style
	 *
	 * @version	29th June 2016
	 * @since	29th June 2016
	 * @params	string		$style	
	 * @return	void
	 */
	public function setStyle($style)
	{
		$this->style = $style;
		$this->themeDefaults();
	}

	/**
	 * grab Form Details
	 *
	 * @version	2nd July 2016
	 * @since	2nd July 2016
	 * @params	object		$element
	 * @return	object		$element
	 */
	public function grabFormDetails($element)
	{
		$element->formID = $this->id;
		$element->theme = $this->theme;
		$element->style = $this->style;
		$element->setThemeStandards($element->theme);
		$element->set('validation', $this->validation);
		return $element;
	}


	/**
	 * remove Element
	 *
	 * @version	2nd July 2016
	 * @since	2nd July 2016
	 * @params	integer		$key	
	 * @return	void
	 */
	public function removeElement($key)
	{
		$this->elements[$key] = null ;
		unset($this->elements[$key]);
	}
	/**
	 * delete Form
	 *
	 * @version	5th September 2016
	 * @since	8th July 2016
	 * @return	void
	 */
	public function deleteForm()
	{
		$el = $this->addElement('deleteBtn', null, 'Delete');
		$el->nameDisplay = 'Are you sure you want to delete this record?' ;
		$el->description = 'This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!' ;
		$el->span->style = "font-size: 90%; color: #cc0000; ";
		$this->renderForm();
	}

	/**
	 * render
	 *
	 * @version	18th July 2016
	 * @since	18th July 2016
	 * @param	string		$style	Style with which to render the form
	 * @param	boolean		$insertSignOff	Insert the Sign Off
	 * @return	void
	 */
	public function render($style = 'standard', $insertSignOff = true)
	{
		$this->renderForm($style, $insertSignOff);
	}
	
	/**
	 * set Method
	 *
	 * @version	23rd July 2016
	 * @since	23rd July 2016
	 * @params	string		$method	
	 * @return	void
	 */
	public function setMethod($method = 'post')
	{
		if (! in_array(mb_strtolower($method), array('get', 'post')))
			$method = 'post';
		$this->method = mb_strtolower($method);
	}

	/**
	 * set
	 *
	 * @version	25th July 2016
	 * @since	25th July 2016
	 * @params	string		$name	Form Element Name
	 * @params	mixed		$value	Form Element Value
	 * @return	Gibbon\form
	 */
	public function set($name, $value)
	{
		return $this->$name = $value ;
		return $this ;
	}

	/**
	 * is Empty
	 *
	 * @version	25th June 2016
	 * @since	25th June 2016
	 * @params	string		$name	Form Element Name
	 * @return	boolean
	 */
	public function isEmpty($name)
	{
		$x = $this->get($name);
		if (empty($x)) return true ;
		return false ;
	}
}
