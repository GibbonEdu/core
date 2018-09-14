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

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view.php') == false) {
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
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Staff Profiles').'</div>';
        echo '</div>';

        $search = (isset($_GET['search']) ? $_GET['search'] : '');
        $allStaff = (isset($_GET['allStaff']) ? $_GET['allStaff'] : '');

        $staffGateway = $container->get(StaffGateway::class);

        // QUERY
        $criteria = $staffGateway->newQueryCriteria()
            ->searchBy($staffGateway->getSearchableColumns(), $search)
            ->filterBy('all', $allStaff)
            ->sortBy(['surname', 'preferredName'])
            ->fromArray($_POST);

        echo '<h2>';
        echo __($guid, 'Search');
        echo '</h2>';

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL']."/index.php", 'get');

        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/staff_view.php");

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
            $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

        if ($highestAction == 'View Staff Profile_full') {
            $row = $form->addRow();
                $row->addLabel('allStaff', __('All Staff'))->description('Include all staff, regardless of status, start date, end date, etc.');
                $row->addCheckbox('allStaff')->checked($allStaff);
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();

        echo '<h2>';
        echo __($guid, 'Choose A Staff Member');
        echo '</h2>';

        $staff = $staffGateway->queryAllStaff($criteria);

        // DATA TABLE
        $table = DataTable::createPaginated('staffManage', $criteria);

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Staff/staff_manage_add.php')
            ->addParam('search', $search)
            ->displayLabel();

        if ($highestAction == 'View Staff Profile_full') {
            $table->addMetaData('filterOptions', [
                'all:on'        => __('All Staff'),
                'type:teaching' => __('Staff Type').': '.__('Teaching'),
                'type:support'  => __('Staff Type').': '.__('Support'),
                'type:other'    => __('Staff Type').': '.__('Other'),
            ]);
        }

        // COLUMNS
        $table->addColumn('fullName', __('Name'))
            ->description(__('Initials'))
            ->width('35%')
            ->sortable(['surname', 'preferredName'])
            ->format(function($person) {
                return Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true)
                    .'<br/><span style="font-size: 85%; font-style: italic">'.$person['initials']."</span>";
            });

        $table->addColumn('type', __('Type'))->width('25%');
        $table->addColumn('jobTitle', __('Job Title'))->width('25%');

        // ACTIONS
        $table->addActionColumn()
            ->addParam('gibbonPersonID')
            ->addParam('search', $criteria->getSearchText(true))
            ->format(function ($person, $actions) use ($guid) {
                $actions->addAction('view', __('View Details'))
                        ->setURL('/modules/Staff/staff_view_details.php');
            });

        echo $table->render($staff);
    }
}
