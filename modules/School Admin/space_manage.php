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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/space_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Facilities').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $search = isset($_GET['search'])? $_GET['search'] : '';

    echo '<h3>';
    echo __($guid, 'search');
    echo '</h3>';
    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/space_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'));
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    echo '<h3>';
    echo __($guid, 'View');
    echo '</h3>';

    //Set pagination variable
    $page = isset($_GET['page'])? $_GET['page'] : 1;
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    try {
        if (!empty($search)) {
            $data = array('search' => '%'.$search.'%');
            $sql = 'SELECT * FROM gibbonSpace WHERE name LIKE :search OR type LIKE :search ORDER BY name';
        } else {
            $data = array();
            $sql = 'SELECT * FROM gibbonSpace ORDER BY name';
        }

        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/space_manage_add.php'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top');
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Type');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Capacity');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Facilities');
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
            echo $row['type'];
            echo '</td>';
            echo '<td>';
            echo $row['capacity'];
            echo '</td>';
            echo '<td>';
            if ($row['computer'] == 'Y') {
                echo __($guid, 'Teaching computer').'<br/>';
            }
            if ($row['computerStudent'] > 0) {
                echo $row['computerStudent'].' student computers<br/>';
            }
            if ($row['projector'] == 'Y') {
                echo __($guid, 'Projector').'<br/>';
            }
            if ($row['tv'] == 'Y') {
                echo __($guid, 'TV').'<br/>';
            }
            if ($row['dvd'] == 'Y') {
                echo __($guid, 'DVD Player').'<br/>';
            }
            if ($row['hifi'] == 'Y') {
                echo __($guid, 'Hifi').'<br/>';
            }
            if ($row['speakers'] == 'Y') {
                echo __($guid, 'Speakers').'<br/>';
            }
            if ($row['iwb'] == 'Y') {
                echo __($guid, 'Interactive White Board').'<br/>';
            }
            if ($row['phoneInternal'] != '') {
                echo __($guid, 'Extension Number').': '.$row['phoneInternal'].'<br/>';
            }
            if ($row['phoneExternal'] != '') {
                echo __($guid, 'Phone Number').': '.$row['phoneExternal'].'<br/>';
            }
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/space_manage_edit.php&gibbonSpaceID='.$row['gibbonSpaceID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/space_manage_delete.php&gibbonSpaceID='.$row['gibbonSpaceID']."&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom');
        }
    }
}
