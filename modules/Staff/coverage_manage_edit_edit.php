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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonStaffCoverageID = $_GET['gibbonStaffCoverageID'] ?? '';
    $gibbonStaffCoverageDateID = $_GET['gibbonStaffCoverageDateID'] ?? '';

    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Staff Coverage'), 'coverage_manage.php')
        ->add(__('Edit Coverage'), 'coverage_manage_edit.php', ['gibbonStaffCoverageID' => $gibbonStaffCoverageID])
        ->add(__('Edit'));

    if (empty($gibbonStaffCoverageID) || empty($gibbonStaffCoverageDateID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);
    $values = $staffCoverageDateGateway->getByID($gibbonStaffCoverageDateID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('staffCoverageEdit', $session->get('absoluteURL').'/modules/Staff/coverage_manage_edit_editProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffCoverageID', $gibbonStaffCoverageID);
    $form->addHiddenValue('gibbonStaffCoverageDateID', $gibbonStaffCoverageDateID);
   
    $row = $form->addRow();
        $row->addLabel('dateLabel', __('Date'));
        $row->addTextField('dateLabel')->readonly()->setValue(Format::date($values['date']));

    if (empty($values['gibbonTTDayRowClassID'])) {
        $row = $form->addRow();
        $row->addLabel('allDay', __('When'));
        $row->addCheckbox('allDay')
            ->description(__('All Day'))
            ->inline()
            ->setClass()
            ->setValue('Y')
            ->checked($values['allDay'])
            ->wrap('<div class="standardWidth floatRight">', '</div>');
    } else {
        $form->addHiddenValue('allDay', 'N');
    }

    $form->toggleVisibilityByClass('timeOptions')->onCheckbox('allDay')->whenNot('Y');

    $row = $form->addRow()->addClass('timeOptions');
        $row->addLabel('time', __('Time'));
        $col = $row->addColumn('time');
        $col->addTime('timeStart')
            ->setClass('w-full mr-1')
            ->isRequired();
        $col->addTime('timeEnd')
            ->setClass('w-full')
            ->chainedTo('timeStart', false)
            ->isRequired();

    $row = $form->addRow();
        $row->addLabel('value', __('Value'));
        $row->addNumber('value')->decimalPlaces(2)->maxLength(4)->minimum(0)->maximum(1);

    $row = $form->addRow();
        $row->addLabel('reason', __('Notes'));
        $row->addTextField('reason')->maxLength(255);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
