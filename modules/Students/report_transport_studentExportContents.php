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

include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Students/report_transport_student.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo '<h1>';
    echo __($guid, 'Student Transport');
    echo '</h1>';

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonPerson.gibbonPersonID, transport, surname, preferredName, address1, address1District, address1Country, nameShort FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY transport, surname, preferredName";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

        $exp = new Gibbon\Excel();
        $exp->exportWithPage($guid, './report_transport_studentExportContents.php', 'studentTransport.xls');

	$excel = new Gibbon\Excel('studentTransport.xlsx');
	if ($excel->estimateCellCount($pdo) > 8000)    //  If too big, then render csv instead.
		return Gibbon\csv::generate($pdo, 'studentTransport');
	$excel->setActiveSheetIndex(0);
	$excel->getProperties()->setTitle('Student Transport');
	$excel->getProperties()->setSubject('Student Transport');
	$excel->getProperties()->setDescription('Student Transport');

	$excel->getActiveSheet()->setCellValueByColumnAndRow(0, 1, __($guid, 'Transport'));
	$excel->getActiveSheet()->setCellValueByColumnAndRow(1, 1, __($guid, 'Student'));
	$excel->getActiveSheet()->setCellValueByColumnAndRow(2, 1, __($guid, 'Address'));
	$excel->getActiveSheet()->setCellValueByColumnAndRow(3, 1, __($guid, 'Parents'));
	$excel->getActiveSheet()->setCellValueByColumnAndRow(4, 1, __($guid, 'Roll Group'));

	$r = 1;
    $count = 0;
    $rowNum = 'odd';
    while ($row = $result->fetch()) {
        $r++;
		$count++;
		//Column A
 		$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $r, $row['transport']);
        //Column B
 		$excel->getActiveSheet()->setCellValueByColumnAndRow(1, $r, formatName('', $row['preferredName'], $row['surname'], 'Student', true));
        //Column C
		$dataFamily = array('gibbonPersonID' => $row['gibbonPersonID']);
		$sqlFamily = 'SELECT gibbonFamily.gibbonFamilyID, nameAddress, homeAddress, homeAddressDistrict, homeAddressCountry 
			FROM gibbonFamily 
				JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) 
			WHERE gibbonPersonID=:gibbonPersonID';
		$resultFamily = $pdo->executeQuery($dataFamily, $sqlFamily, '_');
		$x = '';
        while ($rowFamily = $resultFamily->fetch()) {
            if ($rowFamily['nameAddress'] != '') {
                $x .= $rowFamily['nameAddress'].', ';
            }
            if (substr(rtrim($rowFamily['homeAddress']), -1) == ',') {
                $address = substr(rtrim($rowFamily['homeAddress']), 0, -1);
            } else {
                $address = rtrim($rowFamily['homeAddress']);
            }
            $address = addressFormat($address, rtrim($rowFamily['homeAddressDistrict']), rtrim($rowFamily['homeAddressCountry']));
            if ($address != false) {
                $address = explode(',', $address);
                for ($i = 0; $i < count($address); ++$i) {
                    $x .= $address[$i];
                    if ($i < (count($address) - 1)) {
                        $x .= ', ';
                    }
                }
            }
        }
  		$excel->getActiveSheet()->setCellValueByColumnAndRow(2, $r, $x);
        //Column D
        $contact = '';
        try {
            $dataFamily = array('gibbonPersonID' => $row['gibbonPersonID']);
            $sqlFamily = 'SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID';
            $resultFamily = $connection2->prepare($sqlFamily);
            $resultFamily->execute($dataFamily);
        } catch (PDOException $e) {
            $contact .= "<div class='error'>".$e->getMessage().'</div>';
        }
        while ($rowFamily = $resultFamily->fetch()) {
            try {
                $dataFamily2 = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                $sqlFamily2 = 'SELECT gibbonPerson.* FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                $resultFamily2 = $connection2->prepare($sqlFamily2);
                $resultFamily2->execute($dataFamily2);
            } catch (PDOException $e) {
                $contact .= "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($rowFamily2 = $resultFamily2->fetch()) {
                $contact .= '<u>'.formatName($rowFamily2['title'], $rowFamily2['preferredName'], $rowFamily2['surname'], 'Parent').'</u>, ';
                $numbers = 0;
                for ($i = 1; $i < 5; ++$i) {
                    if ($rowFamily2['phone'.$i] != '') {
                        if ($rowFamily2['phone'.$i.'Type'] != '') {
                            $contact .= '<i>'.$rowFamily2['phone'.$i.'Type'].':</i> ';
                        }
                        if ($rowFamily2['phone'.$i.'CountryCode'] != '') {
                            $contact .= '+'.$rowFamily2['phone'.$i.'CountryCode'].' ';
                        }
                        $contact .= $rowFamily2['phone'.$i].', ';
                        ++$numbers;
                    }
                }
                if ($numbers == 0) {
                    $contact .= "<span style='font-size: 85%; font-style: italic'>No number available.</span>, ";
                }
            }
        }
        if (substr($contact, -2) == ', ') {
            $contact = substr($contact, 0, -2);
        }
  		$excel->getActiveSheet()->setCellValueByColumnAndRow(3, $r, $contact);
  		$excel->getActiveSheet()->setCellValueByColumnAndRow(4, $r, $row['nameShort']);
    }
    if ($count == 0) {
  		$excel->getActiveSheet()->setCellValueByColumnAndRow(0, $r, __($guid, 'There are no records to display.'));
    }
    $excel->exportWorksheet();
}
