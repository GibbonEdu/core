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
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_archive.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Archive Records'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return']);
    }
    
    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort as rollGroup
            FROM gibbonPerson 
            JOIN gibbonIN ON (gibbonIN.gibbonPersonID=gibbonPerson.gibbonPersonID) 
            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID)
            WHERE status='Full' ORDER BY surname, preferredName";
    $result = $pdo->executeQuery($data, $sql);

    $students = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();
    $students = array_map(function($item) {
        return Format::name('', $item['preferredName'], $item['surname'], 'Student', true).' ('.$item['rollGroup'].')';
    }, $students);
    
    if (empty($students)) {
        $page->addError(__('There are no records to display.'));
        return;
    }
    
    $form = Form::create('courseEdit', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/in_archiveProcess.php');
                
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

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
