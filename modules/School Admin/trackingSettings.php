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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/trackingSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Tracking Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('trackingSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/trackingSettingsProcess.php');

    $form->removeClass('standardForm');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    // Get the yearGroups in a $key => $value array
    $sql = "SELECT gibbonYearGroupID as `value`, name FROM gibbonYearGroup ORDER BY sequenceNumber";
    $result = $pdo->executeQuery(array(), $sql);
    $yearGroups = $result->fetchAll(\PDO::FETCH_KEY_PAIR);

    if (empty($yearGroups)) {
        $form->addRow()->addAlert(__('There are no records to display.'), 'error');
    } else {
        // EXTERNAL ASSESSMENT DATA POINTS
        $row = $form->addRow();
            $row->addHeading(__('Data Points').' - '.__('External Assessment'))
                ->append(__('Use the options below to select the external assessments that you wish to include in your Data Points export.'))
                ->append(' ')
                ->append(__('If duplicates of any assessment exist, only the most recent entry will be shown.'));

        // Get the existing External Assesment IDs and categories
        $sql = "SELECT DISTINCT gibbonExternalAssessment.gibbonExternalAssessmentID, gibbonExternalAssessment.nameShort, gibbonExternalAssessmentField.category FROM gibbonExternalAssessment JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE active='Y' ORDER BY nameShort, category";
        $result = $pdo->executeQuery(array(), $sql);

        if ($result->rowCount() < 1) {
            $form->addRow()->addAlert(__('There are no records to display.'), 'error');
        } else {
            // Get the external data points from Settings, if any exist
            $externalAssessmentDataPoints = unserialize(getSettingByScope($connection2, 'Tracking', 'externalAssessmentDataPoints'));
            $externalAssessmentDataPoints = is_array($externalAssessmentDataPoints) ? $externalAssessmentDataPoints : array() ;

            // Create a lookup table for data points as gibbonExternalAssessmentID-category pair
            $externalDP = array();
            foreach ($externalAssessmentDataPoints as $dp) {
                $key = $dp['gibbonExternalAssessmentID'].'-'.$dp['category'];
                $externalDP[$key] = (isset($dp['gibbonYearGroupIDList']))? $dp['gibbonYearGroupIDList'] : '';
            }

            $count = 0;
            while ($assessment = $result->fetch()) {
                $name = 'externalDP['.$count.'][gibbonYearGroupIDList][]';
                $categoryLabel = substr($assessment['category'], (strpos($assessment['category'], '_') + 1));
                $key = $assessment['gibbonExternalAssessmentID'].'-'.$assessment['category'];

                $checked = array();
                if (isset($externalDP[$key])) {
                    // Explode the saved CSV data into an array
                    $checked = explode(',', $externalDP[$key]) ;
                }

                // Add the checkbox group for this gibbonExternalAssessmentID-category pair
                $row = $form->addRow();
                    $row->addLabel($name, __($assessment['nameShort']).' - '.__($categoryLabel));
                    $row->addCheckbox($name)->fromArray($yearGroups)->checked($checked);

                $form->addHiddenValue('externalDP['.$count.'][gibbonExternalAssessmentID]', $assessment['gibbonExternalAssessmentID']);
                $form->addHiddenValue('externalDP['.$count.'][category]', $assessment['category']);

                $count++;
            }
        }

        // INTERNAL ASSESSMENT DATA POINTS
        $row = $form->addRow();
            $row->addHeading(__('Data Points').' - '.__('Internal Assessment'))
                ->append(__('Use the options below to select the internal assessments that you wish to include in your Data Points export.'))
                ->append(' ')
                ->append(__('If duplicates of any assessment exist, only the most recent entry will be shown.'));

        $internalAssessmentTypes = explode(',', getSettingByScope($connection2, 'Formal Assessment', 'internalAssessmentTypes'));

        if (empty($internalAssessmentTypes)) {
            $form->addRow()->addAlert(__('There are no records to display.'), 'error');
        } else {
            // Get the internal data points from Settings, if any exist
            $internalAssessmentDataPoints = unserialize(getSettingByScope($connection2, 'Tracking', 'internalAssessmentDataPoints'));
            $internalAssessmentDataPoints = is_array($internalAssessmentDataPoints) ? $internalAssessmentDataPoints : array() ;

            // Create a lookup table for data points (CSV index order can change)
            $internalDP = array();
            foreach ($internalAssessmentDataPoints as $dp) {
                $internalDP[$dp['type']] = (isset($dp['gibbonYearGroupIDList']))? $dp['gibbonYearGroupIDList'] : '';
            }

            $count = 0;
            foreach ($internalAssessmentTypes as $internalAssessmentType) {
                $name = 'internalDP['.$count.'][gibbonYearGroupIDList][]';
                $checked = array();
                if (isset($internalDP[$internalAssessmentType])) {
                    // Explode the saved CSV data into an array
                    $checked = explode(',', $internalDP[$internalAssessmentType]);
                }

                // Add the checkbox group for this type
                $row = $form->addRow();
                    $row->addLabel($name, __($internalAssessmentType));
                    $row->addCheckbox($name)->fromArray($yearGroups)->checked($checked);

                $form->addHiddenValue('internalDP['.$count.'][type]', $internalAssessmentType);

                $count++;
            }
        }
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
