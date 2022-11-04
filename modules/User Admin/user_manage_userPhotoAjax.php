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

use Gibbon\Domain\User\UserGateway;

include '../../gibbon.php';

if (empty($session->get('gibbonPersonID')) || empty($session->get('gibbonRoleIDPrimary'))) {
    die(__('Your request failed because you do not have access to this action.'));
} elseif ($session->get('gibbonRoleIDCurrentCategory') != 'Staff') {
    die(__('Your request failed because you do not have access to this action.'));
} else {
    $user = $container->get(UserGateway::class)->getByID($_POST['gibbonPersonID'] ?? '', ['image_240']);

    echo $user['image_240'] ?? '';
}
