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
use Aura\Auth\Exception;
use Aura\Auth\Adapter\AbstractAdapter;
use Aura\Auth\Verifier\VerifierInterface;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Auth\Exception\InvalidLoginException;

/**
 * Default database adapter for Aura/Auth 
 *
 * @version  v23
 * @since    v23
 */
class DatabaseAdapter extends AbstractAdapter
{
    /**
     * @var Gibbon\Domain\User\UserGateway
     */
    protected $userGateway;

    /**
     * @var Gibbon\Domain\User\RoleGateway
     */
    protected $roleGateway;

    /**
     * @var Gibbon\Domain\School\SchoolYearGateway
     */
    protected $schoolYearGateway;

    /**
     * @var Aura\Auth\Verifier\VerifierInterface
     */
    protected $verifier;

    /**
     * @var array
     */
    protected $safeUserFields = [
        'gibbonPersonID',
        'username',
        'surname',
        'firstName',
        'preferredName',
        'officialName',
        'email',
        'emailAlternate',
        'website',
        'gender',
        'status',
        'image_240',
        'lastTimestamp',
        'messengerLastRead',
        'calendarFeedPersonal',
        'viewCalendarSchool',
        'viewCalendarPersonal',
        'viewCalendarSpaceBooking',
        'dateStart',
        'personalBackground',
        'gibboni18nIDPersonal',
        'googleAPIRefreshToken',
        'microsoftAPIRefreshToken',
        'genericAPIRefreshToken',
        'receiveNotificationEmails',
        'cookieConsent',
        'gibbonHouseID',
    ];

    /**
     * Constructor
     *
     * @param UserGateway $userGateway
     * @param RoleGateway $roleGateway
     * @param SchoolYearGateway $schoolYearGateway
     * @param VerifierInterface $verifier
     */
    public function __construct(VerifierInterface $verifier, UserGateway $userGateway, RoleGateway $roleGateway, SchoolYearGateway $schoolYearGateway)
    {
        $this->verifier = $verifier;
        $this->userGateway = $userGateway;
        $this->roleGateway = $roleGateway;
        $this->schoolYearGateway = $schoolYearGateway;
    }

    /**
     * Verifies a set of credentials against the database.
     *
     * @param array $input Credential input.
     *
     * @return array An array of login data on success.
     *
     */
    public function login(array $input)
    {
        // Validate that the username and password are both present
        $this->checkInput($input);

        // Retrieve basic user data from the database
        $userData = $this->getUserData($input);

        // Verify this user is allowed to login
        $this->verifyLoginAccess($input, $userData);

        // Verify login credentials
        $this->verifyPassword($input, $userData);

        // Get the full list of safe user data and return it
        return $this->getSafeUserData($userData);
    }

    /**
     * Handle logout logic against the storage backend.
     *
     * @param Auth $auth The authentication object to be logged out.
     * @param string $status The new authentication status after logout.
     *
     */
    public function logout(Auth $auth, $status = Status::ANON)
    {
        // do nothing
    }

    /**
     * Gets an array of basic user data from the database.
     *
     * @param array $input
     * 
     * @return array
     * 
     * @throws Exception\UsernameNotFound
     * @throws Exception\MultipleMatches
     */
    protected function getUserData(array $input)
    {
        $userResult = $this->userGateway->selectLoginDetailsByUsername($input['username']);

        if ($userResult->rowCount() < 1) {
            throw new Exception\UsernameNotFound;
        }

        if ($userResult->rowCount() > 1) {
            throw new Exception\MultipleMatches;
        }

        return $userResult->fetch();
    }

    /**
     *
     * Verifies the user has the ability to login given their credentials.
     *
     * @param array $input The user input array.
     * @param array $data The data from the database.
     *
     * @return bool
     *
     * @throws InvalidLoginException
     *
     */
    protected function verifyLoginAccess($input, $data)
    {
        return true;
    }

    /**
     *
     * Verifies the password using the provided VerifierInterface.
     *
     * @param array $input The user input array.
     * @param array $data The data from the database.
     *
     * @return bool
     *
     * @throws Exception\PasswordIncorrect
     * @throws Exception\InvalidLoginException
     *
     */
    protected function verifyPassword($input, $data)
    {
        if (empty($data['passwordStrong']) || empty($data['passwordStrongSalt'])) {
            throw new InvalidLoginException;
        }

        $verified = $this->verifier->verify(
            $data['passwordStrongSalt'].$input['password'],
            $data['passwordStrong'],
            []
        );

        if (!$verified) {
            throw new Exception\PasswordIncorrect;
        }

        return true;
    }

    /**
     * Get a clean set of full user data before returning it to be used by the LoginService.
     * Excludes unsafe fields such as passwords, etc., which should not be stored in the session.
     *
     * @param array $userData
     * 
     * @return array
     * 
     * @throws Exception\InvalidLoginException
     */
    protected function getSafeUserData($userData)
    {
        $username = $userData['username'];

        $user = $this->userGateway->getByID($userData['gibbonPersonID']);
        $user = array_intersect_key($user, array_flip($this->safeUserFields));

        // Sanitize user provided values
        $user['website'] = filter_var($user['website'], FILTER_VALIDATE_URL);
        $user['calendarFeedPersonal'] = filter_var($user['calendarFeedPersonal'], FILTER_VALIDATE_EMAIL);

        // Setup essential role information
        $user['gibbonRoleIDCurrent'] = $userData['gibbonRoleIDPrimary'];
        $user['gibbonRoleIDCurrentCategory'] = $userData['roleCategory'];
        $user['gibbonRoleIDAll'] = $this->roleGateway->selectRoleListByIDs($userData['gibbonRoleIDAll'])->fetchAll();

        if (empty($user['gibbonRoleIDAll']) || empty($user['gibbonRoleIDCurrent'])) {
            throw new InvalidLoginException;
        }

        // Update user information
        $this->userGateway->update($userData['gibbonPersonID'], [
            'lastIPAddress' => $_SERVER['REMOTE_ADDR'],
            'lastTimestamp' => date('Y-m-d H:i:s'),
            'failCount' => 0,
            'username' => $username,
        ]);

        return [$username, $user]; 
    }
    
}
