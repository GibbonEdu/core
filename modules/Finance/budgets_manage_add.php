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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgets_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Budgets'),'budgets_manage.php')
        ->add(__('Add Budget'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Finance/budgets_manage_edit.php&gibbonFinanceBudgetID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);


    $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/budgets_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading('General Settings', __('General Settings'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->maxLength(100)->required();

    $row = $form->addRow();
        $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
        $row->addTextField('nameShort')->maxLength(8)->required();

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $categories = $container->get(SettingGateway::class)->getSettingByScope('Finance', 'budgetCategories');
    if (empty($categories)) {
        $categories = 'Other';
    }
    $row = $form->addRow();
        $row->addLabel('category', __('Category'));
        $row->addSelect('category')->fromString($categories)->placeholder()->required();

    $form->addRow()->addHeading('Staff', __('Staff'));

    $row = $form->addRow();
        $row->addLabel('staff', __('Staff'));
        $row->addSelectStaff('staff')->selectMultiple();

    $access = array(
        "Full" => __("Full"),
        "Write" => __("Write"),
        "Read" => __("Read")
    );
    $row = $form->addRow();
        $row->addLabel('access', __('Access'));
        $row->addSelect('access')->fromArray($access);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
