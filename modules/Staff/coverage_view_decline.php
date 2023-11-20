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
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Module\Staff\View\StaffCard;
use Gibbon\Module\Staff\View\CoverageView;
use Gibbon\Module\Staff\Tables\CoverageDates;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_view_decline.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('My Coverage'), 'coverage_my.php')
        ->add(__('Decline Coverage Request'));

    
    $gibbonStaffCoverageID = $_GET['gibbonStaffCoverageID'] ?? '';

    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);

    if (empty($gibbonStaffCoverageID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $coverage = $staffCoverageGateway->getCoverageDetailsByID($gibbonStaffCoverageID);

    if (empty($coverage) || $coverage['status'] != 'Requested') {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('staffCoverage', $session->get('absoluteURL').'/modules/Staff/coverage_view_declineProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffCoverageID', $gibbonStaffCoverageID);

    $form->addRow()->addHeading('Decline Coverage Request', __('Decline Coverage Request'));
    
    // Staff Card
    $staffCard = $container->get(StaffCard::class);
    $staffCard->setPerson($coverage['gibbonPersonID'])->compose($page);

    // Coverage Dates
    $table = $container->get(CoverageDates::class)->create($gibbonStaffCoverageID);
    $page->write($table->getOutput());
    
    // Coverage View Composer
    $coverageView = $container->get(CoverageView::class);
    $coverageView->setCoverage($gibbonStaffCoverageID)->compose($page);

    $row = $form->addRow();
        $row->addLabel('markAsUnavailable', __('Not Available'))->description(__('Checking this will mark you as unavailable for any further requests on these dates.'));
        $row->addCheckbox('markAsUnavailable')->checked(true);

    $row = $form->addRow();
        $row->addLabel('notesCoverage', __('Reply'));
        $row->addTextArea('notesCoverage')->setRows(3);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    

    echo $form->getOutput();
}
