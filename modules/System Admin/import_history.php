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

use Gibbon\Data\ImportType;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;
use Gibbon\Domain\System\LogGateway;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, "/modules/System Admin/import_history.php")==false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs
        ->add(__('Import From File'), 'import_manage.php')
        ->add(__('Import History'));

    // Get a list of available import options
    $importTypeList = ImportType::loadImportTypeList($pdo, false);

    $logGateway = $container->get(LogGateway::class);
    $logsByType = $logGateway->selectLogsByModuleAndTitle('System Admin', 'Import - %')->fetchAll();

    $logsByType = array_map(function ($log) use (&$importTypeList) {
        $log['data'] = isset($log['serialisedArray'])? unserialize($log['serialisedArray']) : [];
        $log['importType'] = @$importTypeList[$log['data']['type']];
        return $log['importType'] ? $log : null;
    }, $logsByType);
    $logsByType = array_filter($logsByType);

    $table = DataTable::create('importHistory');
    $table->setTitle(__('Import History'));

    $table->addColumn('timestamp', __('Date'))
        ->format(Format::using('dateTime', 'timestamp'));

    $table->addColumn('user', __('User'))
        ->format(Format::using('name', ['', 'preferredName', 'surname', 'Staff', false, true]));

    $table->addColumn('category', __('Category'))
        ->format(function ($log) {
            return $log['importType']->getDetail('category');
        });

    $table->addColumn('name', __('Name'))
        ->format(function ($log) {
            return $log['importType']->getDetail('name');
        });
        
    $table->addColumn('details', __('Details'))
        ->format(function ($log) {
            return !empty($log['data']['success']) ? __('Success') : __('Failed');
        });

    $table->addActionColumn()
        ->addParam('gibbonLogID')
        ->format(function ($importType, $actions) {
            $actions->addAction('view', __('View'))
                ->modalWindow('600', '550')
                ->setURL('/modules/System Admin/import_history_view.php');
        });

    echo $table->render(new DataSet($logsByType));
}
