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
along with this program. If not, see <http:// www.gnu.org/licenses/>.
*/

use Gibbon\Domain\DataUpdater\DataUpdaterGateway;
use Gibbon\MenuMain;
use Gibbon\MenuModule;
use Gibbon\View\AssetBundle;
use Gibbon\View\Page;

// Gibbon system-wide include
require './gibbon.php';

// Setup the Page and Session objects
$page = $container->get('page');
$session = $container->get('session');

$isLoggedIn = $session->has('username') && $session->has('gibbonRoleIDCurrent');


// Deal with caching
$session->set('pageLoads', $session->has('pageLoads') ? $session->get('pageLoads')+1 : 0);

if ($caching == 0) {
    $cacheLoad = true;
} elseif ($caching > 0 and is_numeric($caching)) {
    $cacheLoad = $session->get('pageLoads') % $caching == 0;
}

// Check for cutting edge code
if (!$session->has('cuttingEdgeCode')) {
    $session->set('cuttingEdgeCode', getSettingByScope($connection2, 'System', 'cuttingEdgeCode'));
}

// Set sidebar values (from the entrySidebar field in gibbonAction and from $_GET variable)
$session->set('sidebarExtra', '');
$session->set('sidebarExtraPosition', '');

// $sidebar is true unless $_GET['sidebar'] explicitly set to 'false'
$sidebar = !isset($_GET['sidebar']) || (strtolower($_GET['sidebar']) !== 'false');

// Check to see if system settings are set from databases
if (!$session->has('systemSettingsSet')) {
    getSystemSettings($guid, $connection2);
}
// If still false, only show warning and exit.
if (!$session->has('systemSettingsSet')) {
    exit(__('System Settings are not set: the system cannot be displayed'));
}


// Try to autoset user's calendar feed if not set already
if ($session->exists('calendarFeedPersonal') && $session->exists('googleAPIAccessToken')) {
    if (!$session->has('calendarFeedPersonal') && $session->has('googleAPIAccessToken')) {
        include_once $session->get('absolutePath').'/lib/google/google-api-php-client/vendor/autoload.php';

        $client2 = new Google_Client();
        $client2->setAccessToken($session->get('googleAPIAccessToken'));
        $service = new Google_Service_Calendar($client2);
        $calendar = $service->calendars->get('primary');

        if ($calendar['id'] != '') {
            try {
                $dataCalendar = [
                    'calendarFeedPersonal' => $calendar['id'],
                    'gibbonPersonID' => $session->get('gibbonPersonID'),
                ];
                $sqlCalendar = 'UPDATE gibbonPerson SET
                    calendarFeedPersonal=:calendarFeedPersonal
                    WHERE gibbonPersonID=:gibbonPersonID';
                $resultCalendar = $connection2->prepare($sqlCalendar);
                $resultCalendar->execute($dataCalendar);
            } catch (PDOException $e) {
                exit($e->getMessage());
            }
            $session->set('calendarFeedPersonal', $calendar['id']);
        }
    }
}

// Check for force password reset flag
if ($session->has('passwordForceReset')) {
    if ($session->get('passwordForceReset') == 'Y' and $session->get('address') != 'preferences.php') {
        $URL = $session->get('absoluteURL').'/index.php?q=preferences.php';
        $URL = $URL.'&forceReset=Y';
        header("Location: {$URL}");
        exit();
    }
}

