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

use Gibbon\View\Page;
use Gibbon\Forms\Form;
use Gibbon\Data\Validator;
use Gibbon\Install\Config;
use Gibbon\Install\Context;
use Gibbon\Services\Format;
use Gibbon\Database\Updater;
use Gibbon\Database\Connection;
use Gibbon\Database\MySqlConnector;
use Gibbon\Install\HttpInstallController;
use Gibbon\Install\Installer;

include '../version.php';
include '../gibbon.php';

//Module includes
require_once '../modules/System Admin/moduleFunctions.php';

// Sanitize the whole $_POST array
$validator = $container->get(Validator::class);
$_POST = $validator->sanitize($_POST);

// Get or set the current step
$step = isset($_GET['step'])? intval($_GET['step']) : 0;
$step = min(max($step, 0), 3);

// Deal with $guid setup, otherwise get and filter the existing $guid
if (empty($step)) {
    $step = 0;
    $guid = Config::randomGuid();
    error_log(sprintf('Installer: Step %s: assigning random guid: %s', var_export($step, true), var_export($guid, true)));
} else {
    $guid = $_POST['guid'] ?? '';
    $guid = preg_replace('/[^a-z0-9-]/', '', substr($guid, 0, 36));
    error_log(sprintf('Installer: Step %s: Using guid from $_POST: %s', var_export($step, true), isset($_POST['guid']) ? var_export($_POST['guid'], true): 'undefined'));
}

 /**
  * @var \Gibbon\Core $gibbon
  * @var \Gibbon\Session\Session $session
  */
// Use the POSTed GUID in place of "undefined".
// Later steps have the guid in the config file but without
// a way to store variables relibly prior to that, installation can fail
$session->setGuid($guid);
$session->set('guid', $guid);
$session->set('absolutePath', realpath(__DIR__ . '/../'));

// Generate and save a nonce for forms on this page to use
$nonce = hash('sha256', substr(mt_rand().date('zWy'), 0, 36));
$sessionNonce = $session->get('nonce', []);
$sessionNonce[$step+1] = $nonce;
$session->set('nonce', $sessionNonce);

// Deal with non-existent stringReplacement session
$session->set('stringReplacement', []);

// Fix missing locale causing failed page load
if (empty($gibbon->locale->getLocale())) {
    $gibbon->locale->setLocale('en_GB');
}

// Create a controller instance.
$controller = HttpInstallController::create(
    $container,
    $session
);

$page = new Page($container->get('twig'), [
    'title'   => __('Gibbon Installer'),
    'address' => '/installer/install.php',
]);

// Generate installer object.
$installer = new Installer($container->get('twig'));

// Generate installation context from the environment.
$context = (Context::fromEnvironment())
    ->setInstallPath(dirname(__DIR__));

ob_start();

// Attempt to download & install the required language files
$locale_code = $_POST['code'] ?? 'en_GB';

//Set language pre-install
if (function_exists('gettext')) {
    $gibbon->locale->setLocale($locale_code);
    bindtextdomain('gibbon', '../i18n');
    textdomain('gibbon');
}

$isConfigValid = true;

$steps = [
    1 => __('System Requirements'),
    2 => __('Database Settings'),
    3 => __('User Account'),
    4 => __('Installation Complete'),
];

