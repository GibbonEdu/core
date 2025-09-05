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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include '../../gibbon.php';

include './moduleFunctions.php';

$URL = $gibbon->session->get('absoluteURL') . '/index.php?q=/modules/' . $gibbon->session->get('module') . '/name.php';

if (!isActionAccessible($guid, $connection2, '/modules/Module Name/name_delete.php')) {
    // Access denied
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $ID = $_POST['ID']; // The ID of the item you will be deleting

    // Check that your required variables are present
    if (empty($thing)) { 
        $URL = $URL.'&return=error3';
        header("Location: {$URL}");
        exit;
    }

    // Your SQL or Gateway delete query
    $URL .= "&return=success";
    header("Location: {$URL}");
}
