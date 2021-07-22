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
use Gibbon\Forms\DatabaseFormFactory;

include '../version.php';
include '../gibbon.php';

//Module includes
require_once '../modules/System Admin/moduleFunctions.php';

// autoload the installer namespace
$autoloader->addPsr4('Gibbon\\Install\\', realpath(__DIR__).'/Installer');

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
} else {
    $guid = $_POST['guid'] ?? '';
    $guid = preg_replace('/[^a-z0-9-]/', '', substr($guid, 0, 36));
}
// Use the POSTed GUID in place of "undefined".
// Later steps have the guid in the config file but without
// a way to store variables relibly prior to that, installation can fail
$gibbon->session->setGuid($guid);
$gibbon->session->set('absolutePath', realpath('../'));

// Generate and save a nonce for forms on this page to use
$nonce = hash('sha256', substr(mt_rand().date('zWy'), 0, 36));
$sessionNonce = $gibbon->session->get('nonce', []);
$sessionNonce[$step+1] = $nonce;
$gibbon->session->set('nonce', $sessionNonce);

// Deal with non-existent stringReplacement session
$gibbon->session->set('stringReplacement', []);

// Fix missing locale causing failed page load
if (empty($gibbon->locale->getLocale())) {
    $gibbon->locale->setLocale('en_GB');
}

$page = new Page($container->get('twig'), [
    'title'   => __('Gibbon Installer'),
    'address' => '/installer/install.php',
]);

ob_start();

// Attempt to download & install the required language files
if ($step >= 1) {
    $locale_code = $_POST['code'] ?? 'en_GB';
    $languageInstalled = !i18nFileExists($gibbon->session->get('absolutePath'), $locale_code)
        ? i18nFileInstall($gibbon->session->get('absolutePath'), $locale_code)
        : true;
}

//Set language pre-install
if (function_exists('gettext')) {
    $gibbon->locale->setLocale($_POST['code'] ?? 'en_GB');
    bindtextdomain('gibbon', '../i18n');
    textdomain('gibbon');
}

