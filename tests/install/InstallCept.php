<?php

use Gibbon\Install\Config;
use Gibbon\Install\Installer;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$requestSuccess = false;
try {
    $I = new InstallTester($scenario);
    $I->wantTo('install Gibbon');
    $I->amOnPage('/installer/install.php');

    // Mark a success in doing CURL request.
    $requestSuccess = true;

    // INSTALLED: Cancel out now if already installed
    if (file_exists(getcwd().'/../config.php')) {
        $I->see('config.php already exists', '.error');
        return;
    }

    // STEP 1 --------------------------------------
    $I->see('Installation - Step 1', 'h2');
    $I->click('Submit');

    // STEP 2 --------------------------------------
    $I->see('Installation - Step 2', 'h2');


    $I->fillField('databaseServer', getenv('DB_HOST'));
    $I->fillField('databaseName', getenv('DB_NAME'));
    $I->fillField('databaseUsername', getenv('DB_USERNAME'));
    $I->fillField('databasePassword', getenv('DB_PASSWORD'));

    $I->fillField('demoData', 'Y');
    $I->click('Submit');

    // STEP 3 --------------------------------------
    $I->see('Installation - Step 3', 'h2');

    $I->dontSee('A database connection could not be established.');

    $formValues = array(
        'title'                 => 'Mr.',
        'surname'               => 'Admin',
        'firstName'             => 'Testing',
        'email'                 => 'testing@gibbon.test',
        'username'              => 'admin',
        'passwordNew'           => '7SSbB9FZN24Q',
        'passwordConfirm'       => '7SSbB9FZN24Q',
        'systemName'            => 'Gibbon',
        'installType'           => 'Testing',
        'cuttingEdgeCode'       => 'Y',
        'cuttingEdgeCodeHidden' => 'Y',
        'statsCollection'       => 'N',
        'organisationName'      => 'Gibbon Testing',
        'organisationNameShort' => 'GiT',
        'currency'              => 'HKD $',
        'country'               => 'Hong Kong',
        'timezone'              => 'UTC',
    );

    $I->uncheckOption('#support');
    $I->submitForm('#content form', $formValues, 'Submit');

    $I->see('Congratulations, your installation is complete.', '.success');

    // Follow the go to homepage link to the front page.
    $I->see('go to your Gibbon homepage', '.success');
    $I->click('go to your Gibbon homepage');

    // The URL should either be empty or a single slash (root).
    $I->canSeeCurrentUrlMatches('/^(|\/)$/');

} catch (Exception $e) {
    if ($requestSuccess) {
        codecept_debug($I->grabTextFrom('body'));
    }
    throw $e;
}

// Connect to database by connection.
$template = new Environment(new FilesystemLoader(__DIR__ . '/../../resources/templates'));
$config = Config::fromFile(__DIR__ . '/../../config.php');
$installer = new Installer($template);
$installer->useConfigConnection($config);

if (empty($installer->getSetting('absoluteURL'))) {
    throw new \Exception('Expected absoluteURL to be not empty.');
}

if (empty($installer->getSetting('absolutePath'))) {
    throw new \Exception('Expected absolutePath to be not empty.');
}
