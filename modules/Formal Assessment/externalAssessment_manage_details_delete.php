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

use Gibbon\Forms\PrefabFormFactory;

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_manage_details_delete.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/externalAssessment.php'>".__($guid, 'View All Assessments')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/externalAssessment_details.php&gibbonPersonID='.$_GET['gibbonPersonID']."'>".__($guid, 'Student Details')."</a> > </div><div class='trailEnd'>".__($guid, 'Delete Assessment').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonExternalAssessmentStudentID = $_GET['gibbonExternalAssessmentStudentID'];
    $gibbonPersonID = $_GET['gibbonPersonID'];
    $search = $_GET['search'];
    $allStudents = '';
    if (isset($_GET['allStudents'])) {
        $allStudents = $_GET['allStudents'];
    }
    if ($gibbonExternalAssessmentStudentID == '' or $gibbonPersonID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID);
            $sql = 'SELECT * FROM gibbonExternalAssessmentStudent WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Formal Assessment/externalAssessment_details.php&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents'>".__($guid, 'Back').'</a>';
                echo '</div>';
            }

            $form = PrefabFormFactory::createDeleteForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/externalAssessment_manage_details_deleteProcess.php?gibbonExternalAssessmentStudentID=$gibbonExternalAssessmentStudentID&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents");
            echo $form->getOutput();
        }
    }
}
