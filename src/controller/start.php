<?php
/*
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
 * @version	15th September 2016
 * @since	7th April 2016
 * @package	Gibbon
 * @subpackage	Controller
 */

namespace Gibbon\controller ;

use Gibbon\core\view ;
use Gibbon\core\trans ;
use Gibbon\Record\theme ;
use stdClass ;

if (class_exists('gibbon'))
{
	$view = new view('default.blank', array(),  $session, $config, $pdo);
	$config = $view->config;
	$session = $view->session;
	$pdo = $view->pdo;
}
else
{
	include "src/controller/gibbon.php";
	$view = new view('default.blank', array(),  $session, $config, $pdo);
	$config = $view->config;
	$session = $view->session;
	$pdo = $view->pdo;
}

$config->injectView($view);

$version = $config->get('version');
$caching = $config->get('caching');

//Deal with caching
$refreshCache = false ;
if (is_int($session->get("pageLoads"))) {
	$session->set("pageLoads", $session->get("pageLoads") + 1) ;
}
else {
	$session->set("pageLoads", 0) ;
	$refreshCache = true ;
}
if ($config->get('caching') == 0) {
	$refreshCache = true ;
}
else if ($caching > 0 AND is_numeric($caching)) {
	if ($session->get("pageLoads")%$caching == 0) {
		$refreshCache = true ;
	}
}
if ($session->get('installType') === 'Development')
	$refreshCache = true ;
	
$session->set('refreshCache', $refreshCache);

$session->set("cuttingEdgeCode", $config->getSettingByScope("System", "cuttingEdgeCode" )) ;

//Set sidebar values (from the entrySidebar field in gibbonAction and from $_GET variable)
$session->set("sidebarExtra", "") ;
$session->set("sidebarExtraPosition", "") ;
if (isset($_GET["sidebar"]))
	$sidebar = $_GET["sidebar"] ;
else 
	$sidebar = false ;

//Check to see if system settings are set from databases
if ($session->isEmpty("systemSettingsSet")) 
	$session->getSystemSettings($pdo);

trans::setStringReplacementList($view) ;

//Try to autoset user's calendar feed if not set already
if ($session->notEmpty("calendarFeedPersonal") && $session->notEmpty('googleAPIAccessToken')) {
	if ($session->isEmpty("calendarFeedPersonal") &&$session->notEmpty('googleAPIAccessToken')) {
		require_once GIBBON_ROOT . '/vendor/google/apiclient/src/Google/Client.php';
		require_once GIBBON_ROOT . 'vendor/google/apiclient-services/Google/Service/Calendar.php';
		$client2 = new Google_Client();
		$client2->setAccessToken($session->get('googleAPIAccessToken'));
		$service = new Google_Service_Calendar($client2);
		$calendar = $service->calendars->get('primary');
	
		if (! empty($calendar["id"])) {
			$session->set("calendarFeedPersonal", $calendar["id"]) ;
		}
	}
}

//Check for force password reset flag
if ($session->notEmpty("passwordForceReset")) {
	if ($session->get("passwordForceReset")=="Y" AND $q!="preferences.php") {
		$URL = $session->get('AbsoluteURL') . "/index.php?q=preferences.php" ;
		$URL=$URL. "&forceReset=Y" ;
		header("Location: {$URL}") ;
	}
}

if ($session->isEmpty("address") AND ! $sidebar) {
	$dataSidebar=array("action"=>"%" . $session->get("action") . "%", "name"=>$session->get("module")); 
	$sqlSidebar="SELECT gibbonAction.name 
		FROM gibbonAction 
			JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) 
		WHERE gibbonAction.URLList LIKE :action 
			AND entrySidebar='N' 
			AND gibbonModule.name=:name" ;
	$resultSidebar = $pdo->executeQuery($dataSidebar, $sqlSidebar);
	if ($pdo->getQuerySuccess() && $resultSidebar->rowCount( )> 0) 
		$sidebar = "false" ;
}

$session->set('sidebar', $sidebar);

//Set theme
if ($session->get('refreshCache') || $session->isEmpty("theme.ID") || $session->isEmpty('theme.Name') || isset($_GET['template'])) {
	$tObj = new theme($view);
	$tObj->setDefaultTheme();
}
//If still false, show warning, otherwise display page
if (! $session->get("systemSettingsSet")) {
	echo trans::__("System Settings are not set: the system cannot be displayed") ;
}
else 
{
	if (isset($_GET['divert']))
	{
		$params = new stdClass();
		$params->action = $session->get('absolutePath') . $session->get("address");
		new view('post.inject', $params, $session, $config, $pdo);
		trans::writeTranslationMissing();
		die();
	}
    new view('home.html', array(), $session, $config, $pdo);
}
trans::writeTranslationMissing();
die();  // Stop here, or run into old scripts