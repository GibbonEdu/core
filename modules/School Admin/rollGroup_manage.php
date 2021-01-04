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
use Gibbon\Domain\RollGroups\RollGroupGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/rollGroup_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonRollGroupID = $_GET['gibbonRollGroupID'] ?? '';

    $page->breadcrumbs->add(__('Manage Roll Groups'));

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
        
    $rollGroupGateway = $container->get(RollGroupGateway::class);

    // QUERY
    $criteria = $rollGroupGateway->newQueryCriteria(true)
        ->sortBy(['sequenceNumber', 'gibbonRollGroup.name'])
        ->fromPOST();

    $rollGroups = $rollGroupGateway->queryRollGroups($criteria, $gibbonSchoolYearID);

    $formatTutorsList = function($row) use ($rollGroupGateway) {
        $tutors = $rollGroupGateway->selectTutorsByRollGroup($row['gibbonRollGroupID'])->fetchAll();
        if (count($tutors) > 1) $tutors[0]['surname'] .= ' ('.__('Main Tutor').')';

        return Format::nameList($tutors, 'Staff', false, true);
    };

    // DATA TABLE
    $table = DataTable::createPaginated('rollGroupManage', $criteria);

    if (!empty($nextSchoolYear)) {
        $table->addHeaderAction('copy', __('Copy All To Next Year'))
            ->setURL('/modules/School Admin/rollGroup_manage_copyProcess.php')
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('gibbonSchoolYearIDNext', $nextSchoolYear['gibbonSchoolYearID'])
            ->setIcon('copy')
            ->onClick('return confirm("'.__('Are you sure you want to continue?').' '.__('This operation cannot be undone.').'");')
            ->displayLabel()
            ->directLink()
            ->append('&nbsp;|&nbsp;');
    }

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/rollGroup_manage_add.php')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->displayLabel();

    $table->addColumn('name', __('Name'))
          ->description(__('Short Name'))
          ->format(function ($rollGroup) {
            return '<strong>' . $rollGroup['name'] . '</strong><br/><small><i>' . $rollGroup['nameShort'] . '</i></small>';
          });
    $table->addColumn('tutors', __('Form Tutors'))->sortable(false)->format($formatTutorsList);
    $table->addColumn('space', __('Location'));
    $table->addColumn('website', __('Website'))
            ->format(Format::using('link', ['website']));
        
    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonRollGroupID')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->format(function ($rollGroup, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/rollGroup_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/School Admin/rollGroup_manage_delete.php');
        });

    echo $table->render($rollGroups);
}
