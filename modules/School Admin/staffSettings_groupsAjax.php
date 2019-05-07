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

// Gibbon system-wide include
require_once '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/staffSettings.php') == false) {
    // Access denied
    die( __('Your request failed because you do not have access to this action.') );
} else {
    $searchTerm = (isset($_REQUEST['q']))? $_REQUEST['q'] : '';

    // Cancel out early for empty searches
    if (empty($searchTerm)) die('[]');

    $resultSet = array();

    // STAFF
    $data = array('search' => '%'.$searchTerm.'%', 'today' => date('Y-m-d') );
    $sql = "SELECT gibbonGroupID, name FROM gibbonGroup ORDER BY name";

    $resultSet = $pdo->select($sql, $data)->fetchAll();

    $absoluteURL = $gibbon->session->get('absoluteURL');
    $list = array_map(function ($token) use ($absoluteURL) {
        return [
            'id'       => $token['gibbonGroupID'],
            'name'     => $token['name'],
        ];
    }, $resultSet);

    // Output the json
    echo json_encode($list);
}
