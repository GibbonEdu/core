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
use Gibbon\Domain\Staff\StaffJobOpeningGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/jobOpenings_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Job Openings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $jobGateway = $container->get(StaffJobOpeningGateway::class);

    // QUERY
    $criteria = $jobGateway->newQueryCriteria()
        ->sortBy(['dateOpen', 'jobTitle'])
        ->fromArray($_POST);

    $jobs = $jobGateway->queryJobOpenings($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('jobOpeningsManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Staff/jobOpenings_manage_add.php')
        ->displayLabel();

    $table->modifyRows(function ($values, $row) {
        if ($values['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'active:Y' => __('Active').': '.__('Yes'),
        'active:N' => __('Active').': '.__('No'),
    ]);

    // COLUMNS
    $table->addColumn('type', __('Type'));
    $table->addColumn('jobTitle', __('Job Title'));
    $table->addColumn('dateOpen', __('Opening Date'))
        ->format(Format::using('date', 'dateOpen'));
    $table->addColumn('active', __('Active'))
        ->format(Format::using('yesNo', 'active'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonStaffJobOpeningID')
        ->addParam('search', $criteria->getSearchText(true))
        ->format(function ($person, $actions) use ($guid) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Staff/jobOpenings_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Staff/jobOpenings_manage_delete.php');
        });

    echo $table->render($jobs);
}
