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
use Aura\Auth\Exception;
use Gibbon\Http\Url;
use Gibbon\Data\Validator;
use Gibbon\Auth\Adapter\DatabaseAdapter;
use Gibbon\Auth\Exception\InvalidLoginException;
use Gibbon\Domain\System\LogGateway;
use Gibbon\Domain\System\SessionGateway;

// Gibbon system-wide include
require_once './gibbon.php';

setCurrentSchoolYear($guid, $connection2);

//The current/actual school year info, just in case we are working in a different year
$session->set('gibbonSchoolYearIDCurrent', $session->get('gibbonSchoolYearID'));
$session->set('gibbonSchoolYearNameCurrent', $session->get('gibbonSchoolYearName'));
$session->set('gibbonSchoolYearSequenceNumberCurrent', $session->get('gibbonSchoolYearSequenceNumber'));

$session->forget('pageLoads');

// Sanitize the whole $_POST array
$_POST = $container->get(Validator::class)->sanitize($_POST);

// Setup system logs and redirect URL
$logGateway = $container->get(LogGateway::class);
$sessionGateway = $container->get(SessionGateway::class);
$URL = Url::fromRoute();

// Setup authentication classes
$authFactory = $container->get(AuthFactory::class);
$auth = $authFactory->newInstance();

// Determine the adapter to use
$authAdapter = $container->get(DatabaseAdapter::class);

// Handle login
try {
    $loginService = $authFactory->newLoginService($authAdapter);

    $loginService->login($auth, [
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
    ]);

    $userData = $auth->getUserData();

    // Update the session data
    $session->set($userData);

    // Update current session record to attach it to this user
    $sessionGateway->update(session_id(), [
        'gibbonPersonID' => $userData['gibbonPersonID'],
        'sessionStatus' => 'Logged In',
        'timestampModified' => date('Y-m-d H:i:s'),
    ]);

    // echo '<pre>';
    // print_r($_SESSION);
    // echo '</pre>';
    // die();

    header("Location: {$URL}");
    exit;

} catch (Exception\UsernameMissing $e) {

    echo "The 'username' field is missing or empty.";
    // header("Location: {$URL->withQueryParam('loginReturn', 'fail1')}");
    exit;

} catch (Exception\PasswordMissing $e) {

    echo "The 'password' field is missing or empty.";
    // header("Location: {$URL->withQueryParam('loginReturn', 'fail1')}");
    exit;

} catch (Exception\UsernameNotFound $e) {

    echo "The username you entered was not found.";
    // header("Location: {$URL->withQueryParam('loginReturn', 'fail1')}");
    exit;

} catch (Exception\MultipleMatches $e) {

    echo "There is more than one account with that username.";
    // header("Location: {$URL->withQueryParam('loginReturn', 'fail1')}");
    exit;

} catch (Exception\PasswordIncorrect $e) {

    echo "The password you entered was incorrect.";
    // header("Location: {$URL->withQueryParam('loginReturn', 'fail1')}");
    exit;

} catch (InvalidLoginException $e) {

    echo $e->getMessage();

    echo "Invalid login details. Please try again.";

}