// USER REDIRECTS
if ($session->get('pageLoads') == 0 && $session->get('address') == '') { // First page load, so proceed
    if (!empty($session->get('username'))) { // Are we logged in?
        $roleCategory = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);

        // Deal with attendance self-registration redirect
        // Are we a student?
        if ($roleCategory == 'Student') {
            // Can we self register?
            if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_studentSelfRegister.php')) {
                // Check to see if student is on site
                $studentSelfRegistrationIPAddresses = getSettingByScope(
                    $connection2, 'Attendance', 'studentSelfRegistrationIPAddresses'
                );
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
                        if (isSchoolOpen($guid, $currentDate, $connection2, true)) { // Is school open today
                            // Check for existence of records today
                            try {
                                $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'date' => $currentDate);
                                $sql = "SELECT type FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date=:date ORDER BY timestampTaken DESC";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $page->addError($e->getMessage());
                            }

                            if ($result->rowCount() == 0) {
                                // No registration yet
                                // Redirect!
                                $URL = $session->get('absoluteURL').
                                    '/index.php?q=/modules/Attendance'.
                                    '/attendance_studentSelfRegister.php'.
                                    '&redirect=true';
                                $session->set('pageLoads', null);
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
            if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_updates.php')) { // Can we update data?
                $redirectByRoleCategory = getSettingByScope(
                    $connection2, 'Data Updater', 'redirectByRoleCategory'
                );
                $redirectByRoleCategory = explode(',', $redirectByRoleCategory);

                // Are we the right role category?
                if (in_array($roleCategory, $redirectByRoleCategory)) {
                    $gateway = new DataUpdaterGateway($pdo);

                    $updatesRequiredCount = $gateway
                        ->countAllRequiredUpdatesByPerson(
                            $session->get('gibbonPersonID')
                        );
                    if ($updatesRequiredCount > 0) {
                        $URL = $session->get('absoluteURL').
                            '/index.php?q=/modules/Data Updater'.
                            '/data_updates.php&redirect=true';
                        $session->set('pageLoads', null);
                        header("Location: {$URL}");
                        exit;
                    }
                }
            }
        }
    }
}

// TODO: replace this with a property check on the current Action object
if ($session->has('address') && $sidebar) {
    $dataSidebar = [
        'action' => '%'.$session->get('action').'%',
        'moduleName' => $session->get('module'),
    ];
    $sqlSidebar = "SELECT gibbonAction.name FROM
        gibbonAction JOIN gibbonModule
        ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID)
        WHERE gibbonAction.URLList LIKE :action
        AND entrySidebar='N'
        AND gibbonModule.name=:moduleName";

    $resultSidebar = $pdo->select($sqlSidebar, $dataSidebar);

    if ($resultSidebar->rowCount() > 0) {
        $sidebar = false;
    }
}

// Set page title
// TODO: move to CoreServiceProvider, using Module class
// $title = $session->get('organisationNameShort').' - '.$session->get('systemName');
// if ($session->get('address') != '') {
//     if (strstr($session->get('address'), '..') == false) {
//         if (getModuleName($session->get('address')) != '') {
//             $title .= ' - '.__(getModuleName($session->get('address')));
//         }
//     }
// }

// Set session duration for session timeout JS handling
$sessionDuration = -1;
if ($session->has('username')) {
    $sessionDuration = getSettingByScope($connection2, 'System', 'sessionDuration');
    $sessionDuration = is_numeric($sessionDuration) ? $sessionDuration : 1200;
    $sessionDuration = ($sessionDuration >= 1200) ? $sessionDuration : 1200;
}

// Set the i18n locale for jQuery UI DatePicker (if the file exists, otherwise fallback to en-GB)
$localeCode = str_replace('_', '-', $session->get('i18n')['code']);
$localeCodeShort = substr($session->get('i18n')['code'], 0, 2);
$localePath = $session->get('absolutePath').'/lib/jquery-ui/i18n/jquery.ui.datepicker-%1$s.js';

$datepickerLocale = 'en-GB';
if (is_file(sprintf($localePath, $localeCode))) {
    $datepickerLocale = $localeCode;
} elseif (is_file(sprintf($localePath, $localeCodeShort))) {
    $datepickerLocale = $localeCodeShort;
}

// JAVASCRIPT
$javascriptConfig = [
    'config' => [
        'datepicker' => [
            'locale' => $datepickerLocale,
        ],
        'thickbox' => [
            'pathToImage' => $session->get('absoluteURL').'/lib/thickbox/loadingAnimation.gif',
        ],
        'tinymce' => [
            'valid_elements' => getSettingByScope($connection2, 'System', 'allowableHTML'),
        ],
        'sessionTimeout' => [
            'sessionDuration' => $sessionDuration,
            'message' => __('Your session is about to expire: you will be logged out shortly.'),
        ]
    ],
];

// Set page scripts: head
$page->scripts()->add('lv', 'lib/LiveValidation/livevalidation_standalone.compressed.js', ['context' => 'head']);
$page->scripts()->add('jquery', 'lib/jquery/jquery.js', ['context' => 'head']);
$page->scripts()->add('jquery-migrate', 'lib/jquery/jquery-migrate.min.js', ['context' => 'head']);
$page->scripts()->add('jquery-ui', 'lib/jquery-ui/js/jquery-ui.min.js', ['context' => 'head']);
$page->scripts()->add('core', 'resources/assets/js/core.js', ['context' => 'head']);

