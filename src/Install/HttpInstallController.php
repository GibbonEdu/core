<?php

namespace Gibbon\Install;

use Gibbon\Contracts\Services\Session;
use Gibbon\Core;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\View\Page;
use Psr\Container\ContainerInterface;

class HttpInstallController
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

    /**
     * Unique installation string
     *
     * @var string
     */
    protected $guid;

    public function __construct(
        Context $context,
        Installer $installer,
        Core $gibbon,
        Page $page,
        string $guid
    )
    {
        $this->context = $context;
        $this->installer = $installer;
        $this->gibbon = $gibbon;
        $this->page = $page;
        $this->guid = $guid;
    }

    /**
     * Create an HttpInstallController instance from
     * container.
     *
     * @param ContainerInterface $container
     * @param string $absolutePath
     * @return HttpInstallController
     */
    public static function create(
        ContainerInterface $container,
        Session $session
    ): HttpInstallController
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

        // Unique installation ID.
        if (!is_string($guid = $session->get('guid'))) {
            throw new \Exception(sprintf('Expected session\'s guid to be string but found %s.', var_export($guid, true)));
        }

        // Absolute path.
        if (empty($absolutePath = $session->get('absolutePath'))) {
            throw new \Exception('Session\'s absolutePath is not set.');
        }

        // Generate installation context from the environment.
        $context = (Context::fromEnvironment())
            ->setInstallPath($absolutePath);

        // Generate installer instance.
        $installer = new Installer($templateEngine);

        // Generate page object for display.
        $page = new Page($templateEngine, [
            'title'   => __('Gibbon Installer'),
            'address' => '/installer/install.php',
        ]);

        return new static(
            $context,
            $installer,
            $gibbon,
            $page,
            $guid
        );
    }

    public static function getSteps(): array
    {
        return [
            1 => __('System Requirements'),
            2 => __('Database Settings'),
            3 => __('User Account'),
            4 => __('Installation Complete'),
        ];
    }

    public static function generateNonce(): string
    {
        return hash('sha256', substr(mt_rand().date('zWy'), 0, 36));
    }

    /**
     * Render the view for step one.
     *
     * @param string $nonce    The generated nonce for next step.
     * @param string $version  The version to install.
     *
     * @return string
     */
    public function viewStepOne(
        string $nonce,
        string $version
    ): string
    {
        $step = isset($_GET['step']) ? intval($_GET['step']) : 0;
        $step = min(max($step, 0), 3);

        //PROCEED
        $trueIcon = "<img title='" . __('Yes'). "' src='../themes/Default/img/iconTick.png' style='width:20px;height:20px;margin-right:10px' />";
        $falseIcon = "<img title='" . __('No'). "' src='../themes/Default/img/iconCross.png' style='width:20px;height:20px;margin-right:10px' />";

        $versionTitle = __('%s Version');
        $versionMessage = __('%s requires %s version %s or higher');

        $phpVersion = phpversion();
        $apacheVersion = function_exists('apache_get_version')? apache_get_version() : false;
        $phpRequirement = $this->gibbon->getSystemRequirement('php');

        $readyToInstall = true;

        $form = Form::create('installer', "./install.php?step=1");
        $form->setTitle(__('Installation - Step {count}', ['count' => $step + 1]));
        $form->setClass('smallIntBorder w-full');
        $form->setMultiPartForm(static::getSteps(), 1);

        $form->addHiddenValue('guid', $this->guid);
        $form->addHiddenValue('nonce', $nonce);
        $form->addRow()->addHeading(__('System Requirements'));

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

        $form->addRow()->addHeading(__('Language Settings'));

        // Use default language, or language submitted by previous attempt.
        $row = $form->addRow();
            $row->addLabel('code', __('System Language'));
            $row->addSelectSystemLanguage('code')->addClass('w-64')->selected($_POST['code'] ?? 'en_GB')->required();

        $row = $form->addRow();
            $row->addFooter();
            if ($readyToInstall) $row->addSubmit();

        return $form->getOutput();
    }

    public function viewStepTwo(
        string $locale_code,
        string $nonce
    ): string
    {
        $step = 1;
        $languageInstalled = !i18nFileExists($this->gibbon->session->get('absolutePath'), $locale_code)
            ? i18nFileInstall($this->gibbon->session->get('absolutePath'), $locale_code)
            : true;

        // Check for the presence of a config file (if it hasn't been created yet)
        $this->context->validateConfigPath();

        if (!$languageInstalled) {
            echo "<div class='error'>";
            echo __('Failed to download and install the required files.').' '.sprintf(__('To install a language manually, upload the language folder to %1$s on your server and then refresh this page. After refreshing, the language should appear in the list below.'), '<b><u>'.
                $this->gibbon->session->get('absolutePath').'/i18n/</u></b>');
            echo '</div>';
        }

        $form = Form::create('installer', "./install.php?step=2");
        $form->setTitle(__('Installation - Step {count}', ['count' => $step + 1]));
        $form->setMultiPartForm(static::getSteps(), 2);

        $form->addHiddenValue('guid', $this->guid);
        $form->addHiddenValue('nonce', $nonce);
        $form->addHiddenValue('code', $locale_code); // Use language assigned in previous step, or default

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

        return $form->getOutput();
    }

    public function viewStepThree(
        Config $config,
        Installer $installer,
        string $nonce,
        string $version
    )
    {
        $step = 2;

        //Let's gather some more information
        $form = Form::create('installer', "./install.php?step=3");
        $form->setTitle(__('Installation - Step {count}', ['count' => $step + 1]));
        //$form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setMultiPartForm(static::getSteps(), 3);

        $form->addHiddenValue('guid', $this->guid);
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

        try {
            $message = HttpInstallController::renderPasswordPolicies(
                $installer->getPasswordPolicies()
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

        $form->addRow()->addHeading(__('System Settings'));

        $pageURL = (@$_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
        $port = '';
        if ($_SERVER['SERVER_PORT'] != '80') {
            $port = ':'.$_SERVER['SERVER_PORT'];
        }
        $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
        $setting = $installer->getSetting('absoluteURL', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addURL($setting['name'])->setValue($pageURL.$_SERVER['SERVER_NAME'].$port.substr($uri_parts[0], 0, -22))->maxLength(100)->required();

        $setting = $installer->getSetting('absolutePath', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->setValue(rtrim($context->getPath(''), '/'))->maxLength(255)->required();

        $setting = $installer->getSetting('systemName', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->maxLength(50)->required()->setValue('Gibbon');

        $installTypes = array(
            'Production'  => __('Production'),
            'Testing'     => __('Testing'),
            'Development' => __('Development')
        );

        $setting = $installer->getSetting('installType', 'System', true);
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
            $row->addYesNo($setting['name'])->selected('Y')->required();

        $form->addRow()->addHeading(__('Organisation Settings'));

        $setting = $installer->getSetting('organisationName', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->setValue('')->maxLength(50)->required();

        $setting = $installer->getSetting('organisationNameShort', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->setValue('')->maxLength(50)->required();

        $form->addRow()->addHeading(__('gibbonedu.com Value Added Services'));

        $setting = $installer->getSetting('gibboneduComOrganisationName', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->setValue();

        $setting = $installer->getSetting('gibboneduComOrganisationKey', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->setValue();

        $form->addRow()->addHeading(__('Miscellaneous'));

        $setting = $installer->getSetting('country', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelectCountry($setting['name'])->required();

        $setting = $installer->getSetting('currency', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelectCurrency($setting['name'])->required();

        $tzlist = array_reduce(\DateTimeZone::listIdentifiers(\DateTimeZone::ALL), function($group, $item) {
            $group[$item] = __($item);
            return $group;
        }, array());
        $setting = $installer->getSetting('timezone', 'System', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelect($setting['name'])->fromArray($tzlist)->required()->placeholder();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        return $form->getOutput();
    }

    public function viewStepFour(
        Installer $installer,
        string $version,
        array $user
    ) {
        $step = 3;
        $output = '';

        // Get settings for rendering below.
        $absoluteURL = $installer->getSetting('absoluteURL');
        $statsCollection = $installer->getSetting('statsCollection');
        $organisationName = $installer->getSetting('organisationName');
        $installType = $installer->getSetting('installType');
        $country = $installer->getSetting('country');

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
        $support = isset($_POST['support']) and $_POST['support'] == 'true';
        if ($support == true) {
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

        $form = Form::create('installer', "./install.php?step=4");
        $form->setTitle(__('Installation - Step {count}', ['count' => $step + 1]));
        $form->setMultiPartForm(static::getSteps(), 4);
        $output .= $form->getOutput();

        return $output;
    }

    /**
     * Parse a given request into Config object.
     *
     * @param string $guid
     * @param array $request
     *
     * @return \Gibbon\Install\Config
     *
     * @throws \Exception
     */
    public static function parseConfigSubmission(string $guid, array $request): Config
    {
        // Get and set database variables (not set until step 1)
        $config = (new Config)
            ->setGuid($guid)
            ->setDatabaseInfo(
                $request['databaseServer'] ?? '',
                $request['databaseName'] ?? '',
                $request['databaseUsername'] ?? '',
                $request['databasePassword'] ?? ''
            )
            ->setFlagDemoData(($request['demoData'] ?? '') === 'Y')
            ->setLocale($request['code'] ?? 'en_GB');

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
     * Validates the request array for post install user creation.
     *
     * @param array $request The submitted array. Use $_POST or equivlant submission array.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If the submission of any field is not correct.
     */
    public static function validateUserSubmission(array $request)
    {
        static::validateRequredFields($request, [
            'title',
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
     * @param array $request
     *
     * @return string[]
     */
    public static function parseUserSubmission(array $request): array
    {
        // Get user account details
        $salt = \getSalt();
        $passwordStrong = hash('sha256', $salt.$request['passwordNew']);
        return [
            'title' => $request['title'],
            'surname' => $request['surname'],
            'firstName' => $request['firstName'],
            'preferredName' => $request['firstName'],
            'officialName' => ($request['firstName'].' '.$request['surname']),
            'username' => $request['username'],
            'passwordStrong' => $passwordStrong,
            'passwordStrongSalt' => $salt,
            'status' => 'Full',
            'canLogin' => 'Y',
            'passwordForceReset' => 'N',
            'gibbonRoleIDPrimary' => '001',
            'gibbonRoleIDAll' => '001',
            'email' => $request['email'],
        ];
    }

    /**
     * Validates the request array for post install settings.
     *
     * @param array $request The submitted array. Use $_POST or equivlant submission array.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If the submission of any field is not correct.
     */
    public static function validatePostInstallSettingsSubmission(array $request)
    {
        static::validateRequredFields($request, [
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

        if ($request['passwordNew'] !== $request['passwordConfirm']) {
            throw new \InvalidArgumentException(__('Your request failed because your passwords did not match.'));
        }
    }

    /**
     * Parse post installation settings for the install process.
     *
     * @param array $request
     *
     * @return mixed[]
     */
    public static function parsePostInstallSettings(array $request)
    {
        $settings = [];

        // Get system settings
        $settings['System']['absoluteURL'] = $request['absoluteURL'];
        $settings['System']['absolutePath'] = $request['absolutePath'];
        $settings['System']['systemName'] = $request['systemName'];
        $settings['System']['organisationName'] = $request['organisationName'];
        $settings['System']['organisationNameShort'] = $request['organisationNameShort'];
        $settings['System']['organisationEmail'] = $request['email'] ?? '';
        $settings['System']['organisationAdministrator'] = 1;
        $settings['System']['organisationDBA'] = 1;
        $settings['System']['organisationHR'] = 1;
        $settings['System']['organisationAdmissions'] = 1;
        $settings['System']['gibboneduComOrganisationName'] = $request['gibboneduComOrganisationName'];
        $settings['System']['gibboneduComOrganisationKey'] = $request['gibboneduComOrganisationKey'];
        $settings['System']['currency'] = $request['currency'];
        $settings['System']['country'] = $request['country'];
        $settings['System']['timezone'] = $request['timezone'];
        $settings['System']['installType'] = $request['installType'];
        $settings['System']['statsCollection'] = $request['statsCollection'];
        $settings['System']['cuttingEdgeCode'] = $request['cuttingEdgeCodeHidden'];

        // Get finance settings
        $settings['Finance']['email'] = $request['email'];

        return $settings;
    }

    /**
     * Validates the request array for post install settings.
     *
     * @param array $request The submitted array. Use $_POST or equivlant submission array.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If the submission of any field is not correct.
     */
    public static function validateRequredFields(array $request, array $requiredFields)
    {
        foreach ($requiredFields as $name) {
            if (!isset($request[$name]) || empty($request[$name])) {
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
    public static function renderPasswordPolicies(array $policies): string
    {
        $html = implode("\n", array_map(function ($policy) {
            return "<li>{$policy}</li>";
        }, $policies));
        return !empty($html) ? "<ul>$html</ul>" : '';
    }
}
