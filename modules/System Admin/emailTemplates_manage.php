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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\EmailTemplateGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/emailTemplates_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Email Templates'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $emailTemplateGateway = new EmailTemplateGateway($pdo);

    $criteria = $emailTemplateGateway->newQueryCriteria()
        ->sortBy(['gibbonModule.type', 'templateName'])
        ->fromPOST();
    $templates = $emailTemplateGateway->queryEmailTemplates($criteria);

    $table = DataTable::createPaginated('EmailTemplates', $criteria);
    $table->setTitle(__('Email Templates'));
    $table->setDescription(__('These templates enable you to customize emails sent by Gibbon using a Twig template syntax. For more information about how to write template code, visit the {link}.', ['link' => Format::link('https://twig.symfony.com/doc/2.x/', __('Twig Documentation'))]));

    $table->addColumn('moduleName', __('Module'))->translatable();
    $table->addColumn('templateName', __('Name'));
    $table->addColumn('moduleType', __('Type'));

    $actions = $table->addActionColumn()
        ->addParam('gibbonEmailTemplateID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/System Admin/emailTemplates_manage_edit.php');
        });

    echo $table->render($templates);
}
