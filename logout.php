<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Http\Url;
use Gibbon\Auth\Adapter\DefaultAdapter;

// Gibbon system-wide include
require_once './gibbon.php';

$URL = Url::fromRoute();
if (isset($_GET['timeout'])) {
    $URL = $URL->withQueryParam('timeout', 'true');
}
// Setup authentication classes
$authFactory = $container->get(AuthFactory::class);
$authAdapter = $container->get(DefaultAdapter::class);
$auth = $authFactory->newInstance();

// Logout
$logoutService = $authFactory->newLogoutService($authAdapter);
$logoutService->logout($auth);

header("Location: {$URL}");
