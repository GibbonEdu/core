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
$step = isset($_GET['step'])? intval($_GET['step']) : 1;
$step = min(max($step, 1), 4);

// Deal with $guid setup, otherwise get and filter the existing $guid
$guid = HttpInstallController::guidFromEnvironment($step);

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
    // Prevent memory or time limit issues.
    ini_set('memory_limit', '5120M');
    set_time_limit(0);

    if ($step === 1) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $controller->handleStepOneSubmit($nonceService, $session, $_POST);
                header('Location: ./install.php?step=2');
                exit;
            } catch (RecoverableException $e) {
                $controller->flashMessage($session, $e);
                header('Location: ./install.php?step=2');
                exit;
            } catch (\Exception $e) {
                $page->addError($e->getMessage());
            }
        }

        // Validate the installation context and show warning.
        // If suitable for installation, show form to choose language.
        echo $controller->viewStepOne(
            $nonceService,
            './install.php?step=1',
            $gibbon->getConfig('version')
        );
    } else if ($step === 2) {
        // Get recoverable exception from previos step and set alert on page.
        $controller->recoverFlashMessage($session, $page);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $controller->handleStepTwoSubmit(
                    $context,
                    $installer,
                    $nonceService,
                    $session,
                    $_POST
                );
                header('Location: ./install.php?step=3');
                exit;
            } catch (\Exception $e) {
                $page->addError($e->getMessage());
            }
        }

        // Show the form to input database options.
        echo $controller->viewStepTwo(
            $nonceService,
            './install.php?step=2',
            $_POST
        );
    } elseif ($step === 3) {
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

                // Forget installation details in session and cookie.
                $session->remove('installLocale');
                setcookie('gibbon_install_guid', '', -1);

                // Redirect to next step
                header('Location: ./install.php?step=4');
                exit;
            } catch (RecoverableException $e) {
                $controller->flashMessage($session, $e);
                header('Location: ./install.php?step=4');
                exit;
            } catch (\Exception $e) {
                $page->addError($e->getMessage());
            }
        }

        // Render step 3 form.
        echo $controller->viewStepThree(
            $context,
            $installer,
            $nonceService,
            './install.php?step=3',
            $version,
            $_POST
        );
    } elseif ($step === 4) {
        // Get recoverable exception from previos step and set alert on page.
        $message = $controller->recoverFlashMessage($session, $page);

        // Display step four (step three results with Gibbon
        // registration result).
        echo $controller->viewStepFour(
            $context,
            $installer,
            $version
        );

        // Show success message if the installation is a complete success.
        if ($message === null) {
            $absoluteURL = str_replace('/installer/install.php', '', $_SERVER['PHP_SELF']);
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
