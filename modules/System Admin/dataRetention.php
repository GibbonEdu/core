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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\DataRetentionGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/dataRetention.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Data Retention'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $page->addMessage(__('Comply with privacy regulations by flushing older, non-academic, data from the system.')." ".__('This action will scrub selected data for all users in the specified category whose status is Left, and whose end date preceeds the specified data. This process clears certain fields, rather than removing any database rows.'));

    $form = Form::create('dataRetention', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/dataRetentionProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $categories = array(
        'Staff'   => __('Staff'),
        'Student' => __('Student'),
        'Parent'  => __('Parent'),
        'Other'   => __('Other'),
    );
    $row = $form->addRow();
        $row->addLabel('category', __('Category'))->description(__('Based on Primary Role only'));
        $row->addSelect('category')->fromArray($categories)->required()->placeholder();

    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addTextField('status')->readonly()->required()->setValue(__('Left'));

    $row = $form->addRow();
        $row->addLabel('date', __('Date'))->description(__("Include users with an end date preceeding this date.")."<br/>".__("Last login is used as a fallback"));
        $row->addDate('date')->required();

    $dataRetentionGateway = $container->get(DataRetentionGateway::class);
    $tables = array_keys($dataRetentionGateway->getAllTables());

    $checked = explode(",", $container->get(SettingGateway::class)->getSettingByScope('System', 'dataRetentionTables'));

    $row = $form->addRow();
        $row->addLabel('tables', __('Tables'))->description(__('Database tables to scrub.')."<br/>".__('The current selection will persist.'));
        $row->addCheckbox('tables')->fromArray($tables)->addCheckAllNone()->checked($checked);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
