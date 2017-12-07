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


// Gibbon system-wide include
include './gibbon.php';

//Deal with caching
if (isset($_SESSION[$guid]['pageLoads'])) {
    ++$_SESSION[$guid]['pageLoads'];
} else {
    $_SESSION[$guid]['pageLoads'] = 0;
}
$cacheLoad = false;
if ($caching == 0) {
    $cacheLoad = true;
} elseif ($caching > 0 and is_numeric($caching)) {
    if ($_SESSION[$guid]['pageLoads'] % $caching == 0) {
        $cacheLoad = true;
    }
}

//Check for cutting edge code
if (isset($_SESSION[$guid]['cuttingEdgeCode']) == false) {
    $_SESSION[$guid]['cuttingEdgeCode'] = getSettingByScope($connection2, 'System', 'cuttingEdgeCode');
}

//Set sidebar values (from the entrySidebar field in gibbonAction and from $_GET variable)
$_SESSION[$guid]['sidebarExtra'] = '';
$_SESSION[$guid]['sidebarExtraPosition'] = '';
if (isset($_GET['sidebar'])) {
    $sidebar = $_GET['sidebar'];
} else {
    $sidebar = '';
}

//Deal with address param q
if (isset($_GET['q'])) {
    $_SESSION[$guid]['address'] = $_GET['q'];
} else {
    $_SESSION[$guid]['address'] = '';
}
$_SESSION[$guid]['module'] = getModuleName($_SESSION[$guid]['address']);
$_SESSION[$guid]['action'] = getActionName($_SESSION[$guid]['address']);
$q = null;
if (isset($_GET['q'])) {
    $q = $_GET['q'];
}

//Check to see if system settings are set from databases
if (@$_SESSION[$guid]['systemSettingsSet'] == false) {
    getSystemSettings($guid, $connection2);
}

