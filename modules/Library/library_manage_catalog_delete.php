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

use Gibbon\Forms\Prefab\DeleteForm;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    //Check if gibbonLibraryItemID specified
    $gibbonLibraryItemID = $_GET['gibbonLibraryItemID'] ?? '';
    $name = $_GET['name'] ?? '';
    $gibbonLibraryTypeID = $_GET['gibbonLibraryTypeID'] ?? '';
    $gibbonSpaceID = $_GET['gibbonSpaceID'] ?? '';
    $status = $_GET['status'] ?? '';
    $gibbonPersonIDOwnership = $_GET['gibbonPersonIDOwnership'] ?? '';
    $typeSpecificFields = $_GET['typeSpecificFields'] ?? '';
    if ($gibbonLibraryItemID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID);
            $sql = 'SELECT * FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/'.$session->get('module')."/library_manage_catalog_deleteProcess.php?name=".$name.'&status='.$status.'&typeSpecificFields='.$typeSpecificFields);
            $form->addHiddenValue('gibbonLibraryItemID', $gibbonLibraryItemID);
            $form->addHiddenValue('gibbonLibraryTypeID', $gibbonLibraryTypeID);
            $form->addHiddenValue('gibbonSpaceID', $gibbonSpaceID);
            $form->addHiddenValue('gibbonPersonIDOwnership', $gibbonPersonIDOwnership);
            echo $form->getOutput();
        }
    }
}
