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
use Gibbon\core\form ;
use Gibbon\core\helper ;
use Gibbon\core\sqlConnection as PDO;
use Gibbon\core\session;
use Gibbon\core\config;
use Gibbon\Record\theme;
use Gibbon\Record\person;
use stdClass ;

/**
 * view Manager
 *
 * @version	12th October 2016
 * @since	19th April 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Core
 */
class view
{

	use functions\stringFunctions , 
		functions\developmentFunctions ,
		functions\dateFunctions ,
		functions\moduleFunctions ;
	
	/**
	 * @var	sqlConnection	$pdo	Gibbon SQL
	 */
	public $pdo ;
	
	/**
	 * @var	config	$config		Gibbon Config
	 */
	public $config ;
	
	/**
	 * @var	session	$session	Gibbon Session
	 */
	public $session ;

	/**
	 * string
	 */
	private $viewName;

	/**
	 * string
	 */
	private $address;

	/**
	 * array
	 */
	private $returns;

	/**
	 * object
	 */
	private $security;

	/**
	 * @var string	$theme	Theme Name (for this page);
	 */
	private $theme;

	/**
	 * @var integer
	 */
	private $wellDepth = 0;

	/**
	 * @var Gibbon\Record\person
	 */
	private $person ;

	/**
	 * @var Gibbon\core\trans
	 */
	private $trans ;

	/**
	 * @var	stdClass
	 */
	private $records ;

	/**
	 * Constructor
	 *
	 * @version	23rd September 2016
	 * @since	19th April 2016
	 * @param	string	View Name
	 * @param	stdClass	Parameters
	 * @param	Gibbon\session
	 * @param	Gibbon\config
	 * @param	Gibbon\sqlConnection
	 * @return	void
	 */
	public function __construct($name = 'default.blank', $params = NULL, session $session = NULL, config $config = NULL, PDO $pdo = NULL)
	{
		if (is_null($this->session))
			$this->session = new session();
		else
			$this->session = $session ;
		if (is_null($config))
			$this->config = new config();
		else
			$this->config = $config ;
		if (! $this->config->isInstall())
			if (is_null($pdo))
				$this->pdo = new PDO();
			else
				$this->pdo = $pdo ;
		$this->mapReturns();
		$this->setTheme();
		$this->trans = $this->getTrans();
		$this->render($name, $params);
	}

	/**
	 * Test Name
	 *
	 * @version	26th August 2016
	 * @since	19th April 2016
	 * @param	string	View Name
	 * @return	void
	 */
	private function testName($name)
	{
		$this->viewName = $name;
		$name = explode('.', $name);
		if ($this->session->isEmpty('absolutePath'))
		{
				$this->session->set('absolutePath', rtrim(GIBBON_ROOT, '/'));
				$this->session->set('absoluteURL', rtrim(GIBBON_URL, '/'));
		}

		// first test in the module with the current Theme.
		$this->address = GIBBON_ROOT . 'src/modules/' . $this->session->get('module') . '/views/' . $this->session->get('theme.Name');
		foreach($name as $w)
			$this->address .= '/' . trim($w) ;
		$this->address .= '.php';
		if ( file_exists($this->address))
			return ;

		// next test in the module with the default theme.
		$this->address = GIBBON_ROOT . 'src/modules/' . $this->session->get('module') . '/views/Bootstrap';
		foreach($name as $w)
			$this->address .= '/' . trim($w) ;
		$this->address .= '.php';
		if (file_exists($this->address))
			return ;

		// Test the module again using name[0] as the module Name 
		
		$moduleName = $name[0];
		unset($name[0]);
		if (is_dir(GIBBON_ROOT . 'src/modules/' . $moduleName))
		{
			//Module with current theme
			$this->address = GIBBON_ROOT . 'src/modules/' . $moduleName . '/views/' . $this->session->get('theme.Name');
			foreach($name as $w)
				$this->address .= '/' . trim($w) ;
			$this->address .= '.php';
			if (file_exists($this->address))
				return ;
			
			//Module with default theme
			$this->address = GIBBON_ROOT . 'src/modules/' . $moduleName . '/views/Bootstrap';
			foreach($name as $w)
				$this->address .= '/' . trim($w) ;
			$this->address .= '.php';
		if (file_exists($this->address))
				return ;
			
		}
		
		array_unshift ($name, $moduleName) ;
		
		// file in the Theme but not in module
		$this->address = GIBBON_ROOT . 'src/themes/' . $this->session->get('theme.Name'). '/views';
		foreach($name as $w)
			$this->address .= '/' . trim($w) ;
		$this->address .= '.php';
		if (file_exists($this->address))
			return ;
 
 		// Not Theme based or Module document, so test default
		$this->address = GIBBON_ROOT . 'src/themes/Bootstrap/views';
		foreach($name as $w)
			$this->address .= '/' . trim($w) ;
		$this->address .= '.php';
		if (file_exists($this->address)) 
			return ;
		
		$this->dump(implode('.', $name) .' was not found!', true, true);
	}

