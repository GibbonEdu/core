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

$page->breadcrumbs->add(__('Manage Catalog'));

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.'));
    }

    echo '<h3>';
    echo __('Search & Filter');
    echo '</h3>';

    //Get current filter values
    $name = null;
    if (isset($_POST['name'])) {
        $name = trim($_POST['name']);
    }
    if ($name == '') {
        if (isset($_GET['name'])) {
            $name = trim($_GET['name']);
        }
    }
    $gibbonLibraryTypeID = null;
    if (isset($_POST['gibbonLibraryTypeID'])) {
        $gibbonLibraryTypeID = trim($_POST['gibbonLibraryTypeID']);
    }
    if ($gibbonLibraryTypeID == '') {
        if (isset($_GET['gibbonLibraryTypeID'])) {
            $gibbonLibraryTypeID = trim($_GET['gibbonLibraryTypeID']);
        }
    }
    $gibbonSpaceID = null;
    if (isset($_POST['gibbonSpaceID'])) {
        $gibbonSpaceID = trim($_POST['gibbonSpaceID']);
    }
    if ($gibbonSpaceID == '') {
        if (isset($_GET['gibbonSpaceID'])) {
            $gibbonSpaceID = trim($_GET['gibbonSpaceID']);
        }
    }
    $status = null;
    if (isset($_POST['status'])) {
        $status = trim($_POST['status']);
    }
    if ($status == '') {
        if (isset($_GET['status'])) {
            $status = trim($_GET['status']);
        }
    }
    $gibbonPersonIDOwnership = null;
    if (isset($_POST['gibbonPersonIDOwnership'])) {
        $gibbonPersonIDOwnership = trim($_POST['gibbonPersonIDOwnership']);
    }
    if ($gibbonPersonIDOwnership == '') {
        if (isset($_GET['gibbonPersonIDOwnership'])) {
            $gibbonPersonIDOwnership = trim($_GET['gibbonPersonIDOwnership']);
        }
    }
    $typeSpecificFields = null;
    if (isset($_POST['typeSpecificFields'])) {
        $typeSpecificFields = trim($_POST['typeSpecificFields']);
    }
    if ($typeSpecificFields == '') {
        if (isset($_GET['typeSpecificFields'])) {
            $typeSpecificFields = trim($_GET['typeSpecificFields']);
        }
    }

    $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/library_manage_catalog.php");

    $row = $form->addRow();
        $row->addLabel('name', __('ID/Name/Producer'));
        $row->addTextField('name')->setValue($name);

    $sql = "SELECT gibbonLibraryTypeID AS value, name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonLibraryTypeID', __('Type'));
        $row->addSelect('gibbonLibraryTypeID')->fromQuery($pdo, $sql, array())->selected($gibbonLibraryTypeID)->placeholder();

    $row = $form->addRow();
        $row->addLabel('gibbonSpaceID', __('Location'));
        $row->addSelectSpace('gibbonSpaceID')->selected($gibbonSpaceID)->placeholder();

    $statuses = array(
        'Available' => __('Available'),
        'Decommissioned' => __('Decommissioned'),
        'In Use' => __('In Use'),
        'Lost' => __('Lost'),
        'On Loan' => __('On Loan'),
        'Repair' => __('Repair'),
        'Reserved' => __('Reserved')
    );
    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')->fromArray($statuses)->selected($status)->placeholder();

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDOwnership', __('Owner/User'));
        $row->addSelectUsers('gibbonPersonIDOwnership')->selected($gibbonPersonIDOwnership)->placeholder();

    $row = $form->addRow();
        $row->addLabel('typeSpecificFields', __('Type-Specific Fields'))->description(__('For example, a computer\'s MAC address or a book\'s ISBN.'));
        $row->addTextField('typeSpecificFields')->setValue($typeSpecificFields);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    //Search with filters applied
    try {
        $data = array();
        $sqlWhere = 'WHERE ';
        if ($name != '') {
            $data['name'] = '%'.$name.'%';
            $data['producer'] = '%'.$name.'%';
            $data['id'] = '%'.$name.'%';
            $sqlWhere .= '(name LIKE :name  OR producer LIKE :producer OR id LIKE :id) AND ';
        }
        if ($gibbonLibraryTypeID != '') {
            $data['gibbonLibraryTypeID'] = $gibbonLibraryTypeID;
            $sqlWhere .= 'gibbonLibraryTypeID=:gibbonLibraryTypeID AND ';
        }
        if ($gibbonSpaceID != '') {
            $data['gibbonSpaceID'] = $gibbonSpaceID;
            $sqlWhere .= 'gibbonSpaceID=:gibbonSpaceID AND ';
        }
        if ($status != '') {
            $data['status'] = $status;
            $sqlWhere .= 'status=:status AND ';
        }
        if ($gibbonPersonIDOwnership != '') {
            $data['gibbonPersonIDOwnership'] = $gibbonPersonIDOwnership;
            $sqlWhere .= 'gibbonPersonIDOwnership=:gibbonPersonIDOwnership AND ';
        }
        if ($typeSpecificFields != '') {
            $data['fields'] = '%'.$typeSpecificFields.'%';
            $sqlWhere .= 'fields LIKE :fields AND ';
        }
        if ($sqlWhere == 'WHERE ') {
            $sqlWhere = '';
        } else {
            $sqlWhere = substr($sqlWhere, 0, -5);
        }

        $sql = "SELECT * FROM gibbonLibraryItem $sqlWhere ORDER BY id";
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/library_manage_catalog_add.php&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=".urlencode($typeSpecificFields)."'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo '<h3>';
        echo __('View');
        echo '</h3>';

        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        echo '<h3>';
        echo __('View');
        echo "<span style='font-weight: normal; font-style: italic; font-size: 55%'> ".sprintf(__('%1$s record(s) in current view'), $result->rowCount()).'</span>';
        echo '</h3>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status");
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo "<th style='width: 80px'>";
        echo __('School ID').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__('Type').'</span>';
        echo '</th>';
        echo '<th>';
        echo __('Name').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__('Producer').'</span>';
        echo '</th>';
        echo '<th>';
        echo __('Location');
        echo '</th>';
        echo '<th>';
        echo __('Ownership').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__('User/Owner').'</span>';
        echo '</th>';
        echo '<th>';
        echo __('Status').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__('Borrowable').'</span>';
        echo '</th>';
        echo "<th style='width: 125px'>";
        echo __('Actions');
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

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo '<b>'.$row['id'].'</b><br/>';
            try {
                $dataType = array('gibbonLibraryTypeID' => $row['gibbonLibraryTypeID']);
                $sqlType = 'SELECT name FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID';
                $resultType = $connection2->prepare($sqlType);
                $resultType->execute($dataType);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultType->rowCount() == 1) {
                $rowType = $resultType->fetch();
                echo "<span style='font-size: 85%; font-style: italic'>".__($rowType['name']).'</span>';
            }
            echo '</td>';
            echo '<td>';
            echo '<b>'.$row['name'].'</b><br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".$row['producer'].'</span>';
            echo '</td>';
            echo '<td>';
            if ($row['gibbonSpaceID'] != '') {
                try {
                    $dataSpace = array('gibbonSpaceID' => $row['gibbonSpaceID']);
                    $sqlSpace = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID';
                    $resultSpace = $connection2->prepare($sqlSpace);
                    $resultSpace->execute($dataSpace);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultSpace->rowCount() == 1) {
                    $rowSpace = $resultSpace->fetch();
                    echo $rowSpace['name'].'<br/>';
                }
            }
            if ($row['locationDetail'] != '') {
                echo "<span style='font-size: 85%; font-style: italic'>".$row['locationDetail'].'</span>';
            }
            echo '</td>';
            echo '<td>';
            if ($row['ownershipType'] == 'School') {
                echo $_SESSION[$guid]['organisationNameShort'].'<br/>';
            } elseif ($row['ownershipType'] == 'Individual') {
                echo 'Individual<br/>';
            }
            if ($row['gibbonPersonIDOwnership'] != '') {
                try {
                    $dataPerson = array('gibbonPersonID' => $row['gibbonPersonIDOwnership']);
                    $sqlPerson = 'SELECT title, preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                    $resultPerson = $connection2->prepare($sqlPerson);
                    $resultPerson->execute($dataPerson);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultPerson->rowCount() == 1) {
                    $rowPerson = $resultPerson->fetch();
                    echo "<span style='font-size: 85%; font-style: italic'>".formatName($rowPerson['title'], $rowPerson['preferredName'], $rowPerson['surname'], 'Staff', false, true).'</span>';
                }
            }
            echo '</td>';
            echo '<td>';
            echo $row['status'].'<br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".ynExpander($guid, $row['borrowable']).'</span>';
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_manage_catalog_edit.php&gibbonLibraryItemID='.$row['gibbonLibraryItemID']."&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=".urlencode($typeSpecificFields)."'><img title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            if ($row['borrowable'] == "Y") {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_lending_item.php&gibbonLibraryItemID='.$row['gibbonLibraryItemID']."&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=".urlencode($typeSpecificFields)."'><img title='".__('Lending')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/attendance.png'/></a> ";
            }
            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_manage_catalog_delete.php&gibbonLibraryItemID='.$row['gibbonLibraryItemID']."&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=".urlencode($typeSpecificFields)."&width=650&height=135'><img style='margin-right: 2px' title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_manage_catalog_duplicate.php&gibbonLibraryItemID='.$row['gibbonLibraryItemID']."&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=".urlencode($typeSpecificFields)."'><img title='".__('Duplicate')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copy.png'/></a>";
            echo '</td>';
            echo '</tr>';

            ++$count;
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status");
        }
    }
}
?>
