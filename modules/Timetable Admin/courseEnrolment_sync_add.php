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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? '';

    $page->breadcrumbs
        ->add(__('Sync Course Enrolment'), 'courseEnrolment_sync.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Map Classes'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if (empty($gibbonSchoolYearID)) {
        echo '<div class="error">';
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
        return;
    }

    $form = Form::create('courseEnrolmentSyncAdd', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_sync_edit.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'))->description(__('Determines the available courses and classes to map.'));
        $row->addSelectYearGroup('gibbonYearGroupID')->required();

    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel('courseClassMapping', __('Compare to Pattern'))->description(sprintf(__('Classes will be matched to Roll Groups that fit the specified pattern. Choose from %1$s. Must contain %2$s'), '[courseShortName] [yearGroupShortName] [rollGroupShortName]', '[classShortName]'));

        $row->addTextField('pattern')
            ->required()
            ->setValue('[yearGroupShortName].[classShortName]')
            ->addValidation('Validate.Format', 'pattern: /(\[classShortName\])/');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