	/**
	 * render
	 *
	 * @version	7th June 2016
	 * @since	19th April 2016
	 * @param	string		$name	View Name
	 * @param	stdClass	$el		Element Parameters
	 * @return	void
	 */
	public function render($name, $el = NULL)
	{
		$params = $el; // Backwards   
		$this->testName($name);
		include $this->address;
	}

	/**
	 * insert Message
	 *
	 * adds to Flash/ returns or echos to stdOut
	 * @version	21st July 2016
	 * @since	26th April 2016
	 * @param	string/array		$message Message to print<br/>
	 * When used as an array, [0] = message and [1] is an array of sprintf inserts passed to the translator.
	 * @param	string		$style Style (error, warning, success, info)
	 * @param	boolean/string		$echo  if true, sends to stdOut else add to flash.
	 * @param	string		$target	Defaulkt is Flash
	 * @return	string/void
	 */
	public function insertMessage($message, $style = 'error', $echo = false, $target = 'flash') 
	{
		if (is_array($message))
		{
			$messageInfo = $message[1];
			$message = $message[0];
		}
		else
		{	
			$messageInfo = array();
		}
		$style = strtolower($style);
		switch ($style) {
			case 'info':
				break;
			case 'warning':
				break;
			case 'success':
				break;
			case 'current':
				break;
			default: 
				$style = 'error';
		}
		if (isset($this->returns[$message]))
			$message = trim($this->returns[$message]);
		if (empty($message)) return ;
		if (is_string($echo) && $echo == 'return')
			return '<div class="'. $style .'">' .  $this->__($message, $messageInfo) .'</div>';
		elseif ($echo)
			echo '<div class="'. $style .'">' .  $this->__($message, $messageInfo) .'</div>';
		else
			$this->session->appendUnique($target, '<div class="'. $style .'">' .  $this->__($message, $messageInfo) .'</div>') ;
		return ;
	}

	/**
	 * Initiate Trail
	 *
	 * @version	27th April 2016
	 * @since	27th April 2016
	 * @param	address		Address of Current Page
	 * @return	HTML String
	 */
	public function initiateTrail($address = NULL) 
	{
		if (empty($address) and isset($_GET['q']))
			$address = $_GET['q'];
		if (empty($address))
			$address = $this->session->get('address');
		if (empty($address))
			throw new Exception('You have not set the current page details correctly.', 29000 + __LINE__);
		return $trail = new trail($address, $this);
	}

	/**
	 * Map Returns
	 *
	 * @version	24th September 2016
	 * @since	29th April 2016
	 * @return	void
	 */
	private function mapReturns() 
	{
		$returns = array();
		$returns["success0"] = "Your request was completed successfully.";
		$returns["error0"] = "Your request failed because you do not have access to this action.";
		$returns["error1"] = "Your request failed because your inputs were invalid.";
		$returns["error2"] = "Your request failed due to a database error.";
		$returns["error3"] = "Your request failed because some inputs did not meet a requirement for uniqueness.";
		$returns["error4"] = "Your request failed because your passwords did not match.";
		$returns["error5"] = "Your request failed because there are no records to show.";
		$returns["error6"] = "Your request was completed successfully, but one or more images were the wrong size and so were not saved.";
		$returns["warning0"] = "Your optional extra data failed to save.";
		$returns["warning1"] = "Your request was successful, but some data was not properly saved.";
		$returns["warning2"] = "Your request was successful, but some data was not properly deleted.";
		$returns["return.success.0"] = "Your request was completed successfully.";
		$returns["return.error.0"] = "Your request failed because you do not have access to this action.";
		$returns["return.error.1"] = "Your request failed because your inputs were invalid.";
		$returns["return.error.2"] = "Your request failed due to a database error.";
		$returns["return.error.3"] = "Your request failed because some inputs did not meet a requirement for uniqueness.";
		$returns["return.error.4"] = "Your request failed because your passwords did not match.";
		$returns["return.error.5"] = "Your request failed because there are no records to show.";
		$returns["return.error.6"] = "Your request was completed successfully, but one or more images were the wrong size and so were not saved.";
		$returns["return.warning.0"] = "Your optional extra data failed to save.";
		$returns["return.warning.1"] = "Your request was successful, but some data was not properly saved.";
		$returns["return.warning.2"] = "Your request was successful, but some data was not properly deleted.";
		$this->returns = $returns;
	}

