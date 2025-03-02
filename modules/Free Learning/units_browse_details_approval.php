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
along with this program. If not, see <http:// www.gnu.org/licenses/>.
*/

use Gibbon\Http\Url;
use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Markbook\MarkbookColumnGateway;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;
use Gibbon\Module\FreeLearning\Domain\System\NotificationGateway;

//  Module includes
require_once __DIR__ . '/moduleFunctions.php';

$settingGateway = $container->get(SettingGateway::class);
$publicUnits = $settingGateway->getSettingByScope('Free Learning', 'publicUnits');

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details_approval.php') == false) {
    //  Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //  Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse_details_approval.php', $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    $roleCategory = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);
    $canManage = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') && $highestAction == 'Browse Units_all';

    //  Get params
    $freeLearningUnitStudentID = $_GET['freeLearningUnitStudentID'] ?? '';
    $freeLearningUnitID = $_GET['freeLearningUnitID'] ?? '';
    $gibbonPersonID = $canManage && !empty($_GET['gibbonPersonID'])
        ? $_GET['gibbonPersonID']
        : $session->get('gibbonPersonID');

    $urlParams = [
        'freeLearningUnitStudentID' => $freeLearningUnitStudentID,
        'freeLearningUnitID'        => $freeLearningUnitID,
        'showInactive'              => $_GET['showInactive'] ?? 'N',
        'gibbonDepartmentID'        => $_REQUEST['gibbonDepartmentID'] ?? '',
        'difficulty'                => $_GET['difficulty'] ?? '',
        'name'                      => $_GET['name'] ?? '',
        'view'                      => $_GET['view'] ?? '',
        'sidebar'                   => 'true',
        'gibbonPersonID'            => $gibbonPersonID,
        'tab'                       => 2,
    ];

    $page->breadcrumbs
        ->add(__m('Browse Units'), 'units_browse.php', $urlParams)
        ->add(__m('Unit Details'), 'units_browse_details.php', $urlParams)
        ->add(__m('Approval'));

    $unitGateway = $container->get(UnitGateway::class);
    $unitStudentGateway = $container->get(UnitStudentGateway::class);

    //  Check that the required values are present
    if (empty($freeLearningUnitID) || empty($freeLearningUnitStudentID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    //  Check that the record exists
    $values = $unitStudentGateway->getUnitStudentDetailsByID($freeLearningUnitID, null, $freeLearningUnitStudentID);
    if (empty($values)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $values['authors'] = $unitGateway->selectUnitAuthorsByID($freeLearningUnitID)->fetchAll();
    $values['departments'] = $unitGateway->selectUnitDepartmentsByID($freeLearningUnitID)->fetchAll(PDO::FETCH_COLUMN, 0);

    $proceed = false;
    // Check to see if we have access to manage all enrolments, or only those belonging to ourselves
    $manageAll = isActionAccessible($guid, $connection2, '/modules/Free Learning/enrolment_manage.php', 'Manage Enrolment_all');
    if ($manageAll == true) {
        $proceed = true;
    }
    else if ($values['enrolmentMethod'] == 'schoolMentor' && $values['gibbonPersonIDSchoolMentor'] == $session->get('gibbonPersonID')) {
        $proceed = true;
    } else {
        $learningAreas = getLearningAreas($connection2, $guid, true);
        if ($learningAreas != '') {
            for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
                if (is_numeric(strpos($values['gibbonDepartmentIDList'], $learningAreas[$i]))) {
                    $proceed = true;
                }
            }
        }
    }

    // Check to see if class is in one teacher teachers
    if ($values['enrolmentMethod'] == 'class') { // Is teacher of this class?
        try {
            $dataClasses = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $values['gibbonCourseClassID']);
            $sqlClasses = "SELECT gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND (role='Teacher' OR role='Assistant')";
            $resultClasses = $connection2->prepare($sqlClasses);
            $resultClasses->execute($dataClasses);
        } catch (PDOException $e) {}
        if ($resultClasses->rowCount() > 0) {
            $proceed = true;
        }
    }

    if ($proceed == false) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    // MARK ANY COMMENT NOTIFICATIONS AS READ
    $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details_approval.php&freeLearningUnitID=$freeLearningUnitID&freeLearningUnitStudentID=$freeLearningUnitStudentID&sidebar=true";
    $notificationGateway = $container->get(NotificationGateway::class);
    $notificationGateway->archiveCommentNotificationsByEnrolment($session->get('gibbonPersonID'), $actionLink);

    // COPY TO MARKBOOK PREPARATION
    $gibbonMarkbookColumnID = null;
    $bigDataSchool = $settingGateway->getSettingByScope('Free Learning', 'bigDataSchool');
    if ($bigDataSchool == "Y" && !empty($values['gibbonCourseClassID'])) {
        $markbookColumnGateway = $container->get(MarkbookColumnGateway::class);
        $gibbonMarkbookColumn = $markbookColumnGateway->selectBy(['name' => $values['name'], 'gibbonCourseClassID' => $values['gibbonCourseClassID']]);
        if ($gibbonMarkbookColumn->rowCount() == 1) {
            $gibbonMarkbookColumnID = $gibbonMarkbookColumn->fetch()['gibbonMarkbookColumnID'];
        }
    }

    //  DETAILS TABLE
    $table = DataTable::createDetails('personal');
    $table->addHeaderAction('edit', __('Edit'))
        ->setURL('/modules/Free Learning/units_manage_edit.php')
        ->addParams($urlParams)
        ->displayLabel();

    $table->addColumn('name', __('Unit Name'));
    $table->addColumn('departments', __('Department'))->format(function ($unit) {
        if (!empty($unit['departments'])) {
            return implode('<br/>', $unit['departments']);
        } else {
            return __m('No Learning Areas available.');
        }
    });
    $table->addColumn('authors', __('Authors'))->format(Format::using('nameList', 'authors'));

    if ($bigDataSchool == "Y" && !empty($values['gibbonCourseClassID']) && !is_null($gibbonMarkbookColumnID) && $values['status'] == 'Complete - Approved') {
        $table->addColumn('linkToMarkbook', 'Markbook')->format(function ($unit) use ($values, $gibbonMarkbookColumnID) {
            return Format::link('./index.php?q=/modules/Markbook/markbook_edit_data.php&gibbonCourseClassID='.$values['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$gibbonMarkbookColumnID.'#'.$values['gibbonPersonIDStudent'], __('Enter Data'));
        });
    }

    echo $table->render([$values]);

    $alert = '';
    $collaborativeAssessment = $settingGateway->getSettingByScope('Free Learning', 'collaborativeAssessment');
    if ($collaborativeAssessment == 'Y' && !empty($values['collaborationKey'])) {
        $alert = Format::alert(__m('Collaborative Assessment is enabled: you will be giving feedback to all members of this group in one go.'), 'message');
    }

    $gibbonHookID = $pdo->selectOne("SELECT gibbonHookID FROM gibbonHook
        JOIN gibbonModule ON (gibbonModule.gibbonModuleID=gibbonHook.gibbonModuleID)
        WHERE gibbonModule.name='Free Learning' AND gibbonHook.type='Student Profile'");

    //  COMMENT FORM
    $form = Form::create('enrolComment', $session->get('absoluteURL').'/modules/Free Learning/units_browse_details_commentProcess.php?'.http_build_query($urlParams));
    $form->setClass('blank');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);
    $form->addHiddenValue('freeLearningUnitStudentID', $freeLearningUnitStudentID);

    //  DISCUSSION
    $logs = $unitStudentGateway->selectUnitStudentDiscussion($freeLearningUnitStudentID)->fetchAll();

    $url = $session->get('absoluteURL').'/modules/Free Learning/units_browse_details_approvalAjax.php?mode=form';
    $gibbonPersonIDUser = $session->get('gibbonPersonID');
    $cuttoff = date('Y-m-d h:m:s', time()-172800);
    $index = 0;
    $logs = array_map(function ($item) use ($url, $gibbonPersonIDUser, $cuttoff, $index, $form) {
        $url .= '&gibbonDiscussionID='.$item['gibbonDiscussionID'];
        $item['comment'] = Format::hyperlinkAll($item['comment']);
        if ($item['gibbonPersonID'] == $gibbonPersonIDUser && $item['timestamp'] > $cuttoff) {
            $item['extra'] = $form->getFactory()
                ->createButton(__('Edit'))->setID('edit'.$index)
                ->setAttribute('hx-get', $url)
                ->setAttribute('hx-target', 'next .discussion-comment')
                ->setAttribute('hx-swap', 'innerHTML transition:false show:#edit'.$index.':top scroll:smooth')
                ->setAttribute('hx-replace-url', 'false')
                ->getOutput();
        }
        return $item;
    }, $logs);

    $logs = array_map(function ($item) use ($gibbonHookID) {
        $item['url'] = !empty($item['gibbonPersonID']) && $item['category'] == 'Student'
            ? './index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$item['gibbonPersonID'].'&hook=Free Learning&module=Free Learning&action=Unit History By Student_all&gibbonHookID='.$gibbonHookID
            : '';
        return $item;
    }, $logs);

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

    $form->addRow()->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
        'title' => __('Comments'),
        'discussion' => $logs
    ]));

    //  ADD COMMENT
    $commentBox = $form->getFactory()->createColumn()->addClass('flex flex-col');
    
    $commentBox->addEditor('addComment', $guid)
        ->showMedia()
        ->required()
        ->setRows(3);
    
    $commentBox->addSubmit(__m('Add Comment'))
        ->setColor('gray')
        ->setClass('text-right mt-2');

    $form->addRow()->addClass('-mt-4')->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
        'discussion' => [[
            'surname' => $session->get('surname'),
            'preferredName' => $session->get('preferredName'),
            'image_240' => $session->get('image_240'),
            'comment' => $commentBox->getOutput(),
        ]]
    ]));

    echo $form->getOutput();

    //  Not ready for approval
    if ($values['status'] == 'Current' || $values['status'] == 'Evidence Not Yet Approved') {
        return;
    }

    //  FORM
    $form = Form::create('approval', $session->get('absoluteURL').'/modules/Free Learning/units_browse_details_approvalProcess.php?'.http_build_query($urlParams));
    $form->setTitle(__m('Unit Complete Approval'));
    $form->setDescription($alert.'<p>'.__m('Use the table below to indicate student completion, based on the evidence shown on the previous page. Leave the student a comment in way of feedback.').'</p>');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);
    $form->addHiddenValue('freeLearningUnitStudentID', $freeLearningUnitStudentID);

    $genderOnFeedback = $settingGateway->getSettingByScope('Free Learning', 'genderOnFeedback');

    if ($collaborativeAssessment == 'Y' && !empty($values['collaborationKey'])) {
        $row = $form->addRow();
            $row->addLabel('student', __('Students'));
            $col = $row->addColumn()->setClass('flex-col');
        $collaborators = $unitStudentGateway->selectUnitCollaboratorsByKey($values['collaborationKey'])->fetchAll();
        foreach ($collaborators as $index => $collaborator) {
            $in = ($collaborator['inCount'] > 0 && isActionAccessible($guid, $connection2, "/modules/Individual Needs/in_view.php")) ? Format::tag(__('Individual Needs'), 'message mr-2 mt-2') : '';
            $gender = ($genderOnFeedback == "Y") ? Format::tag(Format::genderName($collaborator['gender']), 'dull mr-2 mt-2') : "";
            $dateStart = ($bigDataSchool == "Y" && !empty($collaborator['dateStart'])) ? Format::tag(__m("Joined {dateStart}", ["dateStart" => Format::date($collaborator['dateStart'])]), 'dull mr-2 mt-2') : "";
            $col->addContent("<a target='_blank' href='".$session->get('absoluteURL')."\index.php?q=/modules/Free Learning/units_browse.php&gibbonDepartmentID=".(!empty($values['course']) ? $values['course'] : substr($values['gibbonDepartmentIDList'] ?? '', 0, 4))."&difficulty=&name=&view=&sidebar=false&gibbonPersonID=".$collaborator['gibbonPersonID']."'>".Format::name('', $collaborator['preferredName'], $collaborator['surname'], 'Student', false)."</a><br/>".$gender.$dateStart.$in)->wrap('<div class="ml-2 w-full text-left text-sm text-gray-900">', '</div>');
        }
    } else {
        $in = ($values['inCount'] > 0 && isActionAccessible($guid, $connection2, "/modules/Individual Needs/in_view.php")) ? Format::tag(__('Individual Needs'), 'message mr-2 mt-2') : '';
        $gender = ($genderOnFeedback == "Y") ? Format::tag(Format::genderName($values['gender']), 'dull mr-2 mt-2') : "";
        $dateStart = ($bigDataSchool == "Y" && !empty($values['dateStart'])) ? Format::tag(__m("Joined {dateStart}", ["dateStart" => Format::date($values['dateStart'])]), 'dull mr-2 mt-2') : "";
        $row = $form->addRow();
            $row->addLabel('student', __('Student'));
            $row->addContent("<a target='_blank' href='".$session->get('absoluteURL')."\index.php?q=/modules/Free Learning/units_browse.php&gibbonDepartmentID=".(!empty($values['course']) ? $values['course'] : substr($values['gibbonDepartmentIDList'] ?? '', 0, 4))."&difficulty=&name=&view=&sidebar=false&gibbonPersonID=".$values['gibbonPersonID']."'>".Format::name('', $values['preferredName'], $values['surname'], 'Student', false)."</a><br/>".$gender.$dateStart.$in)->wrap('<div class="ml-2 w-full text-left text-sm text-gray-900">', '</div>');
    }

    $submissionLink = $values['evidenceType'] == 'Link'
        ? $values['evidenceLocation']
        : $session->get('absoluteURL').'/'.$values['evidenceLocation'];

    $row = $form->addRow();
        $row->addLabel('submission', __m('Submission'));
        $row->addContent(Format::link($submissionLink, __m('View Submission'), ['class' => 'w-full ml-2 underline', 'target' => '_blank']));

    $row = $form->addRow();
        $col = $row->addColumn();
        $col->addLabel('commentApproval', __m('Teacher Comment'))->description(__m('Leave a comment on the student\'s progress.'));
        $col->addEditor('commentApproval', $guid)->setRows(15)->showMedia()->required();

    $statuses = [
        'Complete - Approved' => __m('Complete - Approved'),
        'Evidence Not Yet Approved' => __m('Evidence Not Yet Approved'),
    ];

    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')->fromArray($statuses)->required()->placeholder()->selected($values['status']);

    $form->toggleVisibilityByClass('approved')->onSelect('status')->when('Complete - Approved');

    $disableExemplarWork = $settingGateway->getSettingByScope('Free Learning', 'disableExemplarWork');
    if ($disableExemplarWork != 'Y') {
        $row = $form->addRow()->addClass('approved');
            $row->addLabel('exemplarWork', __m('Exemplar Work'))->description(__m('Work and comments will be made viewable to other users.'));
            $row->addYesNo('exemplarWork')->required()->selected($values['exemplarWork'] ?? 'N');

        $form->toggleVisibilityByClass('exemplarYes')->onSelect('exemplarWork')->when('Y');

        $row = $form->addRow()->addClass('exemplarYes');
            $row->addLabel('exemplarWorkThumb', __m('Exemplar Work Thumbnail Image'))->description(__('150x150px jpg/png/gif'));
            $row->addFileUpload('file')
                ->accepts('.jpg,.jpeg,.gif,.png')
                ->setAttachment('exemplarWorkThumb', $session->get('absoluteURL'), $values['exemplarWorkThumb']);

        $row = $form->addRow()->addClass('exemplarYes');
            $row->addLabel('exemplarWorkLicense', __m('Exemplar Work Thumbnail Image Credit'))->description(__m('Credit and license for image used above.'));
            $row->addTextField('exemplarWorkLicense')->maxLength(255)->setValue($values['exemplarWorkLicense']);

        $row = $form->addRow()->addClass('exemplarYes');
            $row->addLabel('exemplarWorkEmbed', __m('Exemplar Work Link or Embed'))->description(__m('Include specific link or embed code, otherwise the submitted version of the work will be used.'));
            $row->addTextField('exemplarWorkEmbed')->maxLength(255)->setValue($values['exemplarWorkEmbed']);
    }

    // COPY TO MARKBOOK OUTPUT
    if ($bigDataSchool == "Y" && !empty($values['gibbonCourseClassID']) && !is_null($gibbonMarkbookColumnID)) {
        $row = $form->addRow()->addClass('approved');
            $row->addLabel('copyToMarkbook', __m('Copy To Markbook'))->description(__m('Insert this comment into an existing Markbook column for this class, with a matching column name? If a comment exists already, it will be overwritten.'));
            $row->addYesNo('copyToMarkbook')->required();

        $form->addHiddenValue('gibbonMarkbookColumnID', $gibbonMarkbookColumnID);
    }

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
