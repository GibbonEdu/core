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

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_condition_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/medicalForm_manage.php'>".__($guid, 'Manage Medical Forms')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/medicalForm_manage_edit.php&&gibbonPersonMedicalID='.$_GET['gibbonPersonMedicalID']."'>".__($guid, 'Edit Medical Form')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Condition').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/medicalForm_manage_condition_edit.php&gibbonPersonMedicalConditionID='.$_GET['editID'].'&search='.$_GET['search'].'&gibbonPersonMedicalID='.$_GET['gibbonPersonMedicalID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    //Check if school year specified
    $gibbonPersonMedicalID = $_GET['gibbonPersonMedicalID'];
    $search = $_GET['search'];
    if ($gibbonPersonMedicalID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonPersonMedicalID' => $gibbonPersonMedicalID);
            $sql = 'SELECT gibbonPersonMedical.*, surname, preferredName
                FROM gibbonPersonMedical
                    JOIN gibbonPerson ON (gibbonPersonMedical.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID';
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

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/medicalForm_manage_edit.php&search=$search&gibbonPersonMedicalID=$gibbonPersonMedicalID'>".__($guid, 'Back').'</a>';
                echo '</div>';
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/medicalForm_manage_condition_addProcess.php?gibbonPersonMedicalID=$gibbonPersonMedicalID&search=$search");

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->setClass('smallIntBorder fullWidth');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonPersonMedicalID', $gibbonPersonMedicalID);

            $form->addRow()->addHeading(__('General Information'));

            $row = $form->addRow();
                $row->addLabel('personName', __('Student'));
                $row->addTextField('personName')->setValue(formatName('', htmlPrep($values['preferredName']), htmlPrep($values['surname']), 'Student'))->isRequired()->readonly();

            $data = array();
            $sql = 'SELECT gibbonMedicalConditionID AS value, name FROM gibbonMedicalCondition ORDER BY name';
            $row = $form->addRow();
                $row->addLabel('name', __('Condition Name'));
                $row->addSelect('name')->fromQuery($pdo, $sql, $data)->isRequired()->placeholder();

            $data = array();
            $sql = 'SELECT gibbonMedicalConditionID AS value, name FROM gibbonMedicalCondition ORDER BY name';
            $row = $form->addRow();
                $row->addLabel('gibbonAlertLevelID', __('Risk'));
                $row->addSelectAlert('gibbonAlertLevelID')->isRequired();

            $row = $form->addRow();
                $row->addLabel('triggers', __('Triggers'));
                $row->addTextField('triggers')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('reaction', __('Reaction'));
                $row->addTextField('reaction')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('response', __('Response'));
                $row->addTextField('response')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('medication', __('Medication'));
                $row->addTextField('medication')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('lastEpisode', __('Last Episode Date'));
                $row->addDate('lastEpisode');

            $row = $form->addRow();
                $row->addLabel('lastEpisodeTreatment', __('Last Episode Treatment'));
                $row->addTextField('lastEpisodeTreatment')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('comment', __('Comment'));
                $row->addTextArea('comment');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
?>
