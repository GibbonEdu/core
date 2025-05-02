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
use Gibbon\Domain\System\DiscussionGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;
use Gibbon\View\Page;

$_POST['address'] = '/modules/Free Learning/units_browse_details_approval.php';

require_once '../../gibbon.php';

$mode = $_GET['mode'] ?? 'form';
$mode = ($mode == 'form' || $mode == 'process') ? $mode : 'form';
$gibbonDiscussionID = $_GET['gibbonDiscussionID'] ?? null;
$comment = $_POST['comment'] ?? '';

$twig = $container->get('twig');
$page = $container->get('page');

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details_approval.php')) {
    if (!empty($gibbonDiscussionID)) {

        $discussionGateway = $container->get(discussionGateway::class);
        $discussion = $discussionGateway->getByID($gibbonDiscussionID);

        if (!empty($discussion)) {
            $cuttoff = date('Y-m-d h:m:s', time()-172800);

            if ($discussion['gibbonPersonID'] == $session->get('gibbonPersonID') && $discussion['timestamp'] > $cuttoff ) {

                if ($mode == 'form') {
                    $form = Form::createBlank('commentEdit', $session->get('absoluteURL').'/modules/Free Learning/units_browse_details_approvalAjax.php?mode=process&gibbonDiscussionID='.$gibbonDiscussionID);
                    $form->enableQuickSubmit();
                    $form->setAttribute('hx-select', null)
                        ->setAttribute('hx-target', 'closest .discussion-comment')
                        ->setAttribute('hx-replace-url', 'false')
                        ->setAttribute('hx-swap', 'innerHTML show:no-scroll');

                    $form->setClass('blank');

                    $form->addHiddenValue('address', $session->get('address'));

                    $row = $form->addRow();
                        $row->addEditor('comment', $guid)->setValue($discussion['comment'])->setRows(3)->required();

                    $row = $form->addRow();
                        $row->addSubmit(__('Update'))->addClass('text-right');

                    echo $form->getOutput();
                } else {
                    $data = ['comment' => $comment];
                    $discussionGateway->update($gibbonDiscussionID, $data);

                    if ($discussion['foreignTable'] == 'freeLearningUnitStudent') {
                        $unitStudentGateway = $container->get(UnitStudentGateway::class);
                        $data = ['commentApproval' => $comment];
                        $unitStudentGateway->update($discussion['foreignTableID'], $data);
                    }

                    echo $comment;
                }
            }
        }
    }
}

