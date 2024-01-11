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
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\CustomFieldGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/customFields.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Custom Fields'));

    $customFieldGateway = $container->get(CustomFieldGateway::class);
    $customFieldHandler = $container->get(CustomFieldHandler::class);
    
    // Get a flattened array of the custom field types for listing in the table
    $customFieldTypes = [];
    $types = $container->get(CustomFieldHandler::class)->getTypes();
    array_walk_recursive($types, function($item, $key) use (&$customFieldTypes) { $customFieldTypes[$key] = $item; });

    $contexts = [];
    foreach ($customFieldHandler->getContexts() as $group => $groupContexts) {
        $contexts += $groupContexts;
    }

    $additionalContexts = $customFieldGateway->selectCustomFieldsContexts()->fetchAll(\PDO::FETCH_COLUMN);
    foreach ($additionalContexts as $index => $context) {
        if (!empty($contexts[$context])) {
            unset($additionalContexts[$index]);
            continue;
        }
        $contexts[$context] = __($context);
    }

    foreach ($contexts as $context => $contextName) {
        // QUERY
        $criteria = $customFieldGateway->newQueryCriteria()
            ->sortBy(['sequenceNumber', 'name'])
            ->filterBy('context', $context)
            ->pageSize(0)
            ->fromPOST();

        $customFields = $customFieldGateway->queryCustomFields($criteria);

        if (count($customFields) == 0 && $context != 'User') continue;

        // DATA TABLE
        $table = DataTable::create('customFields'.$context);
        $table->setTitle(__('{context} Fields', ['context' => $contextName]));

        $table->modifyRows(function ($customField, $row) {
            if ($customField['active'] == 'N') $row->addClass('error');
            return $row;
        });

        $action = $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/System Admin/customFields_add.php')
            ->addParam('context', $context != 'User' ? $context : '')
            ->displayLabel();

        if (in_array($context, $additionalContexts)) {
            $action->addParam('context', 'Custom')
                   ->addParam('contextName', $context);
        }

        $table->addDraggableColumn('gibbonCustomFieldID', $session->get('absoluteURL').'/modules/System Admin/customFields_editOrderAjax.php');

        $table->addColumn('name', __('Name'))
            ->description(__('Heading'))
            ->format(function($values) {
                return $values['name'].'<br/>'.Format::small(__($values['heading']));
            });

        $table->addColumn('type', __('Type'))
            ->format(function ($values) use ($customFieldTypes) {
                return isset($customFieldTypes[$values['type']])? $customFieldTypes[$values['type']] : '';
            });

        $table->addColumn('active', __('Active'))->width('10%')->format(Format::using('yesNo', 'active'));

        $table->addColumn('required', __('Required'))->width('10%')->format(Format::using('yesNo', 'required'));

        if ($context == 'User') {
            $table->addColumn('roles', __('Role Categories'))
                ->format(function ($values) {
                    $output = '';
                    if ($values['activePersonStudent']) $output .= __('Student').'<br/>';
                    if ($values['activePersonParent']) $output .= __('Parent').'<br/>';
                    if ($values['activePersonStaff']) $output .= __('Staff').'<br/>';
                    if ($values['activePersonOther']) $output .= __('Other').'<br/>';
                    return $output;
                });
        }

        $table->addActionColumn()
            ->addParam('gibbonCustomFieldID')
            ->format(function ($values, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/System Admin/customFields_edit.php');
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/System Admin/customFields_delete.php');
                
            });
            
        echo $table->render($customFields);
    }
}
