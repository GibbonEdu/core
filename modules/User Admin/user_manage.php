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
use Gibbon\Domain\User\UserGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Users').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h2>';
    echo __($guid, 'Search');
    echo '</h2>';

    $search = isset($_GET['search'])? $_GET['search'] : '';

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/user_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username, role, student ID, email, phone number, vehicle registration'));
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    echo '<h2>';
    echo __($guid, 'View');
    echo '</h2>';

    $userGateway = $container->get(UserGateway::class);
    
    $searchColumns = ['preferredName', 'surname', 'username', 'studentID', 'email', 'emailAlternate', 'phone1', 'phone2', 'phone3', 'phone4', 'vehicleRegistration', 'gibbonRole.name'];

    $criteria = $userGateway->newQueryCriteria()
        ->searchBy($searchColumns, $search)
        ->sortBy(['surname', 'preferredName'])
        ->fromArray($_POST);
    
    $queryResult = $userGateway->queryAllUsers($criteria);

    // Join a set of family data per user
    $people = $queryResult->getColumn('gibbonPersonID');
    $familyData = $userGateway->selectFamilyDetailsByPersonID($people)->fetchGrouped();

    $queryResult->joinColumn('gibbonPersonID', 'families', $familyData);

    $table = DataTable::create('userManage');

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/User Admin/user_manage_add.php')
        ->addParam('search', $search)
        ->displayLabel();

    $table->addFilterOptions([
        'role:student'    => __('Role').': '.__('Student'),
        'role:parent'     => __('Role').': '.__('Parent'),
        'role:staff'      => __('Role').': '.__('Staff'),
        'status:full'     => __('Status').': '.__('Full'),
        'status:left'     => __('Status').': '.__('Left'),
        'status:expected' => __('Status').': '.__('Expected'),
        'date:starting'   => __('Before Start Date'),
        'date:ended'      => __('After End Date'),
    ]);

    $table->addColumn('image_240', __('Photo'))->format(function($item) use ($guid) {
        return getUserPhoto($guid, $item['image_240'], 75);
    })->setWidth('10%');

    $table->addColumn('fullName', __('Name'))->format(function($item) {
        return formatName('', $item['preferredName'], $item['surname'], 'Student', true);
    })->setWidth('30%')->sortable(['surname', 'preferredName']);

    $table->addColumn('status', __('Status'))->sortable()->setWidth('10%');
    $table->addColumn('primaryRole', __('Primary Role'))->setWidth('16%');

    $table->addColumn('family', __('Family'))->format(function($item) use ($guid) {
        $output = '';
        foreach ($item['families'] as $family) {
            $output .= '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$family['gibbonPersonIDStudent'].'&search=&allStudents=on&sort=surname, preferredName&subpage=Family">'.$family['name'].'</a><br/>';
        }
        return $output;
    });

    $table->addColumn('username', __('Username'));

    $col = $table->addActionColumn()->addParam('gibbonPersonID')->addParam('search', $search);
        $col->addAction('edit', __('Edit'))
            ->setURL('/modules/User Admin/user_manage_edit.php');

        $col->addAction('delete', __('Delete'))
            ->setURL('/modules/User Admin/user_manage_delete.php');

        $col->addAction('password', __('Change Password'))
            ->setURL('/modules/User Admin/user_manage_password.php')
            ->setIcon('key');

    echo $table->renderToHTML($queryResult, $criteria);
}
