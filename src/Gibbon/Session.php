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

namespace Gibbon;

use Psr\Container\ContainerInterface;

/**
 * Session Class
 *
 * @version	v13
 * @since	v12
 */
class Session
{
	/**
	 * string
	 */
	private	$guid ;

	/**
	 * Gibbon/sqlConnection
	 */
	private	$pdo ;

	/**
	 * Construct
	 */
	public function __construct(ContainerInterface $container)
	{
		global $guid;

		// Start the session (this should be the first time called)
		if (session_status() !== PHP_SESSION_ACTIVE) {
            //Prevent breakage of back button on POST pages
		    ini_set('session.cache_limiter', 'private');
            session_cache_limiter(false);
        
            session_start();
        }

        $config = $container->get('config');
		$this->guid = (isset($config))? $config->guid() : $guid; // Backwards compatability for external modules
	}

	/**
	 * Set Database Connection
	 * @version  v13
	 * @since    v13
	 * @param    sqlConnection  $pdo
	 */
	public function setDatabaseConnection(sqlConnection $pdo) {
		$this->pdo = $pdo;
	}

	/**
	 * Return the guid string
	 * TODO: Remove this
	 *
	 * @return	string
	 */
	public function guid() {
		return $this->guid;
	}

	/**
	 * Get Session Value
	 *
	 * @param	string	Session Value Name
	 * @param	mixed	default Define a value to return if the variable is empty
	 *
	 * @return	mixed
	 */
	public function get($name, $default = null)
	{
        if (is_array($name)) {
            // Fetch a value from multi-dimensional array with an array of keys
            $retrieve = function($array, $keys, $default) {
                foreach($keys as $key) {
                    if (!isset($array[$key])) return $default;
                    $array = $array[$key];
                }
                return $array;
            };

            return $retrieve($_SESSION[$this->guid], $name, $default);
        }

        return (isset($_SESSION[$this->guid][$name]))? $_SESSION[$this->guid][$name] : $default;
	}

	/**
	 * Set Session Value
	 *
	 * @param	string	Session Value Name
	 * @param	mixed	Session Value
	 *
	 * @return	object	Gibbon\session
	 */
	public function set($name, $value)
	{
		$_SESSION[$this->guid][$name] = $value ;

		return $this;
	}

	/**
	 * Set Multiple Session Values
	 *
	 * @param	array	Array of name => value pairs
	 *
	 * @return	object	Gibbon\session
	 */
	public function setAll( array $values )
	{
		foreach ($values as $name => $value) {
			$this->set($name, $value);
		}

		return $this;
	}

	public function loadSystemSettings($pdo)
	{
		// System settings from gibbonSetting
		$sql = "SELECT name, value FROM gibbonSetting WHERE scope='System'";
	    $result = $pdo->executeQuery(array(), $sql);

        while ($row = $result->fetch()) {
            $this->set($row['name'], $row['value']);
        }
	}

    public function loadLanguageSettings($pdo)
    {
        // Language settings from gibboni18n
        $sql = "SELECT * FROM gibboni18n WHERE systemDefault='Y'";
        $result = $pdo->executeQuery(array(), $sql);

        while ($row = $result->fetch()) {
            $this->set('i18n', $row);
        }
    }

	public function createUserSession($username, $userData) {

		$this->set('username', $username);
		$this->set('passwordStrong', $userData['passwordStrong']);
		$this->set('passwordStrongSalt', $userData['passwordStrongSalt']);
		$this->set('passwordForceReset', $userData['passwordForceReset']);
		$this->set('gibbonPersonID', $userData['gibbonPersonID']);
		$this->set('surname', $userData['surname']);
		$this->set('firstName', $userData['firstName']);
		$this->set('preferredName', $userData['preferredName']);
		$this->set('officialName', $userData['officialName']);
		$this->set('email', $userData['email']);
		$this->set('emailAlternate', $userData['emailAlternate']);
		$this->set('website', filter_var($userData['website'], FILTER_VALIDATE_URL));
		$this->set('gender', $userData['gender']);
		$this->set('status', $userData['status']);
		$this->set('gibbonRoleIDPrimary', $userData['gibbonRoleIDPrimary']);
		$this->set('gibbonRoleIDCurrent', $userData['gibbonRoleIDPrimary']);
		$this->set('gibbonRoleIDCurrentCategory', getRoleCategory($userData['gibbonRoleIDPrimary'], $this->pdo->getConnection()) );
		$this->set('gibbonRoleIDAll', getRoleList($userData['gibbonRoleIDAll'], $this->pdo->getConnection()) );
		$this->set('image_240', $userData['image_240']);
		$this->set('lastTimestamp', $userData['lastTimestamp']);
		$this->set('calendarFeedPersonal', filter_var($userData['calendarFeedPersonal'], FILTER_VALIDATE_EMAIL));
		$this->set('viewCalendarSchool', $userData['viewCalendarSchool']);
		$this->set('viewCalendarPersonal', $userData['viewCalendarPersonal']);
		$this->set('viewCalendarSpaceBooking', $userData['viewCalendarSpaceBooking']);
		$this->set('dateStart', $userData['dateStart']);
		$this->set('personalBackground', $userData['personalBackground']);
		$this->set('messengerLastBubble', $userData['messengerLastBubble']);
		$this->set('gibbonThemeIDPersonal', $userData['gibbonThemeIDPersonal']);
		$this->set('gibboni18nIDPersonal', $userData['gibboni18nIDPersonal']);
		$this->set('googleAPIRefreshToken', $userData['googleAPIRefreshToken']);
		$this->set('receiveNotificationEmails', $userData['receiveNotificationEmails']);
		$this->set('gibbonHouseID', $userData['gibbonHouseID']);

		// Cache FF actions on login
        $this->cacheFastFinderActions($userData['gibbonRoleIDPrimary']);
	}

	/**
	 * Cache translated FastFinder actions to allow searching actions with the current locale
	 * @version  v13
	 * @since    v13
	 * @param    Gibbon/sqlConnection  $pdo
	 */
	public function cacheFastFinderActions($gibbonRoleIDCurrent) {

		// Get the accesible actions for the current user
        $data = array( 'gibbonRoleID' => $gibbonRoleIDCurrent );
        $sql = "SELECT DISTINCT concat(gibbonModule.name, '/', gibbonAction.entryURL) AS id, SUBSTRING_INDEX(gibbonAction.name, '_', 1) AS name, gibbonModule.type, gibbonModule.name AS module
                FROM gibbonModule
                JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID)
                JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                WHERE active='Y'
                AND menuShow='Y'
                AND gibbonPermission.gibbonRoleID=:gibbonRoleID
                ORDER BY name";

        $result = $this->pdo->executeQuery($data, $sql);

        if ($result->rowCount() > 0) {
            $actions = array();

            // Translate the action names
            while ($row = $result->fetch()) {
                $row['name'] = __($row['name']);
                $actions[] = $row;
            }

            // Cache the resulting set of translated actions
            $this->set('fastFinderActions', $actions);
        }
        return $actions;
	}
}
