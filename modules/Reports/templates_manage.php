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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Template Builder'));

    $templateGateway = $container->get(ReportTemplateGateway::class);

    // QUERY
    $criteria = $templateGateway->newQueryCriteria(true)
        ->sortBy('name', 'ASC')
        ->filterBy('active', $_GET['active'] ?? 'Y')
        ->fromPOST();

    $templates = $templateGateway->queryTemplates($criteria);

    // GRID TABLE
    $table = DataTable::createPaginated('templates', $criteria);
    $table->setTitle(__('Template Library'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Reports/templates_manage_add.php')
        ->displayLabel();
        
    $table->addHeaderAction('fonts', __('Manage Fonts'))
        ->setIcon('delivery2')
        ->setURL('/modules/Reports/templates_assets_fonts.php')
        ->displayLabel();

    $table->addHeaderAction('assets', __('Manage Assets'))
        ->setIcon('delivery2')
        ->setURL('/modules/Reports/templates_assets.php')
        ->displayLabel();

    $table->addMetaData('gridClass', 'content-center justify-center');
    $table->addMetaData('gridItemClass', 'w-1/2 sm:w-1/3 text-center mb-4');

    $table->addMetaData('filterOptions', [
        'active:Y'          => __('Active').': '.__('Yes'),
        'active:N'          => __('Active').': '.__('No'),
    ]);

    $table->modifyRows(function($values, $row) {
        if (!empty($values['active']) && $values['active'] != 'Y') $row->addClass('error');
        return $row;
    });

    $table->addColumn('name', __('Name'));
    $table->addColumn('context', __('Context'));

    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

    $table->addActionColumn()
        ->addParam('gibbonReportTemplateID')
        ->format(function ($template, $actions) {
            $actions->addAction('view', __('Preview'))
                    ->setURL('/modules/Reports/templates_preview.php')
                    ->addParam('TB_iframe', 'true')
                    ->modalWindow(900, 500);

            $actions->addAction('edit', __('Edit'))
                    ->addParam('sidebar', 'false')
                    ->setURL('/modules/Reports/templates_manage_edit.php');

            $actions->addAction('copy', __('Duplicate'))
                    ->setIcon('copy')
                    ->setURL('/modules/Reports/templates_manage_duplicate.php');

            if ($template['active'] == 'N') {
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Reports/templates_manage_delete.php');
            }
        });

    echo $table->render($templates);
}