	/**
	 * get Return
	 *
	 * @version	19th May 2016
	 * @since	29th April 2016
	 * @param	string		$editLink	Edit LInk
	 * @param	boolean		$echo	Send to Flash (false = default) or stdout = true.
	 * @return	void
	 */
	public function getReturn($editLink = NULL, $echo = false)
	{
		if (empty($_GET['return']))
			return ;
		$class="error" ;
		$returnMessage = array() ;
		
		foreach($this->returns as $returnKey => $message) {
			if($_GET['return'] == $returnKey) {
				$key = str_replace(array('error', 'success', 'warning', 'info', 'current'), '', $_GET['return']);
				$class = trim(str_replace($key, '', $_GET['return']));
			}
		}
		$returnMessage = 'return.'.$class.'.'.$key;
		if($class == "success" && $editLink != NULL) {
			$this->insertMessage($returnMessage, $class, $echo);
			$returnMessage = sprintf(  $this->__('You can edit your record %1$shere%2$s.'), "<a href='$editLink'>", "</a>" );
		}
		$this->insertMessage($returnMessage, $class, $echo);
	}

	/**
	 * insert Return
	 *
	 * @version	29th April 2016
	 * @since	29th April 2016
	 * @param	string		Key
	 * @param	string		Message (NOT TRANSLATED)
	 * @return	void
	 */
	public function insertReturn($key, $message)
	{
		$this->returns[trim($key)] = $message ;
	}

	/**
	 * linkTop
	 *
	 * @version	1st October 2016
	 * @since	29th April 2016
	 * @param	string		$links
	 * $name=>$link<br />
	 * Link Starts after q=
	 * @param	string		$class
	 * @return	void
	 */
	public function linkTop( array $links, $class = 'linkTop newLinkTop')
	{
		if (empty($links)) return ;
		$linksDefined = $this->session->get('theme.settings.linkTop') ;
		?><div class='<?php echo $class;?>'><?php
        do {
			$link = reset($links) ;
			$el = (object) $linksDefined ;
			$name = key($links) ;
			array_shift($links);
			if (isset($linksDefined[mb_strtolower($name)]))
				$el = (object) $linksDefined[mb_strtolower($name)] ;
			else
			{
				$el = new \stdClass() ;
				$el->name = $name ;
			}
			if (isset($link['onclick']))
			{
				$el->onclick = $link['onclick'];
				unset($link['onclick']);
			}
			if (isset($link['prompt']))
			{
				$el->prompt = $link['prompt'];
				unset($link['prompt']);
			}
			if (isset($link['href']))
				$link = $link['href'];
			$el->link = $this->convertGetArraytoURL($link);
			$this->render('button.basicLink', $el);
			if (! empty($links))
				echo '&nbsp;|&nbsp;';
		} while (! empty($links)); ?>
		</div><?php
	}

	/**
	 * linkTop Return
	 *
	 * @version	1st October 2016
	 * @since	1st October 2016
	 * @param	string		$links
	 * $name=>$link<br />
	 * Link Starts after q=
	 * @param	string		$class
	 * @return	void
	 */
	public function linkTopReturn( array $links, $class = 'linkTop newLinkTop')
	{
		ob_start();
		$this->linkTop($links, $class);
		$out2 = ob_get_contents();
		if (! empty($out2))
			ob_end_clean();
		return $out2 ;
	}

