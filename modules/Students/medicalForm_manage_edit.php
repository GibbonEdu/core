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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/medicalForm_manage.php'>".__($guid, 'Manage Medical Forms')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Medical Form').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if person medical specified
    $gibbonPersonMedicalID = $_GET['gibbonPersonMedicalID'];
    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
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
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/medicalForm_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/medicalForm_manage_editProcess.php?gibbonPersonMedicalID='.$gibbonPersonMedicalID."&search=$search");

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->setClass('smallIntBorder fullWidth');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $form->addRow()->addHeading(__('General Information'));

            $row = $form->addRow();
                $row->addLabel('name', __('Student'));
                $row->addTextField('name')->setValue(formatName('', htmlPrep($values['preferredName']), htmlPrep($values['surname']), 'Student'))->isRequired()->readonly();

            $row = $form->addRow();
                $row->addLabel('bloodType', __('Blood Type'));
                $row->addSelectBloodType('bloodType')->placeholder();

            $row = $form->addRow();
                $row->addLabel('longTermMedication', __('Long-Term Medication?'));
                $row->addYesNo('longTermMedication')->placeholder();

            $form->toggleVisibilityByClass('longTermMedicationDetails')->onSelect('longTermMedication')->when('Y');

            $row = $form->addRow()->addClass('longTermMedicationDetails');
                $row->addLabel('longTermMedicationDetails', __('Medication Details'));
                $row->addTextArea('longTermMedicationDetails')->setRows(5);

            $row = $form->addRow();
                $row->addLabel('tetanusWithin10Years', __('Tetanus Within Last 10 Years?'));
                $row->addYesNo('tetanusWithin10Years')->placeholder();

            $row = $form->addRow();
                $row->addLabel('comment', __('Comment'));
                $row->addTextArea('comment')->setRows(6);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();

            echo '<h2>';
            echo __($guid, 'Medical Conditions');
            echo '</h2>';

            try {
                $data = array('gibbonPersonMedicalID' => $gibbonPersonMedicalID);
                $sql = 'SELECT gibbonPersonMedicalCondition.*, gibbonAlertLevel.name AS risk FROM gibbonPersonMedicalCondition JOIN gibbonAlertLevel ON (gibbonPersonMedicalCondition.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID ORDER BY gibbonPersonMedicalCondition.name';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/medicalForm_manage_condition_add.php&gibbonPersonMedicalID='.$values['gibbonPersonMedicalID']."&search=$search'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
            echo '</div>';

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Risk');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Details');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Medication');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Comment');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                while ($row = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo __($guid, $row['name']);
                    echo '</td>';
                    echo '<td>';
                    echo __($guid, $row['risk']);
                    echo '</td>';
                    echo '<td>';
                    if ($row['triggers'] != '') {
                        echo '<b>'.__($guid, 'Triggers').':</b> '.$row['triggers'].'<br/>';
                    }
                    if ($row['reaction'] != '') {
                        echo '<b>'.__($guid, 'Reaction').':</b> '.$row['reaction'].'<br/>';
                    }
                    if ($row['response'] != '') {
                        echo '<b>'.__($guid, 'Response').':</b> '.$row['response'].'<br/>';
                    }
                    if ($row['lastEpisode'] != '') {
                        echo '<b>'.__($guid, 'Last Episode').':</b> '.dateConvertBack($guid, $row['lastEpisode']).'<br/>';
                    }
                    if ($row['lastEpisodeTreatment'] != '') {
                        echo '<b>'.__($guid, 'Last Episode Treatment').':</b> '.$row['lastEpisodeTreatment'].'<br/>';
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $row['medication'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['comment'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/medicalForm_manage_condition_edit.php&gibbonPersonMedicalID='.$row['gibbonPersonMedicalID'].'&gibbonPersonMedicalConditionID='.$row['gibbonPersonMedicalConditionID']."&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/medicalForm_manage_condition_delete.php&gibbonPersonMedicalID='.$row['gibbonPersonMedicalID'].'&gibbonPersonMedicalConditionID='.$row['gibbonPersonMedicalConditionID']."&search=$search&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }
    }
}
?>
