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

namespace Gibbon\Session;

use Gibbon\Session\Session;
use Psr\Container\ContainerInterface;
use Gibbon\Session\DatabaseSessionHandler;
use Gibbon\Session\EncryptedSessionHandler;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session as SessionInterface;

/**
 * SessionFactory Class
 *
 * @version	v23
 * @since	v23
 */
class SessionFactory
{
    /**
     * Method for creating Session from the ContainerInterface and
     * environment variables (i.e. $guid, $_GET, $_POST).
     *
     * @param array $config An array of data from the config file
     *
     * @return \Gibbon\Session\Session The newly created session object.
     */
    public static function create(ContainerInterface $container): SessionInterface {
        global $guid;

        $config = $container->get('config')->getConfig();

        if (!empty($config['sessionHandler']) && $config['sessionHandler'] == 'database' && $container->has(Connection::class)) {
            $handler = new DatabaseSessionHandler($container->get(Connection::class), $config['sessionEncryptionKey'] ?? null);
        } else {
            $handler = new NativeSessionHandler($config['sessionEncryptionKey'] ?? null);
        }

        // Set the handler for the session, enabling non-default
        session_set_save_handler($handler, true);

        // Start the session (this should be the first time called)
        if (session_status() !== PHP_SESSION_ACTIVE) {
            //Prevent breakage of back button on POST pages
            ini_set('session.cache_limiter', 'private');
            session_cache_limiter(false);

            session_start([
                'cookie_httponly'  => true,
                'cookie_secure'    => isset($_SERVER['HTTPS']),
            ]);

            header('X-Frame-Options: SAMEORIGIN');
        }

        // If session guid is not set, fallback to global $guid.
        $_guid = $config['guid'] ?? $guid ?? '';

        // Detect the current module from the GET 'q' param. Fallback to the POST 'address',
        // which is currently used in many Process pages.
        // TODO: replace this logic when switching to routing.
        $address = $_GET['q'] ?? $_POST['address'] ?? '';
        $module = $address ? getModuleName($address) : '';
        $action = $address ? getActionName($address) : '';

        // Create the instance from information of container
        // and environment.
        return new Session($_guid, $address, $module, $action);
    }

    /**
     * Populates the session with the essential System settings, required for both
     * logged in users and anonymous sessions.
     *
     * @param Session $session
     * @param Connection $db
     */
    public static function populateSettings(Session $session, Connection $db)
    {
        // System settings from gibbonSetting
        $result = $db->select('SELECT name, value FROM gibbonSetting WHERE scope=:scope', [
            ':scope' => 'System',
        ]);

        while ($row = $result->fetch()) {
            $session->set($row['name'], $row['value']);
        }

        // Language settings from gibboni18n
        $result = $db->select('SELECT * FROM gibboni18n WHERE systemDefault=:systemDefault', [
            ':systemDefault' => 'Y',
        ]);

        while ($row = $result->fetch()) {
            $session->set('i18n', $row);
        }
    }

    /**
     * Populates the session with data about a specific logged in user, called after 
     * logging in successfully.
     *
     * @param Session $session
     * @param Connection $db
     * @param string $username
     * @param string $userData
     * @return void
     */
    public static function populateUser(Session $session, Connection $db, $username, $userData)
    {
        $session->set('username', $username);
        $session->set('passwordStrong', $userData['passwordStrong']);
        $session->set('passwordStrongSalt', $userData['passwordStrongSalt']);
        $session->set('passwordForceReset', $userData['passwordForceReset']);
        $session->set('gibbonPersonID', $userData['gibbonPersonID']);
        $session->set('surname', $userData['surname']);
        $session->set('firstName', $userData['firstName']);
        $session->set('preferredName', $userData['preferredName']);
        $session->set('officialName', $userData['officialName']);
        $session->set('email', $userData['email']);
        $session->set('emailAlternate', $userData['emailAlternate']);
        $session->set('website', filter_var($userData['website'], FILTER_VALIDATE_URL));
        $session->set('gender', $userData['gender']);
        $session->set('status', $userData['status']);
        $session->set('gibbonRoleIDPrimary', $userData['gibbonRoleIDPrimary']);
        $session->set('gibbonRoleIDCurrent', $userData['gibbonRoleIDPrimary']);
        $session->set('gibbonRoleIDCurrentCategory', getRoleCategory($userData['gibbonRoleIDPrimary'], $db->getConnection()) );
        $session->set('gibbonRoleIDAll', getRoleList($userData['gibbonRoleIDAll'], $db->getConnection()) );
        $session->set('image_240', $userData['image_240']);
        $session->set('lastTimestamp', $userData['lastTimestamp']);
        $session->set('messengerLastRead', $userData['messengerLastRead']);
        $session->set('calendarFeedPersonal', filter_var($userData['calendarFeedPersonal'], FILTER_VALIDATE_EMAIL));
        $session->set('viewCalendarSchool', $userData['viewCalendarSchool']);
        $session->set('viewCalendarPersonal', $userData['viewCalendarPersonal']);
        $session->set('viewCalendarSpaceBooking', $userData['viewCalendarSpaceBooking']);
        $session->set('dateStart', $userData['dateStart']);
        $session->set('personalBackground', $userData['personalBackground']);
        $session->set('gibboni18nIDPersonal', $userData['gibboni18nIDPersonal']);
        $session->set('googleAPIRefreshToken', $userData['googleAPIRefreshToken']);
        $session->set('receiveNotificationEmails', $userData['receiveNotificationEmails']);
        $session->set('cookieConsent', $userData['cookieConsent'] ?? '');
        $session->set('gibbonHouseID', $userData['gibbonHouseID']);

        //Deal with themes
        $session->set('gibbonThemeIDPersonal', null);
        if (!empty($userData['gibbonThemeIDPersonal'])) {
            $data = array( 'gibbonThemeID' => $userData['gibbonThemeIDPersonal']);
            $sql = "SELECT gibbonThemeID FROM gibbonTheme WHERE gibbonThemeID=:gibbonThemeID";
            $result = $db->select($sql, $data);

            if ($result->rowCount() > 0) {
                $session->set('gibbonThemeIDPersonal', $userData['gibbonThemeIDPersonal']);
            }
        }
    }
}
