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
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Badges\BadgeGateway;

//Module includes
include './modules/Badges/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Badges/badges_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo 'You do not have access to this action.';
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Badges'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Set pagination variable
    $page = null;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    //Build role lookup array
    $allRoles = array();
    try {
        $dataRoles = array();
        $sqlRoles = 'SELECT * FROM gibbonRole';
        $resultRoles = $connection2->prepare($sqlRoles);
        $resultRoles->execute($dataRoles);
    } catch (PDOException $e) {

    }
    while ($rowRoles = $resultRoles->fetch()) {
        $allRoles[$rowRoles['gibbonRoleID']] = $rowRoles['name'];
    }

    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
    $category = null;
    if (isset($_GET['category'])) {
        $category = $_GET['category'];
    }

    $form = Form::create('search', $gibbon->session->get('absoluteURL','').'/index.php', 'get');
    $form->setTitle(__('Search & Filter'));
    $form->addClass('noIntBorder');

    $form->addHiddenValue('q', '/modules/'.$gibbon->session->get('module').'/badges_manage.php');
    $form->addHiddenValue('address', '/modules/' . $gibbon->session->get('address'));

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Name'));
        $row->addTextField('search')->setValue($search);

    $categories = getSettingByScope($connection2, 'Badges', 'badgeCategories');
    $categories = !empty($categories) ? array_map('trim', explode(',', $categories)) : [];
    $row = $form->addRow();
        $row->addLabel('category', __('Category'));
        $row->addSelect('category')->fromArray($categories)->selected($category)->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput(); 

    echo "<h2 class='top'>";
    echo __('View');
    echo '</h2>';


    $badgesGateway = $container->get(BadgeGateway::class);
    $criteria = $badgesGateway->newQueryCriteria()
        ->filterBy('badgeCategory',$_GET['category'] ?? '')
        ->filterBy('badgeName',$_GET['search'] ?? '')
        ->fromPOST();

    $badges = $badgesGateway->queryBadges($criteria,$gibbon->session->get('gibbonSchoolYearID'));
    
    $table = DataTable::createPaginated('badges',$criteria);
    $table->addExpandableColumn('comments')
        ->format(function($row) {
            $output = '';
            if (!empty($row['comment']) && $row['comment'] != '') {
                $output .= '<b>'.__('Comment').'</b><br/>';
                $output .= nl2brr($row['comment']).'<br/><br/>';
            }
            return $output;
        });
    $table->AddColumn('logo',__('Logo'))->width('155px')->format(function($row) use ($gibbon){
        if ($row['logo'] != '') {
            echo "<img class='user' style='max-width: 150px' src='" . $gibbon->session->get('absoluteURL','') . '/' . $row['logo'] . "'/>";
        } else {
            echo "<img class='user' style='max-width: 150px' src='" . $gibbon->session->get('absoluteURL','') . '/themes/' . $gibbon->session->get('gibbonThemeName') . "/img/anonymous_240_square.jpg'/>";
        }
    });
    $table->AddColumn('name',__('Name'));
    $table->AddColumn('category',__('Category'));

    $actions = $table->AddActionColumn('actions',__('Actions'));
        $actions->AddAction('edit',__('Edit'));
        $actions->AddAction('delete',__('Delete'));
        $actions->AddAction('view',__('Show Description'));

    echo $table->render($badges);
    
  
}

?>
