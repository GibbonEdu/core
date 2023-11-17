<?php

namespace Gibbon\Install\Http;

use Gibbon\Contracts\Services\Session;
use Gibbon\Core;
use Gibbon\Database\Updater;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Install\Config;
use Gibbon\Install\Context;
use Gibbon\Install\Http\Exception\ForbiddenException;
use Gibbon\Install\Http\Exception\RecoverableException;
use Gibbon\Install\Http\NonceService;
use Gibbon\Install\Installer;
use Gibbon\Services\Format;
use Gibbon\View\Page;
use Psr\Container\ContainerInterface;
use Gibbon\Forms\MultiPartForm;

class InstallController
{
    /**
     * Installation context.
     *
     * @var \Gibbon\Install\Context
     */
    protected $context;

    /**
     * Template engine for rendering page or config file.
     *
     * @var \Gibbon\Install\Installer
     */
    protected $installer;

    /**
     * NonceService for form security.
     *
     * @var \Gibbon\Install\Http\NonceService
     */
    protected $nonceService;

    /**
     * Gibbon core for retrieving system requirements.
     *
     * @var \Gibbon\Core
     */
    protected $gibbon;

    /**
     * Page object.
     *
     * @var \Gibbon\View\Page
     */
    protected $page;

    public function __construct(
        Context $context,
        Installer $installer,
        NonceService $nonceService,
        Core $gibbon,
        Page $page
    )
    {
        $this->context = $context;
        $this->installer = $installer;
        $this->nonceService = $nonceService;
        $this->gibbon = $gibbon;
        $this->page = $page;
    }

    /**
     * Create an InstallController instance from
     * container.
     *
     * @param ContainerInterface $container
     * @param Session $session
     * @param Page $page
     *
     * @return InstallController
     * @throws \Exception
     */
    public static function create(
        ContainerInterface $container,
        Session $session,
        Page $page
    ): InstallController
    {
        /**
         * Template engine for rendering page or config file.
         *
         * @var \Twig\Environment
         */
        $templateEngine = $container->get('twig');

        /**
         * Gibbon core
         *
         * @var \Gibbon\Core
         */
        $gibbon = $container->get('config');

        // Absolute path.
        if (empty($absolutePath = $session->get('absolutePath'))) {
            throw new \Exception('Session\'s absolutePath is not set.');
        }

        // Generate installation context from the environment.
        $context = (Context::fromEnvironment())
            ->setInstallPath($absolutePath);

        // Generate installer instance.
        $installer = new Installer($templateEngine);

        // Generate and save a nonce for forms on this page to use
        if (!$session->has('nonceToken')) {
            $session->set('nonceToken', \getSalt());
        }
        $nonceService = new NonceService($session->get('nonceToken'));

        return new static(
            $context,
            $installer,
            $nonceService,
            $gibbon,
            $page
        );
    }

    /**
     * Parse the step from GET parameters.
     *
     * @param array $param  The $_GET or get parameter equivlant.
     *
     * @return integer  The step number of the current state.
     */
    public static function stepFromEnvironment(array $param): int
    {
        $step = isset($param['step'])? intval($param['step']) : 1;
        $step = min(max($step, 1), 4);
        return $step;
    }

    /**
     * Handle guid in browser environment for installation.
     *
     * Parse gibbon_install_guid cookie, or generate random guid.
     * The newly generated guid will set to cookie gibbon_install_guid.
     * Will only generate guid in step 1.
     *
     * @param array   $cookie  The cookie array or equivlant.
     * @param integer $step    The installation step number. Step 1 with empty
     *                         gibbon_install_guid cookie will force generating
     *                         new guid.
     *
     * @return string The generated or recoved guid string.
     *
     * @throws \Exception  If guid recovered from cookie is empty, or the cookie
     *                     is not set. Except in step 4, where cookie is supposed
     *                     to be unsets.
     */
    public static function guidFromEnvironment(array $cookie, int $step): string
    {
        // Deal with $guid setup, otherwise get and filter the existing $guid
        if ($step <= 1 && empty($cookie['gibbon_install_guid'])) {
            $guid = Installer::randomGuid();
            setcookie('gibbon_install_guid', $guid, 0, '', '', false, true);
            error_log(sprintf('Installer: Step %s: assigning random guid: %s', var_export($step, true), var_export($guid, true)));
        } else {
            $guid = $cookie['gibbon_install_guid'] ?? '';
            $guid = preg_replace('/[^a-z0-9-]/', '', substr($guid, 0, 36));
            error_log(sprintf('Installer: Step %s: Using guid from $_COOKIE: %s', var_export($step, true), var_export($guid, true)));
        }
        if (empty($guid)) {
            throw new \Exception('guid not found in environment. Please restart the installation.');
        }
        return $guid;
    }

