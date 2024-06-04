<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_archive.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Archive Records'));

    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonFormGroup.nameShort as formGroup
            FROM gibbonPerson
            JOIN gibbonIN ON (gibbonIN.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
            WHERE status='Full' 
            AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
            ORDER BY surname, preferredName";
    $result = $pdo->executeQuery($data, $sql);

    $students = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();
    $students = array_map(function($item) {
        return Format::name('', $item['preferredName'], $item['surname'], 'Student', true).' ('.$item['formGroup'].')';
    }, $students);

    if (empty($students)) {
        echo $page->getBlankSlate();
        return;
    }

    $form = Form::create('courseEdit', $session->get('absoluteURL').'/modules/'.$session->get('module').'/in_archiveProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('deleteCurrentPlans', __('Delete Current Plans?'))->description(__('Deletes Individual Education Plan fields only, not Individual Needs Status fields.'));
        $row->addYesNo('deleteCurrentPlans')->required()->selected('N');

    $row = $form->addRow();
        $row->addLabel('title', __('Archive Title'));
        $row->addTextField('title')->required()->maxLength(50);

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Students'));
        $row->addCheckbox('gibbonPersonID')->fromArray($students)->addCheckAllNone();

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();
}
