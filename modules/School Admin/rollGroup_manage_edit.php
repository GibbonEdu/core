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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/rollGroup_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonRollGroupID = $_GET['gibbonRollGroupID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Roll Groups'), 'rollGroup_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Edit Roll Group'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    if ($gibbonRollGroupID == '' or $gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonRollGroupID' => $gibbonRollGroupID);
            $sql = 'SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonRollGroupID, gibbonSchoolYear.name as schoolYearName, gibbonRollGroup.name, gibbonRollGroup.nameShort, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, gibbonPersonIDEA, gibbonPersonIDEA2, gibbonPersonIDEA3, gibbonSpaceID, gibbonRollGroupIDNext, attendance, website FROM gibbonRollGroup JOIN gibbonSchoolYear ON gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID WHERE gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroupID=:gibbonRollGroupID ORDER BY sequenceNumber, gibbonRollGroup.name';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('rollGroupEdit', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/rollGroup_manage_editProcess.php?gibbonRollGroupID='.$gibbonRollGroupID);
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

            $row = $form->addRow();
                $row->addLabel('schoolYearName', __('School Year'));
                $row->addTextField('schoolYearName')->readonly()->setValue($values['schoolYearName']);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Needs to be unique in school year.'));
                $row->addTextField('name')->required()->maxLength(20);

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'))->description(__('Needs to be unique in school year.'));
                $row->addTextField('nameShort')->required()->maxLength(8);

            $row = $form->addRow();
                $row->addLabel('tutors', __('Tutors'))->description(__('Up to 3 per roll group. The first-listed will be marked as "Main Tutor".'));
                $column = $row->addColumn()->addClass('stacked');
                $column->addSelectStaff('gibbonPersonIDTutor')->placeholder()->photo(false);
                $column->addSelectStaff('gibbonPersonIDTutor2')->placeholder()->photo(false);
                $column->addSelectStaff('gibbonPersonIDTutor3')->placeholder()->photo(false);

            $row = $form->addRow();
                $row->addLabel('EAs', __('Educational Assistant'))->description(__('Up to 3 per roll group.'));
                $column = $row->addColumn()->addClass('stacked');
                $column->addSelectStaff('gibbonPersonIDEA')->placeholder()->photo(false);
                $column->addSelectStaff('gibbonPersonIDEA2')->placeholder()->photo(false);
                $column->addSelectStaff('gibbonPersonIDEA3')->placeholder()->photo(false);

            $row = $form->addRow();
                $row->addLabel('gibbonSpaceID', __('Location'));
                $row->addSelectSpace('gibbonSpaceID');

            $nextYear = getNextSchoolYearID($gibbonSchoolYearID, $connection2);
            $row = $form->addRow();
                $row->addLabel('gibbonRollGroupIDNext', __('Next Roll Group'))->description(__('Sets student progression on rollover.'));
                if (empty($nextYear)) {
                    $row->addAlert(__('The next school year cannot be determined, so this value cannot be set.'));
                } else {
                    $row->addSelectRollGroup('gibbonRollGroupIDNext', $nextYear);
                }

            $row = $form->addRow();
                $row->addLabel('attendance', __('Track Attendance?'))->description(__('Should this class allow attendance to be taken?'));
                $row->addYesNo('attendance');

            $row = $form->addRow();
                $row->addLabel('website', __('Website'))->description(__('Include http://'));
                $row->addURL('website')->maxLength(255);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
