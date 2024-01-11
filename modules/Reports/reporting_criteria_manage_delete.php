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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Module\Reports\Domain\ReportingCriteriaGateway;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_criteria_manage_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonReportingCriteriaID = $_GET['gibbonReportingCriteriaID'] ?? '';
    $gibbonReportingScopeID = $_GET['gibbonReportingScopeID'] ?? '';
    $gibbonReportingCycleID = $_GET['gibbonReportingScopeID'] ?? '';

    if (empty($gibbonReportingCriteriaID) || empty($gibbonReportingScopeID) || empty($gibbonReportingCycleID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $reportingCriteriaGateway = $container->get(ReportingCriteriaGateway::class);
    $values = $reportingCriteriaGateway->getByID($gibbonReportingCriteriaID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if (!empty($values['groupID'])) {
        $groupCount = $reportingCriteriaGateway->selectBy(['gibbonReportingCycleID' => $values['gibbonReportingCycleID'], 'groupID' => $values['groupID']])->rowCount();
        echo Format::alert(__('This is a grouped record created using Add Multiple.').' '.__('Deleting this record will delete all {count} records in the same group. Check the detach option to remove this record from the group and not delete other records.', ['count' => '<b>'.$groupCount.'</b>']), 'error');
    }

    $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/Reports/reporting_criteria_manage_deleteProcess.php?gibbonReportingCriteriaID='.$gibbonReportingCriteriaID, false, false);

    if (!empty($values['groupID'])) {
        $row = $form->addRow();
            $row->addLabel('detach', __('Detach?'))->description(__('Removes this record from a grouped set.'));
            $row->addCheckbox('detach')->setValue('Y');
    }

    $form->addRow()->addConfirmSubmit();

    echo $form->getOutput();
}
