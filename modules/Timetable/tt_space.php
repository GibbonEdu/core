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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\School\FacilityGateway;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_space.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Timetable by Facility').'</div>';
        echo '</div>';

        $gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : null;
        $search = isset($_GET['search'])? $_GET['search'] : '';

        $facilityGateway = $container->get(FacilityGateway::class);

        // CRITERIA
        $criteria = $facilityGateway->newQueryCriteria()
            ->searchBy($facilityGateway->getSearchableColumns(), $search)
            ->sortBy('name')
            ->fromArray($_POST);

        echo '<h2>';
        echo __('Search');
        echo '</h2>';

        $form = Form::create('ttSpace', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/tt_space.php');

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'));
            $row->addTextField('search')->setValue($criteria->getSearchText());

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Search'));

        echo $form->getOutput();

        echo '<h2>';
        echo __('Choose A Facility');
        echo '</h2>';

        $facilities = $facilityGateway->queryFacilities($criteria);

        // DATA TABLE
        $table = DataTable::createPaginated('timetableByFacility', $criteria);

        $table->addColumn('name', __('Name'));
        $table->addColumn('type', __('Type'));

        $table->addActionColumn()
            ->addParam('gibbonSpaceID')
            ->format(function ($row, $actions) {
                $actions->addAction('view', __('View'))
                        ->setURL('/modules/Timetable/tt_space_view.php');
            });

        echo $table->render($facilities);
    }
}
