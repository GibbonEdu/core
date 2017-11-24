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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Set returnTo point for upcoming pages
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Families').'</div>';
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

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/family_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description('Family name.');
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    echo '<h2>';
    echo __($guid, 'View');
    echo '</h2>';

    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonFamily ORDER BY name';
        if ($search != '') {
            $data = array('search' => "%$search%");
            $sql = 'SELECT * FROM gibbonFamily WHERE (name LIKE :search) ORDER BY name';
        }
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/family_manage_add.php&search=$search'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "search=$search");
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Marital Status');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Adults');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Children');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Actions');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        try {
            $resultPage = $connection2->prepare($sqlPage);
            $resultPage->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        while ($row = $resultPage->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            echo $row['status'];
            echo '</td>';
            echo '<td>';
            try {
                $dataAdult = array('gibbonFamilyID' => $row['gibbonFamilyID']);
                $sqlAdult = 'SELECT * FROM gibbonFamilyAdult, gibbonPerson WHERE gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonFamilyID=:gibbonFamilyID ORDER BY surname, preferredName ';
                $resultAdult = $connection2->prepare($sqlAdult);
                $resultAdult->execute($dataAdult);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($rowAdult = $resultAdult->fetch()) {
                echo formatName($rowAdult['title'],  $rowAdult['preferredName'], $rowAdult['surname'], 'Parent');
                if ($rowAdult['status'] == 'Left' or $rowAdult['status'] == 'Expected') {
                    echo ' <i>('.$rowAdult['status'].')</i>';
                }
                echo '<br/>';
            }
            echo '</td>';
            echo '<td>';
            try {
                $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID']);
                $sqlChild = 'SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY surname, preferredName ';
                $resultChild = $connection2->prepare($sqlChild);
                $resultChild->execute($dataChild);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($rowChild = $resultChild->fetch()) {
                echo formatName('', $rowChild['preferredName'], $rowChild['surname'], 'Student');
                if ($rowChild['status'] == 'Left' or $rowChild['status'] == 'Expected') {
                    echo ' <i>('.$rowChild['status'].')</i>';
                }
                echo '<br/>';
            }
            echo '</td>';
            echo "<td style='width: 60px'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/family_manage_edit.php&gibbonFamilyID='.$row['gibbonFamilyID']."&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/family_manage_delete.php&gibbonFamilyID='.$row['gibbonFamilyID']."&search=$search&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "search=$search");
        }
    }
}
?>
