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
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Manage Archives'));

    $page->return->addReturns(['success1' => __('Import successful. {count} records were imported.', ['count' => '<b>'.($_GET['imported'] ?? '0').'</b>'])]);

    $reportArchiveGateway = $container->get(ReportArchiveGateway::class);

    // QUERY
    $criteria = $reportArchiveGateway->newQueryCriteria(true)
        ->sortBy('name', 'ASC')
        ->fromPOST();

    $reports = $reportArchiveGateway->queryArchives($criteria);

    // GRID TABLE
    $table = DataTable::createPaginated('reportsManage', $criteria);
    $table->setTitle(__('View'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Reports/archive_manage_add.php')
        ->displayLabel()
        ->append(' | ');

    $table->addHeaderAction('migrate', __('Migrate Reports'))
        ->setIcon('delivery2')
        ->setURL('/modules/Reports/archive_manage_migrate.php')
        ->displayLabel();

    $table->addColumn('name', __('Name'));
    $table->addColumn('path', __('Path'));
    $table->addColumn('readonly', __('Read Only'))->format(Format::using('yesNo', 'readonly'));

    $table->addActionColumn()
        ->addParam('gibbonReportArchiveID')
        ->format(function ($report, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Reports/archive_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Reports/archive_manage_delete.php');
        });

    echo $table->render($reports);
}
