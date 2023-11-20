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
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Module\Staff\View\StaffCard;
use Gibbon\Module\Staff\View\CoverageView;
use Gibbon\Module\Staff\Tables\CoverageDates;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_view_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('My Coverage'), 'coverage_my.php')
        ->add(__('Edit Coverage'));

    $page->return->addReturns([
            'error3' => __('Failed to write file to disk.'),
        ]);

    $gibbonStaffCoverageID = $_GET['gibbonStaffCoverageID'] ?? '';
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);

    if (empty($gibbonStaffCoverageID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $coverage = $staffCoverageGateway->getByID($gibbonStaffCoverageID);

    if (empty($coverage)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if ($coverage['gibbonPersonID'] != $session->get('gibbonPersonID') && $coverage['gibbonPersonIDStatus'] != $session->get('gibbonPersonID')) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Staff Card
    $staffCard = $container->get(StaffCard::class);
    $staffCard->setPerson($coverage['gibbonPersonID'])->compose($page);

    // Coverage Dates
    $table = $container->get(CoverageDates::class)->create($gibbonStaffCoverageID);
    $page->write($table->getOutput());

    // Coverage View Composer
    $coverageView = $container->get(CoverageView::class);
    $coverageView->setCoverage($gibbonStaffCoverageID)->compose($page);
    
    // FORM
    $form = Form::create('staffCoverageFile', $session->get('absoluteURL').'/modules/Staff/coverage_view_editProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffCoverageID', $gibbonStaffCoverageID);

    $form->addRow()->addHeading('Attachment', __('Attachment'));
    
    $types = array('File' => __('File'),  'Link' => __('Link'), 'Text' => __('Text'));
    $row = $form->addRow();
        $row->addLabel('attachmentType', __('Type'));
        $row->addSelect('attachmentType')->fromArray($types)->placeholder()->selected($coverage['attachmentType'] ?? '');

    // File
    $form->toggleVisibilityByClass('attachmentFile')->onSelect('attachmentType')->when('File');
    $row = $form->addRow()->addClass('attachmentFile');
        $row->addLabel('file', __('File'));
        $row->addFileUpload('file')
            ->required()
            ->setAttachment('attachment', $session->get('absoluteURL'), $coverage['attachmentContent'] ?? '');

    // Text
    $form->toggleVisibilityByClass('attachmentText')->onSelect('attachmentType')->when('Text');
    $row = $form->addRow()->addClass('attachmentText');
        $column = $row->addColumn()->setClass('');
        $column->addLabel('text', __('Text'));
        $column->addEditor('text', $guid)
            ->required()
            ->setValue($coverage['attachmentContent'] ?? '');

    // Link
    $form->toggleVisibilityByClass('attachmentLink')->onSelect('attachmentType')->when('Link');
    $row = $form->addRow()->addClass('attachmentLink');
        $row->addLabel('link', __('Link'));
        $row->addURL('link')
            ->maxLength(255)
            ->required()
            ->setValue($coverage['attachmentContent'] ?? '');

    $form->addRow()->addHeading('Details', __('Details'));

    $row = $form->addRow();
        $row->addLabel('notesStatus', __('Comment'))->description(__('This message is shared with substitutes, and is also visible to users who manage staff coverage.'));
        $row->addTextArea('notesStatus')->setRows(3)->setValue($coverage['notesStatus']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    
    echo $form->getOutput();
}
