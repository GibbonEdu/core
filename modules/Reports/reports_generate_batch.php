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
use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
use Gibbon\Module\Reports\Contexts\ContextFactory;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reports_generate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Generate Reports'), 'reports_generate.php')
        ->add(__('Run'));


    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonReportID = $_GET['gibbonReportID'] ?? '';
    
    $reportGateway = $container->get(ReportGateway::class);
    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);
    
    $report = $reportGateway->getByID($gibbonReportID);
    
    if (empty($gibbonReportID) || empty($report)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $templateData = $container->get(ReportTemplateGateway::class)->getByID($report['gibbonReportTemplateID'] ?? '');
    $context = ContextFactory::create($templateData['context']);

    if (empty($templateData) || empty($context)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // QUERY
    $criteria = $reportGateway->newQueryCriteria(true)
        ->sortBy(['sequenceNumber', 'name'])
        ->fromPOST();

    $reports = $reportGateway->queryYearGroupsByReport($criteria, $gibbonReportID);
    $logs = $reportGateway->getRunningReports();
    $logs = $logs[$gibbonReportID] ?? [];

    // FORM
    $form = BulkActionForm::create('bulkAction', $gibbon->session->get('absoluteURL').'/modules/Reports/reports_generate_batchProcess.php');
    $form->setTitle(__('Year Groups'));
    $form->addHiddenValue('gibbonReportID', $gibbonReportID);

    $bulkActions = array(
        'Generate' => __('Generate'),
    );

    $col = $form->createBulkActionColumn($bulkActions);
        $col->addSelect('status')
            ->fromArray(['Draft' => __('Draft'), 'Final' => __('Final')])
            ->required()
            ->setClass('w-32');
        $col->addSubmit(__('Go'));

    // Data TABLE
    $table = $form->addRow()->addDataTable('reportsGenerate', $criteria)->withData($reports);

    $table->addMetaData('bulkActions', $col);

    $table->addColumn('name', __('Name'));

    $table->addColumn('count', __('Count'))
        ->width('8%')
        ->notSortable()
        ->format(function ($report) use (&$pdo, &$context) {
            if (empty($report['gibbonYearGroupID']) || empty($report['gibbonSchoolYearID'])) {
                return __('N/A');
            }

            $ids = $context->getIdentifiers($pdo, $report['gibbonReportID'], $report['gibbonYearGroupID']);
            return count($ids);
        });

    $table->addColumn('timestamp', __('Last Created'))
        ->notSortable()
        ->format(function ($report) use ($gibbonReportID, &$reportArchiveEntryGateway, &$logs) {
            $reportLogs = $logs[$report['gibbonYearGroupID']] ?? [];
            if (count($reportLogs) > 0) {
                return '<div class="statusBar" data-id="'.($reportLogs['processID'] ?? '').'" data-context="'.($report['gibbonYearGroupID'] ?? '').'" data-report="'.$gibbonReportID.'">'
                      .'<img class="align-middle w-56 -mt-px" src="./themes/Default/img/loading.gif">'
                      .'<span class="tag ml-2 message">'.__('Running').'</span></div>';
            }

            $archive = $reportArchiveEntryGateway->getRecentArchiveEntryByReport($gibbonReportID, 'Batch', $report['gibbonYearGroupID'], 'Staff', true, true);

            if ($archive) {
                $tag = '<span class="tag ml-2 '.($archive['status'] == 'Final' ? 'success' : 'dull').'">'.__($archive['status']).'</span>';
                $url = './modules/Reports/archive_byReport_download.php?gibbonReportArchiveEntryID='.$archive['gibbonReportArchiveEntryID'];
                $title = Format::dateTimeReadable($archive['timestampModified']);
                return Format::link($url, $title).$tag;
            }

            return '';
        });

    $table->addActionColumn()
        ->addParam('gibbonReportID', $gibbonReportID)
        ->format(function ($report, $actions) use (&$logs) {
            $reportLogs = $logs[$report['gibbonYearGroupID']] ?? [];
            
            if (count($reportLogs) == 0) {
                $actions->addAction('run', __('Run'))
                        ->setIcon('run')
                        ->isModal(650, 135)
                        ->addParam('contextData', $report['gibbonYearGroupID'])
                        ->setURL('/modules/Reports/reports_generate_batchConfirm.php');
            } else {
                $actions->addAction('cancel', __('Cancel'))
                        ->setIcon('iconCross')
                        ->isModal(650, 135)
                        ->addParam('contextData', $report['gibbonYearGroupID'])
                        ->addParam('processID', $reportLogs['processID'])
                        ->setURL('/modules/Reports/reports_generate_cancelConfirm.php');
            }

            $actions->addAction('go', __('Go'))
                    ->setIcon('page_right')
                    ->addParam('contextData', $report['gibbonYearGroupID'])
                    ->setURL('/modules/Reports/reports_generate_single.php');
        });

    $table->addCheckboxColumn('contextData', 'gibbonYearGroupID');

    echo $form->getOutput();
}
?>
<script>
$('.statusBar').each(function(index, element) {
    var refresh = setInterval(function () {
        var path = "<?php echo $_SESSION[$guid]['absoluteURL'] ?>/modules/Reports/reports_generate_ajax.php";
        var postData = { gibbonLogID: $(element).data('id'), gibbonReportID: $(element).data('report'), contextID: $(element).data('context') };
        $(element).load(path, postData, function(responseText, textStatus, jqXHR) {
            if (responseText.indexOf('Complete') >= 0) {
                clearInterval(refresh);
                $("[title='Cancel']").remove();
            }
        });
    }, 3000);
});
</script>
