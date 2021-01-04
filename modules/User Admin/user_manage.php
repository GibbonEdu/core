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

use Gibbon\Services\Format;

use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\User\UserGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
        return;
    }

    //Proceed!
    $page->breadcrumbs->add(__('Manage Users'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $search = isset($_GET['search'])? $_GET['search'] : '';

    // CRITERIA
    $userGateway = $container->get(UserGateway::class);
    $criteria = $userGateway->newQueryCriteria(true)
        ->searchBy($userGateway->getSearchableColumns(), $search)
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();

    echo '<h2>';
    echo __('Search');
    echo '</h2>';
    
    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/user_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username, role, student ID, email, phone number, vehicle registration'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    echo '<h2>';
    echo __('View');
    echo '</h2>';

    // QUERY
    $dataSet = $userGateway->queryAllUsers($criteria);

    // Join a set of family data per user
    $people = $dataSet->getColumn('gibbonPersonID');
    $familyData = $userGateway->selectFamilyDetailsByPersonID($people)->fetchGrouped();
    $dataSet->joinColumn('gibbonPersonID', 'families', $familyData);

    // DATA TABLE
    $table = DataTable::createPaginated('userManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/User Admin/user_manage_add.php')
        ->addParam('search', $search)
        ->displayLabel();

    $table->addMetaData('filterOptions', [
        'role:student'    => __('Role').': '.__('Student'),
        'role:parent'     => __('Role').': '.__('Parent'),
        'role:staff'      => __('Role').': '.__('Staff'),
        'status:full'     => __('Status').': '.__('Full'),
        'status:left'     => __('Status').': '.__('Left'),
        'status:expected' => __('Status').': '.__('Expected'),
        'date:starting'   => __('Before Start Date'),
        'date:ended'      => __('After End Date'),
    ]);

    // COLUMNS
    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('10%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'sm']));

    $table->addColumn('fullName', __('Name'))
        ->context('primary')
        ->width('30%')
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Student', true]));

    $table->addColumn('status', __('Status'))
        ->width('10%')
        ->translatable();

    $table->addColumn('primaryRole', __('Primary Role'))
        ->context('secondary')
        ->width('16%')
        ->translatable();

    $table->addColumn('family', __('Family'))
        ->notSortable()
        ->format(function($person) use ($guid) {
            $output = '';
            foreach ($person['families'] as $family) {
                $output .= '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$family['gibbonPersonIDStudent'].'&search=&allStudents=on&sort=surname, preferredName&subpage=Family">'.$family['name'].'</a><br/>';
            }
            return $output;
        });

    $table->addColumn('username', __('Username'))->context('primary');

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonPersonID')
        ->addParam('search', $criteria->getSearchText(true))
        ->format(function ($person, $actions) use ($guid, $highestAction) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/User Admin/user_manage_edit.php');

            if ($highestAction == 'Manage Users_editDelete' && $person['gibbonPersonID'] != $_SESSION[$guid]['gibbonPersonID']) {
                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/User Admin/user_manage_delete.php');
            }

            $actions->addAction('password', __('Change Password'))
                    ->setURL('/modules/User Admin/user_manage_password.php')
                    ->setIcon('key');
        });

    echo $table->render($dataSet);
}
