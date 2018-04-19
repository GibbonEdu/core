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

include '../../gibbon.php';

$gibbonMarkbookColumnID = $_GET['gibbonMarkbookColumnID'];
$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$return = $_GET['return'];
$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Markbook/$return";

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    try {
        $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
        $sql = 'SELECT * FROM gibbonMarkbookColumn JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE (gibbonMarkbookColumn.gibbonCourseClassID=:gibbonCourseClassID AND gibbonMarkbookColumnID=:gibbonMarkbookColumnID)';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit();
    }

    if ($result->rowCount() != 1) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        //Proceed!
		include './markbook_viewExportContents.php';
    }
}
