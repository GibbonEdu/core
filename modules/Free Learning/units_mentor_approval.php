<?php

use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;
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

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
     ->add(__m('Free Learning Mentor Feedback'));

$block = false;
if (isset($_GET['return'])) {
    if ($_GET['return'] == 'success0' or $_GET['return'] == 'success1') {
        $block = true;
    }
}

$returns = ['success0' => __m('Your request was completed successfully. Thank you for your time. The learner you are helping has been notified of your positive feedback.'), 'success1' => __m('Your request was completed successfully. Thank you for your time. The learner you are helping has been notified of your feedback and will resubmit their work in due course, at which point your input will be requested once again: in the meanwhile, no further action is required on your part.')];
$page->return->addReturns($returns);

if (!$block) {
    // Get params
    $unitStudentGateway = $container->get(UnitStudentGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    $freeLearningUnitStudentID =  $_GET['freeLearningUnitStudentID'] ?? '';
    $confirmationKey = $_GET['confirmationKey'] ?? '';

    if ($freeLearningUnitStudentID == '' or $confirmationKey == '') {
        echo Format::alert(__('You have not specified one or more required parameters.'));
    } else {
        //Check student & confirmation key
        try {
            $data = array('freeLearningUnitStudentID' => $freeLearningUnitStudentID, 'confirmationKey' => $confirmationKey) ;
            $sql = 'SELECT freeLearningUnitStudent.*, freeLearningUnit.name AS unit, surname, preferredName, gender, (SELECT count(*) FROM gibbonINPersonDescriptor WHERE gibbonINPersonDescriptor.gibbonPersonID=freeLearningUnitStudent.gibbonPersonIDStudent GROUP BY gibbonINPersonDescriptor.gibbonPersonID) AS inCount
                FROM freeLearningUnitStudent
                    JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                    JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
                WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID
                    AND confirmationKey=:confirmationKey
                    AND freeLearningUnitStudent.status=\'Complete - Pending\'';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo Format::alert(__('Your request failed due to a database error.'));
        }

        if ($result->rowCount()!=1) {
            echo Format::alert(__('The specified record cannot be found.'));
        }
        else {
            $values = $result->fetch() ;
            $freeLearningUnitID = $values['freeLearningUnitID'];

            echo '<p>';
                echo __m('This screen allows you to give feedback on the submitted work. Immediately below you can browse the contents of the unit, which will tell you what has been learned. Following that you can view and feedback on the submitted work.');
            echo '</p>';

            // Show unit content
            echo '<h3>';
                echo __m('Unit Content');
            echo '</h3>';

            $dataBlocks = ['freeLearningUnitID' => $freeLearningUnitID];
            $sqlBlocks = 'SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY sequenceNumber';
            $blocks = $pdo->select($sqlBlocks, $dataBlocks)->fetchAll();

            if (empty($blocks)) {
                echo Format::alert(__('There are no records to display.'));
            } else {
                $templateView = $container->get(View::class);
                $resourceContents = '';

                $blockCount = 0;
                foreach ($blocks as $block) {
                    echo $templateView->fetchFromTemplate('unitBlockCollapsed.twig.html', $block + [
                        'roleCategory' => 'Staff',
                        'gibbonPersonID' => $session->get('username') ?? '',
                        'blockCount' => $blockCount,
                        'freeLearningUnitBlockID' => $block["freeLearningUnitBlockID"],
                        'absoluteURL' => $session->get('absoluteURL'),
                        'gibbonThemeName' => $session->get('gibbonThemeName') ?? 'Default',
                    ]);
                    $resourceContents .= $block['contents'];
                    $blockCount++;
                }
            }

            // FORM
            $form = Form::create('approval', $session->get('absoluteURL').'/modules/Free Learning/units_mentor_approvalProcess.php');
            $form->setTitle(__m('Feedback Form'));
            $form->setDescription(__m('Use the table below to indicate student completion, based on the evidence shown on the previous page. Leave the student a comment in way of feedback.').'</p>');

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);
            $form->addHiddenValue('freeLearningUnitStudentID', $values['freeLearningUnitStudentID']);
            $form->addHiddenValue('confirmationKey', $confirmationKey);

            $genderOnFeedback = $settingGateway->getSettingByScope('Free Learning', 'genderOnFeedback');

            if (!empty($values['collaborationKey'])) {
                $row = $form->addRow();
                    $row->addLabel('student', __('Students'));
                    $col = $row->addColumn()->setClass('flex-col');

                $collaborators = $unitStudentGateway->selectUnitCollaboratorsByKey($values['collaborationKey'])->fetchAll();
                foreach ($collaborators as $index => $collaborator) {
                    $in = ($collaborator['inCount'] > 0 && isActionAccessible($guid, $connection2, "/modules/Individual Needs/in_view.php")) ? Format::tag(__('Individual Needs'), 'message mr-2 mt-2') : '';
                    $gender = ($genderOnFeedback == "Y") ? Format::tag(Format::genderName($collaborator['gender']), 'dull mr-2 mt-2') : "";
                    $col->addContent(Format::name('', $collaborator['preferredName'], $collaborator['surname'], 'Student', false)."<br/>".$gender.$in)->wrap('<div class="ml-2 w-full text-left text-sm text-gray-900">', '</div>');
                }
            } else {
                $in = ($values['inCount'] > 0 && isActionAccessible($guid, $connection2, "/modules/Individual Needs/in_view.php")) ? Format::tag(__('Individual Needs'), 'message mr-2 mt-2') : '';
                $gender = ($genderOnFeedback == "Y") ? Format::tag(Format::genderName($values['gender']), 'dull mr-2 mt-2') : "";
                $row = $form->addRow();
                    $row->addLabel('student', __('Student'));
                    $row->addContent(Format::name('', $values['preferredName'], $values['surname'], 'Student', false)."<br/>".$gender.$in)->wrap('<div class="ml-2 w-full text-left text-sm text-gray-900">', '</div>');
            }

            $submissionLink = $values['evidenceType'] == 'Link'
                ? $values['evidenceLocation']
                : $session->get('absoluteURL').'/'.$values['evidenceLocation'];

            $row = $form->addRow();
                $row->addLabel('submission', __m('Submission'));
                $row->addContent(Format::link($submissionLink, __m('View Submission'), ['class' => 'w-full ml-2 underline', 'target' => '_blank']));

            // DISCUSSION
            $logs = $unitStudentGateway->selectUnitStudentDiscussion($values['freeLearningUnitStudentID'])->fetchAll();

            $logs = array_map(function ($item) {
                $item['comment'] = Format::hyperlinkAll($item['comment']);
                return $item;
            }, $logs);

            $col = $form->addRow()->addColumn();
            $col->addLabel('comments', __m('Comments'));
            $col->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
                'discussion' => $logs
            ]));

            $col = $form->addRow()->addColumn();
                $col->addLabel('commentApproval', __m('Mentor Comment'))->description(__m('Leave a comment on the student\'s progress.'));
                $col->addEditor('commentApproval', $guid)->setRows(15)->showMedia()->required();

            $statuses = [
                'Complete - Approved' => __m('Complete - Approved'),
                'Evidence Not Yet Approved' => __m('Evidence Not Yet Approved'),
            ];

            $row = $form->addRow();
                $row->addLabel('status', __('Status'));
                $row->addSelect('status')->fromArray($statuses)->required()->placeholder()->selected($values['status']);

            $form->toggleVisibilityByClass('approved')->onSelect('status')->when('Complete - Approved');

            $enableManualBadges = $settingGateway->getSettingByScope('Free Learning', 'enableManualBadges');
            if ($enableManualBadges == 'Y' && isModuleAccessible($guid, $connection2, '/modules/Badges/badges_grant.php')) {
                $data = [];
                $sql = "SELECT badgesBadgeID as value, name FROM badgesBadge WHERE active='Y' ORDER BY name";
                $row = $form->addRow()->addClass('approved');
                    $row->addLabel('badgesBadgeID', __m('Badge'))->description(__m('Manually grant a badge'));
                    $row->addSelect('badgesBadgeID')->fromQuery($pdo, $sql, $data)->placeholder();
            }

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
