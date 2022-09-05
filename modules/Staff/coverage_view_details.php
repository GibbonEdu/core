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

use Gibbon\Services\Format;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Module\Staff\View\StaffCard;
use Gibbon\Module\Staff\Tables\CoverageDates;
use Gibbon\Module\Staff\View\CoverageView;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_view_details.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs
        ->add(__('My Coverage'), 'coverage_my.php')
        ->add(__('View Details'));

    $gibbonStaffCoverageID = $_GET['gibbonStaffCoverageID'] ?? '';

    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);
    $coverage = $container->get(StaffCoverageGateway::class)->getByID($gibbonStaffCoverageID);
    
    // Staff Card
    $staffCard = $container->get(StaffCard::class);
    $staffCard->setPerson($coverage['gibbonPersonID'])->compose($page);

    // Coverage Dates
    $table = $container->get(CoverageDates::class)->create($gibbonStaffCoverageID);
    $page->write($table->getOutput());

    // Coverage View Composer
    $coverageView = $container->get(CoverageView::class);
    $coverageView->setCoverage($gibbonStaffCoverageID)->compose($page);

    // Attachment
    if (!empty($coverage['attachmentType'])) {
        $page->writeFromTemplate('statusComment.twig.html', [
            'name'       => __('Attachment'),
            'icon'       => 'internalAssessment',
            'tag'        => 'dull',
            'status'     => __($coverage['attachmentType']),
            'attachment' => $coverage['attachmentType'] != 'Text' ? Format::link($coverage['attachmentContent']) : '',
            'html'       => $coverage['attachmentType'] == 'Text' ? $coverage['attachmentContent'] : '',
        ]);
    }
}
