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

use Gibbon\Data\Validator;
use Gibbon\Domain\Activities\ActivityCategoryGateway;

$_POST['address'] = '/modules/Activities/activities_categories.php';

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_categories.php') == false) {
    exit;
} else {
    // Proceed!
    $order = $_POST['order'] ?? [];

    if (empty($order)) {
        exit;
    } else {
        $categoryGateway = $container->get(ActivityCategoryGateway::class);

        $count = 1;
        foreach ($order as $gibbonActivityCategoryID) {
            $updated = $categoryGateway->update($gibbonActivityCategoryID, ['sequenceNumber' => $count]);
            $count++;
        }
    }
}
