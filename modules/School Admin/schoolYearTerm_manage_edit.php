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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearTerm_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/schoolYearTerm_manage.php'>".__($guid, 'Manage Terms')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Term').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonSchoolYearTermID = $_GET['gibbonSchoolYearTermID'];
    if ($gibbonSchoolYearTermID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearTermID' => $gibbonSchoolYearTermID);
            $sql = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID';
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
            $values = $result->fetch();

            $form = Form::create('schoolYearTerm', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/schoolYearTerm_manage_editProcess.php?gibbonSchoolYearTermID='.$gibbonSchoolYearTermID);
		    $form->setFactory(DatabaseFormFactory::create($pdo));

		    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

		    $row = $form->addRow();
		        $row->addLabel('gibbonSchoolYearID', __('School Year'));
		        $row->addSelectSchoolYear('gibbonSchoolYearID')->isRequired()->selected($values['gibbonSchoolYearID']);

		    $row = $form->addRow();
		        $row->addLabel('sequenceNumber', __('Sequence Number'))->description(__('Must be unique. Controls chronological ordering.'));
		        $row->addSequenceNumber('sequenceNumber', 'gibbonSchoolYearTerm', $values['sequenceNumber'])
		        	->isRequired()
		        	->maxLength(3)
		        	->setValue($values['sequenceNumber']);

		    $row = $form->addRow();
		        $row->addLabel('name', __('Name'));
		        $row->addTextField('name')->isRequired()->maxLength(20)->setValue($values['name']);

		    $row = $form->addRow();
		        $row->addLabel('nameShort', __('Short Name'));
		        $row->addTextField('nameShort')->isRequired()->maxLength(4)->setValue($values['nameShort']);

		    $row = $form->addRow();
		        $row->addLabel('firstDay', __('First Day'));
		        $row->addDate('firstDay')->isRequired()->setValue(dateConvertBack($guid, $values['firstDay']));

		    $row = $form->addRow();
		        $row->addLabel('lastDay', __('Last Day'));
		        $row->addDate('lastDay')->isRequired()->setValue(dateConvertBack($guid, $values['lastDay']));

		    $row = $form->addRow();
		        $row->addFooter();
		        $row->addSubmit();

		    echo $form->getOutput();
        }
    }
}
