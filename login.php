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

use Gibbon\Comms\NotificationEvent;
use Gibbon\Data\PasswordEncoder;
use Gibbon\Data\Validator;
use Gibbon\Database\Connection;
use Gibbon\Session;
use Psr\Container\ContainerInterface;


// Gibbon system-wide include
require_once './gibbon.php';
$loginManager = new LoginManager($container);
$loginManager->isValidUserPassword()
    ->isUserInDatabase()
    ->canUserLogin()
    ->canRoleLogin()
    ->hasExceededFailCount(3)
    ->verifyPassword()
    ->isRoleSet()
    ->isValidSchoolYear()
    ->changeLanguage()
    ->configure()
;

class LoginManager
{
    CONST URL = './index.php';
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
     * LoginManager constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->session = new Session($container);
        $this->connection = $container->get('db');
        $this->session->setDatabaseConnection($this->getConnection());

        setCurrentSchoolYear($this->getGuid(), $this->getConnection()->getConnection());
        $this->post = // Sanitize the whole $_POST array
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
            header("Location: " . self::URL . '?loginReturn=fail0b');
            exit;
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
     * @return bool
     */
    public function isUserInDatabase()
    {
        $data = ['username' => $this->getPostValue('username')];
        $sql = "SELECT gibbonPerson.*, futureYearsLogin, pastYearsLogin FROM gibbonPerson LEFT JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE ((username=:username OR (LOCATE('@', :username)>0 AND email=:username) ) AND (status='Full'))";
        $result = $this->getConnection()->selectOne($sql, $data, true);

        if ($result instanceof PDOStatement)
        {
            setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, null, 'Login - Failed', ['username' => $this->getPostValue('username'), 'reason' => 'Username does not exist'], $_SERVER['REMOTE_ADDR']);
            header("Location: " . self::URL . '?loginReturn=fail1');
            exit;
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
            header("Location: " . self::URL . '?loginReturn=fail2');
            exit;
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
            header("Location: " . self::URL . '?loginReturn=fail9');
            exit;
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
            header("Location: " . self::URL . '?loginReturn=fail6');
            exit;
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
     * @return LoginManager
     */
    public function verifyPassword()
    {
        $encoder = new PasswordEncoder();

        $salt = $this->user['passwordStrongSalt'];
        $password = $this->user['passwordStrong'] . $this->user['password'];
        $passwordTest = $encoder->isPasswordValid($password, $this->getPostValue('password'), $salt);
        if ($encoder->getCurrentEncryption() === 'MD5') {
            $passwordTest = $this->migratePassword($encoder);
        }

        //Test to see if password matches username
        if (! $passwordTest) {
            //FAIL PASSWORD

            $this->incrementFailCount();
            setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, $this->user['gibbonPersonID'], 'Login - Failed', array('username' => $this->user['username'], 'reason' => 'Incorrect password'), $_SERVER['REMOTE_ADDR']);
            $URL = self::URL . '?loginReturn=fail1';
            header("Location: " .$URL);
            exit;
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
        $salt = getSalt();
        $passwordStrong = $encoder->encodePassword($this->getPostValue('password'), $salt, 'SHA256');
        $dataSecure = ['passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'username' => $this->user['username']];
        $sqlSecure = "UPDATE gibbonPerson SET password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt WHERE (username=:username)";
        if ($this->getConnection()->update($sqlSecure,$dataSecure, false) !== 1)
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
            header("Location: ".self::URL."?loginReturn=fail2");
            exit;
        }
        return $this;
    }

    public function isValidSchoolYear()
    {
        if ($this->getPostValue('gibbonSchoolYearID') !== $this->getSession()->get('gibbonSchoolYearID')) {
            if ($this->user['futureYearsLogin'] !== 'Y' && $this->user['pastYearsLogin'] !== 'Y') { //NOT ALLOWED DUE TO CONTROLS ON ROLE, KICK OUT!
                setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, $this->user['gibbonPersonID'], 'Login - Failed',['username' => $this->user['username'], 'reason' => 'Not permitted to access non-current school year'], $_SERVER['REMOTE_ADDR']);
                header("Location: " . self::URL . "?loginReturn=fail9");
                exit();
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
                        header("Location: ".self::URL."?loginReturn=fail9");
                        exit();
                    } elseif ($this->user['pastYearsLogin'] !== 'Y' && $this->getSession()->get('gibbonSchoolYearSequenceNumber') > $rowYear['sequenceNumber']) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
                        setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, $this->user['gibbonPersonID'], 'Login - Failed', ['username' => $this->user['username'], 'reason' => 'Not permitted to access non-current school year'], $_SERVER['REMOTE_ADDR']);
                        header("Location: ".self::URL."?loginReturn=fail9");
                        exit();
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

    public function configure()
    {
        //USER EXISTS, SET SESSION VARIABLES
        $this->getSession()->createUserSession($this->user['username'], $this->user);

        // Set these from local values
        $this->getSession()->set('passwordStrong', $this->user['passwordStrong']);
        $this->getSession()->set('passwordStrongSalt', $this->user['passwordStrongSalt']);
        $this->getSession()->set('googleAPIAccessToken', null);

        //Make best effort to set IP address and other details, but no need to error check etc.
        $data = ['lastIPAddress' => $_SERVER['REMOTE_ADDR'], 'lastTimestamp' => date('Y-m-d H:i:s'), 'failCount' => 0, 'username' => $this->user['username']];
        $sql = 'UPDATE gibbonPerson SET lastIPAddress=:lastIPAddress, lastTimestamp=:lastTimestamp, failCount=:failCount WHERE username=:username';
        $this->getConnection()->update($sql, $data, true);

        if (isset($_GET['q'])) {
            if ($_GET['q'] == '/publicRegistration.php') {
                $URL = './index.php';
            } else {
                $URL = './index.php?q='.$_GET['q'];
            }
        } else {
            $URL = './index.php';
        }

        setLog($this->getConnection()->getConnection(), $this->getSession()->get('gibbonSchoolYearIDCurrent'), null, $this->user['gibbonPersonID'], 'Login - Success', ['username' => $this->user['username']], $_SERVER['REMOTE_ADDR']);
        header("Location: ".$URL);
        exit;
    }
}