// Set page scripts: foot - lib
$page->scripts()->add('jquery-latex', 'lib/jquery-jslatex/jquery.jslatex.js');
$page->scripts()->add('jquery-form', 'lib/jquery-form/jquery.form.js');
$page->scripts()->add('jquery-chained', 'lib/chained/jquery.chained.min.js');
$page->scripts()->add('jquery-date', 'lib/jquery-ui/i18n/jquery.ui.datepicker-'.$datepickerLocale.'.js');
$page->scripts()->add('jquery-time', 'lib/jquery-timepicker/jquery.timepicker.min.js');
$page->scripts()->add('jquery-autosize', 'lib/jquery-autosize/jquery.autosize.min.js');
$page->scripts()->add('jquery-timeout', 'lib/jquery-sessionTimeout/jquery.sessionTimeout.min.js');
$page->scripts()->add('jquery-token', 'lib/jquery-tokeninput/src/jquery.tokeninput.js');
$page->scripts()->add('thickboxi', 'var tb_pathToImage="'.$session->get('absoluteURL').'/lib/thickbox/loadingAnimation.gif";', ['type' => 'inline']);
$page->scripts()->add('thickbox', 'lib/thickbox/thickbox-compressed.js');
$page->scripts()->add('tinymce', 'lib/tinymce/tinymce.min.js');

// Set page scripts: foot - core
$page->scripts()->add('core-config', 'window.Gibbon = '.json_encode($javascriptConfig).';', ['type' => 'inline']);
$page->scripts()->add('core-setup', 'resources/assets/js/setup.js');


// Set page stylesheets
$page->stylesheets()->add('jquery-ui', 'lib/jquery-ui/css/blitzer/jquery-ui.css');
$page->stylesheets()->add('jquery-time', 'lib/jquery-timepicker/jquery.timepicker.css');
$page->stylesheets()->add('jquery-token', 'lib/jquery-tokeninput/styles/token-input-facebook.css');
$page->stylesheets()->add('thickbox', 'lib/thickbox/thickbox.css');
$page->stylesheets()->add('theme', 'themes/Default/css/main.css');


// Set personal background
$personalBackground = null;
if (getSettingByScope($connection2, 'User Admin', 'personalBackground') == 'Y' and $session->has('personalBackground')) {
    $personalBackground = ($session->get('personalBackground') != '') ?
        htmlPrep($session->get('personalBackground')) : null;
}
if (!empty($personalBackground)) {
    $page->stylesheets()->add(
        'personal-background',
        'body { background: url('.$personalBackground.') repeat scroll center top #A88EDB!important; }',
        ['type' => 'inline']
    );
}

// Array for displaying main contents
// TODO: Move to Page class
$contents = array();

// TODO: THIS!!!!
// Setup theme CSS and JS
// try {
//     $theme = getTheme($connection2);
//     $session->set('gibbonThemeID', $theme['id']);
//     $session->set('gibbonThemeName', $theme['name']);
//     foreach ($theme['stylesheets'] as $style) {
//         $page->stylesheets()->add(
//             $style, $style, ['version' => $version]
//         );
//     }
//     foreach ($theme['scripts'] as $script) {
//         $page->scripts()->add(
//             $script, $script, ['version' => $version]
//         );
//     }
// } catch (PDOException $e) {
//     exit($e->getMessage());
// }

// Append module CSS & JS
// if (isset($_GET['q'])) {
//     if ($_GET['q'] != '') {
//         $moduleVersion = $version;
//         if (file_exists('./modules/'.$session->get('module').'/version.php')) {
//             include './modules/'.$session->get('module').'/version.php';
//         }
//         $page->stylesheets()->add(
//             'modules/'.$session->get('module').
//             '/css/module.css',
//             'modules/'.$session->get('module').
//             '/css/module.css',
//             [
//                 'version' => $moduleVersion,
//             ]
//         );
//         $page->scripts()->add(
//             'modules/'.$session->get('module').
//             '/js/module.js',
//             'modules/'.$session->get('module').
//             '/js/module.js',
//             [
//                 'version' => $moduleVersion,
//             ]
//         );
//     }
// }



// Set Google analytics from session cache
$page->addHeadExtra($session->get('analytics'));

