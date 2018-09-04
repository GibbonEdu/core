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
$sidebar = isset($_GET['sidebar']) ? $_GET['sidebar'] : '';

//Check to see if system settings are set from databases
if (@$_SESSION[$guid]['systemSettingsSet'] == false) {
    getSystemSettings($guid, $connection2);
}
// If still false, only show warning and exit.
if ($_SESSION[$guid]['systemSettingsSet'] == false) {
    exit(__($guid, 'System Settings are not set: the system cannot be displayed'));
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
    if ($_SESSION[$guid]['passwordForceReset'] == 'Y' and $_SESSION[$guid]['address'] != 'preferences.php') {
        $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=preferences.php';
        $URL = $URL.'&forceReset=Y';
        header("Location: {$URL}");
        exit();
    }
}

// USER REDIRECTS
if ($_SESSION[$guid]['pageLoads'] == 0 && $_SESSION[$guid]['address'] == '') { //First page load, so proceed
    if (!empty($_SESSION[$guid]['username'])) { //Are we logged in?
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

        // Deal with attendance self-registration redirect
        if ($roleCategory == 'Student') { //Are we a student?
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
                                $errors[] = $e->getMessage();
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

        // Deal with Data Updater redirect (if required updates are enabled)
        $requiredUpdates = getSettingByScope($connection2, 'Data Updater', 'requiredUpdates');
        if ($requiredUpdates == 'Y') { 
            if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_updates.php')) { //Can we update data?
                $redirectByRoleCategory = getSettingByScope($connection2, 'Data Updater', 'redirectByRoleCategory');
                $redirectByRoleCategory = explode(',', $redirectByRoleCategory);

                if (in_array($roleCategory, $redirectByRoleCategory)) { //Are we the right role category?
                    $gateway = new Gibbon\Domain\DataUpdater\DataUpdaterGateway($pdo);

                    $updatesRequiredCount = $gateway->countAllRequiredUpdatesByPerson($_SESSION[$guid]['gibbonPersonID']);
                    if ($updatesRequiredCount > 0) {
                        $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Updater/data_updates.php&redirect=true';
                        $_SESSION[$guid]['pageLoads'] = null;
                        header("Location: {$URL}");
                        exit;
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


// Set module menu
$moduleMenu = null;
if ($sidebar == 'false') {
    //Invoke and show Module Menu
    $menuModule = new Gibbon\MenuModule($gibbon, $pdo);

    // Display the module menu
    $moduleMenu = $menuModule->getMenu('mini');
}

// Set page title
$title = $_SESSION[$guid]['organisationNameShort'].' - '.$_SESSION[$guid]['systemName'];
if ($_SESSION[$guid]['address'] != '') {
    if (strstr($_SESSION[$guid]['address'], '..') == false) {
        if (getModuleName($_SESSION[$guid]['address']) != '') {
            $title .= ' - '.__($guid, getModuleName($_SESSION[$guid]['address']));
        }
    }
}

// Set page scripts
$datepicker_locale = is_file($_SESSION[$guid]['absolutePath'].'/lib/jquery-ui/i18n/jquery.ui.datepicker-'.substr($_SESSION[$guid]['i18n']['code'], 0, 2).'.js') ?
    substr($_SESSION[$guid]['i18n']['code'], 0, 2) :
    str_replace('_', '-', $_SESSION[$guid]['i18n']['code']);
$scripts = array(
    'lib/LiveValidation/livevalidation_standalone.compressed.js',
    'lib/jquery/jquery.js',
    'lib/jquery/jquery-migrate.min.js',
    'lib/jquery-ui/js/jquery-ui.min.js',
    'lib/jquery-ui/i18n/jquery.ui.datepicker-'.$datepicker_locale.'.js',
    'lib/jquery-jslatex/jquery.jslatex.js',
    'lib/jquery-form/jquery.form.js',
    'lib/chained/jquery.chained.min.js',
    'lib/thickbox/thickbox-compressed.js',
    'lib/jquery-autosize/jquery.autosize.min.js',
    'lib/jquery-sessionTimeout/jquery.sessionTimeout.min.js',
    'lib/jquery-timepicker/jquery.timepicker.min.js',
    'assets/js/core.js?v='.$version,
);

// Set page stylesheets
$stylesheets = array(
    'lib/jquery-ui/css/blitzer/jquery-ui.css',
    'lib/thickbox/thickbox.css',
    'lib/jquery-timepicker/jquery.timepicker.css',
);

// Set personal background
$personalBackground = null;
if (getSettingByScope($connection2, 'User Admin', 'personalBackground') == 'Y' and isset($_SESSION[$guid]['personalBackground'])) {
    $personalBackground = ($_SESSION[$guid]['personalBackground'] != '') ?
        htmlPrep($_SESSION[$guid]['personalBackground']) : null;
}

// Set head_extras, which will be rendered as-is in the head section
$head_extras = array();

// Arrays for displaying notices
$errors = array();
$warnings = array();

// Set theme CSS and JS
if ($cacheLoad or $_SESSION[$guid]['themeCSS'] == '' or isset($_SESSION[$guid]['themeJS']) == false or $_SESSION[$guid]['gibbonThemeID'] == '' or $_SESSION[$guid]['gibbonThemeName'] == '') {
    $theme_stylesheet = ($_SESSION[$guid]['i18n']['rtl'] == 'Y') ?
        'themes/Default/css/main.css?v='.$version :
        'themes/Default/css/main_rtl.css?v='.$version;
    $theme_script = 'themes/Default/js/common.js?v='.$version;

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
            $theme_stylesheet = ($_SESSION[$guid]['i18n']['rtl'] == 'Y') ?
                'themes/'.$rowTheme['name'].'/css/main.css?v='.$themeVersion :
                'themes/'.$rowTheme['name'].'/css/main_rtl.css?v='.$themeVersion;
            $theme_script = 'themes/'.$rowTheme['name'].'/js/common.js?v='.$themeVersion;

            $_SESSION[$guid]['gibbonThemeID'] = $rowTheme['gibbonThemeID'];
            $_SESSION[$guid]['gibbonThemeName'] = $rowTheme['name'];
            $_SESSION[$guid]['gibbonThemeAuthor'] = $rowTheme['author'];
            $_SESSION[$guid]['gibbonThemeURL'] = $rowTheme['url'];
        }
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }

    $stylesheets[] = $theme_stylesheet;
    $scripts[] = $theme_script;
} else {
    $head_extras[] = $_SESSION[$guid]['themeCSS'];
    $head_extras[] = $_SESSION[$guid]['themeJS'];
}

// Append module CSS & JS
if (isset($_GET['q'])) {
    if ($_GET['q'] != '') {
        $moduleVersion = $version;
        if (file_exists('./modules/'.$_SESSION[$guid]['module'].'/version.php')){
            include('./modules/'.$_SESSION[$guid]['module'].'/version.php');
        }
        $stylesheets[] = 'modules/'.$_SESSION[$guid]['module'].'/css/module.css?v='.$moduleVersion;
        $scripts[] = 'modules/'.$_SESSION[$guid]['module'].'/js/module.js?v='.$moduleVersion;
    }
}

// Set session duration for session timeout JS handling.
$sessionDuration = -1;
if (isset($_SESSION[$guid]['username'])) {
    $sessionDuration = getSettingByScope($connection2, 'System', 'sessionDuration');
    if (is_numeric($sessionDuration) == false) $sessionDuration = 1200;
    if ($sessionDuration < 1200) $sessionDuration = 1200;
}

// Set google analytics
$head_extras[] = $_SESSION[$guid]['analytics'];

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
    $resultHouse->closeCursor();
}

// Show warning if not in the current school year
if (isset($_SESSION[$guid]['username'])) {
    if ($_SESSION[$guid]['gibbonSchoolYearID'] != $_SESSION[$guid]['gibbonSchoolYearIDCurrent']) {
        $warnings[] = '<b><u>'.sprintf(__($guid, 'Warning: you are logged into the system in school year %1$s, which is not the current year.'), $_SESSION[$guid]['gibbonSchoolYearName']).'</b></u>'.__($guid, 'Your data may not look quite right (for example, students who have left the school will not appear in previous years), but you should be able to edit information from other years which is not available in the current year.');
    }
}

//Show student and staff quick finder
$fastFinder = null;
if (isset($_SESSION[$guid]['username'])) {
    if ($cacheLoad) {
        $fastFinder = getFastFinder($connection2, $guid);
    }
}

// Set main menu HTML
$mainMenu = new Gibbon\MenuMain($gibbon, $pdo);
if ($cacheLoad) $mainMenu->setMenu();
$mainMenuHTML = $mainMenu->getMenu();

// Set flash notification (temp_array)
$flash = getNotificationTray($connection2, $guid, $cacheLoad);

// Set easy return message.
$easyReturnHTML = null;
if ($_SESSION[$guid]['address'] == '') {
    $returns = array();
    $returns['success1'] = __($guid, 'Password reset was successful: you may now log in.');
    if (isset($_GET['return'])) {
        $easyReturnHTML = returnProcessHTML($guid, $_GET['return'], null, $returns);
    }
}

$contents = array();

if ($_SESSION[$guid]['address'] == '') {
    //Welcome message
    if (isset($_SESSION[$guid]['username']) == false) {
        //Create auto timeout message
        if (isset($_GET['timeout'])) {
            if ($_GET['timeout'] == 'true') {
                $warnings[] = __($guid, 'Your session expired, so you were automatically logged out of the system.');
            }
        }

        // Set welcome message
        $contents[] =
            '<h2>'.__($guid, 'Welcome').'</h2>'.
            "<p>{$_SESSION[$guid]['indexText']}</p>";

        // Student public applications permitted?
        $publicApplications = getSettingByScope($connection2, 'Application Form', 'publicApplications');
        if ($publicApplications == 'Y') {
            $contents[] =
                "<h2 style='margin-top: 30px'>".
                __($guid, 'Student Applications').'</h2>'.
                '<p>'.
                sprintf(__($guid, 'Parents of students interested in study at %1$s may use our %2$s online form%3$s to initiate the application process.'), $_SESSION[$guid]['organisationName'], "<a href='".$_SESSION[$guid]['absoluteURL']."/?q=/modules/Students/applicationForm.php'>", '</a>').
                '</p>';
        }

        //Staff public applications permitted?
        $staffApplicationFormPublicApplications = getSettingByScope($connection2, 'Staff Application Form', 'staffApplicationFormPublicApplications');
        if ($staffApplicationFormPublicApplications == 'Y') {
            $contents[] =
                "<h2 style='margin-top: 30px'>" .
                __($guid, 'Staff Applications') .
                '</h2>'.
                '<p>'.
                sprintf(__($guid, 'Individuals interested in working at %1$s may use our %2$s online form%3$s to view job openings and begin the recruitment process.'), $_SESSION[$guid]['organisationName'], "<a href='".$_SESSION[$guid]['absoluteURL']."/?q=/modules/Staff/applicationForm_jobOpenings_view.php'>", '</a>').
                '</p>';
        }

        //Public departments permitted?
        $makeDepartmentsPublic = getSettingByScope($connection2, 'Departments', 'makeDepartmentsPublic');
        if ($makeDepartmentsPublic == 'Y') {
            $contents[] =
                "<h2 style='margin-top: 30px'>".
                __($guid, 'Departments').
                '</h2>'.
                '<p>'.
                sprintf(__($guid, 'Please feel free to %1$sbrowse our departmental information%2$s, to learn more about %3$s.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/?q=/modules/Departments/departments.php'>", '</a>', $_SESSION[$guid]['organisationName']).
                '</p>';
        }

        //Public units permitted?
        $makeUnitsPublic = getSettingByScope($connection2, 'Planner', 'makeUnitsPublic');
        if ($makeUnitsPublic == 'Y') {
            $contents[] =
                "<h2 style='margin-top: 30px'>".
                __($guid, 'Learn With Us').
                '</h2>'.
                '<p>'.
                sprintf(__($guid, 'We are sharing some of our units of study with members of the public, so you can learn with us. Feel free to %1$sbrowse our public units%2$s.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/?q=/modules/Planner/units_public.php&sidebar=false'>", '</a>', $_SESSION[$guid]['organisationName']).
                '</p>';
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
                $contents[] =
                    "<h2 style='margin-top: 30px'>".
                    $options['title'].
                    '</h2>'.
                    '<p>'.
                    stripslashes($options['text']).
                    '</p>';
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
            $contents[] = $_SESSION[$guid]['index_custom.php'];
        }

        //DASHBOARDS!
        //Get role category
        $category = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
        if ($category == false) {
            $contents[] =
                "<div class='error'>".
                __($guid, 'Your current role type cannot be determined.').
                '</div>';
        } elseif ($category == 'Parent') { //Display Parent Dashboard
            $count = 0;
            try {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $errors[] = $e->getMessage();
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
                        $errors[] = $e->getMessage();
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
                $contents[] =
                    '<h2>'.
                    __($guid, 'Parent Dashboard').
                    '</h2>';
                include './modules/Timetable/moduleFunctions.php';

                for ($i = 0; $i < $count; ++$i) {
                    $contents[] =
                        '<h4>'.
                        $students[$i][1].' '.$students[$i][0].
                        '</h4>';

                    $contents[] =
                        "<div style='margin-right: 1%; float:left; width: 15%; text-align: center'>".
                        getUserPhoto($guid, $students[$i][5], 75).
                        "<div style='height: 5px'></div>".
                        "<span style='font-size: 70%'>".
                        "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$students[$i][4]."'>".__($guid, 'Student Profile').'</a><br/>';

                    if (isActionAccessible($guid, $connection2, '/modules/Roll Groups/rollGroups_details.php')) {
                        $contents[] = "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID='.$students[$i][7]."'>".__($guid, 'Roll Group').' ('.$students[$i][3].')</a><br/>';
                    }
                    if ($students[$i][8] != '') {
                        $contents[] = "<a target='_blank' href='".$students[$i][8]."'>".$students[$i][3].' '.__($guid, 'Website').'</a>';
                    }

                    $contents[] = '</span>';
                    $contents[] = '</div>';
                    $contents[] = "<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 83%'>";
                    $dashboardContents = getParentDashboardContents($connection2, $guid, $students[$i][4]);
                    if ($dashboardContents == false) {
                        $contents[] =
                            "<div class='error'>".
                            __($guid, 'There are no records to display.').
                            '</div>';
                    } else {
                        $contents[] = $dashboardContents;
                    }
                    $contents[] = '</div>';
                }
            }
        } elseif ($category == 'Student') { //Display Student Dashboard
            $contents[] =
                '<h2>'.
                __($guid, 'Student Dashboard').
                '</h2>'.
                "<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 100%'>";
            $dashboardContents = getStudentDashboardContents($connection2, $guid, $_SESSION[$guid]['gibbonPersonID']);
            if ($dashboardContents == false) {
                $contents[] = 
                    "<div class='error'>".
                    __($guid, 'There are no records to display.').
                    '</div>';
            } else {
                $contents[] = $dashboardContents;
            }
            $contents[] = '</div>';
        } elseif ($category == 'Staff') { //Display Staff Dashboard
            $smartWorkflowHelp = getSmartWorkflowHelp($connection2, $guid);
            if ($smartWorkflowHelp != false) {
                $contents[] = $smartWorkflowHelp;
            }

            $contents[] =
                '<h2>'.
                __($guid, 'Staff Dashboard').
                '</h2>'.
                "<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 100%'>";
            $dashboardContents = getStaffDashboardContents($connection2, $guid, $_SESSION[$guid]['gibbonPersonID']);
            if ($dashboardContents == false) {
                $contents[] = 
                    "<div class='error'>".
                    __($guid, 'There are no records to display.').
                    '</div>';
            } else {
                $contents[] = $dashboardContents;
            }
            $contents[] = '</div>';
        }
    }
} else {
    if (strstr($_SESSION[$guid]['address'], '..') || strstr($_SESSION[$guid]['address'], 'installer') || strstr($_SESSION[$guid]['address'], 'uploads') || in_array($_SESSION[$guid]['address'] , array('index.php', '/index.php', './index.php')) || substr($_SESSION[$guid]['address'], -11) == '//index.php' || substr($_SESSION[$guid]['address'], -11) == './index.php') {
        $contents[] =
            "<div class='error'>".
            __($guid, 'Illegal address detected: access denied.').
            '</div>';
    } else {
        ob_start();
        if (is_file('./'.$_SESSION[$guid]['address'])) {
            //Include the page
            include './'.$_SESSION[$guid]['address'];
        } else {
            include './error.php';
        }
        $contents[] = ob_get_contents();
        ob_end_clean();
    }
}

// Set footer contents
$footerAuthor = __($guid, 'Powered by') . " <a target='_blank' href='https://gibbonedu.org'>Gibbon</a> v{$version} " .
    (($_SESSION[$guid]['cuttingEdgeCode'] == 'Y') ? 'dev' : '') . " | &#169; <a target='_blank' href='http://rossparker.org'>Ross Parker</a> 2010-" . date('Y');

$footerLicense = __($guid, 'Created under the') . "<a target='_blank' href='https://www.gnu.org/licenses/gpl.html'>GNU GPL</a> at ".
    "<a target='_blank' href='http://www.ichk.edu.hk'>ICHK</a> | ".
    "<a target='_blank' href='https://gibbonedu.org/about/#ourTeam'>" . __($guid, 'Credits') . "</a> | ".
    "<a target='_blank' href='https://gibbonedu.org/about/#translators'>" . __($guid, 'Translators') . '</a>';

$footerThemeAuthor = null;
if ($_SESSION[$guid]['gibbonThemeName'] != 'Default' and $_SESSION[$guid]['gibbonThemeAuthor'] != '') {
    $footerThemeAuthor = ($_SESSION[$guid]['gibbonThemeURL'] != '') ?
        __($guid, 'Theme by')." <a target='_blank' href='".$_SESSION[$guid]['gibbonThemeURL']."'>".$_SESSION[$guid]['gibbonThemeAuthor'].'</a>' :
        __($guid, 'Theme by').' '.$_SESSION[$guid]['gibbonThemeAuthor'];
}

$footerLogo = $_SESSION[$guid]['absoluteURL'] . "/themes/{$_SESSION[$guid]['gibbonThemeName']}/img/logoFooter.png";


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo $title; ?></title>
		<meta charset="utf-8"/>
		<meta name="author" content="Ross Parker, International College Hong Kong"/>

		<link rel="shortcut icon" type="image/x-icon" href="./favicon.ico"/>

		<!-- js stylesheets -->
		<?php foreach ($stylesheets as $stylesheet) { ?>
			<link rel="stylesheet" href="<?php echo $_SESSION[$guid]['absoluteURL'] . '/' . $stylesheet; ?>" type="text/css" media="screen" />
		<?php } ?>
		<?php if ($personalBackground !== null) { ?>
			<style type="text/css">
			body {
			    background: url(<?php echo json_encode($personalBackground); ?>) repeat scroll center top #A88EDB!important;
			}
			</style>
		<?php } ?>
		<!-- js stylesheets end -->

		<!-- js scripts -->
		<?php foreach ($scripts as $script) { ?>
			<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] . '/' . $script; ?>"></script>
		<?php } ?>
		<!-- js scripts end -->

		<!-- js initialization -->
		<script type='text/javascript'>$.datepicker.setDefaults($.datepicker.regional[<?php echo json_encode($datepicker_locale); ?>]);</script>
		<script type="text/javascript">$(function() { $( document ).tooltip({  show: 800, hide: false, content: function () { return $(this).prop('title')}, position: { my: "center bottom-20", at: "center top", using: function( position, feedback ) { $( this ).css( position ); $( "<div>" ).addClass( "arrow" ).addClass( feedback.vertical ).addClass( feedback.horizontal ).appendTo( this ); } } }); });</script>
		<script type="text/javascript">$(function () { $(".latex").latex();});</script>
		<script type="text/javascript"> var tb_pathToImage=<?php echo json_encode($_SESSION[$guid]['absoluteURL'] . '/lib/thickbox/loadingAnimation.gif');  ?>;</script>
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
		<script type="text/javascript">
		$(document).ready(function(){
			var sessionDuration = <?php echo json_encode($sessionDuration); ?>;
			if (sessionDuration > 0) {
				$.sessionTimeout({
					message: '<?php echo __($guid, 'Your session is about to expire: you will be logged out shortly.') ?>',
					keepAliveUrl: 'keepAlive.php' ,
					redirUrl: 'logout.php?timeout=true',
					logoutUrl: 'logout.php' ,
					warnAfter: sessionDuration * 1000,
					redirAfter: (sessionDuration * 1000) + 600000
				});
			}
		});
		</script>
		<!-- js initialization end -->

		<!-- head extras -->
		<?php foreach ($head_extras as $head_extra) echo $head_extra; ?>
		<!-- head extras end -->

	</head>
	<body>

		<?php if (!empty($errors)) { ?>
			<?php foreach ($errors as $error) { ?>
				<div class='error'><?php echo $error; ?></div>
			<?php } ?>
		<?php } ?>

		<?php if (!empty($warnings)) { ?>
			<?php foreach ($warnings as $warning) { ?>
				<div class='warning' style='margin: 10px auto; width:1101px;'><?php echo $warning; ?></div>
			<?php } ?>
		<?php } ?>

		<div id="wrapOuter">
			<div class='minorLinks <?php echo (@$_SESSION[$guid]['gibbonHouseIDLogo'] == '') ? 'minorLinksTopGap' : '' ?>'>
				<?php echo getMinorLinks($connection2, $guid, $cacheLoad); ?>
			</div>
			<div id="wrap">
				<div id="header">
					<div id="header-logo">
						<a href='<?php echo $_SESSION[$guid]['absoluteURL'] ?>'><img height='100px' width='400px' class="logo" alt="Logo" src="<?php echo $_SESSION[$guid]['absoluteURL'].'/'.$_SESSION[$guid]['organisationLogo'];?>"/></a>
					</div>
					<div id="header-finder">
						<?php echo $fastFinder; ?>
					</div>
					<div id="header-menu">
						<?php echo $mainMenuHTML; ?>
						<div class='notificationTray'>
							<?php echo $flash; ?>
						</div>
					</div>
				</div><!--/#header-->
				<div id="content-wrap">
					<div id='<?php echo ($sidebar == 'false') ? 'content-wide' : 'content'; ?>'>
						<?php echo $moduleMenu; ?>
						<?php echo $easyReturnHTML; ?>
						<?php echo implode("\n", $contents); ?>
					</div>
					<?php if ($sidebar != 'false') { ?>
						<div id="sidebar">
							<?php sidebar($gibbon, $pdo);?>
						</div>
						<br style="clear: both">
					<?php } ?>
				</div><!--/#content-wrap-->
				<div id="footer">
					<?php echo $footerAuthor; ?><br/>
					<span style='font-size: 90%; '>
						<?php echo $footerLicense; ?><br/>
						<?php echo $footerThemeAuthor; ?><br/>
					</span>
					<img id='footer-logo' alt='Logo Small' src='<?php echo $footerLogo; ?>'/>
				</div><!--/#footer-->
			</div><!--/#wrap-->
		</div><!--/.#wrapOuter-->
	</body>
</html>
