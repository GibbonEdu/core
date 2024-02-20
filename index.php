<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Domain\System\HookGateway;
use Gibbon\Domain\System\ModuleGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Messenger\MessengerGateway;
use Gibbon\Domain\DataUpdater\DataUpdaterGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Http\Url;

/**
 * BOOTSTRAP
 *
 * The bootstrapping process creates the essential variables and services for
 * Gibbon. These are required for all scripts: page views, CLI and API.
 */
// Gibbon system-wide include
require_once './gibbon.php';

// Module include: Messenger has a bug where files have been relying on these
// functions because this file was included via getNotificationTray()
// TODO: Fix that :)
require_once './modules/Messenger/moduleFunctions.php';

// Setup the Page and Session objects
$theme = $container->get('theme');
$page = $container->get('page');
$session = $container->get('session');

$isLoggedIn = $session->has('username') && $session->has('gibbonRoleIDCurrent');

$settingGateway = $container->get(SettingGateway::class);

/**
 * USER ROLES
 * Force the Gibbon role information to be reloaded every page
 */
if ($isLoggedIn) {
    $userDetails = $container->get(UserGateway::class)->getByID($session->get('gibbonPersonID'), ['gibbonRoleIDPrimary', 'gibbonRoleIDAll']);

    $session->set('gibbonRoleIDPrimary', $userDetails['gibbonRoleIDPrimary']);
    $allRoles = explode(',', $userDetails['gibbonRoleIDAll']);

    if (in_array($session->get('gibbonRoleIDCurrent'), $allRoles) === false) {
        $session->set('gibbonRoleIDCurrent', $userDetails['gibbonRoleIDPrimary']);
    }
}

/**
 * MODULE BREADCRUMBS
 */
if ($isLoggedIn && $module = $page->getModule()) {
    $page->breadcrumbs->setBaseURL(Url::fromModuleRoute($module->name));
    $page->breadcrumbs->add($module->type == 'Core' ? __($module->name) : __m($module->name), $module->entryURL);
}

/**
 * CACHE & INITIAL PAGE LOAD
 *
 * The 'pageLoads' value is used to run code when the user first logs in, and
 * also to reload cached content based on the $caching value in config.php
 *
 * TODO: When we implement routing, these can become part of the HTTP middleware.
 */
$session->set('pageLoads', !$session->exists('pageLoads') ? 0 : $session->get('pageLoads', -1) + 1);

$cacheLoad = true;
$caching = $gibbon->getConfig('caching');
if (!empty($caching) && is_numeric($caching)) {
    $cacheLoad = $session->get('pageLoads') % intval($caching) == 0;
}

/**
 * SYSTEM SETTINGS
 *
 * Checks to see if system settings are set from database. If not, tries to
 * load them in. If this fails, something horrible has gone wrong ...
 *
 * TODO: Move this to the Session creation logic.
 * TODO: Handle the exit() case with a pre-defined error template.
 */
if (!$session->has('systemSettingsSet')) {
    getSystemSettings($guid, $connection2);

    if (!$session->has('systemSettingsSet')) {
        exit(__('System Settings are not set: the system cannot be displayed'));
    }
}

/**
 * USER REDIRECTS
 *
 * TODO: When we implement routing, these can become part of the HTTP middleware.
 */

// Check for force password reset flag
if ($session->has('passwordForceReset')) {
    if ($session->get('passwordForceReset') == 'Y' and $session->get('address') != 'preferences.php') {
        header('Location: ' . Url::fromRoute('preferences')->withQueryParam('forceReset', 'Y'));
        exit();
    }
}

//Upgrade redirect
$upgrade = false;
$versionDB = $settingGateway->getSettingByScope('System', 'version');
$versionCode = $version;
if (version_compare($versionDB, $versionCode, '<') && isActionAccessible($guid, $connection2, '/modules/System Admin/update.php')) {
    if ($session->get('address') == '/modules/System Admin/update.php') {
        $upgrade = true;
    }
    else {
        header('Location: ' . Url::fromModuleRoute('System Admin', 'update'));
        exit();
    }
}

