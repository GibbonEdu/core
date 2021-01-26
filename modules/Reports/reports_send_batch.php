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
        ->add(__('Send Reports'), 'reports_generate.php')
        ->add(__('Select Reports'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $roleCategory = getRoleCategory($gibbon->session->get('gibbonRoleIDCurrent'), $connection2);
    
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
    $form = BulkActionForm::create('bulkAction', $gibbon->session->get('absoluteURL').'/modules/Reports/reports_send_batchProcess.php');
    $form->setTitle($report['name']);
    $form->setDescription(__('This process will send a templated email to each recipient. To customize this email, you can edit the template called {templateName} on the {pageLink} page.', ['templateName' => '<b>'.__('Send Reports to Parents').'</b>', 'pageLink' => Format::link('./index.php?q=/modules/System Admin/emailTemplates_manage.php', __('Email Templates'))]));

    $form->addHiddenValue('gibbonReportID', $gibbonReportID);
    $form->addHiddenValue('contextData', $contextData);
    $form->addHiddenValue('search', $search);

    $bulkActions = array(
        'parents' => __('Send Reports to Parents'),
        'students' => __('Send Reports to Students'),
    );

    $col = $form->createBulkActionColumn($bulkActions);
        $col->addSubmit(__('Go'));

    // Data TABLE
    $table = $form->addRow()->addDataTable('reportsSend', $reportGateway->newQueryCriteria())->withData($reports);

    $table->addMetaData('bulkActions', $col);

    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->width('25%')
        ->format(function ($person) {
            return Format::nameLinked($person['gibbonPersonID'], '', $person['preferredName'], $person['surname'], 'Student', true, false, ['subpage' => 'Reports']);
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
        ->format(function ($report) use ($guid) {
            $title = Format::name($report['parentTitle'], $report['parentPreferredName'], $report['parentSurname'], 'Parent', false).': '.Format::relativeTime($report['timestampAccessed'], false);

            if ($report['timestampSent'] == '0000-00-00 00:00:00') {
                return '<img src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/refresh.png" title="'.__('Sending').'" class="opacity-75">';
            } elseif (!empty($report['timestampSent']) && !empty($report['timestampAccessed'])) {
                return '<img src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/iconTick_double.png" title="'.__('Sent & Read').': '.$title.'">';
            } elseif (!empty($report['timestampSent'])) {
                return '<img src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/iconTick.png" title="'.__('Sent').': '.Format::relativeTime($report['timestampSent'], false).'">';
            } elseif (!empty($report['timestampAccessed'])) {
                return '<img src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/iconTick_light.png" title="'.__('Read Online').': '.$title.'">';
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