	/**
	 * Display Message
	 *
	 * @version	10th May 2016
	 * @since	10th May 2016
	 * @param	string/array		$message Message to print
	 * @param	string		$style Style (error, warning, success, info, default)
	 * @return	void
	 */
	public function displayMessage($message, $style = 'error') 
	{
		$this->insertMessage($message, $style, true);
	}

	/**
	 * Display h3
	 *
	 * @version	23rd May 2016
	 * @since	22nd May 2016
	 * @param	string		$message Message to print
	 * @param	array		$messDetails Detail to insert in message after Translation
	 * @return	void		Sends to stdOut
	 */
	public function h3($message, $messDetails = array()) 
	{
		$el = new \stdClass();
		$el->title = $message ;
		$el->titleDetails = $messDetails ;
		$this->render('default.h3', $el);
	}

	/**
	 * Display h2
	 *
	 * @version	6th July 2016
	 * @since	23rd May 2016
	 * @param	string		$message Message to print
	 * @param	array		$messDetails Detail to insert in message after Translation
	 * @param	boolean		$return
	 * @return	void		Sends to stdOut
	 */
	public function h2($message, $messDetails = array(), $return = false) 
	{
		$el = new \stdClass();
		$el->title = $message ;
		$el->titleDetails = $messDetails ;
		if ($return)
			return $this->renderReturn('default.h2', $el);
		$this->render('default.h2', $el);
	}

	/**
	 * Render Return
	 *
	 * @version	27th May 2016
	 * @since	27th May 2016
	 * @param	string		View Name
	 * @param	stdClass	Parameters
	 * @return	string		HTML Output
	 */
	public function renderReturn($name, $params = NULL )
	{
		ob_start();
		$this->render($name, $params);
		$out2 = ob_get_contents();
		if (! empty($out2))
			ob_end_clean();
		return $out2 ;
	}

	/**
	 * Return Message
	 *
	 * @version	6th June 2016
	 * @since	6th June 2016
	 * @param	string/array		$message Message to print
	 * @param	string		$style Style (error, warning, success, info)
	 * @return	string
	 */
	public function returnMessage($message, $style = 'error') 
	{
		return $this->insertMessage($message, $style, 'return');
	}

	/**
	 * redirect
	 *
	 * @version	12th October 2016
	 * @since	9th June 2016
	 * @param	string/array		$URL	Target url
	 * @return	string
	 */
	public function redirect($URL) 
	{
		if (headers_sent())
			$this->dump('Headers already Sent<br />Not able to redirect to new page.<br />', true, true);
//		header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
//		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // past date to encourage expiring immediately
		header('HTTP/1.1 303 Redirect');
		if (is_array($URL))
			$URL['uniqueKey'] = uniqid();
		if (is_string($URL) && strpos($URL, '?') !== false)
			$URL .= '&uniqueKey='.uniqid();
		elseif (is_string($URL))
			$URL .= '?uniqueKey='.uniqid();
		header('Location: '.$this->convertGetArraytoURL($URL));
		die();
	}

	/**
	 * Display Paragraph
	 *
	 * @version	19th June 2016
	 * @since	19th June 2016
	 * @param	string		$message Message to print
	 * @param	array		$messDetails Detail to insert in message after Translation
	 * @return	void		Sends to stdOut
	 */
	public function paragraph($message, $messDetails = array()) 
	{
		$el = new \stdClass();
		$el->message = $message ;
		$el->messageDetails = $messDetails ;
		$this->render('default.paragraph', $el);
	}

	/**
	 * startList
	 *
	 * @version	6th July 2016
	 * @since	21st June 2016
	 * @param	string		$type of List (ol, ul, dl)
	 * @param	string		$listClass
	 * @return	void		Sends to stdOut
	 */
	public function startList($type, $listClass = null)
	{
		$el = new listElement($this);
		$el->setType($type, $listClass) ;
		return $el ;
	}

	/**
	 * get Security
	 *
	 * @version	22nd June 2016
	 * @since	22nd June 2016
	 * @return	void		Sends to stdOut
	 */
	public function getSecurity()
	{
		if ($this->security instanceof security)
			return $this->security;
		$this->security = new security($this);
		return $this->security;
	}

