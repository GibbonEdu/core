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
use Gibbon\Domain\School\RollGroupGateway;

if (isActionAccessible($guid, $connection2, '/modules/Roll Groups/rollGroups.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__('View Roll Groups').'</div>';
    echo '</div>';

    echo '<p>';
    echo __('This page shows all roll groups in the current school year.');
    echo '</p>';

    $gateway = $container->get(RollGroupGateway::class);
    $rollGroups = $gateway->selectRollGroupsBySchoolYear($_SESSION[$guid]['gibbonSchoolYearID']);
    
    $formatTutorsList = function($row) use ($gateway) {
        $tutors = $gateway->selectTutorsByRollGroup($row['gibbonRollGroupID'])->fetchAll();
        if (count($tutors) > 1) $tutors[0]['surname'] .= ' ('.__('Main Tutor').')';

        return Format::nameList($tutors, 'Staff', false, true);
    };

    $table = DataTable::create('rollGroups');

    $table->addColumn('name', __('Name'));
    $table->addColumn('tutors', __('Form Tutors'))->format($formatTutorsList);
    $table->addColumn('space', __('Room'));
    $table->addColumn('students', __('Students'));
    $table->addColumn('website', __('Website'))->format(Format::using('link', 'website'));

    $actions = $table->addActionColumn()->addParam('gibbonRollGroupID');
    $actions->addAction('view', __('View'))
            ->setURL('/modules/Roll Groups/rollGroups_details.php');

    echo $table->render($rollGroups->toDataSet());
}
