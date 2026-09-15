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

use Gibbon\Domain\Markbook\MarkbookColumnGateway;

include '../../gibbon.php';

$gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';
$gibbonMarkbookColumnID = $_POST['gibbonMarkbookColumnID'] ?? '';
$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/markbook_edit_delete.php&gibbonMarkbookColumnID=$gibbonMarkbookColumnID&gibbonCourseClassID=$gibbonCourseClassID";
$URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonMarkbookColumnID and gibbonCourseClassID specified
    if ($gibbonMarkbookColumnID == '' or $gibbonCourseClassID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } 
    
    $markbookColumnGateway = $container->get(MarkbookColumnGateway::class);
    $highestAction = getHighestGroupedAction($guid, '/modules/Markbook/markbook_edit_delete.php', $connection2);

    if ($highestAction == 'Edit Markbook_everything' || $highestAction == 'Edit Markbook_multipleClassesAcrossSchool') {
        $markbookColumn = $markbookColumnGateway->getByID($gibbonMarkbookColumnID);
    } elseif ($highestAction == 'Edit Markbook_multipleClassesInDepartment') {
        $markbookColumn = $markbookColumnGateway->getMarkbookColumnByDepartmentPerson($gibbonMarkbookColumnID, $session->get('gibbonPersonID'));
    } else {
        $markbookColumn = $markbookColumnGateway->getMarkbookColumnByTeacher($gibbonMarkbookColumnID, $session->get('gibbonPersonID'));
    }

    if (empty($markbookColumn) || $markbookColumn['gibbonMarkbookColumnID'] != $gibbonMarkbookColumnID) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    $deleted = $markbookColumnGateway->delete($gibbonMarkbookColumnID);
    
    if (!$deleted) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    $URLDelete = $URLDelete.'&return=success0';
    header("Location: {$URLDelete}");
    
}