// Redirects after login
if ($session->get('pageLoads') == 0 && !$session->has('address')) { // First page load, so proceed

    if ($session->has('username')) { // Are we logged in?
        $roleCategory = $session->get('gibbonRoleIDCurrentCategory');

        // Deal with attendance self-registration redirect
        // Are we a student?
        if ($roleCategory == 'Student') {
            // Can we self register?
            if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_studentSelfRegister.php')) {
                // Check to see if student is on site
                $studentSelfRegistrationIPAddresses = $settingGateway->getSettingByScope(
                    'Attendance',
                    'studentSelfRegistrationIPAddresses'
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
                            }

                            if ($result->rowCount() == 0) {
                                // No registration yet
                                // Redirect!
                                $URL = Url::fromModuleRoute('Attendance', 'attendance_studentSelfRegister')
                                    ->withQueryParam('redirect', 'true');
                                $session->forget('pageLoads');
                                header("Location: {$URL}");
                                exit;
                            }
                        }
                    }
                }
            }
        }

        // Deal with Data Updater redirect (if required updates are enabled)
        $requiredUpdates = $settingGateway->getSettingByScope('Data Updater', 'requiredUpdates');
        if ($requiredUpdates == 'Y') {
            if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_updates.php')) { // Can we update data?
                $redirectByRoleCategory = $settingGateway->getSettingByScope(
                    'Data Updater',
                    'redirectByRoleCategory'
                );
                $redirectByRoleCategory = explode(',', $redirectByRoleCategory);

                // Are we the right role category?
                if (in_array($roleCategory, $redirectByRoleCategory)) {
                    $gateway = new DataUpdaterGateway($pdo);

                    $updatesRequiredCount = $gateway->countAllRequiredUpdatesByPerson($session->get('gibbonPersonID'));

                    if ($updatesRequiredCount > 0) {
                        $URL = Url::fromModuleRoute('Data Updater', 'data_updates')->withQueryParam('redirect', 'true');
                        $session->forget('pageLoads');
                        header("Location: {$URL}");
                        exit;
                    }
                }
            }
        }
    }
}

/**
 * SIDEBAR SETUP
 *
 * TODO: move all of the sidebar session variables to the $page->addSidebarExtra() method.
 */

// Set sidebar extra content values via Session.
$session->set('sidebarExtra', '');
$session->set('sidebarExtraPosition', 'top');

// Check the current Action 'entrySidebar' to see if we should display a sidebar
$page['showSidebar'] = $page->getAction()
    ? $page->getAction()['entrySidebar'] != 'N'
    : true;

// Override showSidebar if the URL 'sidebar' param is explicitly set
if (!empty($_GET['sidebar'])) {
    $page['showSidebar'] = strtolower($_GET['sidebar']) !== 'false';
}

/**
 * SESSION TIMEOUT
 *
 * Set session duration, and ensures a minimum session duration of 1200.
 */
$sessionDuration = -1;
if ($isLoggedIn) {
    $sessionDuration = $session->get('sessionDuration');
    $sessionDuration = max(intval($sessionDuration), 1200);
    $sessionDuration *= 1000; // Seconds to miliseconds

    // Set a hard limit for session durations, handled server-side.
    // This helps catch cases where the client-side timeout does not kick in.
    $sessionLastActive = $session->get('sessionLastActive', null);
    $sessionHardLimit = $session->get('sessionDuration') + 600;
    if (!empty($sessionLastActive) && time() - $sessionLastActive > $sessionHardLimit ) {
        $URL = $session->get('absoluteURL').'/logout.php?timeout=true';
        header("Location: {$URL}");
        exit();
    }
    $session->set('sessionLastActive', time());
}

/**
 * LOCALE
 *
 * Sets the i18n locale for jQuery UI DatePicker (if the file exists, otherwise
 * falls back to en-GB)
 */
$localeCode = str_replace('_', '-', $session->get('i18n')['code']);
$localeCodeShort = substr($session->get('i18n')['code'], 0, 2);
$localePath = $session->get('absolutePath').'/lib/jquery-ui/i18n/datepicker-%1$s.js';

$datepickerLocale = 'en-GB';
if ($localeCode === 'en-US' || is_file(sprintf($localePath, $localeCode))) {
    $datepickerLocale = $localeCode;
} elseif (is_file(sprintf($localePath, $localeCodeShort))) {
    $datepickerLocale = $localeCodeShort;
}

