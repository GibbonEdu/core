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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_medical_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Data Updater/data_medical_manage.php'>".__($guid, 'Medical Data Updates')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Request').'</div>';
    echo '</div>';

    //Check if school year specified
    $gibbonPersonMedicalUpdateID = $_GET['gibbonPersonMedicalUpdateID'];
    if ($gibbonPersonMedicalUpdateID == 'Y') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID);
            $sql = "SELECT gibbonPersonMedical.* FROM gibbonPersonMedicalUpdate 
                    LEFT JOIN gibbonPersonMedical ON (gibbonPersonMedical.gibbonPersonID=gibbonPersonMedicalUpdate.gibbonPersonID) 
                    WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $data = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID);
            $sql = "SELECT gibbonPersonMedicalUpdate.* FROM gibbonPersonMedicalUpdate 
                    LEFT JOIN gibbonPersonMedical ON (gibbonPersonMedical.gibbonPersonID=gibbonPersonMedicalUpdate.gibbonPersonID) 
                    WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID";
            $newResult = $pdo->executeQuery($data, $sql);

            //Let's go!
            $oldValues = $result->fetch();
            $newValues = $newResult->fetch();

            $compare = array(
                'bloodType'                 => __('Blood Type'),
                'longTermMedication'        => __('Long-Term Medication?'),
                'longTermMedicationDetails' => __('Medication Details'),
                'tetanusWithin10Years'      => __('Tetanus Within Last 10 Years?'),
            );

            $compareCondition = array(
                'name'                 => __('Condition Name'),
                'gibbonAlertLevelID'   => __('Risk'),
                'triggers'             => __('Triggers'),
                'reaction'             => __('Reaction'),
                'response'             => __('Response'),
                'medication'           => __('Medication'),
                'lastEpisode'          => __('Last Episode Date'),
                'lastEpisodeTreatment' => __('Last Episode Treatment'),
                'comment'              => __('Comment'),
            );

            $sql = "SELECT gibbonMedicalConditionID AS value, name FROM gibbonMedicalCondition ORDER BY name";
            $result = $pdo->executeQuery(array(), $sql);
            $conditions = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_KEY_PAIR) : array();

            $sql = "SELECT gibbonAlertLevelID AS value, name FROM gibbonAlertLevel ORDER BY sequenceNumber";
            $result = $pdo->executeQuery(array(), $sql);
            $alerts = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_KEY_PAIR) : array();

            $form = Form::create('updateMedical', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/data_medical_manage_editProcess.php?gibbonPersonMedicalUpdateID='.$gibbonPersonMedicalUpdateID);
            
            $form->setClass('fullWidth colorOddEven');
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonPersonID', $newValues['gibbonPersonID']);
            $form->addHiddenValue('formExists', !empty($oldValues['gibbonPersonMedicalID']));

            $row = $form->addRow()->setClass('head heading');
                $row->addContent(__('Field'));
                $row->addContent(__('Current Value'));
                $row->addContent(__('New Value'));
                $row->addContent(__('Accept'));

            // Create a reusable function for adding comparisons to the form
            $comparisonFields = function ($form, $oldValues, $newValues, $fieldName, $label, $count = '') use ($guid, $conditions, $alerts) {
                $oldValue = isset($oldValues[$fieldName])? $oldValues[$fieldName] : '';
                $newValue = isset($newValues[$fieldName])? $newValues[$fieldName] : '';
                $isMatching = ($oldValue != $newValue);

                if ($fieldName == 'name') {
                    $oldValue = isset($conditions[$oldValue])? $conditions[$oldValue] : $oldValue;
                    $newValue = isset($conditions[$newValue])? $conditions[$newValue] : $newValue;
                }

                if ($fieldName == 'gibbonAlertLevelID') {
                    $oldValue = isset($alerts[$oldValue])? $alerts[$oldValue] : $oldValue;
                    $newValue = isset($alerts[$newValue])? $alerts[$newValue] : $newValue;
                }

                if ($fieldName == 'lastEpisode') {
                    $oldValue = dateConvertBack($guid, $oldValue);
                    $newValue = dateConvertBack($guid, $newValue);
                }

                $row = $form->addRow();
                $row->addLabel($fieldName.'On'.$count, $label);
                $row->addContent($oldValue);
                $row->addContent($newValue)->addClass($isMatching ? 'matchHighlightText' : '');
                
                if ($isMatching) {
                    $row->addCheckbox($fieldName.'On'.$count)->checked(true)->setClass('textCenter');
                    $form->addHiddenValue($fieldName.$count, $newValues[$fieldName]);
                } else {
                    $row->addContent();
                }
            };

            // Basic Medical Form
            $form->addRow()->addHeading(__('Basic Information'));

            foreach ($compare as $fieldName => $label) {
                $comparisonFields($form, $oldValues, $newValues, $fieldName, $label);
            }

            // Existing Conditions
            $data = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID);
            $sql = "SELECT * FROM gibbonPersonMedicalConditionUpdate 
                    WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID 
                    AND NOT gibbonPersonMedicalConditionID IS NULL 
                    ORDER BY gibbonPersonMedicalConditionUpdateID";
            $result = $pdo->executeQuery($data, $sql);

            $count = 0;
            if ($result->rowCount() > 0) {
                while ($newValues = $result->fetch()) {
                    $data = array('gibbonPersonMedicalConditionID' => $newValues['gibbonPersonMedicalConditionID']);
                    $sql = "SELECT * FROM gibbonPersonMedicalCondition 
                            WHERE gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID";
                    $oldResult = $pdo->executeQuery($data, $sql);
                    $oldValues = $oldResult->fetch();

                    $form->addRow()->addHeading(__('Existing Condition').' '.($count+1));
                    $form->addHiddenValue('gibbonPersonMedicalConditionID'.$count, $newValues['gibbonPersonMedicalConditionID']);

                    foreach ($compareCondition as $fieldName => $label) {
                        $comparisonFields($form, $oldValues, $newValues, $fieldName, $label, $count);
                    }

                    $count++;
                }
            }

            // New Conditions
            $data = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID);
            $sql = "SELECT * FROM gibbonPersonMedicalConditionUpdate 
                    WHERE gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID 
                    AND gibbonPersonMedicalConditionID IS NULL ORDER BY name";
            $result = $pdo->executeQuery($data, $sql);

            $count2 = 0;
            if ($result->rowCount() > 0) {
                while ($newValues = $result->fetch()) {
                    $count2++;
                    
                    $form->addRow()->addHeading(__('New Condition').' '.$count2);
                    $form->addHiddenValue('gibbonPersonMedicalConditionUpdateID'.($count+$count2), $newValues['gibbonPersonMedicalConditionUpdateID']);

                    foreach ($compareCondition as $fieldName => $label) {
                        $comparisonFields($form, array(), $newValues, $fieldName, $label, $count+$count2);
                    }
                }
            }

            $form->addHiddenValue('count', $count);
            $form->addHiddenValue('count2', $count2);

            $row = $form->addRow();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}