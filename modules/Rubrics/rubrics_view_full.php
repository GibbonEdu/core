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

//Rubric includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_view_full.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    //Proceed!
    //Check if gibbonRubricID specified
    $gibbonRubricID = $_GET['gibbonRubricID'] ?? '';
    if ($gibbonRubricID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data3 = array('gibbonRubricID' => $gibbonRubricID);
            $sql3 = 'SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID';
            $result3 = $connection2->prepare($sql3);
            $result3->execute($data3);

        if ($result3->rowCount() != 1) {
            $page->addError(__('The specified record does not exist.'));
        } else {
            //Let's go!
            $row3 = $result3->fetch();

            echo "<h2 style='margin-bottom: 10px;'>";
            echo $row3['name'].'<br/>';
            echo '</h2>';

            echo rubricView($guid, $connection2, $gibbonRubricID, false);
        }
    }
}