// Allow the URL to override system default from the i18l param
if (!empty($_GET['i18n']) && $gibbon->locale->getLocale() != $_GET['i18n']) {
    $data = ['code' => $_GET['i18n']];
    $sql = "SELECT * FROM gibboni18n WHERE code=:code LIMIT 1";

    if ($result = $pdo->selectOne($sql, $data)) {
        setLanguageSession($guid, $result, false);
        $gibbon->locale->setLocale($_GET['i18n']);
        $gibbon->locale->setTextDomain($pdo);
        $localeCode = str_replace('_', '-', $gibbon->locale->getLocale());
        $cacheLoad = true;
    }
}

/**
 * JAVASCRIPT
 *
 * The config array defines a set of PHP values that are encoded and passed to
 * the setup.js file, which handles initialization of js libraries.
 */
$javascriptConfig = [
    'config' => [
        'datepicker' => [
            'locale' => $datepickerLocale,
            'dateFormat' => str_replace('yyyy', 'yy', $session->get('i18n')['dateFormat']),
            'firstDay' => $session->get('firstDayOfTheWeek') == 'Monday'? 1 : ($session->get('firstDayOfTheWeek') == 'Saturday' ? 6 : 0),
        ],
        'thickbox' => [
            'pathToImage' => $session->get('absoluteURL').'/lib/thickbox/loadingAnimation.gif',
        ],
        'tinymce' => [
            'valid_elements' => $settingGateway->getSettingByScope('System', 'allowableHTML'),
        ]
    ],
];

/**
 * There are currently a handful of scripts that must be in the page <HEAD>.
 * Otherwise, the preference is to add javascript to the 'foot' at the bottom
 * of the page, which speeds up rendering by deferring their execution until
 * after all content has loaded.
 */

// Set page scripts: head
$page->scripts->addMultiple([
    'lv'             => 'lib/LiveValidation/livevalidation_standalone.compressed.js',
    'jquery'         => 'lib/jquery/jquery.js',
    'jquery-migrate' => 'lib/jquery/jquery-migrate.min.js',
    'jquery-ui'      => 'lib/jquery-ui/js/jquery-ui.min.js',
    'jquery-time'    => 'lib/jquery-timepicker/jquery.timepicker.min.js',
    'jquery-chained' => 'lib/chained/jquery.chained.min.js',
    'core'           => 'resources/assets/js/core.min.js',
], ['context' => 'head']);

// Set page scripts: foot - jquery
$page->scripts->addMultiple([
    'jquery-latex'    => 'lib/jquery-jslatex/jquery.jslatex.js',
    'jquery-form'     => 'lib/jquery-form/jquery.form.js',
    'jquery-autosize' => 'lib/jquery-autosize/jquery.autosize.min.js',
    'jquery-token'    => 'lib/jquery-tokeninput/src/jquery.tokeninput.js',
], ['context' => 'foot']);

//This sets the default for en-US, or changes for none en-US
if($datepickerLocale !== 'en-US'){
    $page->scripts->add('jquery-date', 'lib/jquery-ui/i18n/datepicker-'.$datepickerLocale.'.js');
}

// Set page scripts: foot - misc
$thickboxInline = 'var tb_pathToImage="'.$session->get('absoluteURL').'/lib/thickbox/loadingAnimation.gif";';
$page->scripts->add('thickboxi', $thickboxInline, ['type' => 'inline']);
$page->scripts->addMultiple([
    'thickbox' => 'lib/thickbox/thickbox-compressed.js',
    'tinymce'  => 'lib/tinymce/tinymce.min.js',
], ['context' => 'foot']);

// Set page scripts: foot - core
$page->scripts->add('core-config', 'window.Gibbon = '.json_encode($javascriptConfig).';', ['type' => 'inline']);
$page->scripts->add('core-setup', 'resources/assets/js/setup.js');

// Register scripts available to the core, but not included by default
$page->scripts->register('chart', 'lib/Chart.js/3.0/chart.min.js', ['context' => 'head']);
$page->scripts->register('instascan', 'lib/instascan/instascan.min.js', ['context' => 'head']);

// Set system analytics code from session cache
$page->addHeadExtra($session->get('analytics'));