    /**
     * Get the key and localized name of each steps of installation.
     *
     * @return string[] Steps with step number as key and name as value. Starts from 1.
     */
    public static function getSteps(): array
    {
        return [
            1 => __('System Requirements'),
            2 => __('Database Settings'),
            3 => __('User Account'),
            4 => __('Installation Complete'),
        ];
    }

    /**
     * Render the view for step one.
     *
     * @param string $submitUrl  The url for form submission.
     * @param string $version    The version to install.
     *
     * @return string
     */
    public function viewStepOne(
        string $submitUrl,
        string $version
    ): string
    {
        $nonce = $this->nonceService->generate('install:locale');

        //PROCEED
        $trueIcon = "<img title='" . __('Yes'). "' src='../themes/Default/img/iconTick.png' style='width:20px;height:20px;margin-right:10px' />";
        $falseIcon = "<img title='" . __('No'). "' src='../themes/Default/img/iconCross.png' style='width:20px;height:20px;margin-right:10px' />";

        $versionTitle = __('%s Version');
        $versionMessage = __('%s requires %s version %s or higher');

        $phpVersion = phpversion();
        $apacheVersion = function_exists('apache_get_version')? apache_get_version() : false;
        $phpRequirement = $this->gibbon->getSystemRequirement('php');

        $readyToInstall = true;

        $form = MultiPartForm::create('installer', $submitUrl);
        $form->setTitle(__('Installation - Step {count}', ['count' => 1]));
        $form->setClass('smallIntBorder standardForm w-full');
        $form->addPages(static::getSteps());
        $form->setCurrentPage(1);

        $form->addHiddenValue('nonce', $nonce);
        $form->addRow()->addHeading('System Requirements', __('System Requirements'));

        $readyToInstall = $readyToInstall && version_compare($phpVersion, $phpRequirement, '>=');
        $row = $form->addRow();
            $row->addLabel('phpVersionLabel', sprintf($versionTitle, 'PHP'))->description(sprintf($versionMessage, __('Gibbon').' v'.$version, 'PHP', $phpRequirement));
            $row->addTextField('phpVersion')->setValue($phpVersion)->readonly();
            $row->addContent((version_compare($phpVersion, $phpRequirement, '>='))? $trueIcon : $falseIcon);

        $readyToInstall = $readyToInstall && @extension_loaded('pdo') && extension_loaded('pdo_mysql');
        $row = $form->addRow();
            $row->addLabel('pdoSupportLabel', __('MySQL PDO Support'));
            $row->addTextField('pdoSupport')->setValue((@extension_loaded('pdo_mysql'))? __('Installed') : __('Not Installed'))->readonly();
            $row->addContent((@extension_loaded('pdo') && extension_loaded('pdo_mysql'))? $trueIcon : $falseIcon);

        if ($apacheVersion !== false) {
            /**
             * @var mixed $apacheRequirement
             */
            $apacheRequirement = $this->gibbon->getSystemRequirement('apache');
            foreach ($this->context->checkApacheModules($apacheRequirement) as $moduleName => $active) {
                $readyToInstall = $readyToInstall && $active;
                $row = $form->addRow();
                    $row->addLabel('moduleLabel', 'Apache '.__('Module').' '.$moduleName);
                    $row->addTextField('module')->setValue(($active)? __('Enabled') : __('N/A'))->readonly();
                    $row->addContent(($active)? $trueIcon : $falseIcon);
            }
        }

        // Check Gibbon required extensions.
        $extensions = $this->gibbon->getSystemRequirement('extensions');
        if (!empty($extensions) && is_array($extensions)) {
            foreach ($this->context->checkPhpExtensions($extensions) as $extension => $installed) {
                $readyToInstall = $readyToInstall && $installed;
                $row = $form->addRow();
                    $row->addLabel('extensionLabel', 'PHP ' .__('Extension').' '. $extension);
                    $row->addTextField('extension')->setValue(($installed)? __('Installed') : __('Not Installed'))->readonly();
                    $row->addContent(($installed)? $trueIcon : $falseIcon);
            }
        }

        $directoryError = '';
        try {
            $this->context->validateConfigPath();
        } catch (\Exception $e) {
            $directoryError = $e->getMessage();
            $readyToInstall = false;
        }
        $row = $form->addRow();
            $row->addLabel('systemLabel', 'Directory');
            $row->addTextField('directory')->setValue(empty($directoryError) ? __('Ready') : __('Not Ready'))->readonly();
            $row->addContent(empty($directoryError) ? $trueIcon : $falseIcon);

        // Finally check if the environment is ready for installation
        if ($readyToInstall) {
            $form->setDescription(Format::alert(__('Ready to install.'), 'success'));
        } elseif (!empty($directoryError)) {
            $form->setDescription(Format::alert($directoryError, 'error'));
        } else {
            $form->setDescription(Format::alert(__('Not ready to install.'), 'error'));
        }

        $form->addRow()->addHeading('Language Settings', __('Language Settings'));

        // Use default language, or language submitted by previous attempt.
        $row = $form->addRow();
            $row->addLabel('code', __('System Language'));
            $row->addSelectSystemLanguage('code')->addClass('w-64')->selected($_POST['code'] ?? 'en_GB')->required();

        $row = $form->addRow();
            $row->addFooter();
            if ($readyToInstall) $row->addSubmit();

        return $form->getOutput();
    }

