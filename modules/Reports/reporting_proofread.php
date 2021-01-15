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
use Gibbon\Module\Reports\Domain\ReportingProofGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Reports\TextDiff;
use Gibbon\Services\Format;
use Gibbon\Module\Reports\Domain\ReportingAccessGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_proofread.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Proof Read'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

    $mode = $_GET['mode'] ?? 'Person';
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? $gibbon->session->get('gibbonPersonID');
    $gibbonRollGroupID = $_GET['gibbonRollGroupID'] ?? '';
    $override = $_GET['override'] ?? 'N';

    $proofReview = $gibbonPersonID == $gibbon->session->get('gibbonPersonID') || ($override == 'Y' && $highestAction == 'Proof Read_all');
    if ($mode == 'Roll Group' && !empty($gibbonRollGroupID)) $proofReview = false;

    $reportingProofGateway = $container->get(ReportingProofGateway::class);
    $reportingAccessGateway = $container->get(ReportingAccessGateway::class);

    // FORM
    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setTitle(__('View'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('q', '/modules/Reports/reporting_proofread.php');

    $criteria = $reportingAccessGateway->newQueryCriteria();
    $reportingCycles = $reportingAccessGateway->queryActiveReportingCyclesByPerson($criteria, $gibbonSchoolYearID, $gibbon->session->get('gibbonPersonID'))->toArray();
    $reportingCycleIDs = array_column($reportingCycles, 'gibbonReportingCycleID');

    if (count($reportingCycles) == 0) {
        echo Format::alert(__('There are no active reporting cycles.'), 'message');
        return;
    }
    
    $modes = ['Person' => __('Person'), 'Roll Group' => __('Roll Group')];
    $row = $form->addRow();
        $row->addLabel('mode', __('Proof Read By'));
        $row->addSelect('mode')->fromArray($modes)->selected($mode);

    $form->toggleVisibilityByClass('personMode')->onSelect('mode')->when('Person');
    $form->toggleVisibilityByClass('rollGroupMode')->onSelect('mode')->when('Roll Group');

    

    if ($highestAction == 'Proof Read_all') {
        $row = $form->addRow()->addClass('rollGroupMode');
            $row->addLabel('gibbonRollGroupID', __('Roll Group'));
            $row->addSelectRollGroup('gibbonRollGroupID', $gibbonSchoolYearID)->required()->selected($gibbonRollGroupID);

        $row = $form->addRow()->addClass('personMode');
            $row->addLabel('gibbonPersonID', __('Person'));
            $row->addSelectStaff('gibbonPersonID')->required()->selected($gibbonPersonID);

        if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_write.php', 'Write Reports_editAll')) {
            $row = $form->addRow();
                $row->addLabel('override', __('Override'))->description(__("Allows you to override user access and accept edits for this user."));
                $row->addCheckbox('override')->setValue('Y')->checked($override);
        }
    } elseif ($highestAction == 'Proof Read_mine') {
        $criteria = $reportingAccessGateway->newQueryCriteria()->sortBy('gibbonReportingScope.sequenceNumber');
        $reportingScopes = $reportingAccessGateway->queryActiveReportingScopesByPerson($criteria, $reportingCycleIDs, $gibbon->session->get('gibbonPersonID'))->toArray();
        $reportingScopeIDs = array_column($reportingScopes, 'gibbonReportingScopeID');

        if (count($reportingScopes) == 0) {
            echo Format::alert(__('There are no active reporting cycles.'), 'message');
            return;
        }

        $staff = [];
        $rollGroups = [];

        foreach ($reportingScopes as $scope) {
            if ($scope['canProofRead'] != 'Y') continue;

            $criteria = $reportingAccessGateway->newQueryCriteria();
            $criteriaGroups = $reportingAccessGateway->queryActiveCriteriaGroupsByPerson($criteria, $scope['gibbonReportingScopeID'], $gibbon->session->get('gibbonPersonID'));

            if ($criteriaGroups->getResultCount() > 0) {
                $staffByScope = $reportingAccessGateway->selectAccessibleStaffByReportingScope($scope['gibbonReportingScopeID'])->fetchAll();
                $staff += array_reduce($staffByScope, function ($group, $item) {
                    $gibbonPersonIDStaff = str_pad($item['gibbonPersonID'], 10, '0', STR_PAD_LEFT);
                    $group[$gibbonPersonIDStaff] = Format::name('', $item['preferredName'], $item['surname'], 'Staff', true, true);
                    return $group;
                }, []);
            }

            $rollGroupsByScope = $reportingAccessGateway->selectAccessibleRollGroupsByReportingScope($scope['gibbonReportingScopeID'])->fetchKeyPair();
            $rollGroups = array_merge($rollGroups, $rollGroupsByScope);
        }

        // Prevent access if the staff list is empty
        if (empty($staff)) {
            $page->addMessage(__('There are no active reporting cycles.'));
            return;
        }

        // Ensure the current user is always in the list
        if (empty($staff[$gibbon->session->get('gibbonPersonID')])) {
            $staff[$gibbon->session->get('gibbonPersonID')] = Format::name('', $gibbon->session->get('preferredName'), $gibbon->session->get('surname'), 'Staff', true, true);
        }

        asort($rollGroups, SORT_NATURAL);
        
        $row = $form->addRow()->addClass('rollGroupMode');
            $row->addLabel('gibbonRollGroupID', __('Roll Group'));
            $row->addSelect('gibbonRollGroupID')->fromArray($rollGroups)->required()->placeholder()->selected($gibbonRollGroupID);

        $row = $form->addRow()->addClass('personMode');
            $row->addLabel('gibbonPersonID', __('Person'));
            $row->addSelectPerson('gibbonPersonID')->fromArray($staff)->selected($gibbonPersonID);
    }

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();


    if (empty($gibbonPersonID)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    // Get criteria that needs or has proof reading
    if ($mode == 'Roll Group' && !empty($gibbonRollGroupID)) {
        $proofReading = $reportingProofGateway->selectProofReadingByRollGroup($gibbonSchoolYearID, $gibbonRollGroupID)->fetchAll();
    } elseif ($mode == 'Person' && !empty($gibbonPersonID)) {
        $proofReading = $reportingProofGateway->selectProofReadingByPerson($gibbonSchoolYearID, $gibbonPersonID, $reportingScopeIDs ?? [])->fetchAll();
    }

    if (count($proofReading) == 0) {
        echo Format::alert(__('There are no records to display.'), 'error');
        return;
    }

    $ids = array_column($proofReading ?? [], 'gibbonReportingValueID');
    $proofs = $reportingProofGateway->selectProofsByValueID($ids)->fetchGroupedUnique();
    $proofsDone = array_reduce($proofs, function ($total, $item) {
        return $item['status'] == 'Done' || $item['status'] == 'Accepted' ? $total+1 : $total;
    }, 0);

    echo $page->fetchFromTemplate('ui/writingListHeader.twig.html', [ 
        'canWriteReport' => true,
        'reportingOpen' => true,
        'totalCount' => count($proofReading),
        'progressCount' => $proofsDone,
        'partialCount' => max(0, count($proofs) - $proofsDone),
        'progressColour' => 'green',
    ]);

    $form = Form::createTable('reportingProof', $gibbon->session->get('absoluteURL').'/modules/Reports/reporting_proofreadProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setTitle(__('Comments'));

    $form->addHiddenValue('address', $gibbon->session->get('address'));
    $form->addHiddenValue('gibbonRollGroupID', $gibbonRollGroupID);
    $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
    $form->addHiddenValue('override', $override);
    $form->addHiddenValue('mode', $mode);
    $form->addClass(' blank');

    $differ = new TextDiff();

    foreach ($proofReading as $criteria) {
        $gibbonReportingValueID = $criteria['gibbonReportingValueID'];
        $proof = $proofs[$gibbonReportingValueID] ?? ['status' => '', 'reason' => ''];

        $summaryText = Format::name('', $criteria['preferredName'], $criteria['surname'], 'Student', true).' - '.$criteria['name'];

        if (!empty($proof['status'])) {
            $proofedBy = !empty($proof['surname']) ? __('By').': '.Format::name('', $proof['preferredName'], $proof['surname'], 'Staff', false, true) : '';
            $proofedBy .= !empty($proof['timestampProofed']) ? ' '.Format::relativeTime($proof['timestampProofed'], false) : '';
            $summaryText .= '<span class="tag float-right '.($proof['status'] == 'Done' || $proof['status'] == 'Accepted' ? 'success' : 'message').'" title="'.$proofedBy.'">'.$proof['status'].'</span>';
        }

        $criteriaName = $criteria['criteriaName'];
        if (!empty($criteria['surnameWrittenBy'])) {
            $criteriaName .= ' '.__('by').' '.Format::name('', $criteria['preferredNameWrittenBy'], $criteria['surnameWrittenBy'], 'Staff', false, true);
        }

        $section = $form->addRow()
            ->addDetails()
            ->addClass('border bg-gray-100 rounded mb-2 p-2')
            ->addClass($proof['status'] == 'Done' || $proof['status'] == 'Accepted' ? 'success bg-green-100' : '')
            ->addClass($proof['status'] == 'Edited' ? 'message bg-blue-100' : '')
            ->setID('student'.str_pad($criteria['gibbonPersonIDStudent'], 10, '0', STR_PAD_LEFT))
            ->summary($summaryText)
            ->opened(empty($proof['status']) || ($proofReview && $proof['status'] == 'Edited'));

        if ($proofReview) {
            // REVIEW MODE: see and accept/decline proofs that have been submitted to yourself, or proof your own comments
            if (!empty($proof['status'])) {
                $form->addHiddenValue("proof[{$gibbonReportingValueID}]", $proof['gibbonReportingProofID']);

                $proofReason = $page->fetchFromTemplate('ui/statusComment.twig.html', [
                    'name'    => Format::name('', $proof['preferredName'], $proof['surname'], 'Staff', false, true),
                    'action'  => '',
                    'photo'   => $proof['image_240'],
                    'date'    => Format::relativeTime($proof['timestampProofed']),
                    'status'  => ' ',
                    'tag'     => '',
                    'comment'    => $proof['reason'],
                ]);
                $section->addContent($proofReason);
            } else {
                $section->addContent(__("This comment has not been proof read yet."))->wrap('<div class="py-2 leading-loose italic">', '</div>');
            }

            $section->addLabel("comment[{$gibbonReportingValueID}]Label", $criteriaName)
                ->setClass('text-normal italic pt-1 pl-1');
                
            if ($proof['status'] == 'Edited') {
                $section->addContent($differ->htmlDiff(htmlPrep($criteria['comment']), htmlPrep($proof['comment'])))
                        ->wrap('<div class="text-base font-sans leading-tight text-gray-900 p-1 mb-4">', '</div>');

                $actions = ['Accepted' => __('Accept').'&nbsp;&nbsp;', 'Declined' => __('Decline').'&nbsp;&nbsp;', 'Revised' => __('Edit Comment').'&nbsp;&nbsp;'];
            } else {
                $section->addContent($criteria['comment'])
                    ->wrap('<div class="text-base font-sans leading-tight text-gray-900 p-1 mb-4">', '</div>');
                    
                $actions = ['Revised' => __('Edit Comment').'&nbsp;&nbsp;'];
            }

            $form->toggleVisibilityByClass('comment'.$gibbonReportingValueID)->onRadio("status[{$gibbonReportingValueID}]")->when('Revised');

            $col = $section->addColumn();
            $col->addCommentEditor("comment[{$gibbonReportingValueID}]")
                ->checkName($criteria['preferredName'])
                ->checkPronouns($criteria['gender'])
                ->setClass('flex w-full reportCriteria text-base font-sans')
                ->setID("comment{$gibbonReportingValueID}")
                ->maxLength($criteria['characterLimit'])
                ->setValue($proof['status'] == 'Edited' ? $proof['comment'] : $criteria['comment']);

            $colRow = $section->addColumn()->addColumn()->setClass('flex mt-4 -mb-2 justify-between items-center');
                $col = $colRow->addColumn()->setClass('flex h-10 border rounded items-center bg-gray-200');
                $col->addRadio("status[{$gibbonReportingValueID}]")
                    ->setClass('statusInput text-base leading-loose')
                    ->addData('id', $gibbonReportingValueID)
                    ->inline()
                    ->fromArray($actions);
                
            $col = $colRow->addColumn()->setClass('mt-1');
            $col->addSubmit(__('Save'));

        } else {
            // PROOF READ MODE: view peer comments and optionally suggest edits
            $section->addLabel("comment[{$gibbonReportingValueID}]Label", $criteriaName)
                ->setClass('text-normal italic pt-1 pl-1');

            $proofText = $proof['status'] == 'Edited'
                ? $differ->htmlDiff(htmlPrep($criteria['comment']), htmlPrep($proof['comment']))
                : htmlPrep($criteria['comment']);

            $section->addContent($proofText)
                    ->wrap('<div class="text-base font-sans leading-tight text-gray-900 p-1 mb-4">', '</div>');

            $form->toggleVisibilityByClass('comment'.$gibbonReportingValueID)->onRadio("status[{$gibbonReportingValueID}]")->when('Edited');

            $col = $section->addColumn();
            $col->addCommentEditor("comment[{$gibbonReportingValueID}]")
                ->checkName($criteria['preferredName'])
                ->checkPronouns($criteria['gender'])
                ->setClass('flex reportCriteria text-base font-sans')
                ->addClass('comment'.$gibbonReportingValueID)
                ->setID("comment{$gibbonReportingValueID}")
                ->maxLength($criteria['characterLimit'])
                ->readonly($proof['status'] != 'Edited')
                ->setValue($proof['status'] == 'Edited' ? $proof['comment'] : $criteria['comment']);

            $colRow = $section->addColumn()->addColumn()->setClass('flex mt-4 -mb-2 justify-between items-center');
                $col = $colRow->addColumn()->setClass('flex h-10 border rounded items-center bg-gray-200');
                $col->addRadio("status[{$gibbonReportingValueID}]")
                    ->setClass('statusInput text-base leading-loose')
                    ->addData('id', $gibbonReportingValueID)
                    ->inline()
                    ->checked($proof['status'])
                    ->fromArray(['Done' => __('Looks Good!').'&nbsp;&nbsp;', 'Edited' => __('Edit Comment').'&nbsp;&nbsp;']);

                $col->addTextField("reason[{$gibbonReportingValueID}]")
                    ->setClass('w-64 mt-px mr-2')
                    ->addClass('comment'.$gibbonReportingValueID)
                    ->placeholder('Reason (Optional)')
                    ->setValue($proof['reason'])
                    ->maxLength(255);
                
            $col = $colRow->addColumn()->setClass('mt-1');
            $col->addSubmit(__('Save'));
        }
    }

    echo $form->getOutput();
}
?>

<script>
$('input.statusInput').change(function() {
    console.log($(this).data('id'));
    console.log($(this).val());

    var details = $(this).parents('details').first();
    console.log(details);

    if ($(this).val() == 'Done' || $(this).val() == 'Accepted') {
        details.removeClass('message bg-blue-100').removeClass('error bg-red-100');
        details.addClass('success bg-green-100');
        details.find('textarea').attr('readonly', true);
    } else if ($(this).val() == 'Edited' || $(this).val() == 'Revised') {
        details.removeClass('success bg-green-100').removeClass('error bg-red-100');
        details.addClass('message bg-blue-100');
        details.find('textarea').attr('readonly', false);
    } else if ($(this).val() == 'Declined') {
        details.removeClass('success bg-green-100').removeClass('message bg-blue-100');
        details.addClass('error bg-red-100');
        details.find('textarea').attr('readonly', false);
    }
});

</script>
