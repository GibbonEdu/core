<?php

namespace Gibbon\Install;

use Gibbon\Contracts\Services\Session;
use Gibbon\Core;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\View\Page;
use Psr\Container\ContainerInterface;
use Twig\Environment;

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
    public function viewStepZero(string $nonce, string $version): string
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

    public function viewStepOne(
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

        return $form->getOutput();
    }
}
