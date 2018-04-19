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

include '../../functions.php';
include '../../config.php';

include './moduleFunctions.php';

$order = (isset($_POST['order']))? $_POST['order'] : '';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php') == false) {

    echo __($guid, 'Your request failed because you do not have access to this action.');

} else {

    if ($order != '') {

        $columnOrder = array_slice($order, 1 );
        $minSequence = (isset($_POST['sequence']))? $_POST['sequence'] : 0;

        for ($i = 0; $i < count($columnOrder); $i++) {

            // Re-order the sequenceNumber based off the new column order, using the minimum value to preserve pagination / filters
            try {
                $data = array('gibbonMarkbookColumnID' => $columnOrder[$i], 'sequenceNumber' => $i + $minSequence );
                $sql = 'UPDATE gibbonMarkbookColumn SET sequenceNumber=:sequenceNumber WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                print __($guid, 'Your request failed due to a database error.');
            }

        }

    }
}
