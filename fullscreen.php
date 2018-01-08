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

@session_start();
$_SESSION[$guid]['sidebarExtra'] = '';

//Check to see if system settings are set from databases
if (empty($_SESSION[$guid]['systemSettingsSet'])) {
    getSystemSettings($guid, $connection2);
}
//If still false, show warning, otherwise display page
if (empty($_SESSION[$guid]['systemSettingsSet'])) {
    echo __($guid, 'System Settings are not set: the system cannot be displayed');
} else {
    ?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title><?php echo $_SESSION[$guid]['organisationNameShort'].' - '.$_SESSION[$guid]['systemName'] ?></title>
			<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
			<meta http-equiv="content-language" content="en"/>
			<meta name="author" content="Ross Parker, International College Hong Kong"/>
			<meta name="robots" content="none"/>

			<?php
            //Set theme
            $themeCSS = "<link rel='stylesheet' type='text/css' href='./themes/Default/css/main.css' />";
    if ($_SESSION[$guid]['i18n']['rtl'] == 'Y') {
        $themeCSS .= "<link rel='stylesheet' type='text/css' href='./themes/Default/css/main_rtl.css' />";
    }
    $themeJS = "<script type='text/javascript' src='./themes/Default/js/common.js'></script>";
    $_SESSION[$guid]['gibbonThemeID'] = '001';
    $_SESSION[$guid]['gibbonThemeName'] = 'Default';
    $_SESSION[$guid]['module'] = getModuleName($_SESSION[$guid]['address']);
    $_SESSION[$guid]['action'] = getActionName($_SESSION[$guid]['address']);
    try {
        if (@$_SESSION[$guid]['gibbonThemeIDPersonal'] != null) {
            $dataTheme = array('gibbonThemeIDPersonal' => $_SESSION[$guid]['gibbonThemeIDPersonal']);
            $sqlTheme = 'SELECT * FROM gibbonTheme WHERE gibbonThemeID=:gibbonThemeIDPersonal';
        } else {
            $dataTheme = array();
            $sqlTheme = "SELECT * FROM gibbonTheme WHERE active='Y'";
        }
        $resultTheme = $connection2->prepare($sqlTheme);
        $resultTheme->execute($dataTheme);
        if (count($resultTheme) == 1) {
            $rowTheme = $resultTheme->fetch();
            $themeCSS = "<link rel='stylesheet' type='text/css' href='./themes/".$rowTheme['name']."/css/main.css' />";
            if ($_SESSION[$guid]['i18n']['rtl'] == 'Y') {
                $themeCSS .= "<link rel='stylesheet' type='text/css' href='./themes/".$rowTheme['name']."/css/main_rtl.css' />";
            }
            $themeCJS = "<script type='text/javascript' src='./themes/".$rowTheme['name']."/js/common.js'></script>";
            $_SESSION[$guid]['gibbonThemeID'] = $rowTheme['gibbonThemeID'];
            $_SESSION[$guid]['gibbonThemeName'] = $rowTheme['name'];
        }
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo $e->getMessage();
        echo '</div>';
    }
    echo $themeCSS;
    echo $themeJS;

            //Set module CSS & JS
            if (isset($_GET['q'])) {
                if ($_GET['q'] != '') {
                    $moduleCSS = "<link rel='stylesheet' type='text/css' href='./modules/".$_SESSION[$guid]['module']."/css/module.css' />";
                    $moduleJS = "<script type='text/javascript' src='./modules/".$_SESSION[$guid]['module']."/js/module.js'></script>";
                    echo $moduleCSS;
                    echo $moduleJS;
                }
            }
            ?>

			<link rel="shortcut icon" type="image/x-icon" href="./favicon.ico"/>
			<script type="text/javascript" src="./lib/LiveValidation/livevalidation_standalone.compressed.js"></script>

			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery/jquery.js"></script>
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery/jquery-migrate.min.js"></script>
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-ui/js/jquery-ui.min.js"></script>
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-ui/i18n/jquery.ui.datepicker-en-GB.js"></script>
			<script type="text/javascript">
				$.datepicker.setDefaults($.datepicker.regional['en-GB']);
			</script>
			<link rel="stylesheet" href="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-ui/css/blitzer/jquery-ui.css" type="text/css" media="screen" />
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/chained/jquery.chained.min.js"></script>

			<?php
            if ($_SESSION[$guid]['analytics'] != '') {
                echo $_SESSION[$guid]['analytics'];
            }
    ?>
		</head>
		<body style='background-image: none'>
			<?php
            $_SESSION[$guid]['address'] = $_GET['q'];
    if ($_SESSION[$guid]['address'] == '') {
        echo '<h1>';
        echo __($guid, 'There is no content to display');
        echo '</h1>';
    } else {
        if (strstr($_SESSION[$guid]['address'], '..') != false) {
            echo "<div class='error'>";
            echo __($guid, 'Illegal address detected: access denied.');
            echo '</div>';
        } else {
            if (is_file('./'.$_SESSION[$guid]['address'])) {
                include './'.$_SESSION[$guid]['address'];
            } else {
                include './error.php';
            }
        }
    }
    ?>
		</body>
	</html>
	<?php

}
?>
