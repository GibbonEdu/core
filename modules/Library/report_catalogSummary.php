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

$_SESSION[$guid]['report_student_emergencySummary.php_choices'] = '';

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/report_catalogSummary.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Catalog Summary').'</div>';
    echo '</div>';

    echo '<h3>';
    echo __($guid, 'Search & Filter');
    echo '</h3>';

    //Get current filter values
    $ownershipType = null;
    if (isset($_POST['ownershipType'])) {
        $ownershipType = trim($_POST['ownershipType']);
    }
    if ($ownershipType == '') {
        if (isset($_GET['ownershipType'])) {
            $ownershipType = trim($_GET['ownershipType']);
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

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_catalogSummary.php");

    $row = $form->addRow();
        $row->addLabel('ownershipType', __('Ownership Type'));
        $row->addSelect('ownershipType')->fromArray(array('School' => __('School'), 'Individual' => __('Individual')))->selected($ownershipType)->placeholder();

    $sql = "SELECT gibbonLibraryTypeID as value, name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonLibraryTypeID', __('Item Type'));
        $row->addSelect('gibbonLibraryTypeID')->fromQuery($pdo, $sql, array())->selected($gibbonLibraryTypeID)->placeholder();

    $sql = "SELECT gibbonSpaceID as value, name FROM gibbonSpace ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonSpaceID', __('Location'));
        $row->addSelect('gibbonSpaceID')->fromQuery($pdo, $sql, array())->selected($gibbonSpaceID)->placeholder();

    $options = array("Available" => "Available", "Decommissioned" => "Decommissioned", "In Use" => "In Use", "Lost" => "Lost", "On Loan" => "On Loan", "Repair" => "Repair", "Reserved" => "Reserved");
    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')->fromArray($options)->selected($status)->placeholder();

    $row = $form->addRow();
        $row->addFooter(false);
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

	echo '<h3>';
	echo __($guid, 'Report Data');
	echo '</h3>';

	echo "<div class='linkTop'>";
	echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/report_catalogSummaryExport.php?address='.$_GET['q']."&ownershipType=$ownershipType&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status'><img title='".__($guid, 'Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
	echo '</div>';

	//Search with filters applied
	try {
		$data = array();
		$sqlWhere = 'WHERE ';
		if ($ownershipType != '') {
			$data['ownershipType'] = $ownershipType;
			$sqlWhere .= 'ownershipType=:ownershipType AND ';
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
		if ($sqlWhere == 'WHERE ') {
			$sqlWhere = '';
		} else {
			$sqlWhere = substr($sqlWhere, 0, -5);
		}
		$sql = "SELECT * FROM gibbonLibraryItem $sqlWhere ORDER BY id";
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
		echo "<table cellspacing='0' style='width: 100%'>";
		echo "<tr class='head'>";
		echo '<th>';
		echo __($guid, 'School ID').'<br/>';
		echo "<span style='font-style: italic; font-size: 85%'>".__($guid, 'Type').'</span>';
		echo '</th>';
		echo '<th>';
		echo __($guid, 'Name').'<br/>';
		echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Producer').'</span>';
		echo '</th>';
		echo '<th>';
		echo __($guid, 'Location');
		echo '</th>';
		echo '<th>';
		echo __($guid, 'Ownership').'<br/>';
		echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'User/Owner').'</span>';
		echo '</th>';
		echo '<th>';
		echo __($guid, 'Status').'<br/>';
		echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Borrowable').'</span>';
		echo '</th>';
		echo '<th>';
		echo __($guid, 'Purchase Date').'<br/>';
		echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Vendor').'</span>';
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
			echo '<b>'.$row['id'].'</b><br/>';
			echo "<span style='font-style: italic; font-size: 85%'>";
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
			echo '</span>';
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
			echo "<span style='font-size: 85%; font-style: italic'>".$row['borrowable'].'</span>';
			echo '</td>';
			echo '<td>';
			if ($row['purchaseDate'] == '') {
				echo '<i>'.__($guid, 'Unknown').'</i><br/>';
			} else {
				echo dateConvertBack($guid, $row['purchaseDate']).'<br/>';
			}
			echo "<span style='font-size: 85%; font-style: italic'>".$row['vendor'].'</span>';
			echo '</td>';
            echo '</tr>';

            ++$count;
        }
        echo '</table>';
    }
}
?>
