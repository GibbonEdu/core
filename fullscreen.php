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
*/

//Gibbon system-wide includes
include './gibbon.php';
$_SESSION[$guid]['sidebarExtra'] = '';

//Check to see if system settings are set from databases
if (empty($_SESSION[$guid]['systemSettingsSet'])) {
    getSystemSettings($guid, $connection2);
}
//If still false, show warning, otherwise display page
if (empty($_SESSION[$guid]['systemSettingsSet'])) {
    exit(__($guid, 'System Settings are not set: the system cannot be displayed'));
}

// variables to display
$errors = array();
$warnings = array();
$stylesheets = new \Gibbon\HtmlHelpers\StyleSheets;
$scripts = new \Gibbon\HtmlHelpers\Scripts;
$head_extras = array();

// global variables
$title = $_SESSION[$guid]['organisationNameShort'].' - '.$_SESSION[$guid]['systemName'];
$_SESSION[$guid]['address'] = $_GET['q'];
$_SESSION[$guid]['action'] = getActionName($_SESSION[$guid]['address']);

// get theme
try {
    $theme = getTheme($connection2);
    $_SESSION[$guid]['gibbonThemeID'] = $theme['id'];
    $_SESSION[$guid]['gibbonThemeName'] = $theme['name'];
    $stylesheets->addMultiple($theme['stylesheets']);
    $scripts->addMultiple($theme['scripts']);
} catch (PDOException $e) {
    exit($e->getMessage());
}

// get module
if (isset($_GET['q']) && ($_GET['q'] != '')) {
    $module = getModule($_GET['q']);
    $_SESSION[$guid]['module'] = $module['name'];
    $stylesheets->addMultiple($module['stylesheets']);
    $scripts->addMultiple($module['scripts']);
}

// set default assets
$stylesheets->add('lib/jquery-ui/css/blitzer/jquery-ui.css');
$scripts->addMultiple(array(
    'lib/LiveValidation/livevalidation_standalone.compressed.js',
    'lib/jquery/jquery.js',
    'lib/jquery/jquery-migrate.min.js',
    'lib/jquery-ui/js/jquery-ui.min.js',
    'lib/jquery-ui/i18n/jquery.ui.datepicker-en-GB.js',
    array(
        'content' => '$.datepicker.setDefaults($.datepicker.regional["en-GB"]);',
        'region' => 'head',
        'type' => 'inline',
    ),
    'lib/chained/jquery.chained.min.js',
));

// set google anayltics script, if set
if ($_SESSION[$guid]['analytics'] != '') $head_extras[] = $_SESSION[$guid]['analytics'];

// Render contents
$contents = array();
switch (TRUE) {
    case ($_SESSION[$guid]['address'] == ''):
        $contents[] =
            '<h1>'.
            __($guid, 'There is no content to display').
            '</h1>';
        break;
    case (strstr($_SESSION[$guid]['address'], '..') != false):
        $contents[] =
            "<div class='error'>".
            __($guid, 'Illegal address detected: access denied.').
            '</div>';
        break;
    case is_file('./'.$_SESSION[$guid]['address']):
        ob_start();
        include './'.$_SESSION[$guid]['address'];
        $contents[] = ob_get_contents();
        ob_end_clean();
        break;
    default:
        ob_start();
        include './error.php';
        $contents[] = ob_get_contents();
        ob_end_clean();
}

?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title><?php echo htmlspecialchars($title); ?></title>
			<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
			<meta http-equiv="content-language" content="en"/>
			<meta name="author" content="Ross Parker, International College Hong Kong"/>
			<meta name="robots" content="none"/>
			<link rel="shortcut icon" type="image/x-icon" href="./favicon.ico"/>
			<?php echo $stylesheets->render($_SESSION[$guid]['absoluteURL']); ?>
			<?php echo $scripts->render('head', $_SESSION[$guid]['absoluteURL']); ?>
			<?php foreach ($head_extras as $extra) echo "$extra\n" ?>
		</head>
		<body style='background-image: none'>
			<?php echo implode("\n", $contents); ?>
	        <?php echo $scripts->render('bottom'); ?>
		</body>
	</html>
