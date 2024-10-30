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

use Gibbon\Domain\Messenger\GroupGateway;

// Gibbon system-wide include
require_once '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/staffSettings.php') == false) {
    // Access denied
    die(__('Your request failed because you do not have access to this action.') );
} else {
    $searchTerm = $_REQUEST['q'] ?? '';

    // Cancel out early for empty searches
    if (empty($searchTerm)) die('[]');

    // Search
    $groupGateway = $container->get(GroupGateway::class);
    $criteria = $groupGateway->newQueryCriteria(true)
        ->searchBy($groupGateway->getSearchableColumns(), $searchTerm)
        ->sortBy('name');

    $results = $groupGateway->queryGroups($criteria, $session->get('gibbonSchoolYearID'))->toArray();

    $list = array_map(function ($token) {
        return [
            'id'       => $token['gibbonGroupID'],
            'name'     => $token['name'],
        ];
    }, $results);

    // Output the json
    echo json_encode($list);
}
