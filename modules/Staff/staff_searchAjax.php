<?php

use Gibbon\Services\Format;
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

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view.php') == false) {
    die( __('Your request failed because you do not have access to this action.') );
} else {

    $searchTerm = (isset($_REQUEST['q']))? $_REQUEST['q'] : '';

    // Allow for * as wildcard (as well as %)
    $searchTerm = str_replace('*', '%', $searchTerm);

    // Cancel out early for empty searches
    if (empty($searchTerm)) die('[]');

    $resultSet = array();

    // STAFF
    $data = array('search' => '%'.$searchTerm.'%', 'today' => date('Y-m-d') );
    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, username, image_240, gibbonStaff.type, gibbonStaff.jobTitle
            FROM gibbonPerson
            JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
            WHERE status='Full'
            AND (dateStart IS NULL OR dateStart<=:today)
            AND (dateEnd IS NULL  OR dateEnd>=:today)
            AND (gibbonPerson.surname LIKE :search
                OR gibbonPerson.preferredName LIKE :search
                OR gibbonPerson.username LIKE :search)
            ORDER BY preferredName, surname";

    $resultSet = $pdo->select($sql, $data)->fetchAll();

    $absoluteURL = $gibbon->session->get('absoluteURL');
    $list = array_map(function ($token) use ($absoluteURL) {
        return [
            'id'       => $token['gibbonPersonID'],
            'name'     => Format::name('', $token['preferredName'], $token['surname'], 'Staff', false, true),
            'jobTitle' => !empty($token['jobTitle']) ? $token['jobTitle'] : $token['type'],
            'image'    => $absoluteURL.'/'.$token['image_240'],
        ];
    }, $resultSet);

    // Output the json
    echo json_encode($list);
}
