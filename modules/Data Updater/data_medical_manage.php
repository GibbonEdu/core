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
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\DataUpdater\MedicalUpdateGateway;

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_medical_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Medical Data Updates'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = isset($_REQUEST['gibbonSchoolYearID'])? $_REQUEST['gibbonSchoolYearID'] : $_SESSION[$guid]['gibbonSchoolYearID'];

    // School Year Picker
    if (!empty($gibbonSchoolYearID)) {
        $schoolYearGateway = $container->get(SchoolYearGateway::class);
        $targetSchoolYear = $schoolYearGateway->getSchoolYearByID($gibbonSchoolYearID);

        echo '<h2>';
        echo $targetSchoolYear['name'];
        echo '</h2>';

        echo "<div class='linkTop'>";
            if ($prevSchoolYear = $schoolYearGateway->getPreviousSchoolYearByID($gibbonSchoolYearID)) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q'].'&gibbonSchoolYearID='.$prevSchoolYear['gibbonSchoolYearID']."'>".__('Previous Year').'</a> ';
            } else {
                echo __('Previous Year').' ';
            }
			echo ' | ';
			if ($nextSchoolYear = $schoolYearGateway->getNextSchoolYearByID($gibbonSchoolYearID)) {
				echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q'].'&gibbonSchoolYearID='.$nextSchoolYear['gibbonSchoolYearID']."'>".__('Next Year').'</a> ';
			} else {
				echo __('Next Year').' ';
			}
        echo '</div>';
    }

    $gateway = $container->get(MedicalUpdateGateway::class);

    // QUERY
    $criteria = $gateway->newQueryCriteria(true)
        ->sortBy('status')
        ->sortBy('timestamp', 'DESC')
        ->fromPOST();

    $dataUpdates = $gateway->queryDataUpdates($criteria, $gibbonSchoolYearID);

    // DATA TABLE
    $table = DataTable::createPaginated('medicalUpdateManage', $criteria);

    $table->modifyRows(function ($update, $row) {
        if ($update['status'] != 'Pending') $row->addClass('current');
        return $row;
    });

    // COLUMNS
    $table->addColumn('target', __('Target User'))
        ->sortable(['target.surname', 'target.preferredName'])
        ->format(Format::using('name', ['', 'preferredName', 'surname', 'Student']));
    $table->addColumn('updater', __('Requesting User'))
        ->sortable(['updater.surname', 'updater.preferredName'])
        ->format(Format::using('name', ['updaterTitle', 'updaterPreferredName', 'updaterSurname', 'Parent']));
    $table->addColumn('timestamp', __('Date & Time'))->format(Format::using('dateTime', 'timestamp'));
    $table->addColumn('status', __('Status'))->translatable()->width('12%');

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('gibbonPersonMedicalUpdateID')
        ->format(function ($update, $actions) {
            if ($update['status'] == 'Pending') {
                $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Data Updater/data_medical_manage_edit.php');

                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Data Updater/data_medical_manage_delete.php');
            }
        });

    echo $table->render($dataUpdates);
}
