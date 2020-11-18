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

require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/logs_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('View Logs'), 'logs_view.php')
        ->add(__('Purge Logs'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('logs', $_SESSION[$guid]['absoluteURL'].'/modules/System Admin/logs_view_purgeProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $sql = "SELECT DISTINCT title AS value, title AS name FROM gibbonLog ORDER BY title";
    $row = $form->addRow();
        $row->addLabel('title', __('Title'));
        $row->addSelect('title')->fromQuery($pdo, $sql)->selectMultiple()->required();

    $row = $form->addRow();
        $row->addLabel('cutoffDate', __('Cutoff Date'))->description(__('Delete all logs older than this date.'));
        $row->addDate('cutoffDate')->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