/**
 * STYLESHEETS & CSS
 */
$page->stylesheets->addMultiple([
    'jquery-ui'    => 'lib/jquery-ui/css/blitzer/jquery-ui.css',
    'jquery-time'  => 'lib/jquery-timepicker/jquery.timepicker.css',
    'thickbox'     => 'lib/thickbox/thickbox.css',
], ['weight' => -1]);

// Add right-to-left stylesheet
if ($session->get('i18n')['rtl'] == 'Y') {
    $page->theme->stylesheets->add('theme-rtl', '/themes/'.$session->get('gibbonThemeName').'/css/main_rtl.css', ['weight' => 1]);
}

// Set personal, organisational or theme background
if ($settingGateway->getSettingByScope('User Admin', 'personalBackground') == 'Y' && $session->has('personalBackground')) {
    $backgroundImage = htmlPrep($session->get('personalBackground'));
    $backgroundScroll = 'repeat scroll center top';
} else if ($session->has('organisationBackground')) {
    $backgroundImage = $session->get('absoluteURL').'/'.$session->get('organisationBackground');
    $backgroundScroll = 'repeat fixed center top';
}

if (!empty($backgroundImage)) {
    $page->addData(['bodyBackground' => 'background: url("'.$backgroundImage.'") '.$backgroundScroll.' #626cd3!important;background-size: cover !important;']);
}

$page->stylesheets->add('theme-dev', 'resources/assets/css/theme.min.css');
$page->stylesheets->add('core', 'resources/assets/css/core.min.css', ['weight' => 10]);

/**
 * USER CONFIGURATION
 *
 * This should be moved to a one-time process to run after login, which can be
 * handled by HTTP middleware.
 */

// Try to auto-set user's calendar feed if not set already
if ($session->exists('calendarFeedPersonal') && $session->exists('googleAPIAccessToken')) {
    if (!$session->has('calendarFeedPersonal') && $session->has('googleAPIAccessToken')) {
        $service = $container->get('Google_Service_Calendar');
        try {
            $calendar = $service->calendars->get('primary');
        } catch (\Google_Service_Exception $e) {
        } catch (\InvalidArgumentException $e) {}

        if (!empty($calendar['id'])) {
            $session->set('calendarFeedPersonal', $calendar['id']);
            $container->get(UserGateway::class)->update($session->get('gibbonPersonID'), [
                'calendarFeedPersonal' => $calendar['id'],
            ]);
        }
    }
}

// Get house logo and set session variable, only on first load after login (for performance)
if ($session->get('pageLoads') == 0 and $session->has('username') and !$session->has('gibbonHouseIDLogo')) {
    $dataHouse = array('gibbonHouseID' => $session->get('gibbonHouseID'));
    $sqlHouse = 'SELECT logo, name FROM gibbonHouse
        WHERE gibbonHouseID=:gibbonHouseID';
    $house = $pdo->selectOne($sqlHouse, $dataHouse);

    if (!empty($house)) {
        $session->set('gibbonHouseIDLogo', $house['logo']);
        $session->set('gibbonHouseIDName', $house['name']);
    }
}

// Show warning if not in the current school year
// TODO: When we implement routing, these can become part of the HTTP middleware.
if ($isLoggedIn) {
    if ($session->get('gibbonSchoolYearID') != $session->get('gibbonSchoolYearIDCurrent')) {
        $page->addWarning('<b><u>'.sprintf(__('Warning: you are logged into the system in school year %1$s, which is not the current year.'), $session->get('gibbonSchoolYearName')).'</b></u>'.__('Your data may not look quite right (for example, students who have left the school will not appear in previous years), but you should be able to edit information from other years which is not available in the current year.'));
    }
}

// Maintenance Mode
$maintenanceMode = $settingGateway->getSettingByScope('System Admin', 'maintenanceMode');
if ($maintenanceMode == 'Y') {
    $page->addAlert('<b>'.__('MAINTENANCE MODE').'</b>: '.$settingGateway->getSettingByScope('System Admin', 'maintenanceModeMessage'), 'error');

    if ($isLoggedIn && $session->get('gibbonRoleIDPrimary') != '001') {
        $URL = $session->get('absoluteURL').'/logout.php?timeout=force';
        header("Location: {$URL}");
        exit();
    }
}

