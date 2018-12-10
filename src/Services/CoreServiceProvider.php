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

namespace Gibbon\Services;

use Gibbon\Core;
use Gibbon\Locale;
use Gibbon\Session;
use Gibbon\View\Page;
use Gibbon\Comms\Mailer;
use Gibbon\Comms\SMS;
use Gibbon\Services\Format;
use Gibbon\Domain\System\Theme;
use Gibbon\Domain\System\Module;
use Gibbon\Services\ErrorHandler;
use Gibbon\Contracts\Comms\Mailer as MailerInterface;
use Gibbon\Contracts\Comms\SMS as SMSInterface;
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
        MailerInterface::class,
        SMSInterface::class,
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
        $container = $this->getContainer();

        $container->add('config', new Core($this->absolutePath));
        $container->add('session', new Session($container));
        $container->add('locale', new Locale($this->absolutePath, $container->get('session')));

        Format::setupFromSession($container->get('session'));
    }

    /**
     * This is where the magic happens, within the method you can
     * access the container and register or retrieve anything
     * that you need to, but remember, every alias registered
     * within this method must be declared in the `$provides` array.
     */
    public function register()
    {
        $container = $this->getContainer();
        $absolutePath = $this->absolutePath;
        $session = $container->get('session');
        $pdo = $container->get('db');

        $container->share('twig', function () use ($absolutePath, $session) {
            $loader = new \Twig_Loader_Filesystem($absolutePath.'/resources/templates');

            // Add the theme templates folder so it can override core templates
            $themeName = $session->get('gibbonThemeName');
            if (is_dir($absolutePath.'/themes/'.$themeName.'/templates')) {
                $loader->prependPath($absolutePath.'/themes/'.$themeName.'/templates');
            }

            $twig = new \Twig_Environment($loader, array(
                'cache' => $absolutePath.'/uploads/cache',
                'debug' => $session->get('installType') == 'Development',
            ));

            $twig->addGlobal('absolutePath', $session->get('absolutePath'));
            $twig->addGlobal('absoluteURL', $session->get('absoluteURL'));

            $twig->addFunction(new \Twig_Function('__', function ($string, $domain = null) {
                return __($string, $domain);
            }));

            $twig->addFunction(new \Twig_Function('formatUsing', function ($method, ...$args) {
                return Format::$method(...$args);
            }));

            return $twig;
        });

        $container->share('action', function () use ($session, $pdo) {
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

            $actionData = $pdo->selectOne($sql, $data);

            return $actionData ? $actionData : null;
        });

        $container->share('module', function () use ($session, $pdo) {
            $data = ['moduleName' => $session->get('module')];
            $sql = "SELECT * FROM gibbonModule WHERE name=:moduleName AND active='Y'";
            $moduleData = $pdo->selectOne($sql, $data);

            return $moduleData ? new Module($moduleData) : null;
        });

        $container->share('theme', function () use ($session, $pdo) {
            if ($session->has('gibbonThemeIDPersonal')) {
                $data = ['gibbonThemeID' => $session->get('gibbonThemeIDPersonal')];
                $sql = "SELECT * FROM gibbonTheme WHERE gibbonThemeID=:gibbonThemeID";
            } else {
                $data = [];
                $sql = "SELECT * FROM gibbonTheme WHERE active='Y'";
            }

            $themeData = $pdo->selectOne($sql, $data);

            $session->set('gibbonThemeID', $themeData['gibbonThemeID'] ?? 001);
            $session->set('gibbonThemeName', $themeData['name'] ?? 'Default');

            return $themeData ? new Theme($themeData) : null;
        });

        $container->share('page', function () use ($session, $container) {
            $pageTitle = $session->get('organisationNameShort').' - '.$session->get('systemName');
            if ($session->has('module')) {
                $pageTitle .= ' - '.__($session->get('module'));
            }

            $page = new Page($container->get('twig'), [
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
            return new Mailer($container->get('session'));
        });

        $container->add(SMSInterface::class, function () use ($session, $container) {
            $connection2 = $container->get('db')->getConnection();
            $smsGateway = getSettingByScope($connection2, 'Messenger', 'smsGateway');

            return new SMS([
                'smsGateway'   => $smsGateway,
                'smsSenderID'  => getSettingByScope($connection2, 'Messenger', 'smsSenderID'),
                'smsURL'       => getSettingByScope($connection2, 'Messenger', 'smsURL'),
                'smsURLCredit' => getSettingByScope($connection2, 'Messenger', 'smsURLCredit'),
                'smsUsername'  => getSettingByScope($connection2, 'Messenger', 'smsUsername'),
                'smsPassword'  => getSettingByScope($connection2, 'Messenger', 'smsPassword'),
                'smsMailer'    => $smsGateway == 'Mail to SMS' ? $container->get(MailerInterface::class) : '',
            ]);
        });
    }
}
