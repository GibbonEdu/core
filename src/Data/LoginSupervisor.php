<?php
/**
 * Gibbon, Flexible & Open School System
 * Copyright (C) 2010, Ross Parker
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Date: 22/01/2019
 * Time: 08:40
 */
namespace Gibbon\Data;

use Gibbon\Comms\NotificationEvent;
use Gibbon\Database\Connection;
use Gibbon\Session;
use Psr\Container\ContainerInterface;

/**
 * Class LoginSupervisor
 * @package Gibbon\Data
 */
class LoginSupervisor
{
    /**
     * @var string
     */
    private $guid;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var array
     */
    private $post = [];

    /**
     * @var array
     */
    private $returnParameters = [];

    /**
     * LoginSupervisor constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->session = new Session($container);
        $this->connection = $container->get('db');
        $this->session->setDatabaseConnection($this->getConnection());

        setCurrentSchoolYear($this->getGuid(), $this->getConnection()->getConnection());
        // Sanitize the whole $_POST array
        $validator = new Validator();
        $this->post = $validator->sanitize($_POST);

        $this->getSession()->set('gibbonSchoolYearIDCurrent', $this->getSession()->get('gibbonSchoolYearID'));
        $this->getSession()->set('gibbonSchoolYearNameCurrent', $this->getSession()->get('gibbonSchoolYearName'));
        $this->getSession()->set('gibbonSchoolYearSequenceNumberCurrent', $this->getSession()->get('gibbonSchoolYearSequenceNumber'));

        $this->getSession()->set('pageLoads', null);
    }

    /**
     * @return string
     */
    public function getGuid(): string
    {
        if (empty($this->guid))
            $this->guid = $this->session->guid();
        return $this->guid;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return Session
     */
    public function getSession(): Session
    {
        return $this->session;
    }

    /**
     * @return bool
     */
    public function isValidUserPassword()
    {
        if (empty($this->getPostValue('username')) || empty($this->getPostValue('password'))) {
            $this->redirectTo(['loginReturn' => 'fail0b']);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getPost(): array
    {
        return $this->post;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getPostValue(string $name)
    {
        if (isset($this->post[$name]))
            return $this->post[$name];
        return null;
    }

    /**
     * @var array
     */
    private $user;

    /**
     * @return LoginSupervisor
     */
    public function isUserInDatabase(): LoginSupervisor
    {
        $data = ['username' => $this->getPostValue('username') ?: $this->getSession()->get('username')];
        $sql = "SELECT gibbonPerson.*, futureYearsLogin, pastYearsLogin FROM gibbonPerson LEFT JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE ((username=:username OR (LOCATE('@', :username)>0 AND email=:username) ) AND (status='Full'))";
        $result = $this->getConnection()->selectOne($sql, $data, true);

        if ($result instanceof PDOStatement)
        {
            setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, null, 'Login - Failed', ['username' => $this->getPostValue('username'), 'reason' => 'Username does not exist'], $_SERVER['REMOTE_ADDR']);
            $this->redirectTo(['loginReturn'=>'fail1']);
        }
        $this->user = $result;

        return $this;
    }

    /**
     * @return $this
     */
    public function canUserLogin()
    {
        // Insufficient privileges to login
        if ($this->user['canLogin'] !== 'Y')
        {
            $this->redirectTo(['loginReturn'=>'fail2']);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function canRoleLogin()
    {
        // Get primary role info
        $data = ['gibbonRoleIDPrimary' => $this->user['gibbonRoleIDPrimary']];
        $sql = "SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleIDPrimary";
        $role = $this->getConnection()->selectOne($sql, $data);

        // Login not allowed for this role
        if (!empty($role['canLoginRole']) && $role['canLoginRole'] !== 'Y') {
            $this->redirectTo(['loginReturn'=>'fail9']);
        }
        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function hasExceededFailCount(int $count = 3)
    {
        if ($this->user['failCount'] >= $count)
        {
            $this->user['failCount'] = $this->incrementFailCount();

            setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, $this->user['gibbonPersonID'], 'Login - Failed', ['username' => $this->user['username'], 'reason' => 'Too many failed logins'], $_SERVER['REMOTE_ADDR']);
            $this->redirectTo(['loginReturn'=>'fail6']);
        }
        return $this;
    }

    /**
     * @return int
     */
    private function incrementFailCount(): int
    {
        $this->user['failCount']++;
        $dataSecure = array('lastFailIPAddress' => $_SERVER['REMOTE_ADDR'], 'lastFailTimestamp' => date('Y-m-d H:i:s'), 'failCount' => $this->user['failCount'], 'username' => $this->user['username']);
        $sqlSecure = 'UPDATE gibbonPerson SET lastFailIPAddress=:lastFailIPAddress, lastFailTimestamp=:lastFailTimestamp, failCount=:failCount WHERE (username=:username)';
        $resultSecure = $this->getConnection()->update($sqlSecure, $dataSecure, true);

        if ($this->user['failCount'] == 3) {
            // Raise a new notification event
            $event = new NotificationEvent('User Admin', 'Login - Failed');

            $event->addRecipient($this->getSession()->get('organisationAdministrator'));
            $event->setNotificationText(sprintf(__('Someone failed to login to account "%1$s" 3 times in a row.'), $this->user['username']));
            $event->setActionLink('/index.php?q=/modules/User Admin/user_manage.php&search=' . $this->user['username']);

            $event->sendNotifications($this->getConnection(), $this->getSession());
        }

        return $this->user['failCount'];
    }

    /**
     * @return LoginSupervisor
     */
    public function verifyPassword()
    {
        $encoder = new PasswordEncoder();

        $salt = $this->user['passwordStrongSalt'];
        $password = $this->user['passwordStrong'] . (isset($this->user['password']) ? $this->user['password'] : '');
        $passwordTest = $encoder->isPasswordValid($password, $this->getPostValue('password'), $salt);
        if ($encoder->getCurrentEncryption() === 'MD5') {
            $passwordTest = $this->migratePassword($encoder);
        }

        //Test to see if password matches username
        if (! $passwordTest) {
            //FAIL PASSWORD

            $this->incrementFailCount();
            setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, $this->user['gibbonPersonID'], 'Login - Failed', array('username' => $this->user['username'], 'reason' => 'Incorrect password'), $_SERVER['REMOTE_ADDR']);
            $this->redirectTo(['loginReturn'=>'fail1']);
        }
        return $this;
    }

    /**
     * @param PasswordEncoder $encoder
     * @return bool
     */
    private function migratePassword(PasswordEncoder $encoder): bool
    {
        //Migrate to strong password
        $passwordStrong = $encoder->encodePassword($this->getPostValue('password'), 'SHA256');
        $dataSecure = ['passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $encoder->getSalt(), 'username' => $this->user['username']];
        $sqlSecure = "UPDATE gibbonPerson SET password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt WHERE (username=:username)";
        if ($this->getConnection()->update($sqlSecure,$dataSecure, true) !== 1)
            return false;
        setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, $this->user['gibbonPersonID'], 'Password Changed - Success', ['username' => $this->user['username'], 'reason' => 'Password was migrated from an old encryption.'], $_SERVER['REMOTE_ADDR']);
        return true;
    }

    /**
     * @return $this
     */
    public function isRoleSet()
    {
        if (empty($this->user['gibbonRoleIDPrimary']) || count(getRoleList($this->user['gibbonRoleIDAll'], $this->getConnection()->getConnection())) === 0) {
            //FAILED TO SET ROLES
            setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, $this->user['gibbonPersonID'], 'Login - Failed', ['username' => $this->user['username'], 'reason' => 'Failed to set role(s)'], $_SERVER['REMOTE_ADDR']);
            $this->redirectTo(['loginReturn'=>'fail2']);
        }
        return $this;
    }

    public function isValidSchoolYear()
    {
        if ($this->getPostValue('gibbonSchoolYearID') !== $this->getSession()->get('gibbonSchoolYearID')) {
            if ($this->user['futureYearsLogin'] !== 'Y' && $this->user['pastYearsLogin'] !== 'Y') { //NOT ALLOWED DUE TO CONTROLS ON ROLE, KICK OUT!
                setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, $this->user['gibbonPersonID'], 'Login - Failed',['username' => $this->user['username'], 'reason' => 'Not permitted to access non-current school year'], $_SERVER['REMOTE_ADDR']);
                $this->redirectTo(['loginReturn'=>'fail9']);
            } else {
                //Get details on requested school year
                $dataYear = ['gibbonSchoolYearID' => $this->getPostValue('gibbonSchoolYearID')];
                $sqlYear = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultYear = $this->getConnection()->selectOne($sqlYear, $dataYear, true);

                //Check number of rows returned.
                //If it is not 1, show error
                if (empty($resultYear['gibbonSchoolYearID'])) {
                    die(__('Configuration Error: there is a problem accessing the current Academic Year from the database.'));
                }
                //Else get year details
                else {
                    $rowYear = $resultYear;
                    if ($this->user['futureYearsLogin'] !== 'Y' && $this->getSession()->get('gibbonSchoolYearSequenceNumber') < $rowYear['sequenceNumber']) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
                        setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, $this->user['gibbonPersonID'], 'Login - Failed', ['username' => $this->user['username'], 'reason' => 'Not permitted to access non-current school year'], $_SERVER['REMOTE_ADDR']);
                        $this->redirectTo(['loginReturn'=>'fail9']);
                    } elseif ($this->user['pastYearsLogin'] !== 'Y' && $this->getSession()->get('gibbonSchoolYearSequenceNumber') > $rowYear['sequenceNumber']) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
                        setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, $this->user['gibbonPersonID'], 'Login - Failed', ['username' => $this->user['username'], 'reason' => 'Not permitted to access non-current school year'], $_SERVER['REMOTE_ADDR']);
                        $this->redirectTo(['loginReturn'=>'fail9']);
                    } else { //ALLOWED
                        $this->getSession()->set('gibbonSchoolYearID', $rowYear['gibbonSchoolYearID']);
                        $this->getSession()->set('gibbonSchoolYearName', $rowYear['name']);
                        $this->getSession()->set('gibbonSchoolYearSequenceNumber', $rowYear['sequenceNumber']);
                    }
                }
            }
        }

        return $this;
    }

    public function changeLanguage()
    {

        //Allow for non-system default language to be specified from login form
        if ($this->getPostValue('gibboni18nID') !== $this->getSession()->get(['i18n','gibboni18nID'])) {
            $dataLanguage = ['gibboni18nID' => $this->getPostValue('gibboni18nID')];
            $sqlLanguage = 'SELECT * FROM gibboni18n WHERE gibboni18nID=:gibboni18nID';
            $resultLanguage = $this->getConnection()->selectOne($sqlLanguage, $dataLanguage, true);
            if (! empty($resultLanguage['gibboni18nID'])) {
                setLanguageSession($this->getGuid(), $resultLanguage, false);
            }
        } else {
            //If no language specified, get user preference if it exists
            if (!empty($this->getSession()->get('gibboni18nIDPersonal'))) {
                $dataLanguage = ['gibboni18nID' => $this->getSession()->get('gibboni18nIDPersonal')];
                $sqlLanguage = "SELECT * FROM gibboni18n WHERE active='Y' AND gibboni18nID=:gibboni18nID";
                $resultLanguage = $this->getConnection()->selectOne($sqlLanguage, $dataLanguage, true);
                if (! empty($resultLanguage['gibboni18nID'])) {
                    setLanguageSession($this->getGuid(), $resultLanguage, false);
                }
            }
        }
        return $this;
    }

    /**
     * configure
     */
    public function configure()
    {
        //USER EXISTS, SET SESSION VARIABLES
        $this->getSession()->createUserSession($this->user['username'], $this->user);

        // Set these from local values
        $this->getSession()->set('passwordStrong', $this->user['passwordStrong']);
        $this->getSession()->set('passwordStrongSalt', $this->user['passwordStrongSalt']);
        $this->getSession()->set('googleAPIAccessToken', $this->getSession()->get('googleAPIAccessToken'));
        $this->getSession()->set('passwordForceReset', $this->user['passwordForceReset']);

        //Make best effort to set IP address and other details, but no need to error check etc.
        $data = ['lastIPAddress' => $_SERVER['REMOTE_ADDR'], 'lastTimestamp' => date('Y-m-d H:i:s'), 'failCount' => 0, 'username' => $this->user['username']];
        $sql = 'UPDATE gibbonPerson SET lastIPAddress=:lastIPAddress, lastTimestamp=:lastTimestamp, failCount=:failCount WHERE username=:username';
        $this->getConnection()->update($sql, $data, true);

        if (isset($_GET['q']) && $_GET['q'] === '/publicRegistration.php') {
            if (isset($this->returnParameters['q']) && $this->returnParameters['q'] === '/publicRegistration.php')
                $this->returnParameters = [];
            $parameters = [];
        } elseif (isset($this->returnParameters['q']) && $this->returnParameters['q'] === '/preferences.php') {
                $this->returnParameters = ['q' => '/preferences.php', 'forceReset' => $this->user['passwordForceReset']];
                if ($this->forceReset)
                    $parameters = ['return' => 'successa'];
                else
                    $parameters = ['return' => 'success0'];
        } else {
            $parameters = ['q'=>$_GET['q']];
        }

        setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, $this->user['gibbonPersonID'], 'Login - Success', ['username' => $this->user['username']], $_SERVER['REMOTE_ADDR']);
        $this->redirectTo($parameters);
    }

    /**
     * @return LoginSupervisor
     */
    public function isValidPassword(): LoginSupervisor
    {
        if (empty($this->getPostValue('password')) || empty($this->getPostValue('passwordNew')) || empty($this->getPostValue('passwordConfirm'))) {
            $this->redirectTo(['return' => 'error1']);
        }

        if ($this->getPostValue('passwordNew') !== $this->getPostValue('passwordConfirm')) {
            $this->redirectTo(['return' => 'error4']);
        }

        if ($this->getPostValue('password') === $this->getPostValue('passwordNew')) {
            $this->redirectTo(['return' => 'error7']);
        }

        if (! self::doesPasswordMatchPolicy($this->getConnection()->getConnection(), $this->getPostValue('passwordNew'))) {
            $this->redirectTo(['return' => 'error6']);
        }

        return $this;
    }

    /**
     * @param $connection2
     * @param $passwordNew
     * @return bool
     */
    public static function doesPasswordMatchPolicy($connection, $passwordNew): bool
    {
        $output = true;

        $alpha = getSettingByScope($connection, 'System', 'passwordPolicyAlpha');
        $numeric = getSettingByScope($connection, 'System', 'passwordPolicyNumeric');
        $punctuation = getSettingByScope($connection, 'System', 'passwordPolicyNonAlphaNumeric');
        $minLength = getSettingByScope($connection, 'System', 'passwordPolicyMinLength');

        if ($alpha == false or $numeric == false or $punctuation == false or $minLength == false) {
            $output = false;
        } else {
            if ($alpha != 'N' or $numeric != 'N' or $punctuation != 'N' or $minLength >= 0) {
                if ($alpha == 'Y') {
                    if (preg_match('`[A-Z]`', $passwordNew) == false or preg_match('`[a-z]`', $passwordNew) == false) {
                        $output = false;
                    }
                }
                if ($numeric == 'Y') {
                    if (preg_match('`[0-9]`', $passwordNew) == false) {
                        $output = false;
                    }
                }
                if ($punctuation == 'Y') {
                    if (preg_match('/[^a-zA-Z0-9]/', $passwordNew) == false and strpos($passwordNew, ' ') == false) {
                        $output = false;
                    }
                }
                if ($minLength > 0) {
                    if (strLen($passwordNew) < $minLength) {
                        $output = false;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * @return array
     */
    public function getReturnParameters(): array
    {
        return $this->returnParameters ?: [];
    }

    /**
     * @param array $parameters
     * @return LoginSupervisor
     */
    public function setReturnParameters(array $parameters): LoginSupervisor
    {
        $this->returnParameters = $parameters;
        return $this;
    }

    /**
     * @param array $parameters
     */
    private function redirectTo(array $parameters = [])
    {
        $query = '?';
        foreach(array_merge($this->getReturnParameters(), $parameters) as $q=>$w)
        {
            $query .= $q.'='.$w.'&';
        }
        $query = $this->getSession()->get('absoluteURL') . '/index.php' . rtrim($query, '&?');
        header('Location: ' . $query);
        exit;
    }

    /**
     * @var bool
     */
    private $forceReset = false;

    /**
     * @return LoginSupervisor
     */
    public function saveNewPassword(): LoginSupervisor
    {
        $encoder = new PasswordEncoder();
        $this->user['passwordStrong'] = $encoder->encodePassword($this->getPostValue('passwordNew'));
        $this->user['passwordStrongSalt'] = $encoder->getSalt();
        $this->forceReset = $this->user['passwordForceReset'] === 'Y' ? true : false ;
        $this->user['passwordForceReset'] = 'N';

        $data = array('passwordStrong' => $this->user['passwordStrong'], 'salt' => $this->user['passwordStrongSalt'], 'username' => $this->user['username']);
        $sql = "UPDATE gibbonPerson SET password='', passwordStrong=:passwordStrong, passwordStrongSalt=:salt, passwordForceReset='N' WHERE (username=:username)";
        $result = $this->getConnection()->update($sql, $data, true);
        if ($result !== 1)
            $this->redirectTo(['return'=>'error2']);

        return $this;
    }
}
