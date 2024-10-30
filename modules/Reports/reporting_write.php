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
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingAccessGateway;
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Module\Reports\Forms\ReportingSidebarForm;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_write.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $gibbonPersonID = isActionAccessible($guid, $connection2, '/modules/Reports/reporting_cycles_manage.php')
        ? $_REQUEST['gibbonPersonID'] ?? $session->get('gibbonPersonID')
        : $session->get('gibbonPersonID');

    $page->breadcrumbs
        ->add(__('My Reporting'), 'reporting_my.php', ['gibbonPersonID' => $gibbonPersonID])
        ->add(__('Write Reports'));

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

    $reportingAccessGateway = $container->get(ReportingAccessGateway::class);
    $reportingScope = $container->get(ReportingScopeGateway::class)->getByID($urlParams['gibbonReportingScopeID']);
    $reportingCycle = $container->get(ReportingCycleGateway::class)->getByID($reportingScope['gibbonReportingCycleID'] ?? '');

    if (empty($urlParams['gibbonReportingScopeID']) || $reportingScope['gibbonReportingCycleID'] != $urlParams['gibbonReportingCycleID']) {
        $page->addMessage(__('Select an option in the sidebar to get started.'));
        return;
    }

    if (empty($reportingScope) || empty($reportingCycle)) {
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

    $scopeDetails = $reportingAccessGateway->selectReportingDetailsByScope($reportingScope['gibbonReportingScopeID'], $reportingScope['scopeType'], $urlParams['scopeTypeID'])->fetch();
    $relatedReports = $container->get(ReportingScopeGateway::class)->selectRelatedReportingScopesByID($urlParams['gibbonReportingScopeID'], $reportingScope['scopeType'], $urlParams['scopeTypeID'])->fetchAll();

    $progress = $reportingAccessGateway->selectReportingProgressByScope($urlParams['gibbonReportingScopeID'], $reportingScope['scopeType'], $urlParams['scopeTypeID'], $urlParams['allStudents'] == 'Y')->fetchAll();

    // Map progress into three categories: Incomplete, In Progress & Complete
    $progressByCategory = array_reduce($progress, function ($group, $item) {
        $progressCategory = !empty($item['gibbonReportingProgressID'])
            ? $item['progress']
            : 'Incomplete';

        $group[$progressCategory][$item['gibbonPersonID']] = $item;
        return $group;
    }, []);

    echo $page->fetchFromTemplate('ui/writingListHeader.twig.html', [
        'canWriteReport' => $canWriteReport,
        'reportingOpen' => $reportingOpen,
        'scopeDetails' => $scopeDetails,
        'relatedReports' => $relatedReports,
        'totalCount' => count($progress),
        'progressCount' => count($progressByCategory['Complete'] ?? []),
        'params' => $urlParams,
    ]);

    // PER GROUP CRITERIA
    $reportingCriteria = $reportingAccessGateway->selectReportingCriteriaByGroup($reportingScope['gibbonReportingScopeID'], $reportingScope['scopeType'], $urlParams['scopeTypeID'])->fetchAll();

    // FORM
    if (!empty($reportingCriteria)) {
        $form = Form::create('reportingWriteGlobal', $session->get('absoluteURL').'/modules/Reports/reporting_writeProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonSchoolYearID', $urlParams['gibbonSchoolYearID']);
        $form->addHiddenValue('gibbonReportingCycleID', $reportingScope['gibbonReportingCycleID']);
        $form->addHiddenValue('gibbonReportingScopeID', $reportingScope['gibbonReportingScopeID']);
        $form->addHiddenValue('scopeTypeID', $urlParams['scopeTypeID']);
        $form->addHiddenValue('gibbonPersonID', $urlParams['gibbonPersonID']);

        foreach ($reportingCriteria as $criteria) {
            $fieldName = "value[{$criteria['gibbonReportingCriteriaID']}]";
            $fieldID = "value{$criteria['gibbonReportingCriteriaID']}";

            if ($criteria['valueType'] == 'Comment' || $criteria['valueType'] == 'Remark') {
                $col = $form->addRow()->addColumn();
                $col->addLabel($fieldName, $criteria['name'])->description($criteria['description']);
                $col->addCommentEditor($fieldName)
                    ->setID($fieldID)
                    ->maxLength($criteria['characterLimit'])
                    ->setValue($criteria['comment'])
                    ->readonly(!$canWriteReport);
            } else {
                $row = $form->addRow();
                $row->addLabel($fieldName, $criteria['name'])->description($criteria['description']);

                if ($criteria['valueType'] == 'Grade Scale') {
                    $row->addSelectGradeScaleGrade($fieldName, $criteria['gibbonScaleID'], ['valueMode' => 'id', 'labelMode' => 'both', 'honourDefault' => true])
                        ->setID($fieldID)
                        ->selected($criteria['gibbonScaleGradeID'])
                        ->readonly(!$canWriteReport);
                } elseif ($criteria['valueType'] == 'Yes/No') {
                    $row->addYesNo($fieldName)
                        ->setID($fieldID)
                        ->setValue($criteria['value'])
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
                        ->setID($fieldID)
                        ->maxLength(255)
                        ->setValue($criteria['value'])
                        ->readonly(!$canWriteReport);
                }
            }
        }

        if ($canWriteReport) {
            $row = $form->addRow();
                $row->addSubmit();
        }

        echo $form->getOutput();
    }

    if (!empty($progress)) {
        echo $page->fetchFromTemplate('ui/writingQueue.twig.html', [
            'progress'=> $progressByCategory,
            'params' => $urlParams,
        ]);
    }

    // SIDEBAR: Student List
    $session->set('sidebarExtra', $session->get('sidebarExtra') . $page->fetchFromTemplate('ui/writingSidebar.twig.html', [
        'totalCount' => count($progress),
        'progressCount' => count($progressByCategory['Complete'] ?? []),
        'students' => $progress,
        'params' => $urlParams,
    ]));
}
?>
<script>
    $(document).ready(function(){
        autosize($('textarea'));
    });
</script>
