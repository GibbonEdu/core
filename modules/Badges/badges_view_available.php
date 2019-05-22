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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$gibbon->session->get('module').'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Badges/badges_view_available.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('View Available Badges'));

    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
    $category = null;
    if (isset($_GET['category'])) {
        $category = $_GET['category'];
    }

    echo "<h2 class='top'>";
    echo __('Search & Filter');
    echo '</h2>';

    $form = Form::create('grantbadges',$gibbon->session->get('absoluteURL').'/index.php','GET');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('q','/modules/' . $gibbon->session->get('module') . '/badges_view_available.php');
    $form->addHiddenValue('address',$gibbon->session->get('address'));
    
    $row = $form->addRow();
        $row->addLabel('search',__('Search For'))->description("Badge name");
        $row->addTextField('search');
    
    $sql = "SELECT distinct category as value, category as name FROM badgesBadge WHERE active='Y' ORDER BY category, name";
    $row = $form->addRow();
        $row->addLabel('category',__('Category'));
        $row->addSelect('category')->fromQuery($pdo, $sql, [])->placeholder();
    
    $row = $form->addRow()->addSearchSubmit($gibbon->session);
    echo $form->getOutput();

    ?>
	
	<?php
    echo "<h2 class='top'>";
    echo 'View';
    echo '</h2>';

    try {
        $data = array();
        $sqlWhere = '';
        if ($search != '' || $category != '') {
            $sqlWhere = 'WHERE ';
            if ($search != '') {
                $data['search'] = "%$search%";
                $sqlWhere .= 'badgesBadge.name LIKE :search AND ';
            }
            if ($category != '') {
                $data['category'] = $category;
                $sqlWhere .= 'badgesBadge.category=:category';
            }
            if (mb_substr($sqlWhere, -5) == ' AND ') {
                $sqlWhere = mb_substr($sqlWhere, 0, -5);
            }
        }
        $sql = "SELECT badgesBadge.* FROM badgesBadge $sqlWhere ORDER BY category, badgesBadge.name";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) { echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='warning'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        $count = 0;
        $columns = 3;
        echo "<table class='margin-bottom: 10px; smallIntBorder' cellspacing='0' style='width:100%'>";
        while ($row = $result->fetch()) {
            if ($count % $columns == 0) {
                echo '<tr>';
            }

            echo "<td style='padding-top: 15px!important; padding-bottom: 15px!important; width:33%; text-align: center; vertical-align: top'>";
            if ($row['logo'] != '') {
                echo "<img style='margin-bottom: 20px; max-width: 150px' src='".$gibbon->session->get('absoluteURL','').'/'.$row['logo']."'/><br/>";
            } else {
                echo "<img style='margin-bottom: 20px; max-width: 150px' src='".$gibbon->session->get('absoluteURL','').'/themes/'.$gibbon->session->get('gibbonThemeName')."/img/anonymous_240_square.jpg'/><br/>";
            }
            echo '<b>'.$row['name'].'</b><br/>';
            echo '<span class=\'emphasis small\'>'.$row['category'].'</span><br/>';
            echo '</td>';

            if ($count % $columns == ($columns - 1)) {
                echo '</tr>';
            }
            ++$count;
        }

        if ($count % $columns != 0) {
            for ($i = 0;$i < $columns - ($count % $columns);++$i) {
                echo '<td></td>';
            }
            echo '</tr>';
        }
    }
    echo '</table>';
}
?>
