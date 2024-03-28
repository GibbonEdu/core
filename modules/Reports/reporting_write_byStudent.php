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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\System\HookGateway;
use Gibbon\Module\Reports\Forms\ReportingSidebarForm;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingAccessGateway;
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Module\Reports\Domain\ReportingProgressGateway;
use Gibbon\Domain\User\UserGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_write_byStudent.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $gibbonPersonIDStudent = $_REQUEST['gibbonPersonIDStudent'] ?? '';
    $gibbonPersonID = isActionAccessible($guid, $connection2, '/modules/Reports/reporting_cycles_manage.php')
        ? $_REQUEST['gibbonPersonID'] ?? $session->get('gibbonPersonID')
        : $session->get('gibbonPersonID');
    $urlParams = [
        'gibbonSchoolYearID' => $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID'),
        'gibbonReportingCycleID' => $_GET['gibbonReportingCycleID'] ?? '',
        'gibbonReportingScopeID' => $_GET['gibbonReportingScopeID'] ?? '',
        'scopeTypeID' => $_GET['scopeTypeID'] ?? '',
        'gibbonPersonID' => $gibbonPersonID,
        'allStudents' => $_GET['allStudents'] ?? '',
    ];

    if (!empty($_GET['criteriaSelector'])) {
        list($urlParams['gibbonReportingScopeID'], $urlParams['scopeTypeID']) = explode('-', $_GET['criteriaSelector']);
    }

    // SIDEBAR: Dropdowns
    $sidebarForm = $container->get(ReportingSidebarForm::class)->createForm($urlParams);
    $session->set('sidebarExtra', $sidebarForm->getOutput());

    $page->scripts->add('chart');

    $page->breadcrumbs
        ->add(__('My Reporting'), 'reporting_my.php', ['gibbonPersonID' => $gibbonPersonID])
        ->add(__('Write Reports'), 'reporting_write.php', $urlParams)
        ->add(__('By Student'));

    $reportingAccessGateway = $container->get(ReportingAccessGateway::class);

    $reportingScope = $container->get(ReportingScopeGateway::class)->getByID($urlParams['gibbonReportingScopeID']);
    if (empty($reportingScope)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $reportingCycle = $container->get(ReportingCycleGateway::class)->getByID($reportingScope['gibbonReportingCycleID']);
    if (empty($reportingCycle)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // ACCESS CHECK: overall check (for high-level access) or per-scope check for general access
    $accessCheck = $reportingAccessGateway->getAccessToScopeByPerson($urlParams['gibbonReportingScopeID'], $session->get('gibbonPersonID'));
    if ($highestAction == 'Write Reports_editAll') {
        $reportingOpen = ($accessCheck['reportingOpen'] ?? 'N') == 'Y';
        $canAccessReport = true;
        $canWriteReport = true;
    } elseif ($highestAction == 'Write Reports_mine') {
        $writeCheck = $reportingAccessGateway->getAccessToScopeAndCriteriaGroupByPerson($urlParams['gibbonReportingScopeID'], $reportingScope['scopeType'], $urlParams['scopeTypeID'], $session->get('gibbonPersonID'));
        $reportingOpen = ($writeCheck['reportingOpen'] ?? 'N') == 'Y';
        $canAccessReport = ($accessCheck['canAccess'] ?? 'N') == 'Y';
        $canWriteReport = $reportingOpen && ($writeCheck['canWrite'] ?? 'N') == 'Y';
    }

    if (empty($canAccessReport)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if ($reportingScope['scopeType'] == 'Year Group') {
        $scopeIdentifier = 'gibbonYearGroupID';
    } elseif ($reportingScope['scopeType'] == 'Form Group') {
        $scopeIdentifier = 'gibbonFormGroupID';
    } elseif ($reportingScope['scopeType'] == 'Course') {
        $scopeIdentifier = 'gibbonCourseClassID';
    }

    // Check for student enrolment info, fallback to user info
    $student = $container->get(StudentGateway::class)->selectActiveStudentByPerson($reportingCycle['gibbonSchoolYearID'], $gibbonPersonIDStudent)->fetch();
    if (empty($student)) {
        $student = $container->get(UserGateway::class)->getByID($gibbonPersonIDStudent);
    }

    if (empty($student)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $scopeDetails = $reportingAccessGateway->selectReportingDetailsByScope($urlParams['gibbonReportingScopeID'], $reportingScope['scopeType'], $urlParams['scopeTypeID'])->fetch();
    $relatedReports = $container->get(ReportingScopeGateway::class)->selectRelatedReportingScopesByID($urlParams['gibbonReportingScopeID'], $reportingScope['scopeType'], $urlParams['scopeTypeID'])->fetchAll();
    $reportingProgress = $container->get(ReportingProgressGateway::class)->selectBy(['gibbonReportingScopeID' => $urlParams['gibbonReportingScopeID'], $scopeIdentifier => $urlParams['scopeTypeID'], 'gibbonPersonIDStudent' => $gibbonPersonIDStudent])->fetch();
    $student['alerts'] = getAlertBar($guid, $connection2, $gibbonPersonIDStudent, $student['privacy'], '', false, false, "_blank");

    $progress = $reportingAccessGateway->selectReportingProgressByScope($urlParams['gibbonReportingScopeID'], $reportingScope['scopeType'], $urlParams['scopeTypeID'], $urlParams['allStudents'] == 'Y')->fetchGroupedUnique();
    $progressComplete = array_reduce($progress, function ($group, $item) {
        return $item['progress'] == 'Complete' ? $group + 1 : $group;
    }, 0);

    $keys = array_flip(array_keys($progress));
    $values = array_values($progress);

    $prevStudent = $values[$keys[$gibbonPersonIDStudent] -1] ?? $values[count($values)-1];
    $nextStudent = $values[$keys[$gibbonPersonIDStudent] +1] ?? $values[0];

    echo $page->fetchFromTemplate('ui/reportingStudentHeader.twig.html', [
        'canWriteReport' => $canWriteReport,
        'reportingOpen' => $reportingOpen,
        'student' => $student,
        'scopeDetails' => $scopeDetails,
        'relatedReports' => $relatedReports,
        'prevStudent' => $prevStudent,
        'nextStudent' => $nextStudent,
        'params' => $urlParams,
    ]);

    // PER STUDENT CRITERIA
    $reportingCriteria = $reportingAccessGateway->selectReportingCriteriaByStudentAndScope($reportingScope['gibbonReportingScopeID'], $reportingScope['scopeType'], $urlParams['scopeTypeID'], $gibbonPersonIDStudent)->fetchAll();

    // FORM
    $form = Form::create('reportingWrite', $session->get('absoluteURL').'/modules/Reports/reporting_write_byStudentProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonSchoolYearID', $urlParams['gibbonSchoolYearID']);
    $form->addHiddenValue('gibbonReportingCycleID', $reportingScope['gibbonReportingCycleID']);
    $form->addHiddenValue('gibbonReportingScopeID', $reportingScope['gibbonReportingScopeID']);
    $form->addHiddenValue('gibbonPersonIDStudent', $gibbonPersonIDStudent);
    $form->addHiddenValue('gibbonPersonIDNext', $nextStudent['gibbonPersonID']);
    $form->addHiddenValue('scopeTypeID', $urlParams['scopeTypeID']);
    $form->addHiddenValue('allStudents', $urlParams['allStudents']);
    $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

    $form->addRow()->addClass('reportStatus')->addContent($scopeDetails['name'])->wrap('<h4 class="mt-3 p-0">', '</h4>');

    // HOOKS
    // Custom hooks can replace form fields by criteria type using a custom include.
    // Includes are loaded inside a function to limit their variable scope.
    $hooks = $container->get(HookGateway::class)->selectHooksByType('Report Writing')->fetchGroupedUnique();
    $hookInclude = function ($options, $criteria) use (&$session, &$container, &$form, $student, $scopeDetails, $reportingScope, $reportingCriteria, $urlParams, $canWriteReport) {
        $options = json_decode($options['options'] ?? '', true);
        $includePath = $session->get('absolutePath').'/modules/'.$options['sourceModuleName'].'/'.$options['sourceModuleInclude'];

        if (!empty($options) && is_file($includePath)) {
            include $includePath;
            return true;
        }

        return false;
    };

    $lastCategory = '';
    foreach ($reportingCriteria as $criteria) {
        $fieldName = "value[{$criteria['gibbonReportingCriteriaID']}]";
        $fieldID = "value{$criteria['gibbonReportingCriteriaID']}";

        if (!empty($criteria['category']) && $criteria['category'] != $lastCategory) {
            $row = $form->addRow()->addContent($criteria['category'])->wrap('<h5 class="my-2 p-0 text-sm normal-case border-0">', '</h5>');
        }

        if (isset($hooks[$criteria['criteriaName']])) {
            // Attempt to load a hook, otherwise display an alert.
            if (!$hookInclude($hooks[$criteria['criteriaName']], $criteria)) {
                $form->addHiddenValue($fieldName, $criteria['value']);
                $form->addRow()->addAlert(__('Failed to load {name}', [
                    'name' => $criteria['criteriaName'],
                ]), 'error');
            }

        } elseif ($criteria['valueType'] == 'Comment' || $criteria['valueType'] == 'Remark') {
            $col = $form->addRow()->addColumn();
            $col->addLabel($fieldName, $criteria['name'])->description($criteria['description']);
            $col->addCommentEditor($fieldName)
                ->checkName($student['preferredName'])
                ->checkPronouns($student['gender'])
                ->addClass('reportCriteria')
                ->setID($fieldID)
                ->maxLength($criteria['characterLimit'])
                ->setValue($criteria['comment'])
                ->readonly(!$canWriteReport);
        } else {
            $row = $form->addRow();
            $row->addLabel($fieldName, $criteria['name'])->description($criteria['description']);

            if ($criteria['valueType'] == 'Grade Scale') {
                $gradeSelect = $row->addSelectGradeScaleGrade($fieldName, $criteria['gibbonScaleID'], ['valueMode' => 'value', 'labelMode' => 'both', 'honourDefault' => true])
                    ->addClass('reportCriteria')
                    ->setID($fieldID)
                    ->readonly(!$canWriteReport);

                if (!is_null($criteria['gibbonReportingValueID'])) {
                    $gradeSelect->selected($criteria['value']);
                }
            } elseif ($criteria['valueType'] == 'Yes/No') {
                $row->addYesNo($fieldName)
                    ->addClass('reportCriteria')
                    ->setID($fieldID)
                    ->selected($criteria['value'] ?? $criteria['defaultValue'])
                    ->placeholder()
                    ->readonly(!$canWriteReport);
            } elseif ($criteria['valueType'] == 'Number') {
                $row->addNumber($fieldName)
                    ->addClass('reportCriteria')
                    ->setID($fieldID)
                    ->setValue($criteria['value'])
                    ->maxLength(20)
                    ->onlyInteger(false)
                    ->readonly(!$canWriteReport);
            } elseif ($criteria['valueType'] == 'Image') {
                $row->addFileUpload('file'.$criteria['gibbonReportingCriteriaID'])
                    ->addClass('reportCriteria')
                    ->setID($fieldID)
                    ->setAttachment($fieldName, $session->get('absoluteURL'), $criteria['value'] ?? '')
                    ->readonly(!$canWriteReport);
            } else {
                $row->addTextField($fieldName)
                    ->addClass('reportCriteria')
                    ->setID($fieldID)
                    ->maxLength(255)
                    ->setValue($criteria['value'])
                    ->readonly(!$canWriteReport);
            }
        }

        $lastCategory = $criteria['category'];
    }

    if ($reportingScope['scopeType'] == 'Form Group') {
        $reportingRemarks = $reportingAccessGateway->selectAllRemarksByStudent($reportingScope['gibbonReportingCycleID'], $gibbonPersonIDStudent)->fetchAll();

        if (!empty($reportingRemarks)) {

            $col = $form->addRow()->addColumn();
            $col->addLabel('remarks', __('Remarks'))
                ->addClass('sm:max-w-md')
                ->description(__('Remarks are comments shared by other staff members. They do not appear on the report.'));

            foreach ($reportingRemarks as $remark) {
                $remarkDetails = $reportingAccessGateway->selectReportingDetailsByScope($remark['gibbonReportingScopeID'], $remark['scopeType'], $remark['scopeTypeID'])->fetch();

                $remarkText = $page->fetchFromTemplate('ui/statusComment.twig.html', [
                    'name'    => Format::name($remark['title'], $remark['preferredName'], $remark['surname'], 'Staff', false, true),
                    'action'  => '',
                    'photo'   => $remark['image_240'],
                    'date'    => Format::relativeTime($remark['timestampModified']),
                    'status'  => $remarkDetails['name'] ?? $remark['name'],
                    'tag'     => '',
                    'comment'    => !empty($remark['comment']) ? $remark['comment'] : __('N/A'),
                ]);

                $col->addContent($remarkText);
            }
        }
    }

    $row = $form->addRow();
        $row->addLabel('complete', __('Progress'));
        $row->addCheckbox('complete')
            ->description(__('Complete'))
            ->addClass('align-middle reportCriteria')
            ->setLabelClass('inline-block pt-2 pb-1 px-2 text-base align-middle')
            ->checked($reportingProgress && $reportingProgress['status'] == 'Complete')
            ->readonly(!$canWriteReport)
            ->setDisabled(!$canWriteReport);

    if ($canWriteReport) {
        $row = $form->addRow();
            $row->addSubmit(__('Save & Next'))
                ->prepend(sprintf('<input type="button" value="%s" onclick="save()">',__('Save')))
                ->prepend(sprintf('<span class="unsavedChanges tag message mr-2" style="display:none;" title="%1$s">%1$s</span>', __('Unsaved Changes')));
    }

    echo $form->getOutput();

    // CRITERIA FROM ALL SCOPES
    if ($reportingScope['scopeType'] != 'Course') {
        $reportCriteria = $reportingAccessGateway->selectReportingCriteriaByStudent($reportingScope['gibbonReportingCycleID'], $gibbonPersonIDStudent)->fetchGrouped();

        echo $page->fetchFromTemplate('ui/writingStudentOverview.twig.html', [
            'student' => $student,
            'reportCriteria' => $reportCriteria,
            'params' => $urlParams,
            'canWriteReport' => $canWriteReport,
        ]);

        // FOOTER
        echo $page->fetchFromTemplate('ui/reportingStudentFooter.twig.html', [
            'student' => $student,
            'scopeDetails' => $scopeDetails,
            'prevStudent' => $prevStudent,
            'nextStudent' => $nextStudent,
            'params' => $urlParams,
        ]);
    }

    // SIDEBAR
    $session->set('sidebarExtra', $session->get('sidebarExtra') . $page->fetchFromTemplate('ui/writingSidebar.twig.html', [
        'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
        'totalCount' => count($progress),
        'progressCount' => $progressComplete,
        'students' => $progress,
        'params' => $urlParams,
    ]));
}
?>

<script>
var edited = false;
var complete = false;
var readonly = <?php echo !empty($canWriteReport) && $canWriteReport ? 'false' : 'true'; ?>;

updateStatus();

$(document).ready(function(){
    autosize($('textarea'));
});

function save() {
    $('[name="gibbonPersonIDNext"]').val('');
    document.getElementById('reportingWrite').submit()
}

$('.reportCriteria').on('input', function() {
    edited = true;
    updateStatus();

    window.onbeforeunload = function(event) {
        if (event.explicitOriginalTarget.value=='Save' || event.explicitOriginalTarget.value=='Save & Next') return;
        return "<?php echo __('There are unsaved changes on this page.') ?>";
    };
});

function updateStatus() {
    complete = $('#complete:checked').length > 0;
    displayStatus();
}

function displayStatus(){
    if (readonly) {
        $('.reportStatus div').addClass('bg-gray-300');
        $('.reportStatus h4').html('<?php echo __('Read-only') ?>');
    } else if (complete) {
        $('#reportingWrite .standardForm').removeClass('border-blue-600').addClass('border-green-600');
        $('.reportStatus div').removeClass('bg-blue-200').addClass('bg-green-200');
        $('.reportStatus h4').html('<?php echo __('Complete') ?>');
    } else if (edited) {
        $('#reportingWrite .standardForm').removeClass('border-green-600').addClass('border-blue-600');
        $('.reportStatus div').removeClass('bg-green-200').addClass('bg-blue-200');
        $('.reportStatus h4').html('<?php echo __('Editing') ?>');
    } else {
        $('#reportingWrite .standardForm').removeClass('border-green-600');
        $('.reportStatus div').removeClass('bg-green-200');
    }

    $('input[value="Save & Next"]').toggle(complete);

    if (edited) {
        $('.unsavedChanges').show();
        $('#reportingWrite .standardForm').removeClass('border-green-600').addClass('border-blue-600');
        $('.reportStatus div').removeClass('bg-green-200').addClass('bg-blue-200');
        $('.reportStatus h4').html($('.reportStatus h4').html() + '<span class="inline-block pl-4 normal-case font-normal text-gray-700 text-xs align-middle"><?php echo __('There are unsaved changes on this page.') ?></span>');
    }
}

</script>