// Get house logo and set session variable, only on first load after login (for performance)
if ($session->get('pageLoads') == 0 and $session->has('username') and $session->get('gibbonHouseID') != '') {
    try {
        $dataHouse = array('gibbonHouseID' => $session->get('gibbonHouseID'));
        $sqlHouse = 'SELECT logo, name FROM gibbonHouse
            WHERE gibbonHouseID=:gibbonHouseID';
        $resultHouse = $connection2->prepare($sqlHouse);
        $resultHouse->execute($dataHouse);
    } catch (PDOException $e) {
    }

    if ($resultHouse->rowCount() == 1) {
        $rowHouse = $resultHouse->fetch();
        $session->set('gibbonHouseIDLogo', $rowHouse['logo']);
        $session->set('gibbonHouseIDName', $rowHouse['name']);
    }
    $resultHouse->closeCursor();
}

// Show warning if not in the current school year
if ($isLoggedIn) {
    if ($session->get('gibbonSchoolYearID') != $session->get('gibbonSchoolYearIDCurrent')) {
        $page->addWarning('<b><u>'.sprintf(__('Warning: you are logged into the system in school year %1$s, which is not the current year.'), $session->get('gibbonSchoolYearName')).'</b></u>'.__('Your data may not look quite right (for example, students who have left the school will not appear in previous years), but you should be able to edit information from other years which is not available in the current year.'));
    }
}


// Set any alerts in the index from the URL return parameter
// TODO: remove all returnProcess() from pages? But still them register custom messages ...
if ($session->get('address') == '') {
    if (!empty($_GET['return'])) {
        $customReturns = ['success1' => __('Password reset was successful: you may now log in.')];
        if ($alert = returnProcessGetAlert($_GET['return'], '', $customReturns)) {
            $page->addAlert($alert['context'], $alert['text']);
        }
    }
}

