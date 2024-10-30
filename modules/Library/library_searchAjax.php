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

use Gibbon\Domain\Library\LibraryGateway;

// Gibbon system-wide include
require_once '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_shelves.php') == false) {
    // Access denied
    die(__('Your request failed because you do not have access to this action.'));
} else {
    $searchTerm = $_REQUEST['q'] ?? '';

    // Allow for * as wildcard (as well as %)
    $searchTerm = str_replace('*', '%', $searchTerm);

    // Cancel out early for empty searches
    if (empty($searchTerm)) die('[]');

    // Search
    $libraryGateway = $container->get(LibraryGateway::class);
    $criteria = $libraryGateway->newQueryCriteria(true)
        ->searchBy($libraryGateway->getSearchableColumns(), $searchTerm)
        ->filterBy('parent', 'NULL')
        ->sortBy('name');

    $results = $libraryGateway->queryItemsForShelves($criteria)->toArray();

    $list = array_map(function ($token) {
        return [
            'id'       => $token['gibbonLibraryItemID'],
            'name'     => $token['name'],
            'producer' => $token['producer'],
            'imageLocation' => $token['imageLocation'],
        ];
    }, $results);

    // Output the json
    echo json_encode($list);
}
