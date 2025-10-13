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

use Gibbon\Domain\User\UserGateway;

include '../../gibbon.php';

if (empty($session->get('gibbonPersonID')) || empty($session->get('gibbonRoleIDPrimary'))) {
    die(__('Your request failed because you do not have access to this action.'));
} elseif ($session->get('gibbonRoleIDCurrentCategory') != 'Staff') {
    die(__('Your request failed because you do not have access to this action.'));
} else {
    $fieldName = $_POST['fieldName'] ?? '';
    $gibbonPersonID = $_POST['gibbonPersonID'] ?? $_POST[$fieldName] ?? '';

    $user = $container->get(UserGateway::class)->getByID($gibbonPersonID, ['image_240']);
    $url = $session->get('absoluteURL').'/'.$user['image_240'];

    if (empty($user['image_240']) || !file_exists($session->get('absolutePath').'/'.$user['image_240'])) {
        $url = '';
    }

    echo "<img id='{$fieldName}Photo' src='{$url}' class='relative w-full' x-ref='personPhoto'>";
}