if (!$session->has('address')) {

    // Welcome message
    if (!$isLoggedIn) {
        // Create auto timeout message
        if (isset($_GET['timeout'])) {
            if ($_GET['timeout'] == 'true') {
                $page->addWarning(__('Your session expired, so you were automatically logged out of the system.'));
            }
        }

        // Set welcome message
        $contents[] = '<h2>'.__('Welcome').'</h2>'.
            "<p>{$session->get('indexText')}</p>";

        // Student public applications permitted?
        $publicApplications = getSettingByScope($connection2, 'Application Form', 'publicApplications');
        if ($publicApplications == 'Y') {
            $contents[] = "<h2 style='margin-top: 30px'>".
                __('Student Applications').'</h2>'.
                '<p>'.
                sprintf(__('Parents of students interested in study at %1$s may use our %2$s online form%3$s to initiate the application process.'), $session->get('organisationName'), "<a href='".$session->get('absoluteURL')."/?q=/modules/Students/applicationForm.php'>", '</a>').
                '</p>';
        }

        // Staff public applications permitted?
        $staffApplicationFormPublicApplications = getSettingByScope($connection2, 'Staff Application Form', 'staffApplicationFormPublicApplications');
        if ($staffApplicationFormPublicApplications == 'Y') {
            $contents[] = "<h2 style='margin-top: 30px'>" .
                __('Staff Applications') .
                '</h2>'.
                '<p>'.
                sprintf(__('Individuals interested in working at %1$s may use our %2$s online form%3$s to view job openings and begin the recruitment process.'), $session->get('organisationName'), "<a href='".$session->get('absoluteURL')."/?q=/modules/Staff/applicationForm_jobOpenings_view.php'>", '</a>').
                '</p>';
        }

        // Public departments permitted?
        $makeDepartmentsPublic = getSettingByScope($connection2, 'Departments', 'makeDepartmentsPublic');
        if ($makeDepartmentsPublic == 'Y') {
            $contents[] = "<h2 style='margin-top: 30px'>".
                __('Departments').
                '</h2>'.
                '<p>'.
                sprintf(__('Please feel free to %1$sbrowse our departmental information%2$s, to learn more about %3$s.'), "<a href='".$session->get('absoluteURL')."/?q=/modules/Departments/departments.php'>", '</a>', $session->get('organisationName')).
                '</p>';
        }

        // Public units permitted?
        $makeUnitsPublic = getSettingByScope($connection2, 'Planner', 'makeUnitsPublic');
        if ($makeUnitsPublic == 'Y') {
            $contents[] = "<h2 style='margin-top: 30px'>".
                __('Learn With Us').
                '</h2>'.
                '<p>'.
                sprintf(__('We are sharing some of our units of study with members of the public, so you can learn with us. Feel free to %1$sbrowse our public units%2$s.'), "<a href='".$session->get('absoluteURL')."/?q=/modules/Planner/units_public.php&sidebar=false'>", '</a>', $session->get('organisationName')).
                '</p>';
        }

        // Get any elements hooked into public home page, checking if they are turned on
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
            if ($check == $options['toggleSettingValue']) { // If its turned on, display it
                $contents[] = "<h2 style='margin-top: 30px'>".
                    $options['title'].
                    '</h2>'.
                    '<p>'.
                    stripslashes($options['text']).
                    '</p>';
            }
        }
    } else {
        // Custom content loader
        if ($session->has('index_custom.php') == false) {
            if (is_file('./index_custom.php')) {
                $session->set('index_custom.php', include './index_custom.php');
            } else {
                $session->set('index_custom.php', null);
            }
        }
        if ($session->has('index_custom.php')) {
            $contents[] = $session->get('index_custom.php');
        }

        // DASHBOARDS!
        // Get role category
        $category = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);
        if ($category == false) {
            $contents[] = "<div class='error'>".
                __('Your current role type cannot be determined.').
                '</div>';
        } elseif ($category == 'Parent') { // Display Parent Dashboard
            $count = 0;
            try {
                $data = ['gibbonPersonID' => $session->get('gibbonPersonID')];
                $sql = "SELECT * FROM gibbonFamilyAdult WHERE
                    gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $page->addError($e->getMessage());
            }

            if ($result->rowCount() > 0) {
                // Get child list
                $count = 0;
                $options = '';
                $students = array();
                while ($row = $result->fetch()) {
                    try {
                        $dataChild = [
                            'gibbonSchoolYearID' =>
                                $session->get('gibbonSchoolYearID'),
                            'gibbonFamilyID' => $row['gibbonFamilyID'],
                        ];
                        $sqlChild = "SELECT
                            gibbonPerson.gibbonPersonID,image_240, surname,
                            preferredName, dateStart,
                            gibbonYearGroup.nameShort AS yearGroup,
                            gibbonRollGroup.nameShort AS rollGroup,
                            gibbonRollGroup.website AS rollGroupWebsite,
                            gibbonRollGroup.gibbonRollGroupID
                            FROM gibbonFamilyChild JOIN gibbonPerson
                            ON (gibbonFamilyChild.gibbonPersonID=
                                gibbonPerson.gibbonPersonID)
                            JOIN gibbonStudentEnrolment
                            ON (gibbonPerson.gibbonPersonID=
                                gibbonStudentEnrolment.gibbonPersonID)
                            JOIN gibbonYearGroup
                            ON (gibbonStudentEnrolment.gibbonYearGroupID=
                                gibbonYearGroup.gibbonYearGroupID)
                            JOIN gibbonRollGroup
                            ON (gibbonStudentEnrolment.gibbonRollGroupID=
                                gibbonRollGroup.gibbonRollGroupID)
                            WHERE
                            gibbonStudentEnrolment.gibbonSchoolYearID=
                                :gibbonSchoolYearID
                            AND gibbonFamilyID=:gibbonFamilyID
                            AND gibbonPerson.status='Full'
                            AND (
                                dateStart IS NULL
                                OR dateStart<='".date('Y-m-d')."'
                            )
                            AND (
                                dateEnd IS NULL
                                OR dateEnd>='".date('Y-m-d')."')
                            ORDER BY surname, preferredName ";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    } catch (PDOException $e) {
                        $page->addError($e->getMessage());
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
                $contents[] = '<h2>'.
                    __('Parent Dashboard').
                    '</h2>';
                include './modules/Timetable/moduleFunctions.php';

                for ($i = 0; $i < $count; ++$i) {
                    $contents[] = '<h4>'.
                        $students[$i][1].' '.$students[$i][0].
                        '</h4>';

                    $contents[] = "<div style='margin-right: 1%; float:left; width: 15%; text-align: center'>".
                        getUserPhoto($guid, $students[$i][5], 75).
                        "<div style='height: 5px'></div>".
                        "<span style='font-size: 70%'>".
                        "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$students[$i][4]."'>".__('Student Profile').'</a><br/>';

                    if (isActionAccessible($guid, $connection2, '/modules/Roll Groups/rollGroups_details.php')) {
                        $contents[] = "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID='.$students[$i][7]."'>".__('Roll Group').' ('.$students[$i][3].')</a><br/>';
                    }
                    if ($students[$i][8] != '') {
                        $contents[] = "<a target='_blank' href='".$students[$i][8]."'>".$students[$i][3].' '.__('Website').'</a>';
                    }

                    $contents[] = '</span>';
                    $contents[] = '</div>';
                    $contents[] = "<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 83%'>";
                    $dashboardContents = getParentDashboardContents($connection2, $guid, $students[$i][4]);
                    if ($dashboardContents == false) {
                        $contents[] = "<div class='error'>".
                            __('There are no records to display.').
                            '</div>';
                    } else {
                        $contents[] = $dashboardContents;
                    }
                    $contents[] = '</div>';
                }
            }
        } elseif ($category == 'Student') { // Display Student Dashboard
            $contents[] = '<h2>'.
                __('Student Dashboard').
                '</h2>'.
                "<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 100%'>";
            $dashboardContents = getStudentDashboardContents($connection2, $guid, $session->get('gibbonPersonID'));
            if ($dashboardContents == false) {
                $contents[] = "<div class='error'>".
                    __('There are no records to display.').
                    '</div>';
            } else {
                $contents[] = $dashboardContents;
            }
            $contents[] = '</div>';
        } elseif ($category == 'Staff') { // Display Staff Dashboard
            $smartWorkflowHelp = getSmartWorkflowHelp($connection2, $guid);
            if ($smartWorkflowHelp != false) {
                $contents[] = $smartWorkflowHelp;
            }

            $contents[] = '<h2>'.
                __('Staff Dashboard').
                '</h2>'.
                "<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 100%'>";
            $dashboardContents = getStaffDashboardContents($connection2, $guid, $session->get('gibbonPersonID'));
            if ($dashboardContents == false) {
                $contents[] = "<div class='error'>".
                    __('There are no records to display.').
                    '</div>';
            } else {
                $contents[] = $dashboardContents;
            }
            $contents[] = '</div>';
        }
    }
} else {
    if (strstr($session->get('address'), '..')
        || strstr($session->get('address'), 'installer')
        || strstr($session->get('address'), 'uploads')
        || in_array(
            $session->get('address'),
            array('index.php', '/index.php', './index.php')
        )
        || substr($session->get('address'), -11) == '// index.php'
        || substr($session->get('address'), -11) == './index.php'
    ) {
        $contents[] = "<div class='error'>".
            __('Illegal address detected: access denied.').
            '</div>';
    } else {
        ob_start();
        if (is_file('./'.$session->get('address'))) {
            // Include the page
            include './'.$session->get('address');
        } else {
            include './error.php';
        }
        $contents[] = ob_get_contents();
        ob_end_clean();
    }
}