// Cookie Consent
if ($isLoggedIn) {
    if (!empty($_GET['cookieConsent'])) {
        $container->get(UserGateway::class)->update($session->get('gibbonPersonID'), ['cookieConsent' => 'Y']);
        $session->set('cookieConsent', 'Y');
    }

    $cookieConsentEnabled = $settingGateway->getSettingByScope('System Admin', 'cookieConsentEnabled');
    $privacyPolicy = $settingGateway->getSettingByScope('System Admin', 'privacyPolicy');
    if ($cookieConsentEnabled == 'Y' && $session->get('cookieConsent') != 'Y') {
        $page->addData([
            'cookieConsentEnabled' => 'Y',
            'cookieConsentText' => $settingGateway->getSettingByScope('System Admin', 'cookieConsentText'),
            'hasPrivacyPolicy' => !empty($privacyPolicy),
            'redirectTo' => http_build_query($_GET),
        ]);
    }
}

/**
 * MENU ITEMS & FAST FINDER
 *
 * TODO: Move this somewhere more sensible.
 */
if ($isLoggedIn && !$upgrade) {
    if ($cacheLoad || !$session->has('fastFinder')) {
        $templateData = getFastFinder($connection2, $guid);
        $templateData['enrolmentCount'] = $container->get(StudentGateway::class)->getStudentEnrolmentCount($session->get('gibbonSchoolYearID'));

        $fastFinder = $page->fetchFromTemplate('finder.twig.html', $templateData);
        $session->set('fastFinder', $fastFinder);
    }

    /**
     * @var ModuleGateway
     */
    $moduleGateway = $container->get(ModuleGateway::class);

    if ($page->getModule()) {
        $currentModule = $page->getModule()->getName();
        $menuModule = $session->get('menuModuleName');

        if ($cacheLoad || !$session->has('menuModuleItems') || $currentModule != $menuModule) {
            $menuModuleItems = $moduleGateway->selectModuleActionsByRole($page->getModule()->getID(), $session->get('gibbonRoleIDCurrent'))->fetchGrouped();
        } else {
            $menuModuleItems = $session->get('menuModuleItems');
        }

        // Update the menu items to indicate the current active action
        foreach ($menuModuleItems as $category => &$items) {
            foreach ($items as &$item) {
                $urlList = array_map('trim', explode(',', $item['URLList']));
                $item['active'] = in_array($session->get('action'), $urlList);
                $item['url'] = (string) Url::fromModuleRoute(
                    $item['moduleName'],
                    preg_replace('/\.php$/i', '', $item['entryURL'])
                );
            }
        }

        $session->set('menuModuleItems', $menuModuleItems);
        $session->set('menuModuleName', $currentModule);
    } else {
        $session->forget(['menuModuleItems', 'menuModuleName']);
    }

    if ($cacheLoad || !$session->has('menuMainItems')) {
        $menuMainItems = $moduleGateway->selectModulesByRole($session->get('gibbonRoleIDCurrent'))->fetchGrouped();

        foreach ($menuMainItems as $category => &$items) {
            foreach ($items as &$item) {
                $entryURL = ($item['entryURL'] == 'index.php' || isActionAccessible($guid, $connection2, '/modules/'.$item['name'].'/'.$item['entryURL']))
                    ? $item['entryURL']
                    : $item['alternateEntryURL'];

                // Note: only for backward compatibility. Should remove .php
                // from the gibbonAction table.
                $entryURL = preg_replace('/\.php$/i', '', $entryURL);

                $item['active'] = $session->get('menuModuleName') == $item['name'];
                $item['url'] =  (string) Url::fromModuleRoute($item['name'], $entryURL);
            }
        }

        $session->set('menuMainItems', $menuMainItems);
    }

    // Setup cached message array only if there are recent posts, or if more than one hour has elapsed
    $messageWallLatestPost = $container->get(MessengerGateway::class)->getRecentMessageWallTimestamp();
    $messageWallRefreshed = $session->get('messageWallRefreshed', 0);

    $timeDifference = $messageWallRefreshed - $messageWallLatestPost;
    if (!$session->exists('messageWallArray') || ($messageWallLatestPost >= $messageWallRefreshed) || (time() - $messageWallRefreshed > 3600)) {
        $messageGateway = $container->get(MessengerGateway::class);
        $session->set('messageWallArray', $messageGateway->getMessages('array'));
        $session->set('messageWallRefreshed', time());
    }
}

