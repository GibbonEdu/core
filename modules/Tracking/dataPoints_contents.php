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

Certain code below was taken from the PHPExcel examples, which are licensed under the GNU GPL.
*/

@session_start() ;

//Increase max execution time, as this stuff gets big
ini_set('max_execution_time', 600);

//System includes
include "../../config.php" ;
include "../../functions.php" ;
include "../../version.php" ;

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

//Module includes
include "./moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Tracking/dataPoints.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	/** Include PHPExcel */
	require_once $_SESSION[$guid]["absolutePath"] . '/lib/PHPExcel/Classes/PHPExcel.php';

	// Create new PHPExcel object
	$excel = new PHPExcel();

	//Create border style for use locale_filter_matches
	$style_border= array('borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => '766f6e'),), 'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => '766f6e'),), 'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => '766f6e'),), 'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => '766f6e'),)));
	$style_head_fill=array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'B89FE2'))) ;
	$style_head_fill2=array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'C5D9F1'))) ;
	$style_head_fill3=array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '8DB4E2'))) ;
	$style_head_fill4=array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '538CD6'))) ;
	$style_head_fill5=array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'EBF1DF'))) ;
	$style_head_fill6=array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'D8E3BE'))) ;
	$style_head_fill7=array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'C4D69E'))) ;

	// Set document properties
	$excel->getProperties()->setCreator(formatName("",$_SESSION[$guid]["preferredName"], $_SESSION[$guid]["surname"], "Staff"))
		 ->setLastModifiedBy(formatName("",$_SESSION[$guid]["preferredName"], $_SESSION[$guid]["surname"], "Staff"))
		 ->setTitle(__($guid, 'Assessment Data Points'))
		 ->setDescription(__($guid, 'This information is confidential. Generated by Gibbon (https://gibbonedu.org).')) ;

	 //Get and check settings
 	$externalAssessmentDataPoints=unserialize(getSettingByScope($connection2, "Tracking", "externalAssessmentDataPoints")) ;
 	$internalAssessmentDataPoints=unserialize(getSettingByScope($connection2, "Tracking", "internalAssessmentDataPoints")) ;
	$internalAssessmentTypes=explode(",", getSettingByScope($connection2, "Formal Assessment", "internalAssessmentTypes")) ;
 	if (count($externalAssessmentDataPoints)==0 AND count($internalAssessmentDataPoints)==0) { //Seems like things are not configured, so show error
		$excel->setActiveSheetIndex(0) ->setCellValue('A1', __($guid, 'An error has occurred.')) ;
 	}
 	else { //Seems like things are configured, so proceed
		//Get year groups and create sheets
		$yearGroups=getYearGroups($connection2) ;
		if ($yearGroups=="") {
			$excel->setActiveSheetIndex(0)->setCellValue('A1', __($guid, 'An error has occurred.')) ;
		}
		else {
			//GET ALL INTERNAL ASSESSMENT RESULTS FOR ALL STUDENTS, AND CACHE THEM FOR USE LATER
			$internalResults=array() ;
			try {
				$data=array();
				$sql="SELECT gibbonStudentEnrolment.gibbonYearGroupID, gibbonCourse.nameShort AS course, gibbonInternalAssessmentColumn.type, gibbonPersonIDStudent, attainmentValue, completeDate, gibbonInternalAssessmentColumn.name AS assessment FROM gibbonInternalAssessmentEntry JOIN gibbonPerson ON (gibbonInternalAssessmentEntry.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID) JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID) JOIN gibbonCourseClass ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID) ORDER BY gibbonCourse.nameShort, gibbonInternalAssessmentColumn.name, gibbonPersonIDStudent, completeDate DESC" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { }
			while ($row=$result->fetch()) {
				$internalIndex=$row["gibbonYearGroupID"] . "-" . $row["course"] . "-" . $row["type"] . "-" . $row["gibbonPersonIDStudent"] . "-" . $row["assessment"] ;
				$internalResults[$internalIndex]=$row["attainmentValue"] ;
			}
			
			//GET ALL EXTERNAL ASSESSMENT RESULTS FOR ALL STUDENTS, AND CACHE THEM FOR USE LATER
			$externalResults=array() ;
			try {
				$data=array();
				$sql="SELECT gibbonExternalAssessment.nameShort AS assessment, gibbonExternalAssessmentField.name AS field, gibbonExternalAssessmentField.category, gibbonPerson.gibbonPersonID, gibbonScaleGrade.value, date FROM gibbonExternalAssessmentStudent JOIN gibbonPerson ON (gibbonExternalAssessmentStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) JOIN gibbonExternalAssessmentStudentEntry ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=gibbonExternalAssessmentStudent.gibbonExternalAssessmentStudentID) JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID) JOIN gibbonScaleGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) ORDER BY gibbonExternalAssessment.nameShort, category, gibbonPersonID, date DESC" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { print $e->getMessage() ; }
			while ($row=$result->fetch()) {
				$externalIndex=$row["assessment"] . "-" . $row["category"] . "-" . $row["field"] . "-" . $row["gibbonPersonID"] ;
				if (isset($externalResults[$externalIndex])==FALSE) {
					$externalResults[$externalIndex]=$row["value"] ;
				}
			}


			for ($i=0; $i<count($yearGroups); $i=$i+2) {
				//SET UP SHEET WITH HEADERS, STUDENT INFORMATION ETC
				$activeRow=4 ;
				if ($i>0) {
					$excel->createSheet(); //Create sheet
				}
				$excel->setActiveSheetIndex($i/2) ;
				$excel->getActiveSheet()->setTitle(__($guid, $yearGroups[($i+1)])) ; //Rename sheet
				$excel->getActiveSheet()
					->setCellValue('A3', __($guid, 'Username'))
					->setCellValue('B3', __($guid, 'Surname'))
				   ->setCellValue('C3', __($guid, 'Preferred Name'))
				   ->setCellValue('D3', __($guid, 'DOB'))
				   ->setCellValue('E3', __($guid, 'Roll Group'))
				   ->setCellValue('F3', __($guid, 'Status'));
				foreach(range('A','F') as $columnID) {
					$excel->getActiveSheet()->getStyle($columnID . "3")->applyFromArray($style_border);
					$excel->getActiveSheet()->getStyle($columnID . "3")->applyFromArray($style_head_fill) ;
					$excel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
				}

				$columns=array() ;
				$activeColumn=6 ;
				//GET EXTERNAL ASSESSMENTS/CATEGORIES AND CREATE HEADERS
				try {
					$data=array("gibbonYearGroupID"=>$yearGroups[$i]);
					$sql="SELECT gibbonExternalAssessment.gibbonExternalAssessmentID, gibbonExternalAssessment.nameShort AS assessment, gibbonExternalAssessmentField.category, gibbonExternalAssessmentField.name AS field
						FROM gibbonExternalAssessment
						JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID)
						ORDER BY gibbonExternalAssessment.name, gibbonExternalAssessmentField.category, gibbonExternalAssessmentField.name" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				while ($row=$result->fetch()) {
					foreach ($externalAssessmentDataPoints AS $point) {
						if ($point["gibbonExternalAssessmentID"]==$row["gibbonExternalAssessmentID"] AND $point["category"]==$row["category"]) {
							if (!(strpos($point["gibbonYearGroupIDList"],$yearGroups[$i])===FALSE)) {
								//Output data
								$excel->getActiveSheet()->setCellValue(num2alpha($activeColumn) . '1', $row["assessment"]);
								$excel->getActiveSheet()->setCellValue(num2alpha($activeColumn) . '2', substr($row["category"], (strpos($row["category"], "_")+1)));
								$excel->getActiveSheet()->setCellValue(num2alpha($activeColumn) . '3', $row["field"]);
								$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "1")->applyFromArray($style_border);
								$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "1")->applyFromArray($style_head_fill7) ;
								$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
								$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "2")->applyFromArray($style_border);
								$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "2")->applyFromArray($style_head_fill6) ;
								$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
								$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "3")->applyFromArray($style_border);
								$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "3")->applyFromArray($style_head_fill5) ;
								$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "3")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
								$excel->getActiveSheet()->getColumnDimension(num2alpha($activeColumn))->setAutoSize(true);

								//Cache column for later user
								$columns[($activeColumn-6)]["columnType"]='External' ;
								$columns[($activeColumn-6)]["count"]=($activeColumn-6) ;
								$columns[($activeColumn-6)]["gibbonExternalAssessmentID"]=$row["gibbonExternalAssessmentID"] ;
								$columns[($activeColumn-6)]["assessment"]=$row["assessment"] ;
								$columns[($activeColumn-6)]["category"]=$row["category"] ;
								$columns[($activeColumn-6)]["field"]=$row["field"] ;

								$activeColumn++ ;
							}
						}
					}
				}

				//GET INTERNAL ASSESSMENTS AND CREATE HEADERS
				try {
					$data=array("gibbonYearGroupID"=>$yearGroups[$i]);
					$sql="SELECT DISTINCT gibbonYearGroup.gibbonYearGroupID, gibbonYearGroup.name AS yearGroup, sequenceNumber, gibbonCourse.nameShort AS course, gibbonInternalAssessmentColumn.name AS assessment FROM gibbonYearGroup JOIN gibbonCourse ON (gibbonCourse.gibbonYearGroupIDList LIKE concat('%', gibbonYearGroup.gibbonYearGroupID, '%')) JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE sequenceNumber<=(SELECT sequenceNumber FROM gibbonYearGroup AS year WHERE year.gibbonYearGroupID=:gibbonYearGroupID) ORDER BY sequenceNumber, gibbonCourse.nameShort" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				while ($row=$result->fetch()) {
					foreach ($internalAssessmentTypes AS $type) {
						foreach ($internalAssessmentDataPoints AS $point) {
							if ($point["type"]==$type) {
								if (!(strpos($point["gibbonYearGroupIDList"],$row["gibbonYearGroupID"])===FALSE)) {
									//Output data
									$excel->getActiveSheet()->setCellValue(num2alpha($activeColumn) . '1', $row["yearGroup"]);
									$excel->getActiveSheet()->setCellValue(num2alpha($activeColumn) . '2', $type . "\n" . $row["assessment"]);
									$excel->getActiveSheet()->setCellValue(num2alpha($activeColumn) . '3', $row["course"]);
									$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "1")->applyFromArray($style_border);
									$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "1")->applyFromArray($style_head_fill4) ;
									$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
									$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "2")->applyFromArray($style_border);
									$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "2")->applyFromArray($style_head_fill3) ;
									$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
									$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "2")->getAlignment()->setWrapText(true);
									$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "3")->applyFromArray($style_border);
									$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "3")->applyFromArray($style_head_fill2) ;
									$excel->getActiveSheet()->getStyle(num2alpha($activeColumn) . "3")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
									$excel->getActiveSheet()->getColumnDimension(num2alpha($activeColumn))->setAutoSize(true);

									//Cache column for later user
									$columns[($activeColumn-6)]["columnType"]='Internal' ;
									$columns[($activeColumn-6)]["count"]=($activeColumn-6) ;
									$columns[($activeColumn-6)]["gibbonYearGroupID"]=$row["gibbonYearGroupID"] ;
									$columns[($activeColumn-6)]["yearGroup"]=$row["yearGroup"] ;
									$columns[($activeColumn-6)]["type"]=$type ;
									$columns[($activeColumn-6)]["course"]=$row["course"] ;
									$columns[($activeColumn-6)]["assessment"]=$row["assessment"] ;

									$activeColumn++ ;
								}
							}
						}
					}
				}


				//GET STUDENTS AND LIST THEIR DETAILS
				try {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonYearGroupID"=>$yearGroups[$i]);
					$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, username, dob, nameShort AS rollgroup, status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID = gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID = gibbonRollGroup.gibbonRollGroupID) WHERE (status='Full' OR status='Left') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID ORDER BY status, surname, preferredName" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) {
					$excel->getActiveSheet()
						->setCellValue('A2', __($guid, "Your request failed due to a database error.")) ;
				}
				if ($result->rowCount()<1) {
					$excel->getActiveSheet()
						->setCellValue('A2', __($guid, "There are no records to display.")) ;
				}
				else {
					while ($row=$result->fetch()) {
						//Set rows headers for students
						$excel->getActiveSheet()
							->setCellValue('A' . $activeRow, $row["username"])
							->setCellValue('B' . $activeRow, $row["surname"])
							->setCellValue('C' . $activeRow, $row["preferredName"])
							->setCellValue('D' . $activeRow, dateConvertBack($guid, $row["dob"]))
							->setCellValue('E' . $activeRow, $row["rollgroup"])
							->setCellValue('F' . $activeRow, $row["status"]) ;
						$excel->getActiveSheet()->getStyle('A' . $activeRow)->applyFromArray($style_border);
						$excel->getActiveSheet()->getStyle('B' . $activeRow)->applyFromArray($style_border);
						$excel->getActiveSheet()->getStyle('C' . $activeRow)->applyFromArray($style_border);
						$excel->getActiveSheet()->getStyle('D' . $activeRow)->applyFromArray($style_border);
						$excel->getActiveSheet()->getStyle('E' . $activeRow)->applyFromArray($style_border);
						$excel->getActiveSheet()->getStyle('F' . $activeRow)->applyFromArray($style_border);

						//Create cells for each of the columns cached earlier
						foreach ($columns AS $column) {
							$excel->getActiveSheet()->getStyle(num2alpha($column["count"]+6) . $activeRow)->applyFromArray($style_border);
							if ($column["columnType"]=="External") { //Output external assessment data
								$externalIndex=$column["assessment"] . "-" . $column["category"] . "-" . $column["field"] . "-" . $row["gibbonPersonID"] ;
								if (isset($externalResults[$externalIndex])) {
									$excel->getActiveSheet()->setCellValue(num2alpha($column["count"]+6) . $activeRow, $externalResults[$externalIndex]) ;
								}
							}
							else { //Output internal assessment data
								$internalIndex=$column["gibbonYearGroupID"] . "-" . $column["course"] . "-" . $column["type"] . "-" . $row["gibbonPersonID"] . "-" . $column["assessment"] ;
								if (isset($internalResults[$internalIndex])) {
									$excel->getActiveSheet()->setCellValue(num2alpha($column["count"]+6) . $activeRow, $internalResults[$internalIndex]) ;
								}
							}
						}
						$activeRow++ ;
					}
				}
			}
		}
	}

	//FINALISE THE DOCUMENT SO IT IS READY FOR DOWNLOAD
	// Set active sheet index to the first sheet, so Excel opens this as the first sheet
	$excel->setActiveSheetIndex(0);

	// Redirect output to a client’s web browser (Excel2007)
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="Data Points.xlsx"');
	header('Cache-Control: max-age=0');
	// If you're serving to IE 9, then the following may be needed
	header('Cache-Control: max-age=1');

	// If you're serving to IE over SSL, then the following may be needed
	header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
	header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	header ('Pragma: public'); // HTTP/1.0

	$objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
	$objWriter->save('php://output');
	exit;
}
?>