// Setup menu items
// TODO: replace!!!

if ($isLoggedIn) {
    $absoluteURL = $session->get('absoluteURL');
    $gibbonRoleIDCurrent = $session->get('gibbonRoleIDCurrent');
    $mainMenuCategoryOrder = getSettingByScope($connection2, 'System', 'mainMenuCategoryOrder');

    $data = array('gibbonRoleID' => $gibbonRoleIDCurrent, 'menuOrder' => $mainMenuCategoryOrder );
    $sql = "SELECT gibbonModule.category, gibbonModule.name, gibbonModule.type, gibbonModule.entryURL, gibbonAction.entryURL as alternateEntryURL, (CASE WHEN gibbonModule.type <> 'Core' THEN gibbonModule.name ELSE NULL END) as textDomain
            FROM gibbonModule 
            JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) 
            JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) 
            WHERE gibbonModule.active='Y' 
            AND gibbonAction.menuShow='Y' 
            AND gibbonPermission.gibbonRoleID=:gibbonRoleID 
            GROUP BY gibbonModule.name 
            ORDER BY FIND_IN_SET(gibbonModule.category, :menuOrder), gibbonModule.category, gibbonModule.name, gibbonAction.name";

    $menuMainItems = $pdo->select($sql, $data)->fetchGrouped();

    foreach ($menuMainItems as $category => &$items) {
        foreach ($items as &$item) {
            $modulePath = '/modules/'.$item['name'];
            $item['url'] = isActionAccessible($guid, $connection2, $modulePath.'/'.$item['entryURL'])
                ? $absoluteURL.'/index.php?q='.$modulePath.'/'.$item['entryURL']
                : $absoluteURL.'/index.php?q='.$modulePath.'/'.$item['alternateEntryURL'];
        }
    }



    $moduleID=checkModuleReady($session->get('address'), $connection2);

    $data = array('gibbonModuleID' => $moduleID, 'gibbonRoleID' => $gibbonRoleIDCurrent);
    $sql = "SELECT gibbonAction.category, gibbonModule.entryURL AS moduleEntry, gibbonModule.name AS moduleName, gibbonAction.name as actionName, gibbonModule.type, gibbonAction.precedence, gibbonAction.entryURL, URLList, SUBSTRING_INDEX(gibbonAction.name, '_', 1) as name, (CASE WHEN gibbonModule.type <> 'Core' THEN gibbonModule.name ELSE NULL END) AS textDomain
            FROM gibbonModule
            JOIN gibbonAction ON (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID)
            JOIN gibbonPermission ON (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID)
            WHERE (gibbonModule.gibbonModuleID=:gibbonModuleID)
            AND (gibbonPermission.gibbonRoleID=:gibbonRoleID)
            AND NOT gibbonAction.entryURL=''
            AND gibbonAction.menuShow='Y'
            GROUP BY name
            ORDER BY gibbonModule.name, gibbonAction.category, gibbonAction.name, precedence DESC";

    $menuModuleItems = $pdo->select($sql, $data)->fetchGrouped();

    $currentAction = getActionName($session->get('address'));

    foreach ($menuModuleItems as $category => &$items) {
        foreach ($items as &$item) {
            $item['active'] = stripos($item['URLList'], $currentAction) !== false;
            $item['url'] = $absoluteURL.'/index.php?q=/modules/'.$item['moduleName'].'/'.$item['entryURL'];
        }
    }
}


