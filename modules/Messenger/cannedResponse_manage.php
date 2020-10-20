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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;

$page->breadcrumbs->add(__('Manage Canned Responses'));

if (isActionAccessible($guid, $connection2, '/modules/Messenger/cannedResponse_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonMessengerCannedResponse ORDER BY subject';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    $moduleName = $gibbon->session->get('module');

    $table = DataTable::create('cannedResponses');

    $table->addHeaderAction('add', __('Add'))
        ->displayLabel()
        ->setURL('/modules/' . $moduleName . '/cannedResponse_manage_add.php');

    $table->addColumn('subject', __('Subject'));

    $table->addActionColumn()
        ->addParam('gibbonMessengerCannedResponseID')
        ->format(function ($cannedResponse, $actions) use ($moduleName) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/' . $moduleName . '/cannedResponse_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/' . $moduleName . '/cannedResponse_manage_delete.php');
        });

    echo $table->render($result->toDataSet());
}
