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
use Gibbon\Data\Validator;
use Gibbon\Install\Config;
use Gibbon\Install\Context;
use Gibbon\Install\Exception\ForbiddenException;
use Gibbon\Install\Exception\RecoverableException;
use Gibbon\Install\HttpInstallController;
use Gibbon\Install\Installer;
use Gibbon\Install\NonceService;

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
$session->set('absolutePath', realpath('../'));
if (!$session->has('nonceToken')) {
    $session->set('nonceToken', \getSalt());
}

// Generate and save a nonce for forms on this page to use
$nonceService = new NonceService($session->get('nonceToken'));

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

try {
    if ($step == 0) {
        // Validate the installation context and show warning.
        // If suitable for installation, show form to choose language.
        echo $controller->viewStepOne($nonceService, $gibbon->getConfig('version'));
    } else if ($step == 1) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $controller->handleStepOneSubmit($nonceService, $session, $_POST);
            } catch (RecoverableException $e) {
                $page->addError($e->getMessage(), $e->getLevel());
            }
        }

        // Show the form to input database options.
        echo $controller->viewStepTwo(
            $nonceService,
            "./install.php?step={$step}",
            $_POST
        );
    } elseif ($step == 2) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $controller->handleStepTwoSubmit(
                    $context,
                    $installer,
                    $nonceService,
                    $session,
                    $guid,
                    $_POST
                );
            } catch (\Exception $e) {
                $page->addError($e->getMessage());
            }
        }

        // Render step 3 form.
        echo $controller->viewStepThree(
            $context,
            $installer,
            $nonceService,
            $session,
            "./install.php?step={$step}",
            $version,
            $_POST
        );
    } elseif ($step == 3) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $controller->handleStepThreeSubmit(
                    $container,
                    $context,
                    $installer,
                    $nonceService,
                    $version,
                    $_POST
                );

                // Redirect to next step
                header('Location: ./install.php?step=' . ($step+1));
                exit;
            } catch (\Exception $e) {
                $page->addError($e->getMessage());
            }
        }

        // Forget installation details in session.
        unset($_SESSION['installLocale']);

        // Display step four (step three results with Gibbon
        // registration result).
        echo $controller->viewStepFour(
            $context,
            $installer,
            $version
        );

        if ($settingsFail == true) {
            $page->addError(sprintf(__('Some settings did not save. The system may work, but you may need to remove everything and start again. Try and %1$sgo to your Gibbon homepage%2$s and login as user <u>admin</u> with password <u>gibbon</u>.'), "<a href='$absoluteURL'>", '</a>'));
            $page->addError(sprintf(__('It is also advisable to follow the %1$sPost-Install and Server Config instructions%2$s.'), "<a target='_blank' href='https://gibbonedu.org/support/administrators/installing-gibbon/'>", '</a>'));
        } else {
            $page->addSuccess(sprintf(__('Congratulations, your installation is complete. Feel free to %1$sgo to your Gibbon homepage%2$s and login with the username and password you created.'), "<a href='$absoluteURL'>", '</a>'));
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
