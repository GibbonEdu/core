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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Staff\StaffDutyGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_duty_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonDaysOfWeekID = $_GET['gibbonDaysOfWeekID'] ?? '';

    $form = Form::create('action', $session->get('absoluteURL').'/modules/Staff/staff_duty_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonDaysOfWeekID', $gibbonDaysOfWeekID);

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDList', __('Person'))->description(__('Must be unique.'));
        $row->addSelectStaff('gibbonPersonIDList')->placeholder()->required()->selectMultiple();

    $duty = $container->get(StaffDutyGateway::class)->selectDutyTimeSlotsByWeekday($gibbonDaysOfWeekID)->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonStaffDutyID', __('Duty'));
        $row->addSelect('gibbonStaffDutyID')
            ->fromArray($duty)
            ->required()
            ->placeholder();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
