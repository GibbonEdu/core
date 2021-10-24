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

use Aura\Auth\AuthFactory;
use Aura\Auth\Exception as AuraException;
use Gibbon\Http\Url;
use Gibbon\Data\Validator;
use Gibbon\Auth\Exception;
use Gibbon\Auth\Adapter\DefaultAdapter;
use Gibbon\Auth\Adapter\OAuthAdapterInterface;
use Gibbon\Auth\Adapter\OAuthGoogleAdapter;
use Gibbon\Auth\Adapter\OAuthMicrosoftAdapter;
use Gibbon\Auth\Adapter\OAuthGenericAdapter;
use Gibbon\Domain\System\LogGateway;

// Gibbon system-wide include
require_once './gibbon.php';

setCurrentSchoolYear($guid, $connection2);

// The current/actual school year info, just in case we are working in a different year
$session->set('gibbonSchoolYearIDCurrent', $session->get('gibbonSchoolYearID'));
$session->set('gibbonSchoolYearNameCurrent', $session->get('gibbonSchoolYearName'));
$session->set('gibbonSchoolYearSequenceNumberCurrent', $session->get('gibbonSchoolYearSequenceNumber'));

$session->forget('pageLoads');
$URL = Url::fromRoute();

// Sanitize the whole $_POST array
$_POST = $container->get(Validator::class)->sanitize($_POST);

// Setup system log gateway
$logGateway = $container->get(LogGateway::class);
$logLoginAttempt = function ($type, $reason = '') use ($logGateway, $session){
    $logGateway->addLog($session->get('gibbonSchoolYearIDCurrent'), null, null, $type, [
        'username' => $_POST['username'] ?? $session->get('username') ?? '',
        'reason'   => $reason,
    ],$_SERVER['REMOTE_ADDR']);
};

// Setup authentication classes
$authFactory = $container->get(AuthFactory::class);
$auth = $authFactory->newInstance();

// Determine the authentication adapter to use
$method = $_GET['method'] ?? $session->get('oAuthMethod') ?? '';
switch (strtolower($method)) {
    case 'google':
        $authAdapter = $container->get(OAuthGoogleAdapter::class);
        break;
    case 'microsoft':
        $authAdapter = $container->get(OAuthMicrosoftAdapter::class);
        break;
    case 'oauth':
        $authAdapter = $container->get(OAuthGenericAdapter::class);
        break;
    default:
        $authAdapter = $container->get(DefaultAdapter::class);
}

// Handle OAuth2 redirect when obtaining authorization code
if ($authAdapter instanceof OAuthAdapterInterface && !$authAdapter->hasOAuthCode()) {
    $session->set('oAuthMethod', $method);
    header("Location: {$authAdapter->getAuthorizationUrl()}");
    exit;
}

// Handle login
try {
    $loginService = $authFactory->newLoginService($authAdapter);
    $loginService->login($auth, [
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
    ]);

    // Enable passing URL params to pages after logging in
    if (isset($_GET['q']) && $_GET['q'] != '/publicRegistration.php') {
        unset($_GET['return']);
        $URL = Url::fromRoute()->withQueryParams($_GET);
    }

    $logLoginAttempt('Login - Success');
    header("Location: {$URL}");
    exit;
} catch (AuraException\UsernameMissing $e) {
    header("Location: {$URL->withQueryParam('loginReturn', 'fail0')}");
    exit;
} catch (AuraException\PasswordMissing $e) {
    header("Location: {$URL->withQueryParam('loginReturn', 'fail0')}");
    exit;
} catch (AuraException\UsernameNotFound $e) {
    $logLoginAttempt('Login - Failed', 'Username does not exist');
    header("Location: {$URL->withQueryParam('loginReturn', 'fail1')}");
    exit;
} catch (AuraException\MultipleMatches $e) {
    $logLoginAttempt('Login - Failed', 'Multiple users with the same username or email');
    header("Location: {$URL->withQueryParam('loginReturn', 'fail1')}");
    exit;
} catch (AuraException\PasswordIncorrect $e) {
    $logLoginAttempt('Login - Failed', 'Incorrect password');
    header("Location: {$URL->withQueryParam('loginReturn', 'fail1')}");
    exit;
} catch (Exception\InsufficientPrivileges $e) {
    $logLoginAttempt('Login - Failed', 'Not permitted to login');
    header("Location: {$URL->withQueryParam('loginReturn', 'fail2')}");
    exit;
} catch (Exception\InsufficientYearAccess $e) {
    $logLoginAttempt('Login - Failed', 'Not permitted to access non-current school year');
    header("Location: {$URL->withQueryParam('loginReturn', 'fail3')}");
    exit;
} catch (Exception\InsufficientRoleAccess $e) {
    $logLoginAttempt('Login - Failed', 'Not permitted to login using primary role');
    header("Location: {$URL->withQueryParam('loginReturn', 'fail4')}");
    exit;
} catch (Exception\DatabaseLoginError $e) {
    header("Location: {$URL->withQueryParam('loginReturn', 'fail5')}");
    exit;
} catch (Exception\TooManyFailedLogins $e) {
    $logLoginAttempt('Login - Failed', 'Too many failed logins');
    header("Location: {$URL->withQueryParam('loginReturn', 'fail6')}");
    exit;
} catch (Exception\OAuthLoginError $e) {
    $logLoginAttempt('OAuth Login - Failed', $e->getMessage());
    header("Location: {$URL->withQueryParam('loginReturn', 'fail7')}");
    exit;
} catch (Exception\OAuthUserNotFound $e) {
    $logLoginAttempt('OAuth Login - Failed', 'No matching email found');
    header("Location: {$URL->withQueryParam('loginReturn', 'fail8')}");
    exit;
} catch (Exception\MaintenanceMode $e) {
    header("Location: {$URL->withQueryParam('loginReturn', 'fail10')}");
    exit;
}
