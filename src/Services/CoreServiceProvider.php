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
along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Gibbon\Services;

use Gibbon\Core;
use Gibbon\Locale;
use Gibbon\Comms\SMS;
use Gibbon\View\Page;
use Gibbon\View\View;
use Gibbon\Comms\Mailer;
use Gibbon\Data\Validator;
use Gibbon\Session\Session;
use Gibbon\Domain\System\Theme;
use Gibbon\Domain\System\Module;
use Gibbon\Session\SessionFactory;
use Gibbon\Services\Payment\Payment;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Comms\SMS as SMSInterface;
use Gibbon\Contracts\Comms\Mailer as MailerInterface;
use Gibbon\Contracts\Services\Payment as PaymentInterface;
use Gibbon\Contracts\Services\Session as SessionInterface;
use Gibbon\Data\PasswordPolicy;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

/**
 * DI Container Services for the Core
 *
 * @version v17
 * @since   v17
 */
class CoreServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    protected $absolutePath;

    public function __construct($absolutePath)
    {
        $this->absolutePath = $absolutePath;
    }

    /**
     * The provides array is a way to let the container know that a service
     * is provided by this service provider. Every service that is registered
     * via this service provider must have an alias added to this array or
     * it will be ignored.
     *
     * @var array
     */
    protected $provides = [
        'config',
        'session',
        'locale',
        'twig',
        'page',
        'module',
        'theme',
        PaymentInterface::class,
        MailerInterface::class,
        SMSInterface::class,
        'gibbon_logger',
        'mysql_logger',
        Validator::class,
        PasswordPolicy::class,
    ];

    /**
     * In much the same way, this method has access to the container
     * itself and can interact with it however you wish, the difference
     * is that the boot method is invoked as soon as you register
     * the service provider with the container meaning that everything
     * in this method is eagerly loaded.
     *
     * If you wish to apply inflectors or register further service providers
     * from this one, it must be from a bootable service provider like
     * this one, otherwise they will be ignored.
     */
    public function boot()
    {
        $container = $this->getLeagueContainer();

        $container->share('config', new Core($this->absolutePath));
        $container->share('locale', new Locale($this->absolutePath));
    }

    /**
     * This is where the magic happens, within the method you can
     * access the container and register or retrieve anything
     * that you need to, but remember, every alias registered
     * within this method must be declared in the `$provides` array.
     */
    public function register()
    {
        $container = $this->getLeagueContainer();
        $absolutePath = $this->absolutePath;

        // Logging removed until properly setup & tested

        // $container->share('gibbon_logger', function () use ($container) {
        //     $factory = new LoggerFactory($container->get(SettingGateway::class));
        //     return $factory->getLogger('gibbon');
        // });

        // $container->share('mysql_logger', function () use ($container) {
        //     $factory = new LoggerFactory($container->get(SettingGateway::class));
        //     return $factory->getLogger('mysql');
        // });

        // $pdo->setLogger($container->get('mysql_logger'));

        $container->share('session', function () {
            return SessionFactory::create($this->getContainer());
        });

        $container->share('twig', function () use ($absolutePath) {
            $session = $this->getLeagueContainer()->get('session');
            $loader = new \Twig\Loader\FilesystemLoader($absolutePath.'/resources/templates');

            // Add the theme templates folder so it can override core templates
            $themeName = $session->get('gibbonThemeName');
            if (is_dir($absolutePath.'/themes/'.$themeName.'/templates')) {
                $loader->prependPath($absolutePath.'/themes/'.$themeName.'/templates');
            }

            $enableDebug = $session->get('installType') == 'Development';
            // Override caching on systems during upgrades, when the system version is higher than database version
            if (version_compare((string) $this->getContainer()->get('config')->getVersion(), (string) $session->get('version'), '>')) {
                $enableDebug = true;
            }

            // Add module templates
            $moduleName = $session->get('module');
            if (is_dir($absolutePath.'/modules/'.$moduleName.'/templates')) {
                $loader->prependPath($absolutePath.'/modules/'.$moduleName.'/templates');
            }

            $cachePath = $session->has('cachePath') ? $session->get('cachePath').'/templates' : '/uploads/cache';
            $twig = new \Twig\Environment($loader, array(
                'cache' => $absolutePath.$cachePath,
                'debug' => $enableDebug,
            ));

            $twig->addGlobal('absolutePath', $session->get('absolutePath'));
            $twig->addGlobal('absoluteURL', $session->has('absoluteURL') ? $session->get('absoluteURL') : '.');
            $twig->addGlobal('gibbonThemeName', $themeName);
            $twig->addGlobal('themeColour', $session->has('themeColour') ? $session->get('themeColour') : 'purple');


            $twig->addFunction(new \Twig\TwigFunction('__', function ($string, $domain = null) {
                return __($string, $domain);
            }));

            $twig->addFunction(new \Twig\TwigFunction('__m', function ($string, $params = []) {
                return __m($string, $params);
            }));

            $twig->addFunction(new \Twig\TwigFunction('__n', function ($singular, $plural, $n, $params = [], $options = []) {
                return __n($singular, $plural, $n, $params, $options);
            }));

            $twig->addFunction(new \Twig\TwigFunction('formatUsing', function ($method, ...$args) {
                return Format::$method(...$args);
            }, ['is_safe' => ['html']]));

            $twig->addFilter(new \Twig\TwigFilter('jsonDecode', 'json_decode', ['is_safe' => ['html']]));

            return $twig;
        });

        $container->share('action', function () {
            $session = $this->getLeagueContainer()->get('session');
            $data = [
                'actionName'   => '%'.$session->get('action').'%',
                'moduleName'   => $session->get('module'),
                'gibbonRoleID' => $session->get('gibbonRoleIDCurrent'),
            ];
            $sql = "SELECT gibbonAction.*
                    FROM gibbonAction
                    JOIN gibbonModule ON (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID)
                    LEFT JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID AND gibbonPermission.gibbonRoleID=:gibbonRoleID)
                    LEFT JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPermission.gibbonRoleID)
                    WHERE gibbonAction.URLList LIKE :actionName
                    AND gibbonModule.name=:moduleName";

            $actionData = $this->getContainer()->get('db')->selectOne($sql, $data);

            return $actionData ? $actionData : null;
        });

        $container->share('module', function () {
            $session = $this->getLeagueContainer()->get('session');
            $data = ['moduleName' => $session->get('module')];
            $sql = "SELECT * FROM gibbonModule WHERE name=:moduleName AND active='Y'";
            $moduleData = $this->getContainer()->get('db')->selectOne($sql, $data);

            return $moduleData ? new Module($moduleData) : null;
        });

        $container->share('theme', function () {
            $session = $this->getLeagueContainer()->get('session');
            if ($session->has('gibbonThemeIDPersonal')) {
                $data = ['gibbonThemeID' => $session->get('gibbonThemeIDPersonal')];
                $sql = "SELECT * FROM gibbonTheme WHERE gibbonThemeID=:gibbonThemeID";
            } else {
                $data = [];
                $sql = "SELECT * FROM gibbonTheme WHERE active='Y'";
            }

            $themeData = $this->getContainer()->get('db')->selectOne($sql, $data);

            $session->set('gibbonThemeID', $themeData['gibbonThemeID'] ?? 001);
            $session->set('gibbonThemeName', $themeData['name'] ?? 'Default');

            return $themeData ? new Theme($themeData) : null;
        });

        $container->share('page', function () use ($container) {
            $session = $this->getLeagueContainer()->get('session');

            $pageTitle = $session->get('organisationNameShort').' - '.$session->get('systemName');
            if ($session->has('module')) {
                $pageTitle .= ' - '.__($session->get('module'));
            }

            $page = new Page($container, [
                'title'   => $pageTitle,
                'address' => $session->get('address'),
                'action'  => $container->get('action'),
                'module'  => $container->get('module'),
                'theme'   => $container->get('theme'),
            ]);

            $container->add('errorHandler', new ErrorHandler($session->get('installType'), $page));

            return $page;
        });

        $container->add(MailerInterface::class, function () use ($container) {
            $view = new View($container->get('twig'));
            return (new Mailer($container->get('session')))->setView($view);
        });

        $container->add(SMSInterface::class, function () use ($container) {
            $settingGateway = $container->get(SettingGateway::class);
            $smsGateway = $settingGateway->getSettingByScope('Messenger', 'smsGateway');

            return new SMS([
                'smsGateway'   => $smsGateway,
                'smsSenderID'  => $settingGateway->getSettingByScope('Messenger', 'smsSenderID'),
                'smsURL'       => $settingGateway->getSettingByScope('Messenger', 'smsURL'),
                'smsURLCredit' => $settingGateway->getSettingByScope('Messenger', 'smsURLCredit'),
                'smsUsername'  => $settingGateway->getSettingByScope('Messenger', 'smsUsername'),
                'smsPassword'  => $settingGateway->getSettingByScope('Messenger', 'smsPassword'),
                'smsMailer'    => $smsGateway == 'Mail to SMS' ? $container->get(MailerInterface::class) : '',
            ]);
        });

        $container->add(PaymentInterface::class, function () use ($container) {
           return $container->get(Payment::class);
        });

        $container->add(Validator::class, function () {
            $session = $this->getLeagueContainer()->get('session');
            return new Validator($session->get('allowableHTML', ''), $session->get('allowableIframeSources', ''));
        });

        $container->add(PasswordPolicy::class, function () use ($container) {

            // If for some reason, setting gateway is not managed.
            if (!$container->has(SettingGateway::class)) {
                return PasswordPolicy::createNilPolicy();
            }

            // If for some reason, setting gateway is not loaded.
            $settingGateway = $container->get(SettingGateway::class);
            if (!($settingGateway instanceof SettingGateway)) {
                return PasswordPolicy::createNilPolicy();
            }

            // Load password policy from settings.
            $alpha = $settingGateway->getSettingByScope('System', 'passwordPolicyAlpha');
            $numeric = $settingGateway->getSettingByScope('System', 'passwordPolicyNumeric');
            $punctuation = $settingGateway->getSettingByScope('System', 'passwordPolicyNonAlphaNumeric');
            $minLength = $settingGateway->getSettingByScope('System', 'passwordPolicyMinLength');
            if ($alpha == false or $numeric == false or $punctuation == false or $minLength == false) {
                // If for some reason, password policy is mis-configured.
                return PasswordPolicy::createNilPolicy();
            }

            return new PasswordPolicy(
                $alpha === 'Y',
                $numeric === 'Y',
                $punctuation === 'Y',
                (int) $minLength
            );
        });
    }
}