	/**
	 * get Security
	 *
	 * @version	22nd June 2016
	 * @since	22nd June 2016
	 * @return	void		Sends to stdOut
	 */
	public function getConfig()
	{
		if (! $this->config instanceof configy)
			$this->config = new config();
		$this->config->injectView($this);
		return $this->config;
	}
	
	/**
	 * get pdo
	 *
	 * @version	23rd June 2016
	 * @since	23rd June 2016
	 * @return	Gibbon\sqlConnection
	 */
	public function getPDO()
	{
		if ($this->pdo instanceof sqlConnection)
			return $this->pdo;
		else
			throw new \Exception('No sql Connection class defined.  You may need to inject the view.', intval(21000 + __LINE__));
	}
	
	/**
	 * get Session
	 *
	 * @version	23rd June 2016
	 * @since	23rd June 2016
	 * @return	Gibbon\sqlConnection
	 */
	public function getSession()
	{
		if ($this->session instanceof session)
			return $this->session;
		else
			$this->session = new session();
		return $this->session;
	}
	
	/**
	 * set Theme
	 *
	 * @version	4th October 2016
	 * @since	23rd June 2016
	 * @return	void
	 */
	public function setTheme()
	{
		if ($this->config->isInstall()) return ;
		$tObj = $this->getRecord('theme');
		$tObj->setDefaultTheme();
	}

	/**
	 * get form
	 *
	 * @version	24th June 2016
	 * @since	23rd June 2016
	 * @params	string		$handler	Path to action file.
	 * @params	array		$get		$_GET Parameters.	
	 * @params	boolean		$divert		Divert to file without stdOut.	
	 * @params	string		$name		Form Name / ID	
	 * @return	Gibbon\form
	 */
	public function getForm($handler, $get = array(), $divert = false, $name = 'TheForm', $enctype = false)
	{
		$form = new form($this);
		if (empty($handler) && isset($get['q']))
		{
			$handler = rtrim(GIBBON_ROOT, '/') . '/' . ltrim($get['q'], '/');
		}
		$form->setHandler($handler, $get, $divert, $enctype);
		$form->setName($name);
		return $form;
	}

	/**
	 * get Link
	 *
	 * @version	5th October 2016
	 * @since	25th June 2016
	 * @param	string		$type
	 * @param	string or array		$href
	 * @param	string or array		$prompt
	 * @param	string		$imgParameters  (dump anything that would be in a link between the <a></a>.)
	 * @return	void
	 */
	public function getLink($type, $href, $prompt = '', $imgParameters = '')
	{
		$type = mb_strtolower($type);
		$prompt = empty($prompt) ? '' :  $this->__($prompt) ;
		if ($type === '') 
		{
			$link = new stdClass();
			if (isset($href['onclick']))
			{
				$link->onclick = $href['onclick'];
				unset($href['onclick']);
			}
			if (isset($href['style']))
			{
				$link->style = $href['style'];
				unset($href['style']);
			}
			if (isset($href['class']))
			{
				$link->class = $href['class'];
				unset($href['class']);
			}
			if (isset($href['title']))
			{
				$link->title = $href['title'];
				unset($href['title']);
			}
			if (isset($href['href']))
				$href = $href['href'];
			$link->href = $this->convertGetArraytoURL($href);
			$link->imgParameters = $imgParameters;
			$link->prompt = $prompt;
			$this->render('content.link', $link);
			return ;
		}
		$links = $this->session->get('theme.settings.links');
		if (! array_key_exists($type, $links)) return $this->getLink('', $href, 'Missing-'.$type);
		$link = (object) $links[$type];
		if (isset($href['onclick']))
		{
			$link->onclick = $href['onclick'];
			unset($href['onclick']);
		}
		if (isset($href['style']))
		{
			$link->style = $href['style'];
			unset($href['style']);
		}
		if (isset($href['class']))
		{
			$link->class = $href['class'];
			unset($href['class']);
		}
		if (isset($href['title']))
		{
			$link->title = $href['title'];
			unset($href['title']);
		}
		if (isset($href['href']))
			$href = $href['href'];
		$link->href = $this->convertGetArraytoURL($href);
		$link->imgParameters = $imgParameters;
		if (! empty($prompt))
			$link->prompt = $prompt;
		$this->render('content.link', $link);
		return ;
	}

