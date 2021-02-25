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

use Gibbon\Services\Format;

include '../../config.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/report_catalogSummary.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $ownershipType = trim($ownershipType);
    $gibbonLibraryTypeID = trim($gibbonLibraryTypeID);
    $gibbonSpaceID = trim($gibbonSpaceID);
    $status = trim($status);

    $data = array();
	$sqlWhere = 'WHERE ';
	if ($ownershipType != '') {
		$data['ownershipType'] = $ownershipType;
		$sqlWhere .= 'ownershipType=:ownershipType AND ';
	}
	if ($gibbonLibraryTypeID != '') {
		$data['gibbonLibraryTypeID'] = $gibbonLibraryTypeID;
		$sqlWhere .= 'gibbonLibraryItem.gibbonLibraryTypeID=:gibbonLibraryTypeID AND ';
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
	$sql = "SELECT gibbonLibraryItem.*
		FROM gibbonLibraryItem
			JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) $sqlWhere
		ORDER BY id";
	$result = $pdo->executeQuery($data, $sql, '_');

    //Cache TypeFields
    
        $dataTypeFields = array() ;
        $sqlTypeFields = "SELECT gibbonLibraryType.* FROM gibbonLibraryType";
        $resultTypeFields = $connection2->prepare($sqlTypeFields);
        $resultTypeFields->execute($dataTypeFields);
    $typeFieldsTemp = $resultTypeFields->fetchAll();

    $typeFields = array();
    foreach ($typeFieldsTemp as $typeField) {
        $typeFields[$typeField['gibbonLibraryTypeID']] = $typeField;
    }


	$excel = new Gibbon\Excel('catalogSummary.xlsx');
	if ($excel->estimateCellCount($pdo) > 8000)    //  If too big, then render csv instead.
		return Gibbon\csv::generate($pdo, 'Catalog Summary');
	$excel->setActiveSheetIndex(0);
	$excel->getProperties()->setTitle('Catalog Summary');
	$excel->getProperties()->setSubject('Catalog Summary');
	$excel->getProperties()->setDescription('Catalog Summary');


	$excel->getActiveSheet()->setCellValueByColumnAndRow(0, 1, __('School ID'));
	$excel->getActiveSheet()->setCellValueByColumnAndRow(1, 1, __('Name'). ' '. __('Producer'));
	$excel->getActiveSheet()->setCellValueByColumnAndRow(2, 1, __('Type'));
	$excel->getActiveSheet()->setCellValueByColumnAndRow(3, 1, __('Location'));
	$excel->getActiveSheet()->setCellValueByColumnAndRow(4, 1, __('Ownership').' '.__('User/Owner'));
	$excel->getActiveSheet()->setCellValueByColumnAndRow(5, 1, __('Status').' '.__('Borrowable'));
	$excel->getActiveSheet()->setCellValueByColumnAndRow(6, 1, __('Purchase Date').' '.__('Vendor'));
	$excel->getActiveSheet()->setCellValueByColumnAndRow(7, 1, __('Details'));
	$excel->getActiveSheet()->getStyle("1:1")->getFont()->setBold(true);

    $count = 0;
    $rowNum = 'odd';
	$r = 1;
    while ($row = $result->fetch()) {
        ++$count;
		$r++;
		//Column A
		$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $r, $row['id']);
		//Column B
        $x = $row['name'];
        if ($row['producer'] != '') {
            $x .= "; ".$row['producer'];
        }
		$excel->getActiveSheet()->setCellValueByColumnAndRow(1, $r, $x);
		//Column C
        $x = '';
		$dataType = array('gibbonLibraryTypeID' => $row['gibbonLibraryTypeID']);
		$sqlType = 'SELECT name
			FROM gibbonLibraryType
			WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID';
		if (is_null($resultType = $pdo->executeQuery($dataType, $sqlType))) {
			$x = $pdo->getError();
		}
        if ($resultType->rowCount() == 1) {
            $rowType = $resultType->fetch();
            $x = __($rowType['name']);
        }
		$excel->getActiveSheet()->setCellValueByColumnAndRow(2, $r, $x);
		//Column D
		$x = '';
		if ($row['gibbonSpaceID'] != '') {
			$dataSpace = array('gibbonSpaceID' => $row['gibbonSpaceID']);
			$sqlSpace = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID';
			if (is_null($resultSpace = $pdo->executeQuery($dataSpace, $sqlSpace))) {
				$x = $pdo->getError();
			}
            if ($resultSpace->rowCount() == 1) {
                $rowSpace = $resultSpace->fetch();
                $x = $rowSpace['name'];
            }
        }
        if ($row['locationDetail'] != '') {
            $x .=  "; ".$row['locationDetail'];
        }
		$excel->getActiveSheet()->setCellValueByColumnAndRow(3, $r, $x);
		//Column E
		$x = '';
		if ($row['ownershipType'] == 'School') {
            $x = $_SESSION[$guid]['organisationNameShort'];
        } elseif ($row['ownershipType'] == 'Individual') {
            $x = 'Individual';
        }
        if ($row['gibbonPersonIDOwnership'] != '') {
			$dataPerson = array('gibbonPersonID' => $row['gibbonPersonIDOwnership']);
			$sqlPerson = 'SELECT title, preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
			if (is_null($resultPerson = $pdo->executeQuery($dataPerson, $sqlPerson))) {
				$x .= $pdo->getError();
			}
            if ($resultPerson->rowCount() == 1) {
                $rowPerson = $resultPerson->fetch();
                $x .= "; ".Format::name($rowPerson['title'], $rowPerson['preferredName'], $rowPerson['surname'], 'Staff', false, true);
            }
        }
		$excel->getActiveSheet()->setCellValueByColumnAndRow(4, $r, $x);
		//Column F
		$excel->getActiveSheet()->setCellValueByColumnAndRow(5, $r, $row['status']."; ".$row['borrowable']);
 		//Column G
		$x = '';
        if ($row['purchaseDate'] == '') {
            $x .= __('Unknown');
        } else {
            $x .= dateConvertBack($guid, $row['purchaseDate']);
        }
        if ($row['vendor'] != '') {
            $x .= "; ".$row['vendor'];
        }
		$excel->getActiveSheet()->setCellValueByColumnAndRow(6, $r, $x);
		//Column H
		$x = '';
        $typeFieldsInner = unserialize($typeFields[$row['gibbonLibraryTypeID']]['fields']);
        $fields = unserialize($row['fields']);
        foreach ($typeFieldsInner as $typeField) {
            if (isset($fields[$typeField['name']])) {
                if ($fields[$typeField['name']] != '') {
                    $x .= __($typeField['name']).': ';
                    if (isset($fields[$typeField['name']])) {
                        $x .= $fields[$typeField['name']].' ; ';
                    }
                }
            }
        }
		$excel->getActiveSheet()->setCellValueByColumnAndRow(7, $r, $x);
    }
    if ($count == 0) {
		$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $r, __('There are no records to display.'));
    }
	$excel->exportWorksheet();
}