    /**
     * Remember the installation locale submitted from step 1.
     * And try to install the associated locale file, if not in the system.
     *
     * @param Session $session
     * @param array $data
     *
     * @return void
     *
     * @throws ForbiddenException
     * @throws RecoverableException
     */
    public function handleStepOneSubmit(
        Session $session,
        array $data
    )
    {
        $this->nonceService->verify($data['nonce'] ?? '', 'install:locale');

        // Install locale
        $installLocale = $data['code'] ?? 'en_GB';
        $session->set('installLocale', $installLocale);
        $languageInstalled = !i18nFileExists($this->gibbon->session->get('absolutePath'), $installLocale)
            ? i18nFileInstall($this->gibbon->session->get('absolutePath'), $installLocale)
            : true;
        if (!$languageInstalled) {
            throw new RecoverableException (
                __('Failed to download and install the required files.') . ' ' .
                sprintf(
                    __('To install a language manually, upload the language folder to %1$s on your server and then refresh this page. After refreshing, the language should appear in the list below.'),
                    '<b><u>'.$session->get('absolutePath').'/i18n/</u></b>'
                )
            );
        }
    }

    /**
     * Interface to collect database configurations.
     *
     * @param string       $submitUrl     The url for form submission.
     * @param array        $data          The previously submitted data.
     * @return string
     */
    public function viewStepTwo(
        string $submitUrl,
        array $data
    ): string
    {
        $nonce = $this->nonceService->generate('install:setDbConfig');

        // Check for the presence of a config file (if it hasn't been created yet)
        $this->context->validateConfigPath();

        $form = MultiPartForm::create('installer', $submitUrl);
        $form->setTitle(__('Installation - Step {count}', ['count' => 2]));
        $form->addPages(static::getSteps());
        $form->setCurrentPage(2);

        $form->addHiddenValue('nonce', $nonce);

        $form->addRow()->addHeading('Database Settings', __('Database Settings'));

        $row = $form->addRow();
            $row->addLabel('type', __('Database Type'));
            $row->addTextField('type')->setValue('MySQL')->readonly()->required();

        $row = $form->addRow();
            $row->addLabel('databaseServer', __('Database Server'))->description(__('Localhost, IP address or domain.'));
            $row->addTextField('databaseServer')->setValue($data['databaseServer'] ?? '')->required()->maxLength(255);

        $row = $form->addRow();
            $row->addLabel('databaseName', __('Database Name'))->description(__('This database will be created if it does not already exist. Collation should be {collation}', ['collation' => 'utf8_general_ci or utf8mb3_general_ci']));
            $row->addTextField('databaseName')->setValue($data['databaseName'] ?? '')->required()->maxLength(50);

        $row = $form->addRow();
            $row->addLabel('databaseUsername', __('Database Username'));
            $row->addTextField('databaseUsername')->setValue($data['databaseUsername'] ?? '')->required()->maxLength(50);

        $row = $form->addRow();
            $row->addLabel('databasePassword', __('Database Password'));
            $row->addPassword('databasePassword')->required()->maxLength(255);

        $row = $form->addRow();
            $row->addLabel('demoData', __('Install Demo Data?'));
            $row->addYesNo('demoData')->selected($data['demoData'] ?? 'N');


        //FINISH & OUTPUT FORM
        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        return $form->getOutput();
    }

