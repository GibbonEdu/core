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
use Gibbon\Services\Format;
use Gibbon\Domain\School\FacilityGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/space_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__('Manage Facilities').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $search = isset($_GET['search'])? $_GET['search'] : '';

    $facilityGateway = $container->get(FacilityGateway::class);

    // QUERY
    $criteria = $facilityGateway->newQueryCriteria()
        ->searchBy($facilityGateway->getSearchableColumns(), $search)
        ->sortBy(['name'])
        ->fromArray($_POST);

    echo '<h3>';
    echo __('Search');
    echo '</h3>';

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/space_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    echo '<h3>';
    echo __('View');
    echo '</h3>';

    $facilities = $facilityGateway->queryFacilities($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('facilityManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/space_manage_add.php')
        ->addParam('search', $search)
        ->displayLabel();

    $table->addColumn('name', __('Name'));
    $table->addColumn('type', __('Type'));
    $table->addColumn('capacity', __('Capacity'));
    $table->addColumn('facilities', __('Facilities'))
        ->notSortable()
        ->format(function($values) { 
            $return = null;
            $return .= ($values['computer'] == 'Y') ? __('Teaching computer').'<br/>':'';
            $return .= ($values['computerStudent'] > 0) ? $values['computerStudent'].' '.__('student computers').'<br/>':'';
            $return .= ($values['projector'] == 'Y') ? __('Projector').'<br/>':'';
            $return .= ($values['tv'] == 'Y') ? __('TV').'<br/>':'';
            $return .= ($values['dvd'] == 'Y') ? __('DVD Player').'<br/>':'';
            $return .= ($values['hifi'] == 'Y') ? __('Hifi').'<br/>':'';
            $return .= ($values['speakers'] == 'Y') ? __('Speakers').'<br/>':'';
            $return .= ($values['iwb'] == 'Y') ? __('Interactive White Board').'<br/>':'';
            $return .= ($values['phoneInternal'] != '') ? __('Extension Number').': '.$values['phoneInternal'].'<br/>':'';
            $return .= ($values['phoneExternal'] != '') ? __('Phone Number').': '.$values['phoneExternal'].'<br/>':'';
            return $return;
        });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonSpaceID')
        ->addParam('search', $criteria->getSearchText(true))
        ->format(function ($facilities, $actions) use ($guid) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/space_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/School Admin/space_manage_delete.php');
        });

    echo $table->render($facilities);
}
