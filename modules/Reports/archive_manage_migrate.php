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

use Gibbon\Domain\System\ModuleGateway;
use Gibbon\Forms\Form;
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_manage_migrate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Archives'), 'archive_manage.php')
        ->add(__('Migrate Reports'));

    $reportingModule = $container->get(ModuleGateway::class)->selectBy(['name' => 'Reporting', 'author' => 'Andy Statham'])->fetch();
    $reportingArchiveTable = $pdo->selectOne("SHOW TABLES LIKE 'arrArchive'");

    if (empty($reportingModule)) {
        $page->addError(__('This tool enables you to migrate archived reports from the Reporting module by Andy Statham.').' '.__('You do not have the {moduleName} module installed.', ['moduleName' => 'Reporting']));
        return;
    } elseif (empty($reportingArchiveTable)) {
        $page->addError(__('This tool enables you to migrate archived reports from the Reporting module by Andy Statham.').' '.__('The {tableName} table does not exist in the database.', ['tableName' => 'arrArchive']));
        return;
    }

    $reportingArchiveCount = $pdo->selectOne("SELECT COUNT(*) FROM arrArchive");
    $page->addAlert(__('This tool enables you to migrate archived reports from the Reporting module by Andy Statham.').' '.__('There are {count} records in the {tableName} table.', ['count' => '<b>'.$reportingArchiveCount.'</b>', 'tableName' => 'arrArchive']), $reportingArchiveCount > 0 ? 'message' : 'error');

    if (empty($reportingArchiveCount)) {
        return;
    }

    $form = Form::create('archiveImport', $session->get('absoluteURL').'/modules/Reports/archive_manage_migrateProcess.php');
    $form->addHiddenValue('address', $session->get('address'));

    $archives = $container->get(ReportArchiveGateway::class)->selectWriteableArchives()->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonReportArchiveID', __('Archive'))->description(__('The selected archive determines where files are saved and who can access them.'));
        $row->addSelect('gibbonReportArchiveID')->fromArray($archives)->required()->placeholder();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
