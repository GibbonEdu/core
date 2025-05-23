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

use Gibbon\Domain\System\EmailTemplateGateway;
use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reports_send_batch.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonReportID = $_GET['gibbonReportID'] ?? '';
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? $_GET['contextData'] ?? '';
    $contextData = $_GET['gibbonYearGroupID'] ?? $_GET['contextData'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Send Reports'), 'reports_send.php')
        ->add(__('Select Reports'));

    $roleCategory = $session->get('gibbonRoleIDCurrentCategory');

    $familyGateway = $container->get(FamilyGateway::class);
    $reportGateway = $container->get(ReportGateway::class);
    $reportArchiveGateway = $container->get(ReportArchiveGateway::class);
    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);

    $report = $reportGateway->getByID($gibbonReportID);
    if (empty($gibbonReportID) || empty($report) ) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $archive = $reportArchiveGateway->getByID($report['gibbonReportArchiveID'] ?? '');
    if (empty($archive) || $archive['viewableParents'] != 'Y') {
        echo Format::alert(__('This report is in an archive that is not viewable by {roleCategory}.', ['roleCategory' => __('Parents')]));
        return;
    }

    $criteria = $reportGateway->newQueryCriteria()
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();

    $reports = $reportArchiveEntryGateway->queryArchiveByReport($criteria, $gibbonReportID, $gibbonYearGroupID, 'All', $roleCategory, false, true);

    $studentIDs = $reports->getColumn('gibbonPersonID');
    $familyAdults = $familyGateway->selectFamilyAdultsByStudent($studentIDs, true)->fetchGrouped();
    $reports->joinColumn('gibbonPersonID', 'familyAdults', $familyAdults);

    // FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/Reports/reports_send_batchProcess.php');
    $form->setTitle($report['name']);
    $form->setDescription(__('This process will send a templated email to each recipient. To customize this email, you can edit the template called {templateName} on the {pageLink} page.', ['templateName' => '<b>'.__('Send Reports to Parents').'</b>', 'pageLink' => Format::link('./index.php?q=/modules/System Admin/emailTemplates_manage.php', __('Email Templates'))]));

    $form->addHiddenValue('gibbonReportID', $gibbonReportID);
    $form->addHiddenValue('contextData', $contextData);
    $form->addHiddenValue('search', $search);

    $bulkActions = [
        'Send Reports to Parents' => __('Send Reports to Parents'),
        'Send Reports to Students' => __('Send Reports to Students'),
    ];

    $templates = $container->get(EmailTemplateGateway::class)->selectTemplatesByModule('Reports', 'Send Reports%')->fetchAll();
    $templateOptions = [__('Email Templates') => array_combine(array_column($templates, 'templateName'), array_column($templates, 'templateName'))];
    $templateChained = array_combine(array_column($templates, 'templateName'), array_column($templates, 'templateType'));

    $col = $form->createBulkActionColumn($bulkActions);
        $col->addSelect('templateName')
            ->fromArray($templateOptions)
            ->chainedTo('action', $templateChained)
            ->required()
            ->placeholder();
        $col->addSubmit(__('Go'));

    // Data TABLE
    $table = $form->addRow()->addDataTable('reportsSend', $reportGateway->newQueryCriteria())->withData($reports);

    $table->addMetaData('bulkActions', $col);

    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->width('25%')
        ->format(function ($person) {
            return Format::nameLinked($person['gibbonPersonID'], '', $person['preferredName'], $person['surname'], 'Student', true, false, ['subpage' => 'Reports']);
        })
        ->formatDetails(function ($person) {
            return Format::small($person['email'] ?? '');
        });

    $table->addColumn('timestampModified', __('Last Created'))
        ->format(function ($report) {

            $tag = '<span class="tag ml-2 '.($report['status'] == 'Final' ? 'success' : 'dull').'">'.__($report['status']).'</span>';
            $title = Format::dateTimeReadable($report['timestampModified']);
            $url = './modules/Reports/archive_byStudent_download.php?gibbonReportArchiveEntryID='.$report['gibbonReportArchiveEntryID'].'&gibbonPersonID='.$report['gibbonPersonID'];
            return Format::link($url, $title).$tag;
        });

    $view = new View($container->get('twig'));
    $table->addColumn('contacts', __('Parental Contacts'))
        ->width('35%')
        ->notSortable()
        ->format(function ($report) use ($view) {
            $familyAdults = array_filter($report['familyAdults'], function ($item) {
                return !empty($item['email']);
            });
            if (empty($familyAdults)) return Format::small(__('No email address found.'));

            return $view->fetchFromTemplate(
                'formats/familyContacts.twig.html',
                ['familyAdults' => $report['familyAdults']]
            );
        });

    $table->addColumn('timestampSent', __('Sent'))
        ->format(function ($report) use ($session) {
            $title = Format::name($report['parentTitle'], $report['parentPreferredName'], $report['parentSurname'], 'Parent', false).': '.Format::relativeTime($report['timestampAccessed'], false);

            if ($report['timestampSent'] == '0000-00-00 00:00:00') {
                return Format::tooltip(icon('solid', 'refresh', 'size-6 fill-current text-gray-600 opacity-75'), __('Sending'));
            } elseif (!empty($report['timestampSent']) && !empty($report['timestampAccessed'])) {
                return Format::tooltip(icon('solid', 'check', 'size-6 fill-current text-green-600'), __('Sent & Read').': '.$title);
            } elseif (!empty($report['timestampSent'])) {
                return Format::tooltip(icon('solid', 'check', 'size-6 fill-current text-gray-400'), __('Sent').': '.Format::relativeTime($report['timestampSent'], false));
            } elseif (!empty($report['timestampAccessed'])) {
                return Format::tooltip(icon('solid', 'check', 'size-6 fill-current text-green-600'), __('Read Online').': '.$title);
            }

            return '';
        });

    $table->addCheckboxColumn('identifier', 'gibbonReportArchiveEntryID')
        ->format(function ($report) {
            $emails = array_filter(array_column($report['familyAdults'], 'email'));
            if (empty($report['email']) && empty($emails)) return Format::small(__('N/A'));
        });

    echo $form->getOutput();
}