    /**
     * Do basic installation of config file, database
     * table and data. Will also insert the demo data
     * if needed.
     *
     * @param Context $context
     * @param Installer $installer
     * @param Session $session
     * @param string $guid
     * @param array $data
     *
     * @return void
     *
     * @throws ForbiddenException
     * @throws \Exception
     */
    public function handleStepTwoSubmit(
        Context $context,
        Installer $installer,
        Session $session,
        array $data
    )
    {
        $this->nonceService->verify($data['nonce'] ?? '', 'install:setDbConfig');

        // Check for the presence of a config file (if it hasn't been created yet)
        $context->validateConfigPath();

        // Get guid from session
        $guid = $session->get('guid') ?? '';
        if (empty($guid)) {
            throw new \Exception('guid in session is either not set or empty.');
        }

        // Get and set database variables (not set until step 1)
        $config = static::parseConfigSubmission($guid, $data);

        // Initialize database for the installer with the config data.
        $installer->useConfigConnection($config);

        // Create and check existance of the config file.
        $installer->createConfigFile($context, $config);

        // Get the locale code to install.
        $defaultLocale = $session->get('installLocale') ?: 'en_GB';

        // Run database installation of the config if (1) and (2) are
        // successful.
        $installer->install($context, $defaultLocale);

        // Check if demo data should be installed in the next step.
        $installer->setSetting('demoData', static::parseDemoDataInstallFlag($data) ? 'Y' : 'N', 'Installer');
    }

