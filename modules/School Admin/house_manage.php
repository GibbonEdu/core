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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/School Admin/house_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Houses').'</div>';
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

    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonHouse ORDER BY name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/house_manage_add.php'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    $gibbonHouseIDs = array();
    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo "<th style='width: 170px'>";
        echo __($guid, 'Logo');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Short Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Actions');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        while ($row = $result->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            if ($row['logo'] != '') {
                echo "<img class='user' style='max-width: 150px' src='".$_SESSION[$guid]['absoluteURL'].'/'.$row['logo']."'/>";
            } else {
                echo "<img class='user' style='max-width: 150px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_240_square.jpg'/>";
            }
            echo '</td>';
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            echo $row['nameShort'];
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/house_manage_edit.php&gibbonHouseID='.$row['gibbonHouseID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/house_manage_delete.php&gibbonHouseID='.$row['gibbonHouseID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
            echo '</td>';
            echo '</tr>';

            $gibbonHouseIDs[] = $row['gibbonHouseID'];
            ++$count;
        }
        echo '</table>';
    }

    echo '<h3>';
    echo __($guid, 'Assign Houses');
    echo '</h3>';

    $form = Form::create('houseAssign', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/house_manage_assign.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
        $row->addSelectYearGroup('gibbonYearGroupIDList')->selectMultiple()->isRequired();

    $sql = "SELECT gibbonHouseID as value, name FROM gibbonHouse ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonHouseIDList', __('Houses'));
        $row->addSelect('gibbonHouseIDList')->fromQuery($pdo, $sql)->selectMultiple()->isRequired()->selected($gibbonHouseIDs);

    $row = $form->addRow();
        $row->addLabel('balanceGender', __('Balance by Gender?'));
        $row->addYesNo('balanceGender')->isRequired();

    $row = $form->addRow();
        $row->addLabel('balanceYearGroup', __('Balance by Year Group?'))->description(__('Attempts to keep houses be as balanced as possible per year group, otherwise balances the house numbers school-wide.'));
        $row->addYesNo('balanceYearGroup')->isRequired();

    $row = $form->addRow();
        $row->addLabel('overwrite', __('Overwrite'))->description(__("Replace a student's existing house assignment? If no, it will only assign houses to students who do not already have one."));
        $row->addYesNo('overwrite')->isRequired()->selected('N');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
