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

use Gibbon\Domain\School\YearGroupGateway;

$_POST['address'] = '/modules/School Admin/yearGroup_manage.php';

require_once '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/yearGroup_manage.php') == false) {
    exit;
} else {
    // Proceed!
    $data = $_POST['data'] ?? [];
    $order = json_decode($_POST['order']);

    if (empty($order)) {
        exit;
    } else {
        $yearGroupGateway = $container->get(YearGroupGateway::class);

        $count = 1;
        foreach ($order as $gibbonYearGroupID) {
            $updated = $yearGroupGateway->update($gibbonYearGroupID, ['sequenceNumber' => $count]);
            $count++;
        }
    }
}
