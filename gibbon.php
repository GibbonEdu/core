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
 * @authur	Craig Rayner
 *
 * @version	13th April 2016
 * @since	7th April 2016
 */

require_once dirname(__FILE__) . '/functions.php';

if ( ! defined( 'GIBBON_ROOT' ) )
{
	$dr = dirname(__FILE__);
	$dr = rtrim( str_replace("\\", '/', $dr), '/' );
	define("GIBBON_ROOT", $dr . '/'); 

	$pageURL = 'http';
	if (isset($_SERVER["HTTPS"])) $pageURL .= "s";
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") 
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].dirname($_SERVER["PHP_SELF"]);
	else
		$pageURL .= $_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"]);
	$pageURL = rtrim($pageURL, '/ ') . '/';
	define('GIBBON_URL', $pageURL);
}

if ( ! defined( 'GIBBON_ROOT' ) )
    define('GIBBON_ROOT', str_replace(array('/src', "\\src"), '', dirname(__FILE__)) . '/');
   
require_once GIBBON_ROOT . 'src/Autoloader.php';

$loader = new Autoloader();

$loader->addNameSpace('Gibbon\\', "src/Gibbon");
$loader->addNameSpace('Library\\', "src/Library");

$loader->register();

if (file_exists(GIBBON_ROOT . "config.php"))
	include GIBBON_ROOT . 'config.php';
else 
{
	if (false === strpos($_SERVER['PHP_SELF'], 'installer/install.php'))  // test if installer already invoked and ignore.
	{
		$URL = GIBBON_URL . "installer/install.php" ;
		header("Location: {$URL}");
	}
}

$session = new Gibbon\session();

class gibbon
{
}


/**
 * Print an Object Alias (Dump)
 *
 * @version	16th February 2015
 * @since	OLD
 * @param	mixed The object to be printed
 * @param	boolean Stop execution after printing object.
 * @param	boolean Full print the Call Trace Stack
 * @return	void
 */
function dump($object, $stop = false, $full = false) 
{
	$caller = debug_backtrace();
	echo "<pre>\n";
	echo $caller[0]['line'].': '.$caller[0]['file'];
	echo "\n</pre>\n";
	echo "<pre>\n";
	print_r($object);
	if ($full) 
		print_r($caller);
	echo "\n</pre>\n";
	if ($stop) 
		trigger_error('Object Print Stop', E_USER_ERROR);
	return ;
}
?>