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
use Gibbon\Domain\Finance\PettyCashGateway;

if (isActionAccessible($guid, $connection2, '/modules/Finance/pettyCash_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonFinancePettyCashID = $_GET['gibbonFinancePettyCashID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $action = $_GET['action'] ?? '';

    if (empty($gibbonFinancePettyCashID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(PettyCashGateway::class)->getByID($gibbonFinancePettyCashID);
    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('pettyCashAction', $session->get('absoluteURL').'/modules/Finance/pettyCash_actionProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonFinancePettyCashID', $gibbonFinancePettyCashID);
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('action', $action);

    $row = $form->addRow();
        $row->addLabel('statusDate', __('When'));
        $col = $row->addColumn('statusDate')->setClass('inline gap-2');
        $col->addDate('statusDate')->setValue(date('Y-m-d'))->required()->addClass('flex-1');
        $col->addTime('statusTime')->setValue(date('H:i'))->required()->addClass('flex-1');

    $row = $form->addRow();
        $row->addLabel('notes', __('Notes'));
        $row->addTextArea('notes')->setRows(2)->setValue($values['notes']);

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();
}