$isConfigValid = true;
$canInstall = true;

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
            throw new \Exception(__('Your request failed because you do not have access to this action.'));
        }
    }

    // Check for the presence of a config file (if it hasn't been created yet)
    if ($step < 3) {
        if (file_exists('../config.php')) { // Make sure system is not already installed
            if (filesize('../config.php') > 0 or is_writable('../config.php') == false) {
                $canInstall = false;
            }
        } else { //No config, so continue installer
            if (is_writable('../') == false) { // Ensure that home directory is writable
                $canInstall = false;
            }
        }
    }

    if ($canInstall == false) {
        echo '<div class="error">';
        echo __('The directory containing the Gibbon files is not currently writable, or config.php already exists in the root folder and is not empty or is not writable, so the installer cannot proceed.');
        echo '</div>';
    } else if ($step == 0) { //Choose language

        //PROCEED
        $trueIcon = "<img title='" . __('Yes'). "' src='../themes/Default/img/iconTick.png' style='width:20px;height:20px;margin-right:10px' />";
        $falseIcon = "<img title='" . __('No'). "' src='../themes/Default/img/iconCross.png' style='width:20px;height:20px;margin-right:10px' />";

        $versionTitle = __('%s Version');
        $versionMessage = __('%s requires %s version %s or higher');

        $phpVersion = phpversion();
        $apacheVersion = function_exists('apache_get_version')? apache_get_version() : false;
        $phpRequirement = $gibbon->getSystemRequirement('php');

        // Generate installation context from the environment.
        $context = Context::fromEnvironment();

        $form = Form::create('installer', "./install.php?step=1");
        $form->setTitle(__('Installation - Step {count}', ['count' => $step + 1]));
        $form->setDescription(Format::alert(__('The directory containing the Gibbon files is writable, so the installation may proceed.'), 'success'));
        $form->setClass('smallIntBorder w-full');
        $form->setMultiPartForm($steps, 1);

        $form->addHiddenValue('guid', $guid);
        $form->addHiddenValue('nonce', $nonce);
        $form->addRow()->addHeading(__('System Requirements'));

        $row = $form->addRow();
            $row->addLabel('phpVersionLabel', sprintf($versionTitle, 'PHP'))->description(sprintf($versionMessage, __('Gibbon').' v'.$version, 'PHP', $phpRequirement));
            $row->addTextField('phpVersion')->setValue($phpVersion)->readonly();
            $row->addContent((version_compare($phpVersion, $phpRequirement, '>='))? $trueIcon : $falseIcon);

        $row = $form->addRow();
            $row->addLabel('pdoSupportLabel', __('MySQL PDO Support'));
            $row->addTextField('pdoSupport')->setValue((@extension_loaded('pdo_mysql'))? __('Installed') : __('Not Installed'))->readonly();
            $row->addContent((@extension_loaded('pdo') && extension_loaded('pdo_mysql'))? $trueIcon : $falseIcon);

        if ($apacheVersion !== false) {
            // Check Gibbon required Apache modules.
            $apacheRequirement = $gibbon->getSystemRequirement('apache');
            foreach ($context->checkApacheModules($apacheRequirement) as $moduleName => $active) {
                $row = $form->addRow();
                    $row->addLabel('moduleLabel', 'Apache '.__('Module').' '.$moduleName);
                    $row->addTextField('module')->setValue(($active)? __('Enabled') : __('N/A'))->readonly();
                    $row->addContent(($active)? $trueIcon : $falseIcon);
            }
        }

        // Check Gibbon required extensions.
        $extensions = $gibbon->getSystemRequirement('extensions');
        if (!empty($extensions) && is_array($extensions)) {
            foreach ($context->checkPhpExtensions($extensions) as $extension => $installed) {
                $row = $form->addRow();
                    $row->addLabel('extensionLabel', 'PHP ' .__('Extension').' '. $extension);
                    $row->addTextField('extension')->setValue(($installed)? __('Installed') : __('Not Installed'))->readonly();
                    $row->addContent(($installed)? $trueIcon : $falseIcon);
            }
        }

        $form->addRow()->addHeading(__('Language Settings'));

        // Use default language, or language submitted by previous attempt.
        $row = $form->addRow();
            $row->addLabel('code', __('System Language'));
            $row->addSelectSystemLanguage('code')->addClass('w-64')->selected($_POST['code'] ?? 'en_GB')->required();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();

    } else if ($step == 1) { //Set database options

        if (!$languageInstalled) {
            echo "<div class='error'>";
            echo __('Failed to download and install the required files.').' '.sprintf(__('To install a language manually, upload the language folder to %1$s on your server and then refresh this page. After refreshing, the language should appear in the list below.'), '<b><u>'.$gibbon->session->get('absolutePath').'/i18n/</u></b>');
            echo '</div>';
        }

        $form = Form::create('installer', "./install.php?step=2");
        $form->setTitle(__('Installation - Step {count}', ['count' => $step + 1]));
        $form->setMultiPartForm($steps, 2);

        $form->addHiddenValue('guid', $guid);
        $form->addHiddenValue('nonce', $nonce);
        $form->addHiddenValue('code', $_POST['code'] ?? 'en_GB'); // Use language assigned in previous step, or default

        $form->addRow()->addHeading(__('Database Settings'));

        $row = $form->addRow();
            $row->addLabel('type', __('Database Type'));
            $row->addTextField('type')->setValue('MySQL')->readonly()->required();

        $row = $form->addRow();
            $row->addLabel('databaseServer', __('Database Server'))->description(__('Localhost, IP address or domain.'));
            $row->addTextField('databaseServer')->required()->maxLength(255);

        $row = $form->addRow();
            $row->addLabel('databaseName', __('Database Name'))->description(__('This database will be created if it does not already exist. Collation should be utf8_general_ci.'));
            $row->addTextField('databaseName')->required()->maxLength(50);

        $row = $form->addRow();
            $row->addLabel('databaseUsername', __('Database Username'));
            $row->addTextField('databaseUsername')->required()->maxLength(50);

        $row = $form->addRow();
            $row->addLabel('databasePassword', __('Database Password'));
            $row->addPassword('databasePassword')->required()->maxLength(255);

        $row = $form->addRow();
            $row->addLabel('demoData', __('Install Demo Data?'));
            $row->addYesNo('demoData')->selected('N');


        //FINISH & OUTPUT FORM
        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    } elseif ($step == 2) {

        // Get and set database variables (not set until step 1)
        $config = (new Config)
            ->setGuid($guid)
            ->setDatabaseInfo(
                $_POST['databaseServer'] ?? '',
                $_POST['databaseName'] ?? '',
                $_POST['databaseUsername'] ?? '',
                $_POST['databasePassword'] ?? ''
            )
            ->setFlagDemoData(($_POST['demoData'] ?? '') === 'Y')
            ->setLocale($_POST['code'] ?? 'en_GB');

        // Check config values for ' " \ / chars which will cause errors in config.php
        $isConfigValid = $config->validateDatbaseInfo();
        if (!$isConfigValid) {
            echo '<div class="error">';
            echo __('Your request failed because your inputs were invalid.');
            echo '</div>';
        }

        if ($isConfigValid && $config->hasDatabaseInfo() && $config->hasFlagDemoData()) {
            //Check for db values

            //Establish db connection without database name
            $mysqlConnector = new MySqlConnector();

            try {
                $pdo = $mysqlConnector->connect($config->getDatabaseInfo(), true);
                $mysqlConnector->useDatabase($pdo, $config->getDatabaseName());
                $connection2 = $pdo->getConnection();
                $container->share(Gibbon\Contracts\Database\Connection::class, $pdo);
            } catch (\Exception $e) {
                echo "<div class='error'>";
                echo '<div>' . sprintf(__('A database connection could not be established. Please %1$stry again%2$s.'), "<a href='./install.php'>", '</a>') . '</div>';
                echo '<div>' . sprintf(__('Error details: {error_message}', ['error_message' => $e->getMessage()])) . '</div>';
                echo '</div>';
            }
        }

        if ($isConfigValid && $pdo instanceof Connection) {
            //Set up config.php
            include './installerFunctions.php';
            $configData = $config->getDatabaseInfo() + ['guid' => $guid];
            $configFileContents = $page->fetchFromTemplate('installer/config.twig.html', process_config_vars($configData));

            //Write config
            $fp = fopen('../config.php', 'wb');
            fwrite($fp, $configFileContents);
            fclose($fp);

            if (file_exists('../config.php') == false) { //Something went wrong, config.php could not be created.
                echo "<div class='error'>";
                echo __('../config.php could not be created, and so the installer cannot proceed.');
                echo '</div>';
            } else { //Config, exists, let's press on
                //Let's populate the database
                if (file_exists('../gibbon.sql') == false) {
                    echo "<div class='error'>";
                    echo __('../gibbon.sql does not exist, and so the installer cannot proceed.');
                    echo '</div>';
                } else {
                    $query = @fread(@fopen('../gibbon.sql', 'r'), @filesize('../gibbon.sql')) or die('Encountered a problem.');
                    $query = remove_remarks($query);
                    $query = split_sql_file($query, ';');

                    $i = 1;
                    $partialFail = false;
                    foreach ($query as $sql) {
                        ++$i;
                        try {
                            $connection2->query($sql);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }

                    if ($partialFail == true) {
                        echo "<div class='error'>";
                        echo __('Errors occurred in populating the database; empty your database, remove ../config.php and try again.');
                        echo '</div>';
                    } else {
                        //Try to install the demo data, report error but don't stop if any issues
                        if ($config->getFlagDemoData()) {
                            if (file_exists('../gibbon_demo.sql') == false) {
                                echo "<div class='error'>";
                                echo __('../gibbon_demo.sql does not exist, so we will continue without demo data.');
                                echo '</div>';
                            } else {
                                $query = @fread(@fopen('../gibbon_demo.sql', 'r'), @filesize('../gibbon_demo.sql')) or die('Encountered a problem.');
                                $query = remove_remarks($query);
                                $query = split_sql_file($query, ';');

                                $i = 1;
                                $demoFail = false;
                                foreach ($query as $sql) {
                                    ++$i;
                                    try {
                                        $connection2->query($sql);
                                    } catch (PDOException $e) {
                                        echo $sql.'<br/>';
                                        echo $e->getMessage().'<br/><br/>';
                                        $demoFail = true;
                                    }
                                }

                                if ($demoFail) {
                                    echo "<div class='error'>";
                                    echo __('There were some issues installing the demo data, but we will continue anyway.');
                                    echo '</div>';
                                }
                            }
                        }

                        //Set default language
                        try {
                            $data = array('code' => $config->getLocale());
                            $sql = "UPDATE gibboni18n SET systemDefault='Y' WHERE code=:code";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                        }
                        try {
                            $data = array('code' => $config->getLocale());
                            $sql = "UPDATE gibboni18n SET systemDefault='N' WHERE NOT code=:code";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                        }

                        //Let's gather some more information

                        $form = Form::create('installer', "./install.php?step=3");
                        $form->setTitle(__('Installation - Step {count}', ['count' => $step + 1]));
                        $form->setFactory(DatabaseFormFactory::create($pdo));
                        $form->setMultiPartForm($steps, 3);

                        $form->addHiddenValue('guid', $guid);
                        $form->addHiddenValue('nonce', $nonce);
                        $form->addHiddenValue('code', $config->getLocale());
                        $form->addHiddenValue('cuttingEdgeCodeHidden', 'N');

                        $form->addRow()->addHeading(__('User Account'));

                        $row = $form->addRow();
                            $row->addLabel('title', __('Title'));
                            $row->addSelectTitle('title');

                        $row = $form->addRow();
                            $row->addLabel('surname', __('Surname'))->description(__('Family name as shown in ID documents.'));
                            $row->addTextField('surname')->required()->maxLength(30);

                        $row = $form->addRow();
                            $row->addLabel('firstName', __('First Name'))->description(__('First name as shown in ID documents.'));
                            $row->addTextField('firstName')->required()->maxLength(30);

                        $row = $form->addRow();
                            $row->addLabel('email', __('Email'));
                            $row->addEmail('email')->required();

                        $row = $form->addRow();
                            $row->addLabel('support', __('Receive Support?'))->description(__('Join our mailing list and recieve a welcome email from the team.'));
                            $row->addCheckbox('support')->description(__('Yes'))->setValue('on')->checked('on')->setID('support');

                        $row = $form->addRow();
                            $row->addLabel('username', __('Username'))->description(__('Must be unique. System login name. Cannot be changed.'));
                            $row->addTextField('username')->required()->maxLength(20);

                        $policy = getPasswordPolicy($guid, $connection2);
                        if ($policy != false) {
                            $form->addRow()->addAlert($policy, 'warning');
                        }
                        $row = $form->addRow();
                            $row->addLabel('passwordNew', __('Password'));
                            $password = $row->addPassword('passwordNew')
                                ->required()
                                ->maxLength(30);

                        $alpha = getSettingByScope($connection2, 'System', 'passwordPolicyAlpha');
                        $numeric = getSettingByScope($connection2, 'System', 'passwordPolicyNumeric');
                        $punctuation = getSettingByScope($connection2, 'System', 'passwordPolicyNonAlphaNumeric');
                        $minLength = getSettingByScope($connection2, 'System', 'passwordPolicyMinLength');

                        if ($alpha == 'Y') {
                            $password->addValidation('Validate.Format', 'pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: "'.__('Does not meet password policy.').'"');
                        }
                        if ($numeric == 'Y') {
                            $password->addValidation('Validate.Format', 'pattern: /.*[0-9]/, failureMessage: "'.__('Does not meet password policy.').'"');
                        }
                        if ($punctuation == 'Y') {
                            $password->addValidation('Validate.Format', 'pattern: /[^a-zA-Z0-9]/, failureMessage: "'.__('Does not meet password policy.').'"');
                        }
                        if (!empty($minLength) && is_numeric($minLength)) {
                            $password->addValidation('Validate.Length', 'minimum: '.$minLength.', failureMessage: "'.__('Does not meet password policy.').'"');
                        }

                        $row = $form->addRow();
                            $row->addLabel('passwordConfirm', __('Confirm Password'));
                            $row->addPassword('passwordConfirm')
                                ->required()
                                ->maxLength(30)
                                ->addValidation('Validate.Confirmation', "match: 'passwordNew'");

                        $form->addRow()->addHeading(__('System Settings'));

                        $pageURL = (@$_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
                        $port = '';
                        if ($_SERVER['SERVER_PORT'] != '80') {
                            $port = ':'.$_SERVER['SERVER_PORT'];
                        }
                        $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
                        $setting = getSettingByScope($connection2, 'System', 'absoluteURL', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addURL($setting['name'])->setValue($pageURL.$_SERVER['SERVER_NAME'].$port.substr($uri_parts[0], 0, -22))->maxLength(100)->required();

                        $setting = getSettingByScope($connection2, 'System', 'absolutePath', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addTextField($setting['name'])->setValue(substr(__FILE__, 0, -22))->maxLength(100)->required();

                        $setting = getSettingByScope($connection2, 'System', 'systemName', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addTextField($setting['name'])->maxLength(50)->required()->setValue('Gibbon');

                        $installTypes = array(
                            'Production'  => __('Production'),
                            'Testing'     => __('Testing'),
                            'Development' => __('Development')
                        );

                        $setting = getSettingByScope($connection2, 'System', 'installType', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addSelect($setting['name'])->fromArray($installTypes)->selected('Testing')->required();

                        // Expose version information and translation strings to installer.js functions
                        // for check and set cutting edge code based on gibbonedu.org services value
                        $js_version = json_encode($version);
                        $js_i18n = json_encode([
                            '__edge_code_check_success__' => __('Cutting Edge Code check successful.'),
                            '__edge_code_check_failed__' => __('Cutting Edge Code check failed'),
                        ]);
                        $page->addHeadExtra("
                        <script type='text/javascript'>
                        window.gibboninstaller = {
                            version: {$js_version},
                            i18n: {$js_i18n},
                            msg: function (msg) {
                                return this.i18n[msg] || msg;
                            },
                        };
                        </script>
                        ");

                        $statusInitial = "<div id='status' class='warning'><div style='width: 100%; text-align: center'><img style='margin: 10px 0 5px 0' src='../themes/Default/img/loading.gif' alt='Loading'/><br/>".__('Checking for Cutting Edge Code.')."</div></div>";
                        $row = $form->addRow();
                            $row->addContent($statusInitial);
                        $setting = getSettingByScope($connection2, 'System', 'cuttingEdgeCode', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addTextField($setting['name'])->setValue('No')->readonly();

                        $setting = getSettingByScope($connection2, 'System', 'statsCollection', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addYesNo($setting['name'])->selected('Y')->required();

                        $form->addRow()->addHeading(__('Organisation Settings'));

                        $setting = getSettingByScope($connection2, 'System', 'organisationName', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addTextField($setting['name'])->setValue('')->maxLength(50)->required();

                        $setting = getSettingByScope($connection2, 'System', 'organisationNameShort', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addTextField($setting['name'])->setValue('')->maxLength(50)->required();

                        $form->addRow()->addHeading(__('gibbonedu.com Value Added Services'));

                        $setting = getSettingByScope($connection2, 'System', 'gibboneduComOrganisationName', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addTextField($setting['name'])->setValue();

                        $setting = getSettingByScope($connection2, 'System', 'gibboneduComOrganisationKey', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addTextField($setting['name'])->setValue();

                        $form->addRow()->addHeading(__('Miscellaneous'));

                        $setting = getSettingByScope($connection2, 'System', 'country', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addSelectCountry($setting['name'])->required();

                        $setting = getSettingByScope($connection2, 'System', 'currency', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addSelectCurrency($setting['name'])->required();

                        $tzlist = array_reduce(DateTimeZone::listIdentifiers(DateTimeZone::ALL), function($group, $item) {
                            $group[$item] = __($item);
                            return $group;
                        }, array());
                        $setting = getSettingByScope($connection2, 'System', 'timezone', true);
                        $row = $form->addRow();
                            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
                            $row->addSelect($setting['name'])->fromArray($tzlist)->required()->placeholder();

                        $row = $form->addRow();
                            $row->addFooter();
                            $row->addSubmit();

                        echo $form->getOutput();
                    }
                }
            }
        }
    } elseif ($step == 3) {
        //New PDO DB connection
        $mysqlConnector = new MySqlConnector();

        try {
            $pdo = $mysqlConnector->connect($gibbon->getConfig(), true);
            $connection2 = $pdo->getConnection();
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo '<div>' . sprintf(__('A database connection could not be established. Please %1$stry again%2$s.'), "<a href='./install.php'>", '</a>') . '</div>';
            echo '<div>' . sprintf(__('Error details: {error_message}', ['error_message' => $e->getMessage()])) . '</div>';
            echo '</div>';
        }

        // check if correctly created the PDO object.
        if ($pdo instanceof Connection) {
            //Get user account details
            $title = $_POST['title'];
            $surname = $_POST['surname'];
            $firstName = $_POST['firstName'];
            $preferredName = $_POST['firstName'];
            $username = $_POST['username'];
            $password = $_POST['passwordNew'];
            $passwordConfirm = $_POST['passwordConfirm'];
            $email = $_POST['email'];
            $support = isset($_POST['support']) and $_POST['support'] == 'true';

            //Get system settings
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
                echo "<div class='error'>";
                echo __('Some required fields have not been set, and so installation cannot proceed.');
                echo '</div>';
            } elseif ($password != $passwordConfirm) {
                echo "<div class='error'>";
                echo __('Your request failed because your passwords did not match.');
                echo '</div>';
            } else {
                $salt = getSalt();
                $passwordStrong = hash('sha256', $salt.$password);

                $userFail = false;
                //Write to database
                try {
                    $data = array('title' => $title, 'surname' => $surname, 'firstName' => $firstName, 'preferredName' => $preferredName, 'officialName' => ($firstName.' '.$surname), 'username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'status' => 'Full', 'canLogin' => 'Y', 'passwordForceReset' => 'N', 'gibbonRoleIDPrimary' => '001', 'gibbonRoleIDAll' => '001', 'email' => $email);
                    $sql = "INSERT INTO gibbonPerson SET gibbonPersonID=1, title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, status=:status, canLogin=:canLogin, passwordForceReset=:passwordForceReset, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll, email=:email";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $userFail = true;
                    echo "<div class='error'>";
                    echo sprintf(__('Errors occurred in populating the database; empty your database, remove ../config.php and %1$stry again%2$s.'), "<a href='./install.php'>", '</a>');
                    echo '</div>';
                }

                try {
                    $dataStaff = array('gibbonPersonID' => 1, 'type' => 'Teaching');
                    $sqlStaff = "INSERT INTO gibbonStaff SET gibbonPersonID=1, type='Teaching'";
                    $resultStaff = $connection2->prepare($sqlStaff);
                    $resultStaff->execute($dataStaff);
                } catch (PDOException $e) {
                }

                if ($userFail == false) {
                    $settingsFail = false;

                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'absoluteURL', $absoluteURL);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'absolutePath', $absolutePath);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'systemName', $systemName);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'organisationName', $organisationName);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'organisationNameShort', $organisationNameShort);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'organisationEmail', $email);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'organisationAdministrator', 1);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'organisationDBA', 1);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'organisationHR', 1);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'organisationAdmissions', 1);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'gibboneduComOrganisationName', $gibboneduComOrganisationName);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'gibboneduComOrganisationKey', $gibboneduComOrganisationKey);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'currency', $currency);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'country', $country);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'timezone', $timezone);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'installType', $installType);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'statsCollection', $statsCollection);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'System', 'cuttingEdgeCode', $cuttingEdgeCode);
                    $settingsFail = $settingsFail || !install_setting_set($connection2, 'Finance', 'email', $email);

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

                        $settingsFail = $settingsFail && !install_setting_set($connection2, 'System', 'cuttingEdgeCodeLine', $updater->cuttingEdgeMaxLine);
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
                        echo "<div class='error'>";
                        echo sprintf(__('Some settings did not save. The system may work, but you may need to remove everything and start again. Try and %1$sgo to your Gibbon homepage%2$s and login as user <u>admin</u> with password <u>gibbon</u>.'), "<a href='$absoluteURL'>", '</a>');
                        echo '<br/><br/>';
                        echo sprintf(__('It is also advisable to follow the %1$sPost-Install and Server Config instructions%2$s.'), "<a target='_blank' href='https://gibbonedu.org/support/administrators/installing-gibbon/'>", '</a>');
                        echo '</div>';
                    } else {
                        echo "<div class='success'>";
                        echo sprintf(__('Congratulations, your installation is complete. Feel free to %1$sgo to your Gibbon homepage%2$s and login with the username and password you created.'), "<a href='$absoluteURL'>", '</a>');
                        echo '</div>';

                        echo $page->fetchFromTemplate('ui/gettingStarted.twig.html', ['postInstall' => true]);
                    }
                }
            }
        }
    }

} catch (\Exception $e) {
    $page->addError($e->getMessage);
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