    /**
     * Render the post-installation setting form
     *
     * @param Context $context
     * @param Installer $installer
     * @param string $submitUrl
     * @param string $version
     * @param array $data
     *
     * @return string
     */
    public function viewStepThree(
        Context $context,
        Installer $installer,
        string $submitUrl,
        string $version,
        array $data
    ): string
    {
        $nonce = $this->nonceService->generate('install:postInstallSettings');

        // Connect database according to config file information.
        $config = Config::fromFile($context->getConfigPath());

        // Initialize database for the installer with the config data.
        $installer->useConfigConnection($config);

        //Let's gather some more information
        $form = MultiPartForm::create('installer', $submitUrl);
        $form->setTitle(__('Installation - Step {count}', ['count' => 3]));
        $form->setFactory(DatabaseFormFactory::create($installer->getConnection()));
        $form->addPages(static::getSteps());
        $form->setCurrentPage(3);

        $form->addHiddenValue('nonce', $nonce);
        $form->addHiddenValue('cuttingEdgeCodeHidden', 'N');

        $form->addRow()->addHeading('User Account', __('User Account'));

        $row = $form->addRow();
            $row->addLabel('title', __('Title'));
            $row->addSelectTitle('title')->selected($data['title'] ?? '');

        $row = $form->addRow();
            $row->addLabel('surname', __('Surname'))->description(__('Family name as shown in ID documents.'));
            $row->addTextField('surname')->setValue($data['surname'] ?? '')->required()->maxLength(30);

        $row = $form->addRow();
            $row->addLabel('firstName', __('First Name'))->description(__('First name as shown in ID documents.'));
            $row->addTextField('firstName')->setValue($data['firstName'] ?? '')->required()->maxLength(30);

        $row = $form->addRow();
            $row->addLabel('email', __('Email'));
            $row->addEmail('email')->setValue($data['email'] ?? '')->required();

        $row = $form->addRow();
            $row->addLabel('support', __('Receive Support?'))->description(__('Join our mailing list and recieve a welcome email from the team.'));
            $row->addCheckbox('support')->description(__('Yes'))->setValue('on')->checked(empty($data) || isset($data['support']))->setID('support');

        $row = $form->addRow();
            $row->addLabel('username', __('Username'))->description(__('Must be unique. System login name. Cannot be changed.'));
            $row->addTextField('username')->setValue($data['username'] ?? '')->required()->maxLength(20);

        try {
            $message = static::renderPasswordPolicy(
                $installer->getPasswordPolicy()
            );
            if (!empty($message)) {
                $form->addRow()->addAlert($message, 'warning');
            }
        } catch (\Exception $e) {
            $form->addRow()->addAlert(__('An error occurred.'), 'warning');
        }

        $row = $form->addRow();
            $row->addLabel('passwordNew', __('Password'));
            $password = $row->addPassword('passwordNew')
                ->required()
                ->maxLength(30);

        $alpha = $installer->getSetting('passwordPolicyAlpha');
        $numeric = $installer->getSetting('passwordPolicyNumeric');
        $punctuation = $installer->getSetting('passwordPolicyNonAlphaNumeric');
        $minLength = $installer->getSetting('passwordPolicyMinLength');

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

        $form->addRow()->addHeading('System Settings', __('System Settings'));

        if (empty($absoluteURL)) {
            $absoluteURL = static::guessAbsoluteUrl();
        }
        $setting = $installer->getSetting('absoluteURL', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addURL($setting['name'])->setValue($absoluteURL)->maxLength(100)->required();

        $setting = $installer->getSetting('absolutePath', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->setValue(rtrim($context->getPath(''), '/'))->maxLength(255)->required();

        $setting = $installer->getSetting('systemName', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->maxLength(50)->required()->setValue($data[$setting['name']] ?? 'Gibbon');

        $installTypes = array(
            'Production'  => __('Production'),
            'Testing'     => __('Testing'),
            'Development' => __('Development')
        );

        $setting = $installer->getSetting('installType', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelect($setting['name'])->fromArray($installTypes)->selected($data[$setting['name']] ?? 'Testing')->required();

        // Expose version information and translation strings to installer.js functions
        // for check and set cutting edge code based on gibbonedu.org services value
        $js_version = json_encode($version);
        $js_i18n = json_encode([
            '__edge_code_check_success__' => __('Cutting Edge Code check successful.'),
            '__edge_code_check_failed__' => __('Cutting Edge Code check failed'),
        ]);
        echo "
        <script type='text/javascript'>
        window.gibboninstaller = {
            version: {$js_version},
            i18n: {$js_i18n},
            msg: function (msg) {
                return this.i18n[msg] || msg;
            },
        };
        </script>
        ";

        $statusInitial = "<div id='status' class='warning'><div style='width: 100%; text-align: center'><img style='margin: 10px 0 5px 0' src='../themes/Default/img/loading.gif' alt='Loading'/><br/>".__('Checking for Cutting Edge Code.')."</div></div>";
        $row = $form->addRow();
            $row->addContent($statusInitial);

        $setting = $installer->getSetting('cuttingEdgeCode', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->setValue('No')->readonly();

        $setting = $installer->getSetting('statsCollection', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addYesNo($setting['name'])->selected(($data[$setting['name']] ?? 'N') == 'Y')->required();

        $form->addRow()->addHeading('Organisation Settings', __('Organisation Settings'));

        $setting = $installer->getSetting('organisationName', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->setValue($data[$setting['name']] ?? '')->maxLength(50)->required();

        $setting = $installer->getSetting('organisationNameShort', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->setValue($data[$setting['name']] ?? '')->maxLength(50)->required();

        $form->addRow()->addHeading('gibbonedu.com Value Added Services', __('gibbonedu.com Value Added Services'));

        $setting = $installer->getSetting('gibboneduComOrganisationName', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->setValue($data[$setting['name']] ?? '');

        $setting = $installer->getSetting('gibboneduComOrganisationKey', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->setValue($data[$setting['name']] ?? '');

        $form->addRow()->addHeading('Miscellaneous', __('Miscellaneous'));

        $setting = $installer->getSetting('country', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelectCountry($setting['name'])->selected($data[$setting['name']] ?? '')->required();

        $setting = $installer->getSetting('currency', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelectCurrency($setting['name'])->selected($data[$setting['name']] ?? '')->required();

        $tzlist = array_reduce(\DateTimeZone::listIdentifiers(\DateTimeZone::ALL), function($group, $item) {
            $group[$item] = __($item);
            return $group;
        }, array());
        $setting = $installer->getSetting('timezone', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelect($setting['name'])->fromArray($tzlist)->selected($data[$setting['name']] ?? '')->required()->placeholder();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        return $form->getOutput();
    }

    /**
     * Handle the post installation setups. Includes,
     * * Create admin user.
     * * Set initial settings.
     * * Update database version.
     *
     * @param ContainerInterface $container
     * @param Context $context
     * @param Installer $installer
     * @param Session $session
     * @param string $version
     * @param array $data
     *
     * @return void
     *
     * @throws ForbiddenException
     * @throws \Exception
     */
    public function handleStepThreeSubmit(
        ContainerInterface $container,
        Context $context,
        Installer $installer,
        Session $session,
        string $version,
        array $data
    )
    {
        $this->nonceService->verify($data['nonce'] ?? '', 'install:postInstallSettings');

        // Connect database according to config file information.
        $config = Config::fromFile($context->getConfigPath());
        $installer->useConfigConnection($config);
        $absoluteURL = static::guessAbsoluteUrl();

        // parse the submission from POST.
        try {
            static::validateUserSubmission($data);
            static::validatePostInstallSettingsSubmission($data);
        } catch (\InvalidArgumentException $e) {
            throw new \Exception(__('Installation cannot proceed. {reason}', ['reason' => $e->getMessage()]));
        }

        // Write the submitted user to database.
        try {
            $user = static::parseUserSubmission($data);
            $installer->createUser($user);
        } catch (\PDOException $e) {
            throw new \Exception(__('Errors occurred in populating the database; empty your database, remove ../config.php and %1$stry again%2$s.', ["<a href='./install.php'>", '</a>']));
        }

        // Set the new user as teaching staff.
        try {
            $installer->setPersonAsStaff(1, 'Teaching');
        } catch (\PDOException $e) {
        }

        // Parse all submitted settings and store to Gibbon database.
        $settingsFail = false;
        $settings = static::parsePostInstallSettings($data);
        foreach ($settings as $scope => $scopeSettings) {
            foreach ($scopeSettings as $key => $value) {
                $settingsFail = !$installer->setSetting($key, $value, $scope) || $settingsFail;
            }
        }

        // If is cutting edge mode, run updater.
        if ($installer->getSetting('cuttingEdgeCode') === 'Y') {
            // Note: must create the updater after settings are all set
            //       or the updater won't get the correct absolutePath
            //       to get the correct version.php path with.
            $updater = $container->get(Updater::class);
            $errors = $updater->update();

            if (!empty($errors)) {
                echo Format::alert(__('Some aspects of your update failed.'));
            }
            $settingsFail = !$installer->setSetting('cuttingEdgeCodeLine', $updater->cuttingEdgeMaxLine) || $settingsFail;
        }

        if ($installer->getSetting('demoData', 'Installer') === 'Y') {
            $demoDataFail = !$installer->installDemoData($context);
            if ($demoDataFail) {
                error_log('Installer: demo data failed. Will trigger RecoverableException.');
                throw new RecoverableException(__('There were some issues installing the demo data, but we will continue anyway.'));
            }
        }

        // Update DB version for existing languages (installed manually?)
        i18nCheckAndUpdateVersion($container, $version);

        // Clean up installation variables.
        static::cleanUp($session);

        if ($settingsFail) {
            error_log('Installer: settings failed. Will trigger RecoverableException.');
            throw new RecoverableException(
                sprintf(__('Some settings did not save. The system may work, but you may need to remove everything and start again. Try and %1$sgo to your Gibbon homepage%2$s and login as user <u>admin</u> with password <u>gibbon</u>.'), "<a href='$absoluteURL'>", '</a>') . "<br/>\n" .
                sprintf(__('It is also advisable to follow the %1$sPost-Install and Server Config instructions%2$s.'), "<a target='_blank' href='https://gibbonedu.org/support/administrators/installing-gibbon/'>", '</a>')
            );
        }
    }

    /**
     * Render installation results.
     *
     * @param Context $context
     * @param Installer $installer
     * @param string $current_url
     * @param string $version
     *
     * @return string
     */
    public function viewStepFour(
        Context $context,
        Installer $installer,
        string $version
    ): string {
        $step = 3;
        $output = '';

        // Connect database according to config file information.
        $config = Config::fromFile($context->getConfigPath());
        $installer->useConfigConnection($config);

        // Get settings for rendering below.
        $absoluteURL = $installer->getSetting('absoluteURL');
        $statsCollection = $installer->getSetting('statsCollection');
        $organisationName = $installer->getSetting('organisationName');
        $installType = $installer->getSetting('installType');
        $country = $installer->getSetting('country');
        $registerGibbonSupport = $installer->getSetting('registerGibbonSupport');

        // parse absolute path and protocol for gibbon registration or support.
        $absolutePathProtocol = '';
        $absolutePath = '';
        if (substr($absoluteURL, 0, 7) == 'http://') {
            $absolutePathProtocol = 'http';
            $absolutePath = substr($absoluteURL, 7);
        } elseif (substr($absoluteURL, 0, 8) == 'https://') {
            $absolutePathProtocol = 'https';
            $absolutePath = substr($absoluteURL, 8);
        }

        if ($statsCollection == 'Y') {
            // TODO: ideally, this should be an HTTP call in backend instead of
            // an iframe in the frontend.
            $url = Installer::gibbonServiceURL('tracker/tracker', [
                'absolutePathProtocol' => $absolutePathProtocol,
                'absolutePath' => $absolutePath,
                'organisationName' => $organisationName,
                'type' => $installType,
                'version' => $version,
                'country' => $country,
                'usersTotal' => 1,
                'usersFull' => 1,
            ]);
            $output .= "<iframe style='display: none; height: 10px; width: 10px' src='{$url}'></iframe>";
        }

        //Deal with request to receive welcome email by calling gibbonedu.org iframe
        if ($registerGibbonSupport === 'Y') {
            // Get the installing administrator, supposedly.
            $user = $installer->getGibbonPerson(1);

            // TODO: ideally, this should be an HTTP call in backend instead of
            // an iframe in the frontend.
            $url = Installer::gibbonServiceURL('support/supportRegistration', [
                'absolutePathProtocol' => $absolutePathProtocol,
                'absolutePath' => $absolutePath,
                'organisationName' => $organisationName,
                'email' => $user['email'],
                'title' => $user['title'],
                'surname' => $user['surname'],
                'preferredName' => $user['preferredName'],
            ]);
            $output .= "<iframe class='support' style='display: none; height: 10px; width: 10px' src='{$url}'></iframe>";
        }

        $form = MultiPartForm::create('installer', "./install.php?step=4");
        $form->setTitle(__('Installation - Step {count}', ['count' => $step + 1]));
        $form->addPages(static::getSteps());
        $form->setCurrentPage(4);

        $output .= $form->getOutput();

        return $output;
    }

    /**
     * Store recoverable exception to show in next page.
     *
     * @param Session $session
     * @param RecoverableException $message
     *
     * @return self
     */
    public function flashMessage(Session $session, RecoverableException $message)
    {
        $session->set('flashMessage', $message);
        return $this;
    }

    /**
     * Read flash message from session and show on the page.
     *
     * @param Session $session  The session to read from.
     * @param Page    $page     The page object to show the message on.
     *
     * @return RecoverableException|null  The message, if any, or null.
     */
    public function recoverFlashMessage(Session $session, Page $page): ?RecoverableException
    {
        if ($session->has('flashMessage')) {
            $m = $session->get('flashMessage');
            if ($m instanceof RecoverableException) {
                $page->addAlert($m->getMessage(), $m->getLevel());
            } else {
                $page->addError($m->getMessage());
            }
            $session->remove('flashMessage'); // reset
            return $m;
        }
        return null;
    }

    /**
     * Parse a given request into Config object.
     *
     * @param string $guid
     * @param array $data
     *
     * @return \Gibbon\Install\Config
     *
     * @throws \Exception
     */
    public static function parseConfigSubmission(string $guid, array $data): Config
    {
        // Get and set database variables (not set until step 1)
        $config = (new Config)
            ->setGuid($guid)
            ->setDatabaseInfo(
                $data['databaseServer'] ?? '',
                $data['databaseName'] ?? '',
                $data['databaseUsername'] ?? '',
                $data['databasePassword'] ?? ''
            );

        if (!$config->hasDatabaseInfo()) {
            throw new \Exception(__('You have not provide appropriate database info.'));
        }

        // Check config values for ' " \ / chars which will cause errors in config.php
        if (!$config->validateDatbaseInfo()) {
            throw new \Exception(__('Your request failed because your inputs were invalid.'));
        }

        return $config;
    }

    /**
     * Parse demo data installation config.
     *
     * @param array $data
     *
     * @return bool If the user wants to install demo data to this Gibbon.
     */
    public static function parseDemoDataInstallFlag(array $data): bool
    {
        return ($data['demoData'] ?? '') === 'Y';
    }

    /**
     * Validates the request array for post install user creation.
     *
     * @param array $data The submitted array. Use $_POST or equivlant submission array.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If the submission of any field is not correct.
     */
    public static function validateUserSubmission(array $data)
    {
        static::validateRequredFields($data, [
            'surname',
            'firstName',
            'username',
            'passwordNew',
            'passwordConfirm',
            'email',
        ]);
    }

    /**
     * Parse user information for user creation in the install process.
     *
     * @param array $data
     *
     * @return string[]
     */
    public static function parseUserSubmission(array $data): array
    {
        // Get user account details
        $salt = \getSalt();
        $passwordStrong = hash('sha256', $salt.$data['passwordNew']);
        return [
            'title' => $data['title'],
            'surname' => $data['surname'],
            'firstName' => $data['firstName'],
            'preferredName' => $data['firstName'],
            'officialName' => ($data['firstName'].' '.$data['surname']),
            'username' => $data['username'],
            'passwordStrong' => $passwordStrong,
            'passwordStrongSalt' => $salt,
            'status' => 'Full',
            'canLogin' => 'Y',
            'passwordForceReset' => 'N',
            'gibbonRoleIDPrimary' => '001',
            'gibbonRoleIDAll' => '001',
            'email' => $data['email'],
        ];
    }

    /**
     * Validates the request array for post install settings.
     *
     * @param array $data The submitted array. Use $_POST or equivlant submission array.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If the submission of any field is not correct.
     */
    public static function validatePostInstallSettingsSubmission(array $data)
    {
        static::validateRequredFields($data, [
            'absoluteURL',
            'absolutePath',
            'systemName',
            'organisationName',
            'organisationNameShort',
            'timezone',
            'country',
            'installType',
            'statsCollection',
            'cuttingEdgeCode',
            'email',
        ]);

        if ($data['passwordNew'] !== $data['passwordConfirm']) {
            throw new \InvalidArgumentException(__('Your request failed because your passwords did not match.'));
        }
    }

    /**
     * Parse post installation settings for the install process.
     *
     * @param array $data
     *
     * @return mixed[]
     */
    public static function parsePostInstallSettings(array $data)
    {
        $settings = [];

        // Get system settings
        $settings['System']['absoluteURL'] = $data['absoluteURL'];
        $settings['System']['absolutePath'] = $data['absolutePath'];
        $settings['System']['systemName'] = $data['systemName'];
        $settings['System']['organisationName'] = $data['organisationName'];
        $settings['System']['organisationNameShort'] = $data['organisationNameShort'];
        $settings['System']['organisationEmail'] = $data['email'] ?? '';
        $settings['System']['organisationAdministrator'] = 1;
        $settings['System']['organisationDBA'] = 1;
        $settings['System']['organisationHR'] = 1;
        $settings['System']['organisationAdmissions'] = 1;
        $settings['System']['gibboneduComOrganisationName'] = $data['gibboneduComOrganisationName'];
        $settings['System']['gibboneduComOrganisationKey'] = $data['gibboneduComOrganisationKey'];
        $settings['System']['currency'] = $data['currency'];
        $settings['System']['country'] = $data['country'];
        $settings['System']['timezone'] = $data['timezone'];
        $settings['System']['installType'] = $data['installType'];
        $settings['System']['statsCollection'] = $data['statsCollection'];
        $settings['System']['cuttingEdgeCode'] = $data['cuttingEdgeCodeHidden'];
        $settings['System']['registerGibbonSupport'] = !empty($data['support']) ? 'Y' : 'N';

        // Get finance settings
        $settings['Finance']['email'] = $data['email'];

        return $settings;
    }

    /**
     * Validates the request array for post install settings.
     *
     * @param array $data The submitted array. Use $_POST or equivlant submission array.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If the submission of any field is not correct.
     */
    public static function validateRequredFields(array $data, array $requiredFields)
    {
        foreach ($requiredFields as $name) {
            if (!isset($data[$name]) || empty($data[$name])) {
                throw new \InvalidArgumentException(__('The required field "{name}" is not set.', ['name' => $name]));
            }
        }
    }

    /**
     * Parse password policies into HTML list.
     *
     * @param string[] $policies
     *
     * @return string HTML list.
     */
    public static function renderPasswordPolicy(array $policies): string
    {
        $html = implode("\n", array_map(function ($policy) {
            return "<li>{$policy}</li>";
        }, $policies));
        return !empty($html) ? "<ul>$html</ul>" : '';
    }

    /**
     * Guess the absoluteUrl for the installation environment.
     *
     * @return string
     */
    public static function guessAbsoluteUrl(): string
    {
        // Find out the base installation URL path.
        $prefixLength = strlen(realpath($_SERVER['DOCUMENT_ROOT']));

        // Suppose the entry script is "/installer/install.php"
        // then the base dir would be "./../" from the entry point
        // perspective.
        $baseDir = realpath('./../') . '/';

        // Construct the full URL to the base URL path.
        $urlBasePath = substr($baseDir, $prefixLength);
        $host = $_SERVER['HTTP_HOST'];
        $protocol = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
        return rtrim("{$protocol}://{$host}{$urlBasePath}", '/');
    }

    /**
     * Clearn up environment after installation.
     *
     * @param Session $session
     *
     * @return void
     */
    private static function cleanUp(Session $session)
    {
        // Forget installation details in session and cookie.
        $session->remove('installLocale');
        $session->remove('nonceToken');
        setcookie('gibbon_install_guid', '', -1);
    }
}