// Allow the URL to override system default from the i18l param
if (!empty($_GET['i18n']) && $gibbon->locale->getLocale() != $_GET['i18n']) {
    try {
        $data = array('code' => $_GET['i18n']);
        $sql = "SELECT * FROM gibboni18n WHERE code=:code LIMIT 1";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {}

    if ($result->rowCount() == 1) {
        setLanguageSession($guid, $result->fetch(), false);
        $gibbon->locale->setLocale($_GET['i18n']);
        $gibbon->locale->setTextDomain($pdo);
        $cacheLoad = true;
    }
}

//Try to autoset user's calendar feed if not set already
if (isset($_SESSION[$guid]['calendarFeedPersonal']) and isset($_SESSION[$guid]['googleAPIAccessToken'])) {
    if ($_SESSION[$guid]['calendarFeedPersonal'] == '' and $_SESSION[$guid]['googleAPIAccessToken'] != null) {
        require_once $_SESSION[$guid]['absolutePath'].'/lib/google/google-api-php-client/vendor/autoload.php';
        $client2 = new Google_Client();
        $client2->setAccessToken($_SESSION[$guid]['googleAPIAccessToken']);
        $service = new Google_Service_Calendar($client2);
        $calendar = $service->calendars->get('primary');

        if ($calendar['id'] != '') {
            try {
                $dataCalendar = array('calendarFeedPersonal' => $calendar['id'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sqlCalendar = 'UPDATE gibbonPerson SET calendarFeedPersonal=:calendarFeedPersonal WHERE gibbonPersonID=:gibbonPersonID';
                $resultCalendar = $connection2->prepare($sqlCalendar);
                $resultCalendar->execute($dataCalendar);
            } catch (PDOException $e) {
            }
            $_SESSION[$guid]['calendarFeedPersonal'] = $calendar['id'];
        }
    }
}

//Check for force password reset flag
if (isset($_SESSION[$guid]['passwordForceReset'])) {
    if ($_SESSION[$guid]['passwordForceReset'] == 'Y' and $q != 'preferences.php') {
        $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=preferences.php';
        $URL = $URL.'&forceReset=Y';
        header("Location: {$URL}");
        exit();
    }
}

//Deal with attendance self-registration redirect
if ($_SESSION[$guid]['pageLoads'] == 0 && $_SESSION[$guid]['address'] == '') { //First page load, so proceed
    if (!empty($_SESSION[$guid]['username'])) { //Are we logged in?
        if (getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2) == 'Student') { //Are we a student?
            if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_studentSelfRegister.php')) { //Can we self register?
                //Check to see if student is on site
                $studentSelfRegistrationIPAddresses = getSettingByScope($connection2, 'Attendance', 'studentSelfRegistrationIPAddresses');
                $realIP = getIPAddress();
                if ($studentSelfRegistrationIPAddresses != '' && !is_null($studentSelfRegistrationIPAddresses)) {
                    $inRange = false ;
                    foreach (explode(',', $studentSelfRegistrationIPAddresses) as $ipAddress) {
                        if (trim($ipAddress) == $realIP) {
                            $inRange = true ;
                        }
                    }
                    if ($inRange) {
                        $currentDate = date('Y-m-d');
                        if (isSchoolOpen($guid, $currentDate, $connection2, true)) { //Is school open today
                            //Check for existence of records today
                            try {
                                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'date' => $currentDate);
                                $sql = "SELECT type FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date=:date ORDER BY timestampTaken DESC";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($result->rowCount() == 0) { //No registration yet
                                //Redirect!
                                $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Attendance/attendance_studentSelfRegister.php&redirect=true';
                                $_SESSION[$guid]['pageLoads'] = null;
                                header("Location: {$URL}");
                                exit;
                            }
                        }
                    }
                }
            }
        }
    }
}

if ($_SESSION[$guid]['address'] != '' and $sidebar != true) {
    try {
        $dataSidebar = array('action' => '%'.$_SESSION[$guid]['action'].'%', 'name' => $_SESSION[$guid]['module']);
        $sqlSidebar = "SELECT gibbonAction.name FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonAction.URLList LIKE :action AND entrySidebar='N' AND gibbonModule.name=:name";
        $resultSidebar = $connection2->prepare($sqlSidebar);
        $resultSidebar->execute($dataSidebar);
    } catch (PDOException $e) {
    }
    if ($resultSidebar->rowCount() > 0) {
        $sidebar = 'false';
    }
}

//If still false, show warning, otherwise display page
if ($_SESSION[$guid]['systemSettingsSet'] == false) {
    echo __($guid, 'System Settings are not set: the system cannot be displayed');
} else {
    ?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title>
				<?php
                echo $_SESSION[$guid]['organisationNameShort'].' - '.$_SESSION[$guid]['systemName'];
                if ($_SESSION[$guid]['address'] != '') {
                    if (strstr($_SESSION[$guid]['address'], '..') == false) {
                        if (getModuleName($_SESSION[$guid]['address']) != '') {
                            echo ' - '.__($guid, getModuleName($_SESSION[$guid]['address']));
                        }
                    }
                }
                ?>
			</title>
			<meta charset="utf-8"/>
			<meta name="author" content="Ross Parker, International College Hong Kong"/>

			<link rel="shortcut icon" type="image/x-icon" href="./favicon.ico"/>
			<script type="text/javascript" src="./lib/LiveValidation/livevalidation_standalone.compressed.js"></script>

			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery/jquery.js"></script>
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery/jquery-migrate.min.js"></script>
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-ui/js/jquery-ui.min.js"></script>
			<?php
            if (isset($_SESSION[$guid]['i18n']['code'])) {
                if (is_file($_SESSION[$guid]['absolutePath'].'/lib/jquery-ui/i18n/jquery.ui.datepicker-'.substr($_SESSION[$guid]['i18n']['code'], 0, 2).'.js')) {
                    echo "<script type='text/javascript' src='".$_SESSION[$guid]['absoluteURL'].'/lib/jquery-ui/i18n/jquery.ui.datepicker-'.substr($_SESSION[$guid]['i18n']['code'], 0, 2).".js'></script>";
                    echo "<script type='text/javascript'>$.datepicker.setDefaults($.datepicker.regional['".substr($_SESSION[$guid]['i18n']['code'], 0, 2)."']);</script>";
                } elseif (is_file($_SESSION[$guid]['absolutePath'].'/lib/jquery-ui/i18n/jquery.ui.datepicker-'.str_replace('_', '-', $_SESSION[$guid]['i18n']['code']).'.js')) {
                    echo "<script type='text/javascript' src='".$_SESSION[$guid]['absoluteURL'].'/lib/jquery-ui/i18n/jquery.ui.datepicker-'.str_replace('_', '-', $_SESSION[$guid]['i18n']['code']).".js'></script>";
                    echo "<script type='text/javascript'>$.datepicker.setDefaults($.datepicker.regional['".str_replace('_', '-', $_SESSION[$guid]['i18n']['code'])."']);</script>";
                }
            }
   			?>
			<script type="text/javascript">$(function() { $( document ).tooltip({  show: 800, hide: false, content: function () { return $(this).prop('title')}, position: { my: "center bottom-20", at: "center top", using: function( position, feedback ) { $( this ).css( position ); $( "<div>" ).addClass( "arrow" ).addClass( feedback.vertical ).addClass( feedback.horizontal ).appendTo( this ); } } }); });</script>
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-jslatex/jquery.jslatex.js"></script>
			<script type="text/javascript">$(function () { $(".latex").latex();});</script>
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-form/jquery.form.js"></script>
			<link rel="stylesheet" href="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-ui/css/blitzer/jquery-ui.css" type="text/css" media="screen" />
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/chained/jquery.chained.min.js"></script>
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/thickbox/thickbox-compressed.js"></script>
			<script type="text/javascript"> var tb_pathToImage="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/thickbox/loadingAnimation.gif"</script>
			<link rel="stylesheet" href="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/thickbox/thickbox.css" type="text/css" media="screen" />
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-autosize/jquery.autosize.min.js"></script>
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-sessionTimeout/jquery.sessionTimeout.min.js"></script>
            <script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-timepicker/jquery.timepicker.min.js"></script>
            <link rel="stylesheet" href="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-timepicker/jquery.timepicker.css" type="text/css" media="screen" />
            <script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-cropit/exif.js"></script>
            <script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-cropit/jquery.cropit.js"></script>
            <script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/assets/js/core.js?v=<?php echo $version; ?>"></script>
			<?php
            if (isset($_SESSION[$guid]['username'])) {
                $sessionDuration = getSettingByScope($connection2, 'System', 'sessionDuration');
                if (is_numeric($sessionDuration) == false) {
                    $sessionDuration = 1200;
                }
                if ($sessionDuration < 1200) {
                    $sessionDuration = 1200;
                }
                ?>
				<script type="text/javascript">
					$(document).ready(function(){
						$.sessionTimeout({
							message: '<?php echo __($guid, 'Your session is about to expire: you will be logged out shortly.') ?>',
							keepAliveUrl: 'keepAlive.php' ,
							redirUrl: 'logout.php?timeout=true',
							logoutUrl: 'logout.php' ,
							warnAfter: <?php echo $sessionDuration * 1000 ?>,
							redirAfter: <?php echo($sessionDuration * 1000) + 600000 ?>
			 			});
					});
				</script>
			<?php

            }
            //Set theme
            if ($cacheLoad or $_SESSION[$guid]['themeCSS'] == '' or isset($_SESSION[$guid]['themeJS']) == false or $_SESSION[$guid]['gibbonThemeID'] == '' or $_SESSION[$guid]['gibbonThemeName'] == '') {
                $_SESSION[$guid]['themeCSS'] = "<link rel='stylesheet' type='text/css' href='./themes/Default/css/main.css?v=".$version."' />";
                if ($_SESSION[$guid]['i18n']['rtl'] == 'Y') {
                    $_SESSION[$guid]['themeCSS'] .= "<link rel='stylesheet' type='text/css' href='./themes/Default/css/main_rtl.css?v=".$version."' />";
                }
                $_SESSION[$guid]['themeJS'] = "<script type='text/javascript' src='./themes/Default/js/common.js?v=".$version."'></script>";
                $_SESSION[$guid]['gibbonThemeID'] = '001';
                $_SESSION[$guid]['gibbonThemeName'] = 'Default';
                $_SESSION[$guid]['gibbonThemeAuthor'] = '';
                $_SESSION[$guid]['gibbonThemeURL'] = '';
                try {
                    if (isset($_SESSION[$guid]['gibbonThemeIDPersonal'])) {
                        $dataTheme = array('gibbonThemeIDPersonal' => $_SESSION[$guid]['gibbonThemeIDPersonal']);
                        $sqlTheme = 'SELECT * FROM gibbonTheme WHERE gibbonThemeID=:gibbonThemeIDPersonal';
                    } else {
                        $dataTheme = array();
                        $sqlTheme = "SELECT * FROM gibbonTheme WHERE active='Y'";
                    }
                    $resultTheme = $connection2->prepare($sqlTheme);
                    $resultTheme->execute($dataTheme);
                    if ($resultTheme->rowCount() == 1) {
                        $rowTheme = $resultTheme->fetch();
                        $themeVersion = ($rowTheme['name'] != 'Default')? $rowTheme['version'] : $version;
                        $_SESSION[$guid]['themeCSS'] = "<link rel='stylesheet' type='text/css' href='./themes/".$rowTheme['name']."/css/main.css?v=".$themeVersion."' />";
                        if ($_SESSION[$guid]['i18n']['rtl'] == 'Y') {
                            $_SESSION[$guid]['themeCSS'] .= "<link rel='stylesheet' type='text/css' href='./themes/".$rowTheme['name']."/css/main_rtl.css?v=".$themeVersion."' />";
                        }
                        $_SESSION[$guid]['themeJS'] = "<script type='text/javascript' src='./themes/".$rowTheme['name']."/js/common.js?v=".$themeVersion."'></script>";
                        $_SESSION[$guid]['gibbonThemeID'] = $rowTheme['gibbonThemeID'];
                        $_SESSION[$guid]['gibbonThemeName'] = $rowTheme['name'];
                        $_SESSION[$guid]['gibbonThemeAuthor'] = $rowTheme['author'];
                        $_SESSION[$guid]['gibbonThemeURL'] = $rowTheme['url'];
                    }
                } catch (PDOException $e) {
                    echo "<div class='error'>";
                    echo $e->getMessage();
                    echo '</div>';
                }
            }

    		echo $_SESSION[$guid]['themeCSS'];
    		echo $_SESSION[$guid]['themeJS'];

            //Set module CSS & JS
            if (isset($_GET['q'])) {
                if ($_GET['q'] != '') {
                    $moduleVersion = $version;
                    if (file_exists('./modules/'.$_SESSION[$guid]['module'].'/version.php')){
                        include('./modules/'.$_SESSION[$guid]['module'].'/version.php');
                    }

                    $moduleCSS = "<link rel='stylesheet' type='text/css' href='./modules/".$_SESSION[$guid]['module']."/css/module.css?v=".$moduleVersion."' />";
                    $moduleJS = "<script type='text/javascript' src='./modules/".$_SESSION[$guid]['module']."/js/module.js?v=".$moduleVersion."'></script>";
                    echo $moduleCSS;
                    echo $moduleJS;
                }
            }

            //Set personalised background, if permitted
            if ($personalBackground = getSettingByScope($connection2, 'User Admin', 'personalBackground') == 'Y' and isset($_SESSION[$guid]['personalBackground'])) {
                if ($_SESSION[$guid]['personalBackground'] != '') {
                    echo '<style type="text/css">';
                    echo 'body {';
                    echo 'background: url("'.$_SESSION[$guid]['personalBackground'].'") repeat scroll center top #A88EDB!important;';
                    echo '}';
                    echo '</style>';
                }
            }


            //Initialise tinymce
            ?>
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/tinymce/tinymce.min.js"></script>
			<script type="text/javascript">
			tinymce.init({
				selector: "div#editorcontainer textarea",
				width: '738px',
				menubar : false,
				toolbar: 'bold, italic, underline,forecolor,backcolor,|,alignleft, aligncenter, alignright, alignjustify, |, formatselect, fontselect, fontsizeselect, |, table, |, bullist, numlist,outdent, indent, |, link, unlink, image, media, hr, charmap, subscript, superscript, |, cut, copy, paste, undo, redo, fullscreen',
				plugins: 'table, template, paste, visualchars, link, template, textcolor, hr, charmap, fullscreen',
			 	statusbar: false,
			 	valid_elements: '<?php echo getSettingByScope($connection2, 'System', 'allowableHTML') ?>',
                invalid_elements: '',
                apply_source_formatting : true,
			 	browser_spellcheck: true,
			 	convert_urls: false,
			 	relative_urls: false,
                default_link_target: "_blank"
			 });
			</script>
			<style>
				div.mce-listbox button, div.mce-menubtn button { padding-top: 2px!important ; padding-bottom: 2px!important }
			</style>
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-tokeninput/src/jquery.tokeninput.js"></script>
			<link rel="stylesheet" href="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/jquery-tokeninput/styles/token-input-facebook.css" type="text/css" />

			<?php
            //Analytics setting
            if ($_SESSION[$guid]['analytics'] != '') {
                echo $_SESSION[$guid]['analytics'];
            }

    	?>
		</head>
		<body>
			<?php
            //Get house logo and set session variable, only on first load after login (for performance)
            if ($_SESSION[$guid]['pageLoads'] == 0 and isset($_SESSION[$guid]['username']) and $_SESSION[$guid]['gibbonHouseID'] != '') {
                try {
                    $dataHouse = array('gibbonHouseID' => $_SESSION[$guid]['gibbonHouseID']);
                    $sqlHouse = 'SELECT logo, name FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
                    $resultHouse = $connection2->prepare($sqlHouse);
                    $resultHouse->execute($dataHouse);
                } catch (PDOException $e) {
                }

                if ($resultHouse->rowCount() == 1) {
                    $rowHouse = $resultHouse->fetch();
                    $_SESSION[$guid]['gibbonHouseIDLogo'] = $rowHouse['logo'];
                    $_SESSION[$guid]['gibbonHouseIDName'] = $rowHouse['name'];
                }
            }

            //Show warning if not in the current school year
            if (isset($_SESSION[$guid]['username'])) {
                if ($_SESSION[$guid]['gibbonSchoolYearID'] != $_SESSION[$guid]['gibbonSchoolYearIDCurrent']) {
                    echo "<div style='margin: 10px auto; width:1101px;' class='warning'>";
                    echo '<b><u>'.sprintf(__($guid, 'Warning: you are logged into the system in school year %1$s, which is not the current year.'), $_SESSION[$guid]['gibbonSchoolYearName']).'</b></u>'.__($guid, 'Your data may not look quite right (for example, students who have left the school will not appear in previous years), but you should be able to edit information from other years which is not available in the current year.');
                    echo '</div>';
                }
            }
    		?>

			<div id="wrapOuter">
				<?php
                if (@$_SESSION[$guid]['gibbonHouseIDLogo'] == '') {
                    echo "<div class='minorLinks minorLinksTopGap'>";
                } else {
                    echo "<div class='minorLinks'>";
                }
				echo getMinorLinks($connection2, $guid, $cacheLoad);
				echo '</div>';?>
				<div id="wrap">
					<div id="header">
						<div id="header-logo">
							<a href='<?php echo $_SESSION[$guid]['absoluteURL'] ?>'><img height='100px' width='400px' class="logo" alt="Logo" src="<?php echo $_SESSION[$guid]['absoluteURL'].'/'.$_SESSION[$guid]['organisationLogo'];?>"/></a>
						</div>
						<div id="header-finder">
							<?php
                            //Show student and staff quick finder
                            if (isset($_SESSION[$guid]['username'])) {
                                if ($cacheLoad) {
                                    $_SESSION[$guid]['studentFastFinder'] = getFastFinder($connection2, $guid);
                                }
                                if (isset($_SESSION[$guid]['studentFastFinder'])) {
                                    echo $_SESSION[$guid]['studentFastFinder'];
                                }
                            }
   				 			?>
						</div>
						<div id="header-menu">
							<?php
                            //Get main menu
                            $mainMenu = new Gibbon\menuMain($gibbon, $pdo);
                            if ($cacheLoad) {
                                $mainMenu->setMenu();
                            }

                            // Display the main menu
							echo $mainMenu->getMenu();

                            //Display notification temp_array
                            echo "<div class='notificationTray'>";
                                echo getNotificationTray($connection2, $guid, $cacheLoad);
                            echo "</div>";

							?>
						</div>
					</div>
					<div id="content-wrap">
						<?php
                        //Allow for wide pages (no sidebar)
                        if ($sidebar == 'false') {
                            echo "<div id='content-wide'>";

                            //Invoke and show Module Menu
                            $menuModule = new Gibbon\menuModule($gibbon, $pdo);

                            // Display the module menu
                            echo $menuModule->getMenu('mini');

                            //No closing </div> required here
                        } else {
                            echo "<div id='content'>";
                        }

						if ($_SESSION[$guid]['address'] == '') {
                            $returns = array();
                        	$returns['success1'] = __($guid, 'Password reset was successful: you may now log in.');

                            if ($gibbon->locale->getLocale() == 'zh_HK') {
                                $returns['success2'] = __($guid, '帳號已確認成功，請查看  閣下之電郵以獲取登入資訊。倘若未有收到確認電郵，請查看電子郵箱中的「垃圾郵件」。');
                                $returns['success3'] = __($guid, '相片上載成功。  閣下之帳戶已確認成功，歡迎登入。');
                            } else {
                                $returns['success2'] = __($guid, 'Account confirmation was successful: you may now log in. Please check your email for login details. If you do not receive an email within a few minutes please check your spam folder as some emails may end up there.');
                                $returns['success3'] = __($guid, 'Photo upload successful. Your account has already been confirmed: you may now log in with your existing account details.');
                            }

                        	if (isset($_GET['return'])) {
                        	    returnProcess($guid, $_GET['return'], null, $returns);
                        	}
						}

                        //Show index page Content
                            if ($_SESSION[$guid]['address'] == '') {
                                //Welcome message
                                if (isset($_SESSION[$guid]['username']) == false) {
                                    //Create auto timeout message
                                    if (isset($_GET['timeout'])) {
                                        if ($_GET['timeout'] == 'true') {
                                            echo "<div class='warning'>";
                                            echo __($guid, 'Your session expired, so you were automatically logged out of the system.');
                                            echo '</div>';
                                        }
                                    }

                                    echo '<h2>';
                                    echo __($guid, 'Welcome');
                                    echo '</h2>';
                                    echo '<p>';
                                    echo $_SESSION[$guid]['indexText'];
                                    echo '</p>';

                                    //Student public applications permitted?
                                    $publicApplications = getSettingByScope($connection2, 'Application Form', 'publicApplications');
                                    if ($publicApplications == 'Y') {
                                        echo "<h2 style='margin-top: 30px'>";
                                        echo __($guid, 'Student Applications');
                                        echo '</h2>';
                                        echo '<p>';
                                        echo sprintf(__($guid, 'Parents of students interested in study at %1$s may use our %2$s online form%3$s to initiate the application process.'), $_SESSION[$guid]['organisationName'], "<a href='".$_SESSION[$guid]['absoluteURL']."/?q=/modules/Students/applicationForm.php'>", '</a>');
                                        echo '</p>';
                                    }

                                    //Staff public applications permitted?
                                    $staffApplicationFormPublicApplications = getSettingByScope($connection2, 'Staff Application Form', 'staffApplicationFormPublicApplications');
                                    if ($staffApplicationFormPublicApplications == 'Y') {
                                        echo "<h2 style='margin-top: 30px'>";
                                        echo __($guid, 'Staff Applications');
                                        echo '</h2>';
                                        echo '<p>';
                                        echo sprintf(__($guid, 'Individuals interested in working at %1$s may use our %2$s online form%3$s to view job openings and begin the recruitment process.'), $_SESSION[$guid]['organisationName'], "<a href='".$_SESSION[$guid]['absoluteURL']."/?q=/modules/Staff/applicationForm_jobOpenings_view.php'>", '</a>');
                                        echo '</p>';
                                    }

                                    //Public departments permitted?
                                    $makeDepartmentsPublic = getSettingByScope($connection2, 'Departments', 'makeDepartmentsPublic');
                                    if ($makeDepartmentsPublic == 'Y') {
                                        echo "<h2 style='margin-top: 30px'>";
                                        echo __($guid, 'Departments');
                                        echo '</h2>';
                                        echo '<p>';
                                        echo sprintf(__($guid, 'Please feel free to %1$sbrowse our departmental information%2$s, to learn more about %3$s.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/?q=/modules/Departments/departments.php'>", '</a>', $_SESSION[$guid]['organisationName']);
                                        echo '</p>';
                                    }

                                    //Public units permitted?
                                    $makeUnitsPublic = getSettingByScope($connection2, 'Planner', 'makeUnitsPublic');
                                    if ($makeUnitsPublic == 'Y') {
                                        echo "<h2 style='margin-top: 30px'>";
                                        echo __($guid, 'Learn With Us');
                                        echo '</h2>';
                                        echo '<p>';
                                        echo sprintf(__($guid, 'We are sharing some of our units of study with members of the public, so you can learn with us. Feel free to %1$sbrowse our public units%2$s.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/?q=/modules/Planner/units_public.php&sidebar=false'>", '</a>', $_SESSION[$guid]['organisationName']);
                                        echo '</p>';
                                    }

                                    //Get any elements hooked into public home page, checking if they are turned on
                                    try {
                                        $dataHook = array();
                                        $sqlHook = "SELECT * FROM gibbonHook WHERE type='Public Home Page' ORDER BY name";
                                        $resultHook = $connection2->prepare($sqlHook);
                                        $resultHook->execute($dataHook);
                                    } catch (PDOException $e) {
                                    }
                                    while ($rowHook = $resultHook->fetch()) {
                                        $options = unserialize(str_replace("'", "\'", $rowHook['options']));
                                        $check = getSettingByScope($connection2, $options['toggleSettingScope'], $options['toggleSettingName']);
                                        if ($check == $options['toggleSettingValue']) { //If its turned on, display it
                                            echo "<h2 style='margin-top: 30px'>";
                                            echo $options['title'];
                                            echo '</h2>';
                                            echo '<p>';
                                            echo stripslashes($options['text']);
                                            echo '</p>';
                                        }
                                    }
                                } else {
                                    //Custom content loader
                                    if (isset($_SESSION[$guid]['index_custom.php']) == false) {
                                        if (is_file('./index_custom.php')) {
                                            $_SESSION[$guid]['index_custom.php'] = include './index_custom.php';
                                        } else {
                                            $_SESSION[$guid]['index_custom.php'] = null;
                                        }
                                    }
                                    if (isset($_SESSION[$guid]['index_custom.php'])) {
                                        echo $_SESSION[$guid]['index_custom.php'];
                                    }

                                    //DASHBOARDS!
                                    //Get role category
                                    $category = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
                                    if ($category == false) {
                                        echo "<div class='error'>";
                                        echo __($guid, 'Your current role type cannot be determined.');
                                        echo '</div>';
                                    } elseif ($category == 'Parent') { //Display Parent Dashboard
                                        $count = 0;
                                        try {
                                            $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                            $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }

                                        if ($result->rowCount() > 0) {
                                            //Get child list
                                            $count = 0;
                                            $options = '';
                                            $students = array();
                                            while ($row = $result->fetch()) {
                                                try {
                                                    $dataChild = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonFamilyID' => $row['gibbonFamilyID']);
                                                    $sqlChild = "SELECT gibbonPerson.gibbonPersonID, image_240, surname, preferredName, dateStart, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, gibbonRollGroup.website AS rollGroupWebsite, gibbonRollGroup.gibbonRollGroupID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName ";
                                                    $resultChild = $connection2->prepare($sqlChild);
                                                    $resultChild->execute($dataChild);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                                while ($rowChild = $resultChild->fetch()) {
                                                    $students[$count][0] = $rowChild['surname'];
                                                    $students[$count][1] = $rowChild['preferredName'];
                                                    $students[$count][2] = $rowChild['yearGroup'];
                                                    $students[$count][3] = $rowChild['rollGroup'];
                                                    $students[$count][4] = $rowChild['gibbonPersonID'];
                                                    $students[$count][5] = $rowChild['image_240'];
                                                    $students[$count][6] = $rowChild['dateStart'];
                                                    $students[$count][7] = $rowChild['gibbonRollGroupID'];
                                                    $students[$count][8] = $rowChild['rollGroupWebsite'];
                                                    ++$count;
                                                }
                                            }
                                        }

                                        if ($count > 0) {
                                            echo '<h2>';
                                            echo __($guid, 'Parent Dashboard');
                                            echo '</h2>';
                                            include './modules/Timetable/moduleFunctions.php';

                                            for ($i = 0; $i < $count; ++$i) {
                                                echo '<h4>';
                                                echo $students[$i][1].' '.$students[$i][0];
                                                echo '</h4>';

                                                echo "<div style='margin-right: 1%; float:left; width: 15%; text-align: center'>";
                                                echo getUserPhoto($guid, $students[$i][5], 75);
                                                echo "<div style='height: 5px'></div>";
                                                echo "<span style='font-size: 70%'>";
                                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$students[$i][4]."'>".__($guid, 'Student Profile').'</a><br/>';
                                                if (isActionAccessible($guid, $connection2, '/modules/Roll Groups/rollGroups_details.php')) {
                                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID='.$students[$i][7]."'>".__($guid, 'Roll Group').' ('.$students[$i][3].')</a><br/>';
                                                }
                                                if ($students[$i][8] != '') {
                                                    echo "<a target='_blank' href='".$students[$i][8]."'>".$students[$i][3].' '.__($guid, 'Website').'</a>';
                                                }

                                                echo '</span>';
                                                echo '</div>';
                                                echo "<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 83%'>";
                                                $dashboardContents = getParentDashboardContents($connection2, $guid, $students[$i][4]);
                                                if ($dashboardContents == false) {
                                                    echo "<div class='error'>";
                                                    echo __($guid, 'There are no records to display.');
                                                    echo '</div>';
                                                } else {
                                                    echo $dashboardContents;
                                                }
                                                echo '</div>';
                                            }
                                        }
                                    } elseif ($category == 'Student') { //Display Student Dashboard
                                        echo '<h2>';
                                        echo __($guid, 'Student Dashboard');
                                        echo '</h2>';
                                        echo "<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 100%'>";
                                        $dashboardContents = getStudentDashboardContents($connection2, $guid, $_SESSION[$guid]['gibbonPersonID']);
                                        if ($dashboardContents == false) {
                                            echo "<div class='error'>";
                                            echo __($guid, 'There are no records to display.');
                                            echo '</div>';
                                        } else {
                                            echo $dashboardContents;
                                        }
                                        echo '</div>';
                                    } elseif ($category == 'Staff') { //Display Staff Dashboard
                                        $smartWorkflowHelp = getSmartWorkflowHelp($connection2, $guid);
                                        if ($smartWorkflowHelp != false) {
                                            echo $smartWorkflowHelp;
                                        }

                                        echo '<h2>';
                                        echo __($guid, 'Staff Dashboard');
                                        echo '</h2>';
                                        echo "<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 100%'>";
                                        $dashboardContents = getStaffDashboardContents($connection2, $guid, $_SESSION[$guid]['gibbonPersonID']);
                                        if ($dashboardContents == false) {
                                            echo "<div class='error'>";
                                            echo __($guid, 'There are no records to display.');
                                            echo '</div>';
                                        } else {
                                            echo $dashboardContents;
                                        }
                                        echo '</div>';
                                    }
                                }
                            } else {
                                if (strstr($_SESSION[$guid]['address'], '..') || strstr($_SESSION[$guid]['address'], 'installer') || strstr($_SESSION[$guid]['address'], 'uploads') || in_array($_SESSION[$guid]['address'] , array('index.php', '/index.php', './index.php')) || substr($_SESSION[$guid]['address'], -11) == '//index.php' || substr($_SESSION[$guid]['address'], -11) == './index.php') {
                                    echo "<div class='error'>";
                                    echo __($guid, 'Illegal address detected: access denied.');
                                    echo '</div>';
                                } else {
                                    if (is_file('./'.$_SESSION[$guid]['address'])) {
                                        //Include the page
                                        include './'.$_SESSION[$guid]['address'];
                                    } else {
                                        include './error.php';
                                    }
                                }
                            }
   				 			?>
						</div>
						<?php
                        if ($sidebar != 'false') {
                            ?>
							<div id="sidebar">
								<?php sidebar($gibbon, $pdo);?>
							</div>
							<br style="clear: both">
							<?php
                        }
   				 		?>
					</div>
					<div id="footer">
						<?php echo __($guid, 'Powered by') ?> <a target='_blank' href="https://gibbonedu.org">Gibbon</a> v<?php echo $version ?><?php if ($_SESSION[$guid]['cuttingEdgeCode'] == 'Y') { echo 'dev';} ?> | &#169; <a target='_blank' href="http://rossparker.org">Ross Parker</a> 2010-<?php echo date('Y') ?><br/>
						<span style='font-size: 90%; '>
							<?php echo __($guid, 'Created under the') ?> <a target='_blank' href="https://www.gnu.org/licenses/gpl.html">GNU GPL</a> at <a target='_blank' href='http://www.ichk.edu.hk'>ICHK</a> | <a target='_blank' href='https://gibbonedu.org/about/#ourTeam'><?php echo __($guid, 'Credits'); ?></a><br/>
							<?php
                                $seperator = false;
								$thirdLine = false;
								if ($_SESSION[$guid]['i18n']['maintainerName'] != '' and $_SESSION[$guid]['i18n']['maintainerName'] != 'Gibbon') {
									if ($_SESSION[$guid]['i18n']['maintainerWebsite'] != '') {
										echo __($guid, 'Translation led by')." <a target='_blank' href='".$_SESSION[$guid]['i18n']['maintainerWebsite']."'>".$_SESSION[$guid]['i18n']['maintainerName'].'</a>';
									} else {
										echo __($guid, 'Translation led by').' '.$_SESSION[$guid]['i18n']['maintainerName'];
									}
									$seperator = true;
									$thirdLine = true;
								}
								if ($_SESSION[$guid]['gibbonThemeName'] != 'Default' and $_SESSION[$guid]['gibbonThemeAuthor'] != '') {
									if ($seperator) {
										echo ' | ';
									}
									if ($_SESSION[$guid]['gibbonThemeURL'] != '') {
										echo __($guid, 'Theme by')." <a target='_blank' href='".$_SESSION[$guid]['gibbonThemeURL']."'>".$_SESSION[$guid]['gibbonThemeAuthor'].'</a>';
									} else {
										echo __($guid, 'Theme by').' '.$_SESSION[$guid]['gibbonThemeAuthor'];
									}
									$thirdLine = true;
								}
								if ($thirdLine == false) {
									echo '<br/>';
								}
								?>
						</span>
						<img style='z-index: 9999; margin-top: -82px; margin-left: 850px; opacity: 0.8' alt='Logo Small' src='./themes/<?php echo $_SESSION[$guid]['gibbonThemeName'] ?>/img/logoFooter.png'/>
					</div>
				</div>
			</div>
		</body>
	</html>
	<?php

}
?>