/**
 * TEMPLATE DATA
 *
 * These values are merged with the Page class settings & content, then passed
 * into the template engine for rendering. They're a work in progress, but once
 * they're more finalized we can document them for theme developers.
 */

$page->addData([
    'isLoggedIn'        => $isLoggedIn,
    'organisationLogo'  => $session->get('organisationLogo'),
    'organisationName'  => $session->get('organisationName'),
    'cacheString'       => $session->get('cacheString'),
    'version'           => $gibbon->getVersion(),
    'versionName'       => 'v'.$gibbon->getVersion().($session->get('cuttingEdgeCode') == 'Y'? 'dev' : ''),
    'rightToLeft'       => $session->get('i18n')['rtl'] == 'Y',
    'lang'              => $localeCode,
    'address'           => $page->getAddress(),
]);

if ($isLoggedIn) {
    $page->addData([
        'menuMain'       => $session->get('menuMainItems', []),
        'menuModule'     => $session->get('menuModuleItems', []),
        'fastFinder'     => $session->get('fastFinder'),
    ]);
}

/**
 * GET PAGE CONTENT
 *
 * TODO: move queries into Gateway classes.
 * TODO: rewrite dashboards as template files.
 */
if (!$session->has('address')) {
    // Welcome message
    if (!$isLoggedIn) {
        // Create auto timeout message
        if (isset($_GET['timeout'])) {
            $page->addWarning(
                $_GET['timeout'] == 'force'
                    ? __('You have been manually logged out of {system} by a system administrator.', ['system' => $session->get('systemName')])
                    : __('Your session expired, so you were automatically logged out of the system.'));
        }

        $templateData = [
            'indexText'                 => $session->get('indexText'),
            'organisationName'          => $session->get('organisationName'),
            'admissionsEnabled'         => $settingGateway->getSettingByScope('Admissions', 'admissionsEnabled') == 'Y',
            'admissionsLinkText'        => $settingGateway->getSettingByScope('Admissions', 'admissionsLinkText'),
            'admissionsLinkName'        => $settingGateway->getSettingByScope('Admissions', 'admissionsLinkName'),
            'publicRegistration'        => $settingGateway->getSettingByScope('User Admin', 'enablePublicRegistration') == 'Y',
            'publicStudentApplications' => $settingGateway->getSettingByScope('Application Form', 'publicApplications') == 'Y',
            'publicStaffApplications'   => $settingGateway->getSettingByScope('Staff Application Form', 'staffApplicationFormPublicApplications') == 'Y',
            'makeDepartmentsPublic'     => $settingGateway->getSettingByScope('Departments', 'makeDepartmentsPublic') == 'Y',
            'makeUnitsPublic'           => $settingGateway->getSettingByScope('Planner', 'makeUnitsPublic') == 'Y',
            'privacyPolicy'             => $settingGateway->getSettingByScope('System Admin', 'privacyPolicy'),
        ];

        // Get any elements hooked into public home page, checking if they are turned on
        $hooks = $container->get(HookGateway::class)->selectHooksByType('Public Home Page')->fetchAll();

        foreach ($hooks as $hook) {
            $options = unserialize(str_replace("'", "\'", $hook['options']));
            $check = $settingGateway->getSettingByScope($options['toggleSettingScope'], $options['toggleSettingName']);
            if ($check == $options['toggleSettingValue']) { // If its turned on, display it
                $matches = [];
                preg_match("/href=\\\'.([^\\\]*)\\\'/", $options['text'], $matches);
                $options['url'] = $matches[1] ?? '';
                $options['text'] = stripslashes(strip_tags($options['text']));
                $templateData['indexHooks'][] = $options;
            }
        }

        $page->writeFromTemplate('welcome.twig.html', $templateData);

    } else {
        // Pinned Messages
        $pinnedMessagesOnHome = $settingGateway->getSettingByScope('Messenger', 'pinnedMessagesOnHome');
        if ($pinnedMessagesOnHome == 'Y' && isActionAccessible($guid, $connection2, '/modules/Messenger/messageWall_view.php')) {
            $pinnedMessages = array_reduce($session->get('messageWallArray', []), function ($group, $item) {
                if ($item['messageWallPin'] == 'Y') {
                    $group[$item['gibbonMessengerID']] = $item;
                }
                return $group;
            }, []);

            $session->set('pinnedMessages', $pinnedMessages);

            if ($session->has('pinnedMessages')) {
                $page->writeFromTemplate('ui/pinnedMessages.twig.html', ['pinnedMessages' => $session->get('pinnedMessages')]);
            }
        }

        // Custom content loader
        $globals = [
            'guid'        => $guid,
            'connection2' => $connection2,
            'session'     => $session,
        ];

        $session->set('index_custom.php', $page->fetchFromFile('./index_custom.php', $globals));

        if ($session->has('index_custom.php')) {
            $page->write($session->get('index_custom.php'));
        }

        // DASHBOARDS!
        $category = $session->get('gibbonRoleIDCurrentCategory');

        switch ($category) {
            case 'Parent':
                if ($settingGateway->getSettingByScope('School Admin', 'parentDashboardEnable') != "N") {
                    $page->write($container->get(Gibbon\UI\Dashboard\ParentDashboard::class)->getOutput());
                }
                break;
            case 'Student':
                if ($settingGateway->getSettingByScope('School Admin', 'studentDashboardEnable') != "N") {
                    $page->write($container->get(Gibbon\UI\Dashboard\StudentDashboard::class)->getOutput());
                }
                break;
            case 'Staff':
                if ($settingGateway->getSettingByScope('School Admin', 'staffDashboardEnable') != "N") {
                    $page->write($container->get(Gibbon\UI\Dashboard\StaffDashboard::class)->getOutput());
                }
                break;
            case 'Other':
                break;
            default:
                $page->write('<div class="error">'.__('Your current role type cannot be determined.').'</div>');
        }
    }
} else {
    $address = trim($page->getAddress(), ' /');

    if ($page->isAddressValid($address, true) == false) {
        $page->addError(__('Illegal address detected: access denied.'));
    } else {
        // Pass these globals into the script of the included file, for backwards compatibility.
        // These will be removed when we begin the process of ooifying action pages.
        $globals = [
            'guid'        => $guid,
            'gibbon'      => $gibbon,
            'version'     => $version,
            'pdo'         => $pdo,
            'connection2' => $connection2,
            'autoloader'  => $autoloader,
            'container'   => $container,
            'page'        => $page,
            'session'     => $session,
        ];

        if (is_file('./'.$address)) {
            $page->writeFromFile('./'.$address, $globals);
        } else {
            $page->writeFromTemplate('error.twig.html');
        }
    }
}