$twig = $container->get('twig');
$page = $container->get('page');

// TODO: remove
$session->set('gibbonThemeName', 'Default');

$sidebarContents = '';
if ($sidebar) {
    ob_start();
    sidebar($gibbon, $pdo);
    $sidebarContents = ob_get_contents();
    ob_end_clean();
}


// TODO: Cacheload FastFinder, Main Menu


$templateData = [
    'page'              => $page->gatherData(),
    'contents'          => $contents,
    'isLoggedIn'        => $isLoggedIn,
    'organisationLogo'  => $session->get('organisationLogo'),
    'version'           => $gibbon->getVersion(),
    'versionName'       => 'v'.$gibbon->getVersion().($session->get('cuttingEdgeCode') == 'Y'? 'dev' : ''),
    'gibbonThemeName'   => $session->get('gibbonThemeName'),
    'gibbonHouseIDLogo' => $session->get('gibbonHouseIDLogo'),
    'minorLinks'        => getMinorLinks($connection2, $guid, $cacheLoad),
    'notificationTray'  => getNotificationTray($connection2, $guid, $cacheLoad),
    'sidebar'           => $sidebar,
    'sidebarContents'   => $sidebarContents,
];

if ($isLoggedIn) {
    $templateData = array_replace($templateData, [
        'menuMain'   => $menuMainItems ?? '',
        'menuModule' => $menuModuleItems ?? '',
        'fastFinder' => getFastFinder($connection2, $guid),
    ]);
}

echo $twig->render('index.twig.html', $templateData);


