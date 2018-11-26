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

use Gibbon\Forms\Prefab\DeleteForm;

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_condition_delete.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $gibbonPersonMedicalID = $_GET['gibbonPersonMedicalID'] ?? '';
    $gibbonPersonMedicalConditionID = $_GET['gibbonPersonMedicalConditionID'] ?? '';
    $search = $_GET['search'] ?? '';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    if ($gibbonPersonMedicalID == '' or $gibbonPersonMedicalConditionID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonPersonMedicalConditionID' => $gibbonPersonMedicalConditionID);
            $sql = 'SELECT * FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/medicalForm_manage_edit.php&search=$search&gibbonPersonMedicalID=$gibbonPersonMedicalID'>".__('Back').'</a>';
                echo '</div>';
            }

            $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/medicalForm_manage_condition_deleteProcess.php?gibbonPersonMedicalID=$gibbonPersonMedicalID&gibbonPersonMedicalConditionID=$gibbonPersonMedicalConditionID&search=$search");
            echo $form->getOutput();
        }
    }
}
