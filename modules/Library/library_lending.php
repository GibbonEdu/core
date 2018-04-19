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

if (isActionAccessible($guid, $connection2, '/modules/Library/library_lending.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Lending & Activity Log').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.'));
    }

    echo '<h3>';
    echo __($guid, 'Search & Filter');
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

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_lending.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/library_lending.php");

    $row = $form->addRow();
        $row->addLabel('name', __('ID/Name/Producer'));
        $row->addTextField('name')->setValue($name)->maxLength(50);

    $data = array();
    $sql = "SELECT gibbonLibraryTypeID AS value, name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonLibraryTypeID', __('Type'));
        $row->addSelect('gibbonLibraryTypeID')->fromQuery($pdo, $sql, $data)->placeholder()->selected($gibbonLibraryTypeID);

    $row = $form->addRow();
        $row->addLabel('gibbonSpaceID', __('Space'));
        $row->addSelectSpace('gibbonSpaceID')->placeholder()->selected($gibbonSpaceID);

    $statuses = array(
        'Available' => __('Available'),
        'On Loan' => __('On Loan'),
        'Repair' => __('Repair'),
        'Reserved' => __('Reserved')
    );
    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($statuses)->selected($status)->placeholder();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();


    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    echo '<h3>';
    echo __($guid, 'View');
    echo '</h3>';

    //Search with filters applied
    try {
        $data = array();
        $sqlWhere = 'AND ';
        $sqlWhere2 = 'AND ';
        if ($name != '') {
            $data['name'] = '%'.$name.'%';
            $data['producer'] = '%'.$name.'%';
            $data['id'] = '%'.$name.'%';
            $data['name2'] = '%'.$name.'%';
            $data['producer2'] = '%'.$name.'%';
            $data['id2'] = '%'.$name.'%';
            $sqlWhere .= '(name LIKE :name  OR producer LIKE :producer OR id LIKE :id) AND ';
            $sqlWhere2 .= '(name LIKE :name2  OR producer LIKE :producer2 OR id LIKE :id2) AND ';
        }
        if ($gibbonLibraryTypeID != '') {
            $data['gibbonLibraryTypeID'] = $gibbonLibraryTypeID;
            $data['gibbonLibraryTypeID2'] = $gibbonLibraryTypeID;
            $sqlWhere .= 'gibbonLibraryTypeID=:gibbonLibraryTypeID AND ';
            $sqlWhere2 .= 'gibbonLibraryTypeID=:gibbonLibraryTypeID2 AND ';
        }
        if ($gibbonSpaceID != '') {
            $data['gibbonSpaceID'] = $gibbonSpaceID;
            $data['gibbonSpaceID2'] = $gibbonSpaceID;
            $sqlWhere .= 'gibbonSpaceID=:gibbonSpaceID AND ';
            $sqlWhere2 .= 'gibbonSpaceID=:gibbonSpaceID2 AND ';
        }
        if ($status != '') {
            $data['status'] = $status;
            $data['status2'] = $status;
            $sqlWhere .= 'status=:status AND ';
            $sqlWhere .= 'status=:status2 AND ';
        }
        if ($sqlWhere == 'AND ') {
            $sqlWhere = '';
        } else {
            $sqlWhere = substr($sqlWhere, 0, -5);
        }
        if ($sqlWhere2 == 'AND ') {
            $sqlWhere2 = '';
        } else {
            $sqlWhere2 = substr($sqlWhere2, 0, -5);
        }

        $sql = "(SELECT gibbonLibraryItem.*, NULL AS borrower FROM gibbonLibraryItem WHERE (status='Available' OR status='Repair' OR status='Reserved') AND NOT ownershipType='Individual' AND borrowable='Y' $sqlWhere)
			UNION
			(SELECT gibbonLibraryItem.*, concat(preferredName, ' ', surname) AS borrower FROM gibbonLibraryItem JOIN gibbonPerson ON (gibbonLibraryItem.gibbonPersonIDStatusResponsible=gibbonPerson.gibbonPersonID) WHERE (gibbonLibraryItem.status='On Loan') AND NOT ownershipType='Individual' AND borrowable='Y' $sqlWhere2) ORDER BY name, producer";
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status");
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Number');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'ID');
        echo '</th>';
        echo "<th style='width: 250px'>";
        echo __($guid, 'Name').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Producer').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Type');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Location');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Status').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Return Date').'<br/>'.__($guid, 'Borrower').'</span>';
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
            if ((strtotime(date('Y-m-d')) - strtotime($row['returnExpected'])) / (60 * 60 * 24) > 0 and $row['status'] == 'On Loan') {
                $rowNum = 'error';
            }

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $count + 1;
            echo '</td>';
            echo '<td>';
            echo '<b>'.$row['id'].'</b>';
            echo '</td>';
            echo '<td>';
            if (strlen($row['name']) > 30) {
                echo '<b>'.substr($row['name'], 0, 30).'...</b><br/>';
            } else {
                echo '<b>'.$row['name'].'</b><br/>';
            }
            echo "<span style='font-size: 85%; font-style: italic'>".$row['producer'].'</span>';
            echo '</td>';
            echo '<td>';
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
                echo __($guid, $rowType['name']).'<br/>';
            }
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
            echo $row['status'].'<br/>';
            if ($row['returnExpected'] != '' or $row['borrower'] != '') {
                echo "<span style='font-size: 85%; font-style: italic'>";
                if ($row['returnExpected'] != '') {
                    echo dateConvertBack($guid, $row['returnExpected']).'<br/>';
                }
                if ($row['borrower'] != '') {
                    echo $row['borrower'];
                }
                echo '</span>';
            }
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_lending_item.php&gibbonLibraryItemID='.$row['gibbonLibraryItemID']."&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
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
