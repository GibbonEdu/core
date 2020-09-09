<?php

try {
    $I = new InstallTester($scenario);
    $I->wantTo('install Gibbon');
    $I->amOnPage('/installer/install.php');

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
    
    if (getenv('DB_HOST')){
        $I->fillField('databaseServer', getenv('DB_HOST'));
    } else {
        $I->fillField('databaseServer', 'localhost');
    }
    if (getenv('DB_HOST')){
        $I->fillField('databaseName', getenv('DB_NAME'));
    } else {
        $I->fillField('databaseName', 'gibbon');
    }
    if (getenv('DB_USERNAME')){
        $I->fillField('databaseUsername', getenv('DB_USERNAME'));
    } else {
        $I->fillField('databaseUsername', 'root');
    }
    if (getenv('databasePassword')){
        $I->fillField('databasePassword', getenv('DB_PASSWORD'));
    } else {
        $I->fillField('databasePassword', 'root');
    }
    $I->selectOption('demoData', 'Y');
    $I->click('Submit');

    // STEP 3 --------------------------------------
    $I->see('Installation - Step 3', 'h2');

    $I->dontSee('A database connection could not be established.');

    $formValues = array(
        'title'                 => 'Mr.',
        'surname'               => 'CI',
        'firstName'             => 'Travis',
        'email'                 => 'testing@gibbon.test',
        'username'              => 'admin',
        'passwordNew'           => 'travisci',
        'passwordConfirm'       => 'travisci',
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

} catch (Exception $e) {
    codecept_debug($I->grabTextFrom('body'));
    throw $e;
}
