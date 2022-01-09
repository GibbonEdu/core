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
use Gibbon\Domain\School\SchoolYearGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/formGroup_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Form Groups'), 'formGroup_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Edit Form Group'));

    //Check if gibbonFormGroupID and gibbonSchoolYearID specified
    if ($gibbonFormGroupID == '' or $gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFormGroupID' => $gibbonFormGroupID);
            $sql = 'SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonFormGroupID, gibbonSchoolYear.name as schoolYearName, gibbonFormGroup.name, gibbonFormGroup.nameShort, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, gibbonPersonIDEA, gibbonPersonIDEA2, gibbonPersonIDEA3, gibbonSpaceID, gibbonFormGroupIDNext, attendance, website FROM gibbonFormGroup JOIN gibbonSchoolYear ON gibbonFormGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID WHERE gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFormGroupID=:gibbonFormGroupID ORDER BY sequenceNumber, gibbonFormGroup.name';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('formGroupEdit', $session->get('absoluteURL').'/modules/'.$session->get('module').'/formGroup_manage_editProcess.php?gibbonFormGroupID='.$gibbonFormGroupID);
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));
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
                $row->addLabel('tutors', __('Tutors'))->description(__('Up to 3 per form group. The first-listed will be marked as "Main Tutor".'));
                $column = $row->addColumn()->addClass('stacked');
                $column->addSelectStaff('gibbonPersonIDTutor')->placeholder()->photo(false);
                $column->addSelectStaff('gibbonPersonIDTutor2')->placeholder()->photo(false);
                $column->addSelectStaff('gibbonPersonIDTutor3')->placeholder()->photo(false);

            $row = $form->addRow();
                $row->addLabel('EAs', __('Educational Assistant'))->description(__('Up to 3 per form group.'));
                $column = $row->addColumn()->addClass('stacked');
                $column->addSelectStaff('gibbonPersonIDEA')->placeholder()->photo(false);
                $column->addSelectStaff('gibbonPersonIDEA2')->placeholder()->photo(false);
                $column->addSelectStaff('gibbonPersonIDEA3')->placeholder()->photo(false);

            $row = $form->addRow();
                $row->addLabel('gibbonSpaceID', __('Location'));
                $row->addSelectSpace('gibbonSpaceID');

            $nextYear = $container->get(SchoolYearGateway::class)->getNextSchoolYearByID($gibbonSchoolYearID);
            $row = $form->addRow();
                $row->addLabel('gibbonFormGroupIDNext', __('Next Form Group'))->description(__('Sets student progression on rollover.'));
                if (empty($nextYear)) {
                    $row->addAlert(__('The next school year cannot be determined, so this value cannot be set.'));
                } else {
                    $row->addSelectFormGroup('gibbonFormGroupIDNext', $nextYear['gibbonSchoolYearID']);
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