	/**
	 * return Link
	 *
	 * @version	30th June 2016
	 * @since	30th June 2016
	 * @param	string		$type
	 * @param	string		$href
	 * @param	string		$prompt
	 * @param	string		$imgParameters  (dump anything that would be in a link.
	 * @return	string
	 */
	public function returnLink($type, $href, $prompt = '', $imgParameters = '')
	{
		$link = new \stdClass();
		$link->href = $href;
		$link->imgParameters = $imgParameters;
		$link->prompt = $prompt;
		$link->type = $type ;
		return $this->renderReturn('content.linkReturn', $link);
	}

	/**
	 * return Link Image
	 *
	 * @version	30th June 2016
	 * @since	30th June 2016
	 * @param	string		$type
	 * @param	string		$imgParameters  (dump anything that would be in an image.)
	 * @return	string
	 */
	public function returnLinkImage($type, $imgParameters = '')
	{
		$type = mb_strtolower($type);
		if ($type === '') 
		{
			return '';
		}
		$links = $this->session->get('theme.settings.links');
		$link =	(object) $links[$type];
		$link->imgParameters = $imgParameters;
		return $this->renderReturn('content.image', $link);
	}

	/**
	 * Display h4
	 *
	 * @version	6th July 2016
	 * @since	6th July 2016
	 * @param	string		$message Message to print
	 * @param	array		$messDetails Detail to insert in message after Translation
	 * @param	boolean		$return		Return the string.
	 * @return	void/string		
	 */
	public function h4($message, $messDetails = array(), $return = false) 
	{
		$el = new \stdClass();
		$el->title = $message ;
		$el->titleDetails = $messDetails ;
		if ($return)
			return $this->renderReturn('default.h4', $el);
		$this->render('default.h4', $el);
	}

	/**
	 * module Menu
	 *
	 * @version	6th July 2016
	 * @since	6th July 2016
	 */
	public function getModuleMenu() 
	{
		return new \Gibbon\Menu\moduleMenu($this);
	}

	/**
	 * display Alert
	 *
	 * @version	21st July 2016
	 * @since	21st July 2016
	 * @param	string		$message
	 * @param	array		$alert
	 * return	void
	 */
	public function displayAlert($message, $alert) 
	{
		return $this->insertAlert($message, $alert, true);
	}

	/**
	 * display Alert
	 *
	 * @version	21st July 2016
	 * @since	21st July 2016
	 * @param	string		$message
	 * @param	array		$alert
	 * return	void
	 */
	public function returnAlert($message, $alert) 
	{
		return $this->insertAlert($message, $alert, 'return');
	}

	/**
	 * display Alert
	 *
	 * @version	21st July 2016
	 * @since	21st July 2016
	 * @param	string		$message
	 * @param	array		$alert
	 * @param	boolean/string		$echo
	 * @param	string		$target
	 * return	void/string
	 */
	public function insertAlert($message, $alert, $echo = false, $target = 'flash') 
	{
		$message = trim($message);
		if (empty($message)) return ;
		
		$x = "<div class='error' style='background-color: #".$alert[4].'; border: 1px solid #'.$alert[3].'; color: #'.$alert[3]."'>";
		$x .=  $this->__('This student has one or more %1$s risk medical conditions.', $alert[1]);
		$x .= '</div>';
		
		if ($echo == 'return')
			return $x;
		elseif ($echo)
			echo $x;
		else
			$this->session->append($target, $x) ;
	}

	/**
	 * convert Get Array to URL
	 *
	 * @version	5th August 2016
	 * @since	21st July 2016
	 * @param	array/string		$link
	 * return	string
	 */
	public function convertGetArraytoURL($link, $url = true) 
	{
		if (is_string($link)) return $link;
		$get = '';
		if ($url) 
		{
			$w = GIBBON_URL . 'index.php';
			if (isset($link['q']))
			{
				$get .= 'q=' . $link['q'] . '&' ;
				unset($link['q']); 
			}
		}
		else
		{
			$w = GIBBON_ROOT;
			if (isset($link['q']))
			{
				$w .= $link['q'];
				unset($link['q']); 
			}
		}
		if (empty($link)) $link = array();
		foreach($link as $name=>$value)
			$get .= $name . '=' . $value . '&' ;
		$w .= str_replace(' ', '+', '?'. rtrim($get, '&'));
		return $w ;
	}