/**
 * HEADER DATA
 *
 * Add this after loading page content, so it can update based on page changes.
 */
if ($isLoggedIn) {
    $header = $container->get(Gibbon\UI\Components\Header::class);

    $page->addData([
        'currentUser'       => $header->getUserDetails(),
        'minorLinks'        => $header->getMinorLinks(),
        'statusTray'        => $header->getStatusTray(),
    ]);
}

/**
 * RETURN PROCESS
 *
 * Adds an alert to the index based on the URL 'return' parameter.
 */
if (!empty($_GET['return'])) {
    if (!($session->get('address') == 'notifications.php' AND $session->get('username') == '')) {
        if ($alert = $page->return->process($_GET['return'])){
            $page->addAlert($alert['text'], $alert['context']);
        }
    }
}

/**
 * GET SIDEBAR CONTENT
 *
 * TODO: rewrite the Sidebar class as a template file.
 */
$sidebarContents = '';
if ($page['showSidebar']) {
    $page->addSidebarExtra($session->get('sidebarExtra'));
    $session->set('sidebarExtra', '');

    $page->addData([
        'sidebar'         => $page['showSidebar'],
        'sidebarContents' => $container->get(Gibbon\UI\Components\Sidebar::class)->getOutput(),
        'sidebarPosition' => $session->get('sidebarExtraPosition'),
    ]);
}


/**
 * DONE!!
 */
echo $page->render('index.twig.html');
