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

namespace Gibbon\Auth\Adapter;

use Aura\Auth\Auth;
use Aura\Auth\Status;
use Aura\Auth\Exception as AuraException;
use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Verifier\VerifierInterface;
use Gibbon\Http\Url;
use Gibbon\Auth\Exception;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\System\I18nGateway;
use Gibbon\Domain\System\SessionGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\ThemeGateway;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Contracts\Services\Session;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * Default database adapter for Aura/Auth 
 *
 * @version  v23
 * @since    v23
 */
abstract class AuthenticationAdapter implements AdapterInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var \Gibbon\Contracts\Services\Session
     */
    protected $session;

    /**
     * @var \Gibbon\Domain\System\SessionGateway
     */
    protected $sessionGateway;

    /**
     * @var \Gibbon\Domain\User\UserGateway
     */
    protected $userGateway;

    /**
     *
     * Verifies a set of credentials. Adapters must implement this.
     *
     * @param array $input Credential input.
     *
     * @return array An array of login data on success.
     *
     */
    abstract public function login(array $input);


    /**
     * Handle logout logic against the storage backend.
     *
     * @param Auth $auth The authentication object to be logged out.
     * @param string $status The new authentication status after logout.
     */
    public function logout(Auth $auth, $status = Status::ANON)
    {
        // Update current session to remove user data
        $sessionGateway = $this->getContainer()->get(SessionGateway::class);
        $sessionGateway->update(session_id(), [
            'gibbonPersonID' => null,
            'gibbonActionID' => null,
            'sessionStatus' => null,
            'sessionData' => null,
        ]);

        session_destroy();
    }

    /**
     *
     * Handle a resumed session against the storage backend.
     *
     * @param Auth $auth The authentication object to be resumed.
     *
     * @return null
     *
     */
    public function resume(Auth $auth)
    {
        // Satisfy the interface, but do nothing at this time
    }

    /**
     * Gets an array of basic user data from the database.
     *
     * @param array $input
     * 
     * @return array
     * 
     * @throws Aura\Auth\Exception\UsernameNotFound
     * @throws Aura\Auth\Exception\MultipleMatches
     */
    protected function getUserData(array $input)
    {
        $userResult = $this->userGateway->selectLoginDetailsByUsername($input['username'] ?? '');

        if ($userResult->rowCount() < 1) {
            throw new AuraException\UsernameNotFound;
        }

        if ($userResult->rowCount() > 1) {
            throw new AuraException\MultipleMatches;
        }

        return $userResult->fetch();
    }

    /**
     * Verifies user access beyond basic login credentials.
     *
     * @param array $userData User data from the database.
     *
     * @return array An array of login data on success.
     *
     */
    protected function verifyLogin(array $userData)
    {
        $this->session = $this->getContainer()->get(Session::class);
        $this->sessionGateway = $this->getContainer()->get(SessionGateway::class);
        $this->userGateway = $this->getContainer()->get(UserGateway::class);
        
        // Verify user access beyond basic login credentials
        $this->verifyUserAccess($userData);
        $this->verifySchoolYearAccess($userData);

        // Verification has succeeded, update and return a safe set of user data.
        $this->updateUserData($userData);

        return $this->getSafeUserData($userData);
    }
    
    /**
     *
     * Verifies the user has the ability to login given their credentials.
     *
     * @param array $input The user input array.
     * @param array $data The data from the database.
     *
     * @throws Gibbon\Auth\Exception\DatabaseLoginError
     * @throws Gibbon\Auth\Exception\InsufficientPrivileges
     * @throws Gibbon\Auth\Exception\InsufficientRoleAccess
     * @throws Gibbon\Auth\Exception\MaintenanceMode
     * @throws Gibbon\Auth\Exception\TooManyFailedLogins
     *
     */
    protected function verifyUserAccess($userData)
    {
        $primaryRole = $this->getContainer()->get(RoleGateway::class)->getByID($userData['gibbonRoleIDPrimary']);
        $maintenanceMode = $this->getContainer()->get(SettingGateway::class)->getSettingByScope('System Admin', 'maintenanceMode');

        // Missing role ID information
        if (empty($userData['gibbonRoleIDPrimary']) || empty($userData['gibbonRoleIDAll'])) {
            throw new Exception\DatabaseLoginError;
        }

        // Insufficient privileges for this user
        if ($userData['canLogin'] != 'Y') {
            throw new Exception\InsufficientPrivileges;
        }

        // Insufficient privileges for this role
        if (!empty($primaryRole['canLoginRole']) && $primaryRole['canLoginRole'] != 'Y') {
            throw new Exception\InsufficientRoleAccess;
        }

        // Check for maintenance mode
        if ($maintenanceMode == 'Y' && $userData['roleName'] != 'Administrator') {
            throw new Exception\MaintenanceMode;
        }

        // Check fail count, reject & alert if 3rd time
        if ($userData['failCount'] >= 3) {
            $this->updateFailCount($userData, $userData['failCount'] + 1);
            $this->notifySystemAdmin($userData);
            throw new Exception\TooManyFailedLogins;
        }
    }

    /**
     * Verifies the user has the ability to login given their credentials.
     *
     * @param array $input The user input array.
     * @param array $data The data from the database.
     *
     * @throws Gibbon\Auth\Exception\InsufficientYearAccess
     * @throws Gibbon\Auth\Exception\DatabaseLoginError
     *
     */
    protected function verifySchoolYearAccess($userData)
    {
        $gibbonSchoolYearIDSelected = $_POST['gibbonSchoolYearID'] ?? '';

        // Cancel out here if we're logging into the current year
        if (empty($gibbonSchoolYearIDSelected) || $gibbonSchoolYearIDSelected == $this->session->get('gibbonSchoolYearID')) {
            return;
        }

        // Not allowed to access either future or past years
        if ($userData['futureYearsLogin'] != 'Y' && $userData['pastYearsLogin'] != 'Y') {
            throw new Exception\InsufficientYearAccess;
        }

        // Check that this is a valid school year
        $schoolYear = $this->getContainer()->get(SchoolYearGateway::class)->getByID($gibbonSchoolYearIDSelected);
        if (empty($schoolYear)) {
            throw new Exception\DatabaseLoginError;
        }

        // Not allowed to access future years
        if ($userData['futureYearsLogin'] != 'Y' && $this->session->get('gibbonSchoolYearSequenceNumber') < $schoolYear['sequenceNumber']) {
            throw new Exception\InsufficientYearAccess;
        }

        // Not allowed to access past years
        if ($userData['pastYearsLogin'] != 'Y' && $this->session->get('gibbonSchoolYearSequenceNumber') > $schoolYear['sequenceNumber']) {
            throw new Exception\InsufficientYearAccess;
        }

        // Login allowed, set selected school year
        $this->session->set('gibbonSchoolYearID', $schoolYear['gibbonSchoolYearID']);
        $this->session->set('gibbonSchoolYearName', $schoolYear['name']);
        $this->session->set('gibbonSchoolYearSequenceNumber', $schoolYear['sequenceNumber']);
    }

    /**
     * Update last login information and update database session information.
     *
     * @param array $userData
     */
    protected function updateUserData($userData)
    {
        // Update user last login information
        $this->userGateway->update($userData['gibbonPersonID'], [
            'lastIPAddress' => $_SERVER['REMOTE_ADDR'],
            'lastTimestamp' => date('Y-m-d H:i:s'),
            'failCount' => 0,
            'username' => $userData['username'],
        ]);

        // Update current session record to attach it to this user
        $this->sessionGateway->update(session_id(), [
            'gibbonPersonID' => $userData['gibbonPersonID'],
            'sessionStatus' => 'Logged In',
            'timestampModified' => date('Y-m-d H:i:s'),
        ]);

        // Update user personal theme
        if (!empty($userData['gibbonThemeIDPersonal'])) {
            $themeGateway = $this->getContainer()->get(ThemeGateway::class);
            $this->session->set('gibbonThemeIDPersonal', $themeGateway->exists($userData['gibbonThemeIDPersonal'])
                ? $userData['gibbonThemeIDPersonal']
                : null
            );
        }

        // Update user language
        $languageSelected = $_POST['gibboni18nID'] ?? $this->session->get('gibboni18nIDPersonal') ?? null;
        if (!empty($languageSelected)) {
            if ($i18n = $this->getContainer()->get(I18nGateway::class)->getByID($languageSelected)) {
                $this->session->set('i18n', $i18n);
            }
        }
    }

    /**
     * Update the fail count and last login information.
     *
     * @param array $userData
     */
    protected function updateFailCount($userData, $failCount = 0)
    {
        $this->userGateway->update($userData['gibbonPersonID'], [
            'lastFailIPAddress' => $_SERVER['REMOTE_ADDR'],
            'lastFailTimestamp' => date('Y-m-d H:i:s'),
            'failCount' => $failCount,
            'username' => $userData['username'],
        ]);
    }

    /**
     * Get a clean set of user data for the session before returning it to be used by the LoginService.
     * Excludes unsafe fields such as passwords, etc., which should not be stored in the session.
     *
     * @param array $userData
     * 
     * @return array
     */
    protected function getSafeUserData($userData)
    {
        // Filter fields from gibbonPerson based on the safeUserFields array
        $user = $this->userGateway->getSafeUserData($userData['gibbonPersonID']);

        // Sanitize user provided values
        $user['website'] = filter_var($user['website'], FILTER_VALIDATE_URL);
        $user['calendarFeedPersonal'] = filter_var($user['calendarFeedPersonal'], FILTER_VALIDATE_EMAIL);

        // Setup essential role information
        $roleGateway = $this->getContainer()->get(RoleGateway::class);
        $user['gibbonRoleIDPrimary'] = $userData['gibbonRoleIDPrimary'];
        $user['gibbonRoleIDCurrent'] = $userData['gibbonRoleIDPrimary'];
        $user['gibbonRoleIDCurrentCategory'] = $userData['roleCategory'];
        $user['gibbonRoleIDAll'] = $roleGateway->selectRoleListByIDs($userData['gibbonRoleIDAll'])->fetchAll();

        // Load user data into the session
        $this->session->set($user);

        return [$userData['username'], $user]; 
    }

    /**
     * Raise a notification event for too many failed logins.
     *
     * @param array $userData
     */
    protected function notifySystemAdmin($userData)
    {
        if ($userData['failCount'] != 3) return;

        // Raise a new notification event
        $event = new NotificationEvent('User Admin', 'Login - Failed');

        $event->addRecipient($this->session->get('organisationAdministrator'));
        $event->setNotificationText(sprintf(__('Someone failed to login to account "%1$s" 3 times in a row.'), $userData['username']));
        $event->setActionLink(Url::fromModuleRoute('User Admin', 'user_manage')->withAbsoluteURL()->withQueryParam('search', $userData['username']));

        $event->sendNotifications($this->getContainer()->get('db'), $this->session);
    }
    
}
