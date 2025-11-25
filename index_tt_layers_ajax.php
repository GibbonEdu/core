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


use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;

// Gibbon system-wide includes
include './gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') == false) {
    // Access denied
    echo Format::alert(__('Your request failed because you do not have access to this action.'), 'error');
} else {
    $order = $_POST['order'] ?? [];
    $order = array_reverse($order);

    if (empty($order)) return;

    $count = 0;
    $sequence = [];
    foreach ($order as $index => $layerID) {
        $sequence[] = $layerID.':'.$count;
        $count = $count + 10;
    }

    $userGateway = $container->get(UserGateway::class);
    $userGateway->setUserPreferenceByScope($session->get('gibbonPersonID'), 'ttOptions', 'layerOrder', implode(',', $sequence));
}