/**
 * Variables to be used in the display logic below.
 *
 * Content arrays:
 *
 * @var $scripts array
 *      Array of script paths string. Presumed to be based on site path.
 * @var $head_extras array
 *      Array of extra HTML string in the HTML's head section that
 *      will be print as-is.
 * @var $errors array
 *      Array of error strings to be displayed on top.
 * @var $warnings array
 *      Array of warning strings to be displayed on top.
 * @var $contents array
 *      Array of HTML string of the main body contents.
 *
 *
 * Globals:
 *
 * @var $guid string
 *      GUID of the Gibbon installation.
 * @var $gibbon Gibbon\Core
 *      The container core of Gibbon installation.
 * @var $connection2 PDO
 *      PDO connection object to default database.
 *
 *
 * Boolean flags:
 *
 * @var $cacheLoad bool
 *      Boolean flag if cache is loaded or not.
 * @var $hasTopGap bool
 *      Boolean flag if the top .minorLinks div should render with a gap.
 * @var $sidebar bool
 *      Wether if the page should render the sidebar.
 *
 *
 * General contents:
 *
 * @var $page Page
 *      Global page object for rendering.
 * @var $title string
 *      HTML page title.
 * @var $session->get('absoluteURL') string
 *      String of the full base path of the Giddon installation.
 * @var $personalBackground string
 *      Path to personal background image.
 * @var $datepickerLocale string
 *      Locale code for datepicker.
 * @var $pdo Gibbon\Database\Connection
 *      Primary database connection object.
 * @var $sessionDuration int
 *      Number of seconds until session expire. If equal -1, session will
 *      never expire.
 * @var $headerLogoLink string
 *      Path that the header logo would link to.
 * @var $headerLogo string
 *      URL of the header logo image.
 * @var $headerFinder string?
 *      HTML string of the header finder, or null.
 * @var $headereMenu string
 *      HTML string of the header menu.
 * @var $notificationTray string
 *      HTML string of the notification tray in header.
 * @var $moduleMenu string?
 *      HTML string of module menu, or null.
 * @var $easyReturnHTML string?
 *      HTML string of the easy return message, or null.
 * @var $footerAuthor string
 *      HTML string of auther credit.
 * @var $footerLicense string
 *      HTML string of license links and declaration.
 * @var $footerThemeAuthor string?
 *      HTML string of theme author information, if any. Or null.
 * @var $footerLogo string
 *      URL path to the footer logo.




<!DOCTYPE html PUBLIC "-// W3C// DTD XHTML 1.0 Transitional// EN" "http:// www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http:// www.w3.org/1999/xhtml">
    <head>
        <title><?php echo $page->getTitle(); ?></title>
        <meta charset="utf-8"/>
        <meta name="author" content="Ross Parker, International College Hong Kong"/>

        <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico"/>

        <!-- js stylesheets -->
        <!-- <?php echo Page::renderStyleheets($page->stylesheets(), ['context' => 'head']); ?> -->
        <!-- js stylesheets end -->

        <!-- js initialization -->
        <script type='text/javascript'>
            window.Gibbon = <?php echo json_encode(
                [
                    'behaviour' => [
                        'datepicker' => [
                            'locale' => $datepickerLocale,
                        ],
                        'thickbox' => [
                            'pathToImage' => $session->get('absoluteURL') .
                                '/lib/thickbox/loadingAnimation.gif',
                        ],
                        'tinymce' => [
                            'valid_elements' => getSettingByScope(
                                $connection2, 'System', 'allowableHTML'
                            ),
                        ],
                        'sessionTimeout' => [
                            'sessionDuration' => $sessionDuration,
                            'message' => __(
                                $guid,
                                'Your session is about to expire: '.
                                'you will be logged out shortly.'
                            ),
                        ]
                    ],
                ]
            ); ?>;
        </script>
        <!-- js initialization end -->

        <!-- js scripts -->
        <!-- <?php echo Page::renderScripts($page->scripts(), ['context' => 'head']); ?> -->
        <!-- js scripts end -->

        <!-- head extras -->
        <?php foreach ($head_extras as $head_extra): ?>
            <?php echo $head_extra; ?>
        <?php endforeach; ?>
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
            <div class='minorLinks<?php echo $hasTopGap ? ' minorLinksTopGap' : '' ?>'>
                <?php echo getMinorLinks($connection2, $guid, $cacheLoad); ?>
            </div>
            <div id="wrap">
                <div id="header">
                    <div id="header-logo">
                        <a href='<?php echo $headerLogoLink ?>'><img
                            height='100px' width='400px' class="logo" alt="Logo"
                            src="<?php echo $headerLogo; ?>"
                        /></a>
                    </div>
                    <div id="header-finder">
                        <?php echo $headerFinder; ?>
                    </div>
                    <div id="header-menu">
                        <?php echo $headerMenu; ?>
                        <div class='notificationTray'>
                            <?php echo $notificationTray; ?>
                        </div>
                    </div>
                </div><!--/#header-->
                <div id="content-wrap">
                    <div id='<?php echo (!$sidebar) ? 'content-wide' : 'content'; ?>'>
                        <?php echo $moduleMenu; ?>
                        <?php echo $easyReturnHTML; ?>
                        <?php echo implode("\n", $contents); ?>
                    </div>
                    <?php if ($sidebar) { ?>
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
        <?php echo Page::renderScripts($page->scripts(), ['context' => 'foot']); ?>
    </body>
</html>

*/