	/**
	 * Display Strong (bold)
	 *
	 * @version	12th August 2016
	 * @since	12th August 2016
	 * @param	string		$message Message to print
	 * @param	array		$messDetails Detail to insert in message after Translation
	 * @param	boolean		$return		Return the string.
	 * @return	void/string		
	 */
	public function strong($message, $messDetails = array(), $return = false) 
	{
		$el = new \stdClass();
		$el->title = $message ;
		$el->titleDetails = $messDetails ;
		if ($return)
			return $this->renderReturn('default.strong', $el);
		$this->render('default.strong', $el);
	}

	/**
	 * Display Strong (bold)
	 *
	 * @version	12th August 2016
	 * @since	12th August 2016
	 * @param	string		$message Message to print
	 * @param	array		$messDetails Detail to insert in message after Translation
	 * @param	boolean		$return		Return the string.
	 * @return	void/string		
	 */
	public function bold($message, $messDetails = array(), $return = false) 
	{
		$this->strong($message, $messDetails, $return);
	}

	/**
	 * start Well
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @param	boolean		$return
	 * @return	void/string	
	 */
	public function startWell($return = false) 
	{
		++$this->wellDepth;
		if ($return) return $this->renderReturn('default.startWell');
		$this->render('default.well.startWell');
	}

	/**
	 * start Well
	 *
	 * @version	13th August 2016
	 * @since	13th August 2016
	 * @param	boolean		$return
	 * @return	void/string	
	 */
	public function endWell($return = false) 
	{
		--$this->wellDepth;
		if ($return) return $this->renderReturn('default.endWell');
		$this->render('default.well.finish');
	}

	/**
	 * get Action String
	 *
	 * @version	18th August 2016
	 * @since	18th August 2016
	 * @param	string		$action Relative Path from ROOT of the target script.
	 * @return	string	
	 */
	public function getActionString($action)
	{
		$action = GIBBON_ROOT . $action ;
		return 'action='.$action.'&_token='.md5($this->config->get('guid').$action).'&divert=true';
	}
	
