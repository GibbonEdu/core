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
use Gibbon\Domain\Staff\StaffGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Staff').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $search = (isset($_GET['search']) ? $_GET['search'] : '');
    $allStaff = (isset($_GET['allStaff']) ? $_GET['allStaff'] : '');

    $staffGateway = $container->get(StaffGateway::class);

    // CRITERIA
    $criteria = $staffGateway->newQueryCriteria()
        ->searchBy($staffGateway->getSearchableColumns(), $search)
        ->filterBy('all', $allStaff)
        ->sortBy(['surname', 'preferredName'])
        ->fromArray($_POST);

    echo '<h2>';
    echo __('Search & Filter');
    echo '</h2>';

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL']."/index.php", 'get');

    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/staff_manage.php");

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

    $row = $form->addRow();
        $row->addLabel('allStaff', __('All Staff'))->description('Include Expected and Left.');
        $row->addCheckbox('allStaff')->checked($allStaff);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    echo '<h2>';
    echo __('View');
    echo '</h2>';

    $staff = $staffGateway->queryAllStaff($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('staffManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Staff/staff_manage_add.php')
        ->addParam('search', $search)
        ->displayLabel();

    $table->addMetaData('filterOptions', [
        'all:on'          => __('All Staff'),
        'type:teaching'   => __('Staff Type').': '.__('Teaching'),
        'type:support'    => __('Staff Type').': '.__('Support'),
        'type:other'      => __('Staff Type').': '.__('Other'),
        'status:full'     => __('Status').': '.__('Full'),
        'status:left'     => __('Status').': '.__('Left'),
        'status:expected' => __('Status').': '.__('Expected'),
    ]);

    // COLUMNS
    $table->addColumn('fullName', __('Name'))
        ->description(__('Initials'))
        ->width('35%')
        ->sortable(['surname', 'preferredName'])
        ->format(function($person) {
            return Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true)
                .'<br/><span style="font-size: 85%; font-style: italic">'.$person['initials']."</span>";
        });

    $table->addColumn('type', __('Staff Type'))->width('20%');
    $table->addColumn('status', __('Status'))->width('10%');
    $table->addColumn('jobTitle', __('Job Title'))->width('20%');

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonStaffID')
        ->addParam('search', $criteria->getSearchText(true))
        ->format(function ($person, $actions) use ($guid) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Staff/staff_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Staff/staff_manage_delete.php');
        });

    echo $table->render($staff);
}
