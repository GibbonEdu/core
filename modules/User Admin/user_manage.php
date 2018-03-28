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
use Gibbon\Domain\QueryFilters;
use Gibbon\Forms\Form;

use Gibbon\UserAdmin\Domain\UserGateway;

if (!function_exists('isActionAccessible')) {
    require_once '../../gibbon.php';
}

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

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
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

    $search = isset($_GET['search'])? $_GET['search'] : '';
    $searchColumns = ['preferredName', 'surname', 'username', 'studentID', 'email', 'emailAlternate', 'phone1', 'phone2', 'phone3', 'phone4', 'vehicleRegistration', 'gibbonRole.name'];

    $filters = QueryFilters::createFromPost()->addSearch($search, $searchColumns)->defaultSort('fullName');
    
    $gateway = new UserGateway($pdo);
    $resultSet = $gateway->queryAllUsers($filters);

    // Build a set of family data
    $people = array_column($resultSet->data, 'gibbonPersonID');

    $dataFamily = array('people' => implode(',', $people));
    $sqlFamily = "(
        SELECT gibbonFamilyAdult.gibbonPersonID, gibbonFamilyAdult.gibbonFamilyID, 'adult' AS role, gibbonFamily.name, dob, (SELECT gibbonPersonID FROM gibbonFamilyChild WHERE gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID LIMIT 1) as gibbonPersonIDStudent
        FROM gibbonFamily 
        JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) 
        JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) 
        WHERE FIND_IN_SET(gibbonFamilyAdult.gibbonPersonID, :people)
    ) UNION (
        SELECT gibbonFamilyChild.gibbonPersonID, gibbonFamilyChild.gibbonFamilyID, 'child' AS role, gibbonFamily.name, dob, gibbonPerson.gibbonPersonID as gibbonPersonIDStudent
        FROM gibbonFamily 
        JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) 
        JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) 
        WHERE FIND_IN_SET(gibbonFamilyChild.gibbonPersonID, :people)
    ) ORDER BY gibbonFamilyID, role, dob DESC, gibbonPersonID";
    
    $resultFamily = $pdo->executeQuery($dataFamily, $sqlFamily);
    $familyData = $resultFamily->rowCount() > 0 ? $resultFamily->fetchAll(\PDO::FETCH_GROUP) : array();

    $resultSet->updateResults(function(&$item) use (&$familyData){
        $id = intval($item['gibbonPersonID']);
        $item['families'] = isset($familyData[$id])? $familyData[$id] : array();
        return $item;
    });

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/user_manage_add.php&search=$search'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    $table = DataTable::createFromResultSet('userManage', $resultSet)->setPath('.'.$_SESSION[$guid]['address']);

    $table->addColumn('image_240', __('Photo'))->format(function($item) use ($guid) {
        return getUserPhoto($guid, $item['image_240'], 75);
    })->setSortable(false);

    $table->addColumn('fullName', __('Name'))->format(function($item) {
        return formatName('', $item['preferredName'], $item['surname'], 'Student', true);
    });

    $table->addColumn('status', __('Status'));
    $table->addColumn('primaryRole', __('Primary Role'));

    $table->addColumn('family', __('Family'))->format(function($item) use ($guid) {
        $output = '';
        foreach ($item['families'] as $family) {
            $output .= '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$family['gibbonPersonIDStudent'].'&search=&allStudents=on&sort=surname, preferredName&subpage=Family">'.$family['name'].'</a><br/>';
        }
        return $output;
    })->setSortable(false);

    $table->addColumn('username', __('Username'));

    $col = $table->addActionColumn();
        $col->addAction('edit', __('Edit'))
            ->setURL('/modules/User Admin/user_manage_edit.php')
            ->addParam('gibbonPersonID')
            ->addParam('search', $search);

        $col->addAction('delete', __('Delete'))
            ->setURL('/modules/User Admin/user_manage_delete.php')
            ->addParam('gibbonPersonID')
            ->addParam('search', $search);

        $col->addAction('password', __('Change Password'))
            ->setIcon('key')
            ->setURL('/modules/User Admin/user_manage_password.php')
            ->addParam('gibbonPersonID')
            ->addParam('search', $search);

    echo $table->getOutput();
}