	/**
	 * Inject Module CSS
	 * 
	 * Inject Module CSS into page Header
	 * @version	7th October 2016
	 * @since	6th September 2016
	 * @param	string		$module
	 * @return	stdOut
	 */
	public function injectModuleCSS($module = null)
	{
		if (is_null($module) && $this->session->isEmpty('module')) return ;
		
		$module = is_null($module) ? $this->session->get('module') : $module;
		$theme = $this->session->get('theme.Name', 'Bootstrap');
		$cssURL = '';
		
		
		// Load the Default Module CSS
		if (file_exists(GIBBON_ROOT . 'src/modules/'.$module.'/css/module.css')) {
			$cssURL = GIBBON_URL . 'src/modules/'.$module.'/css/module.css';
			$this->addScript('
<script type="application/javascript" language="javascript">
	$("head").append(\'<link rel="stylesheet" type="text/css" href="'.$cssURL.'" media="screen" />\');
</script>
');
		}
		
		
		// Load the Theme Module CSS 
		if (file_exists(GIBBON_ROOT . 'src/modules/'.$module.'/css/'.$theme.'/module.css'))
		{
			$cssURL = GIBBON_URL . 'src/modules/'.$module.'/css/'.$theme.'/module.css';
			$this->addScript('
<script type="application/javascript" language="javascript">
	$("head").append(\'<link rel="stylesheet" type="text/css" href="'.$cssURL.'" media="screen" />\');
</script>
');
		}
	} 

	/**
	 * get Person
	 * 
	 * @version	11th October 2016
	 * @since	6th September 2016
	 * @param	integer	$id	PersonID
	 * @return	boolean
	 */
	public function getPerson($id = null)
	{
		if (! $this->person instanceof person)
			$this->person = $this->getRecord('person');
		if (! empty($id))
			$this->person->find($id);
		return $this->person ;
	}

	/**
	 * add Script
	 * 
	 * @version	21st September 2016
	 * @since	21st September 2016
	 * @param	string		$script
	 * @return	void
	 */
	public function addScript($script)
	{	
		$script = preg_replace("/<script.*>/", '', $script);
		$script = str_replace('</script>', '', $script);
		$this->session->push('scripts', $script, $this);
	}

	/**
	 * Translation
	 *
	 * @version	23rd September 2016
	 * @since	21st September 2016
	 * @return	void
	 */
	public function __($text, $options = array(), $choice = NULL)
	{
		return $this->getTrans()->__($text, $options, $choice);
	}

	/**
	 * get Translation
	 *
	 * @version	23rd September 2016
	 * @since	23rd September 2016
	 * @return	void
	 */
	public function getTrans()
	{
		if (! $this->trans instanceof trans)
			$this->trans = new trans();
		return $this->trans;
	}

	/**
	 * display Image
	 *
	 * @version	1st October 2016
	 * @since	30th September 2016
	 * @param	string		$fileName	Basename 
	 * @param	string		$alt	Image Alternate
	 * @param	integer		$width
	 * @param	integer		$height
	 * @param	string		$class
	 * @param	string		$onclick
	 * @return	void
	 */
	public function displayImage($fileName, $alt, $width = null, $height = null, $class = null, $onclick = null)
	{
		$fileName = file_exists($this->session->get('theme.path').'img/'.$fileName) ? " src='".$this->session->get('theme.url').'img/'.$fileName."'" : " src='".$this->session->get('theme.defaultURL').'img/'.$fileName."'" ;
		$alt = " alt='".$alt."'";
		$width = ! is_null($width) ? " width='".intval($width)."'" : '' ;
		$height = ! is_null($height) ? " height='".intval($height)."'" : '' ;
		$class = ! is_null($class) ? "  class='".$class."'" : '' ;
		$onclick = ! is_null($onclick) ? "  onClick='".$onclick."'" : '' ;
		echo "<img".$fileName.$alt.$width.$height.$class.$onclick." />" ;
	}

	/**
	 * get Record
	 *
	 * @version	2nd October 2016
	 * @since	1st October 2016
	 * @param	string		$recordName	
	 * @return	Gibbon\Record\$recordName
	 */
	public function getRecord($recordName)
	{
		if (! $this->records instanceof stdClass)
			$this->records = new stdClass();
		$fullName = "\\Gibbon\\Record\\".$recordName ;
		if (isset($this->records->$recordName) && $this->records->$recordName instanceof $fullName)
			return $this->records->$recordName;
		if (! class_exists($fullName))
			$this->dump($fullName, true, true);
		$this->records->$recordName = new $fullName($this);
		return $this->records->$recordName ;
	}

	/**
	 * get Icon
	 *
	 * @version	18th August 2016
	 * @since	25th June 2016
	 * @param	string		$type
	 * @param	string/array		$prompt
	 * @param	string		$imgParameters  (dump anything that would be in a link.
	 * @return	void
	 */
	public function getIcon($type, $title = '', $class = '')
	{
		$type = mb_strtolower($type);
		$links = $this->session->get('theme.settings.links');
		$link = (object) $links[$type];
		$link->title = ' title="'.$this->__($title).'"';
		$link->oldTitle = $this->__($title);
		$link->class = $class;
		$link->oldClass = ! empty($class) ? ' class="'.$class.'"' : '';
		
		$this->render('content.icon', $link);
		return ;
	}

	/**
	 * return Icon
	 *
	 * @version	30th June 2016
	 * @since	30th June 2016
	 * @param	string		$type
	 * @param	string		$prompt
	 * @param	string		$imgParameters  (dump anything that would be in a link.
	 * @return	string
	 */
	public function returnIcon($type, $title = '', $imgParameters = '')
	{
		ob_start();
		$this->getIcon($type, $title, $imgParameters);
		$out2 = ob_get_contents();
		if (! empty($out2))
			ob_end_clean();
		return $out2 ;
	}

	/**
	 * display Scripts
	 *
	 * @version	8th October 2016
	 * @since	8th October 2016
	 * @return	string
	 */
	public function displayScripts()
	{
		if (! is_array($this->session->get('scripts')))
		{
			$this->session->clear('scripts');
			return ;
		}
		foreach($this->session->get('scripts') as $script)
		{ ?>
<script type="text/javascript">
<?php echo $script; ?>
</script>
		<?php
		}
		$this->session->clear('scripts');
	}
}
