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

use Gibbon\Forms\Form;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Forms\Layout\Element;

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_my.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'My Activities').'</div>';
    echo '</div>';

    try {
        $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "(SELECT gibbonActivity.*, gibbonActivityStudent.status, NULL AS role FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y') UNION (SELECT gibbonActivity.*, NULL as status, gibbonActivityStaff.role FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID) WHERE gibbonActivityStaff.gibbonPersonID=:gibbonPersonID2 AND gibbonSchoolYearID=:gibbonSchoolYearID2 AND active='Y') ORDER BY name";
         $result = $connection2->prepare($sql);
        $result->execute($data);
   } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        $highestAction = getHighestGroupedAction($guid, '/modules/Activities/activities_attendance.php', $connection2);
        $options = getSettingByScope($connection2, 'Activities', 'activityTypes');
        $form = Form::create('bulkAction',$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_my.php');
        $table = $form->addRow()->addTable();

        //Initialise headers
        $header = $table->addHeaderRow();
        $header->addContent(__('Activity'));
        $header->addContent(__('Type'));
        $header->addContent(__('Role'));
        $header->addContent(__('Status'));
        $header->addContent(__('Actions'));
        
        $class = true;
        foreach($result->fetchAll() as $act)
        {
            $actRow = $table->addRow()->addClass($class == true? 'even' : 'odd');
            $actRow->addContent($act['name']);
            $actRow->addContent($act['type']);
            $actRow->addContent($act['role']);
            $actRow->addContent($act['status'] ? $act['status'] : "N/A");
            $col = $actRow->addColumn()->addClass('inline');
            $col->addWebLink()->setURL($_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_manage_enrolment.php&gibbonActivityID=' . $act['gibbonActivityID'] . '&search=&gibbonSchoolYearTermID=')->setEmbeddedElements(new Element('<img src="./themes/Default/img/config.png"/>'));
            $col->addWebLink()->setURL($_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_my_full.php&gibbonActivityID=' . $act['gibbonActivityID'] . 'width=1000&height=550')->setEmbeddedElements(new Element('<img src="./themes/Default/img/plus.png"/>'));
            $col->addWebLink()->setURL($_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_attendance.php&gibbonActivityID=' . $act['gibbonActivityID'])->setEmbeddedElements(new Element('<img src="./themes/Default/img/attendance.png"/>')); 
            switch($class)
            {
                case true : $class = false; break;
                case false : $class = true; break;
            }
        }
        echo $form->getOutput();
    }
}
