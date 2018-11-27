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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Attendance Settings'), 'attendanceSettings.php')
        ->add(__('Edit Attendance Code'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonAttendanceCodeID = (isset($_GET['gibbonAttendanceCodeID']))? $_GET['gibbonAttendanceCodeID'] : NULL;

    if (empty($gibbonAttendanceCodeID)) {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
	    try {
	        $data = array('gibbonAttendanceCodeID' => $gibbonAttendanceCodeID);
	        $sql = 'SELECT * FROM gibbonAttendanceCode WHERE gibbonAttendanceCodeID=:gibbonAttendanceCodeID';
	        $result = $connection2->prepare($sql);
	        $result->execute($data);
	    } catch (PDOException $e) {
	        echo "<div class='error'>".$e->getMessage().'</div>';
	    }

	    if ($result->rowCount() != 1) {
	        echo "<div class='error'>";
	        echo __('The selected record does not exist, or you do not have access to it.');
	        echo '</div>';
	    } else {
	        //Let's go!
            $values = $result->fetch(); 
            
            $form = Form::create('attendanceCode', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/attendanceSettings_manage_editProcess.php?gibbonAttendanceCodeID='.$gibbonAttendanceCodeID);
            $form->setFactory(DatabaseFormFactory::create($pdo));
        
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        
            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
                $row->addTextField('name')->isRequired()->maxLength(30);
            
            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
                $row->addTextField('nameShort')->isRequired()->maxLength(4);
        
            $directions = array(
                'In'     => __('In Class'),
                'Out' => __('Out of Class'),
            );
            $row = $form->addRow();
                $row->addLabel('direction', __('Direction'));
                $row->addSelect('direction')->isRequired()->fromArray($directions);
        
            $scopes = array(
                'Onsite'         => __('Onsite'),
                'Onsite - Late'  => __('Onsite - Late'),
                'Offsite'        => __('Offsite'),
                'Offsite - Left' => __('Offsite - Left'),
            );
            $row = $form->addRow();
                $row->addLabel('scope', __('Scope'));
                $row->addSelect('scope')->isRequired()->fromArray($scopes);
        
            $row = $form->addRow();
                $row->addLabel('sequenceNumber', __('Sequence Number'));
                $row->addSequenceNumber('sequenceNumber', 'gibbonAttendanceCode', $values['sequenceNumber'])->isRequired()->maxLength(3);
        
            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->isRequired();
        
            $row = $form->addRow();
                $row->addLabel('reportable', __('Reportable'));
                $row->addYesNo('reportable')->isRequired();
        
            $row = $form->addRow();
                $row->addLabel('future', __('Allow Future Use'))->description(__('Can this code be used in Set Future Absence?'));
                $row->addYesNo('future')->isRequired();
        
            $row = $form->addRow();
                $row->addLabel('gibbonRoleIDAll', __('Available to Roles'))->description(__('Controls who can use this code.'));
                $row->addSelectRole('gibbonRoleIDAll')->selectMultiple()->loadFromCSV($values);
        
            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);
        
            echo $form->getOutput();
		}
	}
}
