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
 *
 * @author	Craig Rayner
 *
 * @version	26th August 2016
 * @since	7th April 2016
 * @package	Gibbon
 * @subpackage	Controller
 */
  
if ( ! defined( 'GIBBON_ROOT' ) )
{
	$path = pathinfo($_SERVER['PHP_SELF']);
	$dr = dirname(dirname(dirname(__FILE__)));
	$dr = rtrim( str_replace("\\", '/', $dr), '/' );
	define("GIBBON_ROOT", $dr . '/'); 
	define ("GIBBON_CONFIG", GIBBON_ROOT . 'config/local/');

	$pageURL = 'http';
	if (isset($_SERVER["HTTPS"])) $pageURL .= "s";
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") 
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].(! empty($path['dirname']) ? $path['dirname'] : '');
	else
		$pageURL .= $_SERVER["SERVER_NAME"].(! empty($path['dirname']) ? $path['dirname'] : '');
	$pageURL = str_replace(array('lib/google/'), '', rtrim($pageURL, '/ ') . '/');
	define('GIBBON_URL', $pageURL);
}

if (! defined('GIBBON_NEW')) define('GIBBON_NEW', true);

require GIBBON_ROOT . 'vendor/autoload.php';

use Gibbon\core\config ;
use Gibbon\core\session ;
use Gibbon\core\module ;
use Gibbon\core\view ;
use Gibbon\core\post ;
use Gibbon\core\sqlConnection ;
use Gibbon\Record\theme ;

//$whoops = new \Whoops\Run ;
//$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
//$whoops->register();

$config = new config();
$session = new session();
$session->set('absoluteURL', rtrim(GIBBON_URL, '/'));
$session->set('absolutePath', rtrim(GIBBON_ROOT, '/'));
$session->set('SQLConnection', 0);
$session->clear('pageAnchors');

//testing option Only  Change the default theme.
if (isset($_GET['template']) && $session->get('installType') === 'Development') 
{
	$tObj = new theme(new view('default.blank'));
	$tObj->switchTemplate($_GET['template']);
}

//Deal with address param q
if (isset($_GET["q"])) {
	$session->set("address", $_GET["q"]);
}
else {
	$session->clear("address") ;
	unset($_GET['q']);
}
$session->clear("module") ;
$session->clear("action") ;
$session->clear("install") ;
$session->set("module", module::getModuleName($session->get("address"))) ;
$session->set("action", module::getActionName($session->get("address"))) ;

if ($config->isInstall()) {
	$session->set('install', true);
	$view = new view('install.html', null, $session, $config, null);
	die();
}
else
{
	$pdo = new sqlConnection();
	$session->clear('install');
	$config->getPDO($pdo);
	$tz = $session->isEmpty("timezone") ? $config->getSettingByScope('System', 'timezone') : $session->get("timezone") ;
	$tz = empty($tz) ? 'UTC' : $tz ;
	date_default_timezone_set($tz);
	$session->set('timezone', $tz);
	new post($pdo, $session, $config);
}

include __DIR__.'/start.php';

class gibbon
{
}
