<?php

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\User;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\User\UserGateway;
use League\OAuth2\Client\Provider\GenericProvider;

include "../../gibbon.php";


$provider = new GenericProvider([
    // Required
    'clientId'                  => 'ea478154-7b76-4359-b36b-f772f1be9007',
    'clientSecret'              => '_GpVSvjKNq1mx3B.4~d5FG5chL67EU1.Rh',
    'redirectUri'               => 'http://localhost:8888/gibbon/lib/google/microsoft.php',
    // Optional
    'urlAuthorize'              => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
    'urlAccessToken'            => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
    'urlResourceOwnerDetails'   => 'https://outlook.office.com/api/v1.0/me',
    'scopes'                    => 'openid profile offline_access email user.read.all calendars.read calendars.read.shared',
]);

if (isset($_GET['error'])) {
    echo $_GET['error'];
    echo $_GET['error_description'];
    exit;
}

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    // header('Location: '.$authUrl);
    // exit;

    $themeName = isset($_SESSION[$guid]['gibbonThemeName'])? $_SESSION[$guid]['gibbonThemeName'] : 'Default';
    echo '<a target=\'_top\' class="login block mb-4" href="' . $authUrl . '" onclick="addGoogleLoginParams(this)">';
        echo '<button class="w-full bg-white rounded shadow border border-gray-400 flex items-center px-2 py-1 mb-2 text-gray-600 hover:shadow-md hover:border-blue-600 hover:text-blue-600">';
            echo '<img class="w-6 h-6 m-2" src="themes/'.$themeName.'/img/microsoft-login.svg">';
            echo '<span class="flex-grow text-lg">'.__('Sign in with Microsoft').'</span>';
        echo '</button>';
    echo '</a>';

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    try {
        // We got an access token, let's now get the user's details
        $graph = new Graph();
        $graph->setAccessToken($token->getToken());

        // $user = $graph->createRequest('GET', '/me?$select=displayName,userPrincipalName,mail')
        $user = $graph->createRequest('GET', '/me')
            ->setReturnType(User::class)
            ->execute();

        // printf('Hello %s! (%s)', $user->getDisplayName(), $user->getUserPrincipalName() );

        // echo '<pre>';
        // print_r($user);
        // echo '</pre>';

    } catch (Exception $e) {
        // Failed to get user details
        exit('Oh dear...');
    }

    $gibbon->session->set('microsoftAPIAccessToken', $token->getToken());
    $gibbon->session->set('viewCalendarSchool', 'Y');
    $gibbon->session->set('calendarFeed', 'Testing');

    $refreshToken = $token->getRefreshToken();
    $email = $user->getUserPrincipalName();

    $userGateway = $container->get(UserGateway::class);
    $roleGateway = $container->get(RoleGateway::class);

    $userSelect = $userGateway->selectBy(['email' => $email, 'status' => 'Full']);

    if ($userSelect->rowCount() != 1) {
        setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, null, 'Microsoft Login - Failed', array('username' => $email, 'reason' => 'No matching email found', 'email' => $email), $_SERVER['REMOTE_ADDR']);
        $session->forget('microsoftAPIAccessToken');
        session_destroy();
        $_SESSION[$guid] = NULL;
        $URL = "../../index.php?loginReturn=fail8";
        header("Location: {$URL}");
        exit;
    }

    $user = $userSelect->fetch();
    $role = $roleGateway->getByID($user['gibbonRoleIDPrimary']);

    if ($user['canLogin'] != 'Y' || empty($role) || (!empty($role['canLoginRole']) && $role['canLoginRole'] != 'Y')) {
        $session->forget('microsoftAPIAccessToken');
        @session_destroy();
        $URL = "../../index.php?loginReturn=fail2";
        header("Location: {$URL}");
        exit;
    }

    //USER EXISTS, SET SESSION VARIABLES
	$gibbon->session->createUserSession($user['username'], $user);

    $userGateway->update($user['gibbonPersonID'], [
        'lastIPAddress' => $_SERVER["REMOTE_ADDR"],
        'lastTimestamp' => date("Y-m-d H:i:s"),
    ]);

    if (!empty($refreshToken)) {
        $gibbon->session->set('microsoftAPIRefreshToken', $token->getRefreshToken());
        $userGateway->update($user['gibbonPersonID'], [
            'microsoftAPIRefreshToken' => $refreshToken,
        ]);
    }

    if ($session->has('username')) {
        setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, $user['gibbonPersonID'], 'Microsoft Login - Success', ['username' => $user['username']], $_SERVER['REMOTE_ADDR']);
        $URL = "../../index.php";
        header("Location: {$URL}");
        exit;
    } else {
        setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, null, 'Google Login - Failed', array('username' => $user['username'], 'reason' => 'No matching email found', 'email' => $email), $_SERVER['REMOTE_ADDR']);
        $session->forget('microsoftAPIAccessToken');
        session_destroy();
        $_SESSION[$guid] = NULL;
        $URL = "../../index.php?loginReturn=fail8";
        header("Location: {$URL}");
        exit;
    }
}