try {

    // Check session for the presence of a valid nonce; if found, remove it so it's used only once.
    if ($step >= 1) {
        $checkNonce = $_POST['nonce'] ?? '';
        if (!empty($sessionNonce[$step]) && $sessionNonce[$step] == $checkNonce) {
            unset($sessionNonce[$step]);
        } else {
            error_log(sprintf('Debug: expected nonce %s, got %s', var_export($sessionNonce[$step], true), var_export($checkNonce, true)));
            throw new \Exception(__('Your request failed because you do not have access to this action.'));
        }
    }

    if ($step == 0) { //Choose language
        echo $controller->viewStepOne($nonce, $gibbon->getConfig('version'));
    } else if ($step == 1) { //Set database options
        echo $controller->viewStepTwo($locale_code, $nonce);
    } elseif ($step == 2) {
        // Check for the presence of a config file (if it hasn't been created yet)
        $context->validateConfigPath();

        // Get and set database variables (not set until step 1)
        $config = HttpInstallController::parseConfigSubmission($guid, $_POST);

        // Run database installation of the config.
        $installer->install($context, $config);

        // Render step 3 form.
        echo $controller->viewStepThree(
            $config,
            $installer,
            $nonce,
            $version
        );
    } elseif ($step == 3) {
        //New PDO DB connection
        $mysqlConnector = new MySqlConnector();

        try {
            $pdo = $mysqlConnector->connect($gibbon->getConfig(), true);
            $connection2 = $pdo->getConnection();
            $installer->setConnection($connection2);
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo '<div>' . sprintf(__('A database connection could not be established. Please %1$stry again%2$s.'), "<a href='./install.php'>", '</a>') . '</div>';
            echo '<div>' . sprintf(__('Error details: {error_message}', ['error_message' => $e->getMessage()])) . '</div>';
            echo '</div>';
        }

        // check if correctly created the PDO object.
        if (!$pdo instanceof Connection) {
            throw new \Exception('Internal Error: Connection type incorrect.');
        }

        // Get user account details
        $title = $_POST['title'];
        $surname = $_POST['surname'];
        $firstName = $_POST['firstName'];
        $preferredName = $_POST['firstName'];
        $username = $_POST['username'];
        $password = $_POST['passwordNew'];
        $passwordConfirm = $_POST['passwordConfirm'];
        $email = $_POST['email'];
        $support = isset($_POST['support']) and $_POST['support'] == 'true';

        // Get system settings
        $absoluteURL = $_POST['absoluteURL'];
        $absolutePath = $_POST['absolutePath'];
        $systemName = $_POST['systemName'];
        $organisationName = $_POST['organisationName'];
        $organisationNameShort = $_POST['organisationNameShort'];
        $currency = $_POST['currency'];
        $timezone = $_POST['timezone'];
        $country = $_POST['country'];
        $installType = $_POST['installType'];
        $statsCollection = $_POST['statsCollection'];
        $cuttingEdgeCode = $_POST['cuttingEdgeCodeHidden'];
        $gibboneduComOrganisationName = $_POST['gibboneduComOrganisationName'];
        $gibboneduComOrganisationKey = $_POST['gibboneduComOrganisationKey'];

        if ($surname == '' or $firstName == '' or $preferredName == '' or $email == '' or $username == '' or $password == '' or $passwordConfirm == '' or $email == '' or $absoluteURL == '' or $absolutePath == '' or $systemName == '' or $organisationName == '' or $organisationNameShort == '' or $timezone == '' or $country == '' or $installType == '' or $statsCollection == '' or $cuttingEdgeCode == '') {
            throw new \Exception(__('Some required fields have not been set, and so installation cannot proceed.'));
        }
        if ($password != $passwordConfirm) {
            throw new \Exception(__('Your request failed because your passwords did not match.'));
        }

        $salt = getSalt();
        $passwordStrong = hash('sha256', $salt.$password);

        $userFail = false;
        //Write to database
        try {
            $installer->createUser([
                'title' => $title,
                'surname' => $surname,
                'firstName' => $firstName,
                'preferredName' => $preferredName,
                'officialName' => ($firstName.' '.$surname),
                'username' => $username,
                'passwordStrong' => $passwordStrong,
                'passwordStrongSalt' => $salt,
                'status' => 'Full',
                'canLogin' => 'Y',
                'passwordForceReset' => 'N',
                'gibbonRoleIDPrimary' => '001',
                'gibbonRoleIDAll' => '001',
                'email' => $email,
            ]);
        } catch (\PDOException $e) {
            throw new \Exception(__('Errors occurred in populating the database; empty your database, remove ../config.php and %1$stry again%2$s.', ["<a href='./install.php'>", '</a>']));
        }

        try {
            $installer->setPersonAsStaff(1, 'Teaching');
        } catch (\PDOException $e) {
        }

        $settingsFail = false;
        $settingsFail = $settingsFail || !$installer->setSetting('absoluteURL', $absoluteURL);
        $settingsFail = $settingsFail || !$installer->setSetting('absolutePath', $absolutePath);
        $settingsFail = $settingsFail || !$installer->setSetting('systemName', $systemName);
        $settingsFail = $settingsFail || !$installer->setSetting('organisationName', $organisationName);
        $settingsFail = $settingsFail || !$installer->setSetting('organisationNameShort', $organisationNameShort);
        $settingsFail = $settingsFail || !$installer->setSetting('organisationEmail', $email);
        $settingsFail = $settingsFail || !$installer->setSetting('organisationAdministrator', 1);
        $settingsFail = $settingsFail || !$installer->setSetting('organisationDBA', 1);
        $settingsFail = $settingsFail || !$installer->setSetting('organisationHR', 1);
        $settingsFail = $settingsFail || !$installer->setSetting('organisationAdmissions', 1);
        $settingsFail = $settingsFail || !$installer->setSetting('gibboneduComOrganisationName', $gibboneduComOrganisationName);
        $settingsFail = $settingsFail || !$installer->setSetting('gibboneduComOrganisationKey', $gibboneduComOrganisationKey);
        $settingsFail = $settingsFail || !$installer->setSetting('currency', $currency);
        $settingsFail = $settingsFail || !$installer->setSetting('country', $country);
        $settingsFail = $settingsFail || !$installer->setSetting('timezone', $timezone);
        $settingsFail = $settingsFail || !$installer->setSetting('installType', $installType);
        $settingsFail = $settingsFail || !$installer->setSetting('statsCollection', $statsCollection);
        $settingsFail = $settingsFail || !$installer->setSetting('cuttingEdgeCode', $cuttingEdgeCode);
        $settingsFail = $settingsFail || !$installer->setSetting('email', $email, 'Finance');

        if ($statsCollection == 'Y') {
            $absolutePathProtocol = '';
            $absolutePath = '';
            if (substr($absoluteURL, 0, 7) == 'http://') {
                $absolutePathProtocol = 'http';
                $absolutePath = substr($absoluteURL, 7);
            } elseif (substr($absoluteURL, 0, 8) == 'https://') {
                $absolutePathProtocol = 'https';
                $absolutePath = substr($absoluteURL, 8);
            }
            echo "<iframe style='display: none; height: 10px; width: 10px' src='https://gibbonedu.org/services/tracker/tracker.php?absolutePathProtocol=".urlencode($absolutePathProtocol).'&absolutePath='.urlencode($absolutePath).'&organisationName='.urlencode($organisationName).'&type='.urlencode($installType).'&version='.urlencode($version).'&country='.$country."&usersTotal=1&usersFull=1'></iframe>";
        }

        if ($cuttingEdgeCode == 'Y') {
            $updater = $container->get(Updater::class);
            $errors = $updater->update();

            if (!empty($errors)) {
                echo Format::alert(__('Some aspects of your update failed.'));
            }

            $settingsFail = $settingsFail && !$installer->setSetting('cuttingEdgeCodeLine', $updater->cuttingEdgeMaxLine);
        }

        // Update DB version for existing languages (installed manually?)
        i18nCheckAndUpdateVersion($container, $version);

        //Deal with request to receive welcome email by calling gibbonedu.org iframe
        if ($support == true) {
            $absolutePathProtocol = '';
            $absolutePath = '';
            if (substr($absoluteURL, 0, 7) == 'http://') {
                $absolutePathProtocol = 'http';
                $absolutePath = substr($absoluteURL, 7);
            } elseif (substr($absoluteURL, 0, 8) == 'https://') {
                $absolutePathProtocol = 'https';
                $absolutePath = substr($absoluteURL, 8);
            }
            echo "<iframe class='support' style='display: none; height: 10px; width: 10px' src='https://gibbonedu.org/services/support/supportRegistration.php?absolutePathProtocol=".urlencode($absolutePathProtocol).'&absolutePath='.urlencode($absolutePath).'&organisationName='.urlencode($organisationName).'&email='.urlencode($email).'&title='.urlencode($title).'&surname='.urlencode($surname).'&preferredName='.urlencode($preferredName)."'></iframe>";
        }

        $form = Form::create('installer', "./install.php?step=4");
        $form->setTitle(__('Installation - Step {count}', ['count' => $step + 1]));
        $form->setMultiPartForm($steps, 4);
        echo $form->getOutput();

        if ($settingsFail == true) {
            $page->addError(__('Some settings did not save. The system may work, but you may need to remove everything and start again. Try and %1$sgo to your Gibbon homepage%2$s and login as user <u>admin</u> with password <u>gibbon</u>.', ["<a href='$absoluteURL'>", '</a>']));
            $page->addError(__('It is also advisable to follow the %1$sPost-Install and Server Config instructions%2$s.', ["<a target='_blank' href='https://gibbonedu.org/support/administrators/installing-gibbon/'>", '</a>']));
        } else {
            $page->addSuccess(__('Congratulations, your installation is complete. Feel free to %1$sgo to your Gibbon homepage%2$s and login with the username and password you created.', ["<a href='$absoluteURL'>", '</a>']));
            echo $page->fetchFromTemplate('ui/gettingStarted.twig.html', ['postInstall' => true]);
        }
    }

} catch (\Exception $e) {
    // Catch exception that stops installation at any step and
    // proerly display it on the page.
    $page->addError(__('Installation failed: {reason}', [
        'reason' => $e->getMessage(),
    ]));
}

$page->write(ob_get_clean());

$page->addData([
    'gibbonThemeName' => 'Default',
    'absolutePath'    => realpath('../'),
    'absoluteURL'     => str_replace('/installer/install.php', '', $_SERVER['PHP_SELF']),
    'sidebar'         => true,
    'contentClass'    => 'max-w-4xl mx-auto px-12 pt-6 pb-12',
    'step'            => $step,
]);

echo $page->render('installer/install.twig.html');
