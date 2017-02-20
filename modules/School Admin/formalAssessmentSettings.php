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

@session_start();

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/formalAssessmentSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Formal Assessment Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('formalAssessmentSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/formalAssessmentSettingsProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $form->addRow()->addHeading('Internal Assessment Settings');

    $setting = getSettingByScope($connection2, 'Formal Assessment', 'internalAssessmentTypes', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], $setting['nameDisplay'])->description($setting['description']);
        $row->addTextArea($setting['name'])->setValue($setting['value'])->isRequired();

    $form->addRow()->addHeading('Primary External Assessement')->append('These settings allow a particular type of external assessment to be associated with each year group. The selected assessment will be used as the primary assessment to be used as a baseline for comparison (for example, within the Markbook). In addition, a particular field category can be chosen from which to draw data (if no category is chosen, the system will try to pick the best data automatically).');

    $row = $form->addRow()->setClass('break');
        $row->addContent(__('Year Group'));
        $row->addContent(__('External Assessment'));
        $row->addContent(__('Field Set'));

    // External Assessments, $key => $valye pairs
    $sql = "SELECT gibbonExternalAssessmentID as `value`, name FROM gibbonExternalAssessment WHERE active='Y' ORDER BY name";
    $results = $pdo->executeQuery(array(), $sql);
    $externalAssessments = $results->fetchAll(\PDO::FETCH_KEY_PAIR);

    // External Assessment Field Sets
    $sql = "SELECT gibbonExternalAssessmentField.gibbonExternalAssessmentID, category FROM gibbonExternalAssessment JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE active='Y' ORDER BY gibbonExternalAssessmentID, category";
    $results = $pdo->executeQuery(array(), $sql);

    $externalAssessmentsFieldSetNames = array();
    $externalAssessmentsFieldSetIDs = array();

    // Build two arrays, one of $key => $value for the dropdown, one of $key => $class for the chainedTo method
    if ($results && $results->rowCount() > 0) {
        while ($assessment = $results->fetch()) {
            $key = $assessment['gibbonExternalAssessmentID'].'-'.$assessment['category'];
            $externalAssessmentsFieldSetNames[$key] = substr($assessment['category'], strpos($assessment['category'], '_') + 1);
            $externalAssessmentsFieldSetIDs[$key] = $assessment['gibbonExternalAssessmentID'];
        }
    }

    // Get and unserialize the current settings value
    $primaryExternalAssessmentByYearGroup = unserialize(getSettingByScope($connection2, 'School Admin', 'primaryExternalAssessmentByYearGroup'));

    // Split the ID portion off of the ID-category pair, for the first dropdown
    $primaryExternalAssessmentIDsByYearGroup = array_map(function($v) { return (stripos($v, '-') !== false? substr($v, 0, strpos($v, '-')) : $v); }, $primaryExternalAssessmentByYearGroup);

    $sql = 'SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber';
    $result = $pdo->executeQuery(array(), $sql);

    // Add one row per year group
    while ($yearGroup = $result->fetch()) {
        $id = $yearGroup['gibbonYearGroupID'];

        $selectedID = (isset($primaryExternalAssessmentIDsByYearGroup[$id]))? $primaryExternalAssessmentIDsByYearGroup[$id] : '';
        $selectedField = (isset($primaryExternalAssessmentByYearGroup[$id]))? $primaryExternalAssessmentByYearGroup[$id] : '';

        $row = $form->addRow();
        $row->addContent($yearGroup['name']);

        $row->addSelect('gibbonExternalAssessmentID['.$id.']')
            ->setID('gibbonExternalAssessmentID'.$id)
            ->setClass('mediumWidth')
            ->placeholder()
            ->fromArray($externalAssessments)
            ->selected($selectedID);

        $row->addSelect('category['.$id.']')
            ->setID('category'.$id)
            ->setClass('mediumWidth')
            ->placeholder()
            ->fromArray($externalAssessmentsFieldSetNames)
            ->selected($selectedField)
            ->chainedTo('gibbonExternalAssessmentID'.$id, $externalAssessmentsFieldSetIDs);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}

