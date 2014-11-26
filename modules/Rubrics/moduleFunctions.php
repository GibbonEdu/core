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

function rubricEdit($guid, $connection2, $gibbonRubricID, $scaleName="") {
	$output=false ;
	
	//Get rows, columns and cells
	try {
		$dataRows=array("gibbonRubricID"=>$gibbonRubricID); 
		$sqlRows="SELECT * FROM gibbonRubricRow WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber" ;
		$resultRows=$connection2->prepare($sqlRows);
		$resultRows->execute($dataRows);
	}
	catch(PDOException $e) { }
	$rowCount=$resultRows->rowCount() ;
	
	try {
		$dataColumns=array("gibbonRubricID"=>$gibbonRubricID); 
		$sqlColumns="SELECT * FROM gibbonRubricColumn WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber" ;
		$resultColumns=$connection2->prepare($sqlColumns);
		$resultColumns->execute($dataColumns);
	}
	catch(PDOException $e) { }
	$columnCount=$resultColumns->rowCount() ;
	
	try {
		$dataCells=array("gibbonRubricID"=>$gibbonRubricID); 
		$sqlCells="SELECT * FROM gibbonRubricCell WHERE gibbonRubricID=:gibbonRubricID" ;
		$resultCells=$connection2->prepare($sqlCells);
		$resultCells->execute($dataCells);
	}
	catch(PDOException $e) { }
	$cellCount=$resultCells->rowcount() ;

	if ($rowCount<=0 OR $columnCount<=0) {
		$output.="<div class='error'>" ;
			$output.=_("The rubric cannot be drawn.") ;
		$output.="</div>" ;
	}
	else {
		$count=0 ;
		$rows=array() ;
		while ($rowRows=$resultRows->fetch()) {
			$rows[$count][0]=$rowRows["gibbonRubricRowID"] ;
			$rows[$count][1]=$rowRows["title"] ;
			$rows[$count][2]=$rowRows["sequenceNumber"] ;
			$rows[$count][3]=$rowRows["gibbonOutcomeID"] ;
			$count++ ;
		}
		$count=0 ;
		$columns=array() ;
		while ($rowColumns=$resultColumns->fetch()) {
			$columns[$count][0]=$rowColumns["gibbonRubricColumnID"] ;
			$columns[$count][1]=$rowColumns["title"] ;
			$columns[$count][2]=$rowColumns["sequenceNumber"] ;
			$columns[$count][3]=$rowColumns["gibbonScaleGradeID"] ;
			$count++ ;
		}
		$cells=array() ;
		while ($rowCells=$resultCells->fetch()) {
			$cells[$rowCells["gibbonRubricRowID"]][$rowCells["gibbonRubricColumnID"]][0]=$rowCells["contents"] ;
			$cells[$rowCells["gibbonRubricRowID"]][$rowCells["gibbonRubricColumnID"]][1]=$rowCells["gibbonRubricCellID"] ;
		}
	
	
		$output.="<style type=\"text/css\">" ;
			$output.="table.rubric { width: 100%; border-collapse: collapse; border: 1px solid #000 }" ;
			$output.="table.rubric tr { border: 1px solid #000 }" ;
			$output.="table.rubric td { border: 1px solid #000 }" ;
		$output.="</style>" ;
		$output.="<div class='linkTop'>" ;
			$output.="<a onclick='return confirm(\"Are you sure you want to edit rows and columns? Any unsaved changes will be lost.\")' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rubrics_edit_editRowsColumns.php&gibbonRubricID=$gibbonRubricID'>Edit Rows & Columns<img title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/ style='margin: 0px 1px -4px 3px'></a>" ;
		$output.="</div>" ;
		$output.="<form method='post' action='" . $_SESSION[$guid]['absoluteURL'] . "/modules/" . $_SESSION[$guid]["module"] . "/rubrics_edit_editCellProcess.php?gibbonRubricID=$gibbonRubricID'>" ;
			$output.="<table cellspacing='0' class='rubric'>" ;
				//Create header
				$output.="<tr class='head'>" ;
					$output.="<td style='width: 100px; background-color: #fff; border-left: 1px solid #fff; border-top: 1px solid #fff'></td>" ;
					for ($n=0; $n<$columnCount; $n++) {
						$output.="<td style='vertical-align: bottom'>" ;
							if ($columns[$n][3]!="") {
								try {
									$dataOutcome=array("gibbonScaleGradeID"=>$columns[$n][3]); 
									$sqlOutcome="SELECT * FROM gibbonScaleGrade WHERE gibbonScaleGradeID=:gibbonScaleGradeID" ;
									$resultOutcome=$connection2->prepare($sqlOutcome);
									$resultOutcome->execute($dataOutcome);
								}
								catch(PDOException $e) { print "Error" ; }
								if ($resultOutcome->rowCount()!=1) {
									 print _("Error") ;
								}
								else {
									$rowOutcome=$resultOutcome->fetch() ;
									$output.="<b>" . _($rowOutcome["descriptor"]) . " (" . _($rowOutcome["value"]) . ")</b><br/>" ;
									$output.="<span style='font-size: 85%'><i>" . _($scaleName) . " Scale</i></span><br/>" ;
								}
							}
							else {
								$output.="<b>" . $columns[$n][1] . "</b><br/>" ;
							}
							$output.="<a onclick='return confirm(\"" . _('Are you sure you want to delete this column? Any unsaved changes will be lost.') . "\")' href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/rubrics_edit_deleteColumnProcess.php?gibbonRubricID=$gibbonRubricID&gibbonRubricColumnID=" . $columns[$n][0] . "&address=" . $_GET["q"] . "'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/ style='margin: 2px 0px 0px 0px'></a>" ;	
						$output.="</td>" ;
					}
				$output.="</tr>" ;
				
				//Create body
				for ($i=0; $i<$rowCount; $i++) {
					$output.="<tr style='height: auto'>" ;
						$output.="<td style='background-color: #666'>" ;
							if ($rows[$i][3]!="") {
								try {
									$dataOutcome=array("gibbonOutcomeID"=>$rows[$i][3]); 
									$sqlOutcome="SELECT * FROM gibbonOutcome WHERE gibbonOutcomeID=:gibbonOutcomeID" ;
									$resultOutcome=$connection2->prepare($sqlOutcome);
									$resultOutcome->execute($dataOutcome); 
								}
								catch(PDOException $e) { print "Error" ; }
								if ($resultOutcome->rowCount()!=1) {
									 print _("Error") ;
								}
								else {
									$rowOutcome=$resultOutcome->fetch() ;
									if ($rowOutcome["category"]=="") {
										$output.="<b>" . $rowOutcome["name"] . "</b><br/>" ;
									}
									else {
										$output.="<b>" . $rowOutcome["name"] . "</b><i> - " . $rowOutcome["category"] . "</i><br/>" ;
									}
									
									$output.="<span style='font-size: 85%'><i>" . $rowOutcome["scope"] . " " . _('Outcome') . "</i></span><br/>" ;
								}
							}
							else {
								$output.="<b>" . $rows[$i][1] . "</b><br/>" ;
							}
							$output.="<a onclick='return confirm(\"" . _('Are you sure you want to delete this row? Any unsaved changes will be lost.') . "\")' href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/rubrics_edit_deleteRowProcess.php?gibbonRubricID=$gibbonRubricID&gibbonRubricRowID=" . $rows[$i][0] . "&address=" . $_GET["q"] . "'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/ style='margin: 2px 0px 0px 0px'></a><br/>" ;
						$output.="</td>" ;
						for ($n=0; $n<$columnCount; $n++) {
							$output.="<td style='background: none; background-color: #fff; padding: 0px; margin: 0px'>" ;
								$output.="<textarea name='cell[]' style='background-color: #fff!important; border: 1px none #fff; font-size: 85%; width: 100%; height: 100px; margin: 0; padding: 0; resize: none'>" ; if (isset($cells[$rows[$i][0]][$columns[$n][0]][0])) { $output.=$cells[$rows[$i][0]][$columns[$n][0]][0] ; } $output.="</textarea>" ;
								$output.="<input type='hidden' name='gibbonRubricCellID[]' value='" ; if (isset($cells[$rows[$i][0]][$columns[$n][0]][1])) { $output.=$cells[$rows[$i][0]][$columns[$n][0]][1] ; } $output.="'>" ;
								$output.="<input type='hidden' name='gibbonRubricColumnID[]' value='" . $columns[$n][0] . "'>" ;
								$output.="<input type='hidden' name='gibbonRubricRowID[]' value='" . $rows[$i][0] . "'>" ;
							$output.="</td>" ;
						}
					$output.="</tr>" ;
				}
			$output.="</table>" ;
			$output.="<table cellspacing='0' style='width: 100%;'>" ;
				$output.="<tr style='border: 1px none #000'>" ;
					$output.="<td class='right' colspan=3 style='border: 1px none #000'>" ;
						$output.="<input type='hidden' name='address' value='" . $_SESSION[$guid]["address"] . "'>" ;
						$output.="<input type='submit' value='" . _('Submit') . "'>" ;
					$output.="</td>" ;
				$output.="</tr>" ;
			$output.="</table>" ;
		$output.="</form>" ;
	}
	
	return $output ;
}

//If $mark=TRUE, then marking tools are made available, otherwise it is view only
function rubricView($guid, $connection2, $gibbonRubricID, $mark, $gibbonPersonID="", $contextDBTable="", $contextDBTableIDField="", $contextDBTableID="", $contextDBTableGibbonRubricIDField="", $contextDBTableNameField="", $contextDBTableDateField="") {
	$output=false ;
	
	try {
		$data=array("gibbonRubricID"=>$gibbonRubricID); 
		$sql="SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) {
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	if ($result->rowCount()!=1) {
		print "<div class='error'>" ;
			print _("The specified record cannot be found.") ;
		print "</div>" ; 
	}
	else {
		$row=$result->fetch() ;
		
		//Get rows, columns and cells
		try {
			$dataRows=array("gibbonRubricID"=>$gibbonRubricID); 
			$sqlRows="SELECT * FROM gibbonRubricRow WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber" ;
			$resultRows=$connection2->prepare($sqlRows);
			$resultRows->execute($dataRows);
		}
		catch(PDOException $e) { }
		$rowCount=$resultRows->rowCount() ;
		
		try {
			$dataColumns=array("gibbonRubricID"=>$gibbonRubricID); 
			$sqlColumns="SELECT * FROM gibbonRubricColumn WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber" ;
			$resultColumns=$connection2->prepare($sqlColumns);
			$resultColumns->execute($dataColumns);
		}
		catch(PDOException $e) { }
		$columnCount=$resultColumns->rowCount() ;
		
		try {
			$dataCells=array("gibbonRubricID"=>$gibbonRubricID); 
			$sqlCells="SELECT * FROM gibbonRubricCell WHERE gibbonRubricID=:gibbonRubricID" ;
			$resultCells=$connection2->prepare($sqlCells);
			$resultCells->execute($dataCells);
		}
		catch(PDOException $e) { }
		$cellCount=$resultCells->rowcount() ;
		
		if ($rowCount<=0 OR $columnCount<=0) {
			$output.="<div class='error'>" ;
				$output.=_("The rubric cannot be drawn.") ;
			$output.="</div>" ;
		}
		else {
			$count=0 ;
			$rows=array() ;
			while ($rowRows=$resultRows->fetch()) {
				$rows[$count][0]=$rowRows["gibbonRubricRowID"] ;
				$rows[$count][1]=$rowRows["title"] ;
				$rows[$count][2]=$rowRows["sequenceNumber"] ;
				$rows[$count][3]=$rowRows["gibbonOutcomeID"] ;
				$count++ ;
			}
			$count=0 ;
			$columns=array() ;
			while ($rowColumns=$resultColumns->fetch()) {
				$columns[$count][0]=$rowColumns["gibbonRubricColumnID"] ;
				$columns[$count][1]=$rowColumns["title"] ;
				$columns[$count][2]=$rowColumns["sequenceNumber"] ;
				$columns[$count][3]=$rowColumns["gibbonScaleGradeID"] ;
				$count++ ;
			}
			$cells=array() ;
			while ($rowCells=$resultCells->fetch()) {
				$cells[$rowCells["gibbonRubricRowID"]][$rowCells["gibbonRubricColumnID"]][0]=$rowCells["contents"] ;
				$cells[$rowCells["gibbonRubricRowID"]][$rowCells["gibbonRubricColumnID"]][1]=$rowCells["gibbonRubricCellID"] ;
			}
			
			//Get other uses of this rubric in this context
			$contexts=array() ;
			$contextCount=0 ;
			if ($contextDBTable!="" AND $contextDBTableIDField!="" AND $contextDBTableID!="" AND $contextDBTableGibbonRubricIDField!="" AND $contextDBTableNameField!="" AND $contextDBTableDateField!="") { 
				try {
					$dataContext=array("gibbonPersonID"=>$gibbonPersonID); 
					$sqlContext="SELECT * FROM gibbonRubricEntry JOIN $contextDBTable ON (gibbonRubricEntry.contextDBTableID=$contextDBTable.$contextDBTableIDField AND gibbonRubricEntry.gibbonRubricID=$contextDBTable.$contextDBTableGibbonRubricIDField) JOIN gibbonRubricCell ON (gibbonRubricEntry.gibbonRubricCellID=gibbonRubricCell.gibbonRubricCellID) WHERE contextDBTable='$contextDBTable' AND gibbonRubricEntry.gibbonPersonID=:gibbonPersonID AND NOT $contextDBTableDateField IS NULL ORDER BY $contextDBTableDateField DESC" ;
					$resultContext=$connection2->prepare($sqlContext);
					$resultContext->execute($dataContext);
				}
				catch(PDOException $e) { print $e->getMessage() ; }
				while ($rowContext=$resultContext->fetch()) {
					if (isset($cells[$rowContext["gibbonRubricRowID"]][$rowContext["gibbonRubricColumnID"]][2])) {
						$cells[$rowContext["gibbonRubricRowID"]][$rowContext["gibbonRubricColumnID"]][2].=$rowContext[$contextDBTableNameField] . " (" . dateConvertBack($guid, $rowContext[$contextDBTableDateField]) . ")<br/>" ;
					}
					else {
						$cells[$rowContext["gibbonRubricRowID"]][$rowContext["gibbonRubricColumnID"]][2]=$rowContext[$contextDBTableNameField] . " (" . dateConvertBack($guid, $rowContext[$contextDBTableDateField]) . ")<br/>" ;
					}
				}
			}
			
			
			if ($mark==TRUE) {
				print "<p>" ;
					print _("Click on any of the cells below to highlight them. Data is saved automatically after each click.") ;
				print "</p>" ;
			}
		
			//Controls for viewing mode	
			if ($gibbonPersonID!="") {
				$output.="<script type='text/javascript'>" ;
					$output.="$(document).ready(function(){" ;
						$output.="$('div.historical').css('display','none');" ;
								
						$output.="$('#type').change(function(){" ;
							$output.="if ($('select.type option:selected').val()=='Current' ) {" ;
								$output.="$('div.historical').css('display','none');" ;
								$output.="$('div.currentView').css('display','block');" ;
							$output.="} " ;
							$output.="else if ($('select.type option:selected').val()=='Historical' ) {" ;
								$output.="$('div.currentView').css('display','none');" ;
								$output.="$('div.historical').css('display','block');" ;
							$output.="}" ; 
						$output.="});" ;
					$output.="});" ;
				$output.="</script>" ;	
				$output.="<div class='linkTop'>" ;
					$output.="Viewing Mode: <select name='type' id='type' class='type' style='width: 152px; float: none'>" ;
						$output.="<option id='type' name='type' value='Current'>". _('Current') . "</option>" ;
						$output.="<option id='type' name='type' value='Historical'>" . _('Historical Data') . "</option>" ;
					$output.="</select>" ;
				$output.="</div>" ;
			}
			
			$output.="<style type=\"text/css\">" ;
				$output.="table.rubric { width: 100%; border-collapse: collapse; border: 1px solid #000 }" ;
				$output.="table.rubric tr { border: 1px solid #000 }" ;
				$output.="table.rubric td { border: 1px solid #000 }" ;
			$output.="</style>" ;
			$output.="<form method='post' action='" . $_SESSION[$guid]['absoluteURL'] . "/modules/" . $_SESSION[$guid]["module"] . "/rubrics_data_editProcess.php?gibbonRubricID=$gibbonRubricID&gibbonPersonID=$gibbonPersonID'>" ;
				$output.="<table cellspacing='0' class='rubric'>" ;
					//Create header
					$output.="<tr class='head'>" ;
						$output.="<td style='width: 100px; background: none; background-color: #ffffff; border-left: 1px solid #fff; border-top: 1px solid #fff'></td>" ;
						for ($n=0; $n<$columnCount; $n++) {
							$output.="<td style='vertical-align: bottom'>" ;
								if ($columns[$n][3]!="") {
									try {
										$dataOutcome=array("gibbonScaleGradeID"=>$columns[$n][3]); 
										$sqlOutcome="SELECT * FROM gibbonScaleGrade WHERE gibbonScaleGradeID=:gibbonScaleGradeID" ;
										$resultOutcome=$connection2->prepare($sqlOutcome);
										$resultOutcome->execute($dataOutcome);
									}
									catch(PDOException $e) { print "Error" ; }
									if ($resultOutcome->rowCount()!=1) {
										 print _("Error") ;
									}
									else {
										$rowOutcome=$resultOutcome->fetch() ;
										$output.="<b>" . _($rowOutcome["descriptor"]) . " (" . _($rowOutcome["value"]) . ")</b><br/>" ;
										//Try to get scale name
										if ($row["gibbonScaleID"]!="") { 
											try {
												$dataScale=array("gibbonScaleID"=>$row["gibbonScaleID"]); 
												$sqlScale="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
												$resultScale=$connection2->prepare($sqlScale);
												$resultScale->execute($dataScale);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											
											if ($resultScale->rowCount()==1) {
												$rowScale=$resultScale->fetch() ;
											}
										}
										if ($rowScale["name"]!="") {
											$output.="<span style='font-size: 85%'><i>" . _($rowScale["name"]) . " Scale</i></span><br/>" ;
										}
									}
								}
								else {
									$output.="<b>" . $columns[$n][1] . "</b><br/>" ;
								}
							$output.="</td>" ;
						}
					$output.="</tr>" ;
					
					//Create body
					for ($i=0; $i<$rowCount; $i++) {
						$output.="<tr style='height: auto'>" ;
							$output.="<td style='background: none!important; background-color: #666!important; color: #fff; vertical-align: top; padding: 0px!important'>" ;
								if ($rows[$i][3]!="") {
									try {
										$dataOutcome=array("gibbonOutcomeID"=>$rows[$i][3]); 
										$sqlOutcome="SELECT * FROM gibbonOutcome WHERE gibbonOutcomeID=:gibbonOutcomeID" ;
										$resultOutcome=$connection2->prepare($sqlOutcome);
										$resultOutcome->execute($dataOutcome); 
									}
									catch(PDOException $e) { print "Error" ; }
									if ($resultOutcome->rowCount()!=1) {
										 print _("Error") ;
									}
									else {
										$rowOutcome=$resultOutcome->fetch() ;
										
										//Check if outcome is specified in unit
										if ($contextDBTable!="" AND $contextDBTableID!="" AND $contextDBTableIDField!="") {
											try {
												$dataOutcome2=array("gibbonOutcomeID"=>$rows[$i][3], "contextDBTableID"=>$contextDBTableID); 
												$sqlOutcome2="SELECT * FROM gibbonOutcome JOIN gibbonUnitOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) JOIN $contextDBTable ON ($contextDBTable.gibbonUnitID=gibbonUnitOutcome.gibbonUnitID) WHERE gibbonOutcome.gibbonOutcomeID=:gibbonOutcomeID AND $contextDBTableIDField=:contextDBTableID" ;
												$resultOutcome2=$connection2->prepare($sqlOutcome2);
												$resultOutcome2->execute($dataOutcome2); 
											}
											catch(PDOException $e) { print "Error" ; }
											if ($resultOutcome2->rowCount()) {
												$output.="<img style='float: right' title='This outcome is one of the unit outcomes.' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> " ;
											}
										}
										
										if ($rowOutcome["category"]=="") {
											$output.="<span title='" . htmlprep($rowOutcome["description"]) . "'><b>" . $rowOutcome["name"] . "</b></span><br/>" ;
										}
										else {
											$output.="<span title='" . htmlprep($rowOutcome["description"]) . "'><b>" . $rowOutcome["name"] . "</b><i> - " . $rowOutcome["category"] . "</i></span><br/>" ;
										}
										$output.="<span style='font-size: 85%'><i>" . $rowOutcome["scope"] . " " . _('Outcome') . "</i></span><br/>" ;
									}
								}
								else {
									$output.="<b>" . $rows[$i][1] . "</b><br/>" ;
								}
							$output.="</td>" ;
							for ($n=0; $n<$columnCount; $n++) {
								if ($mark==TRUE) {
									$output.="<script type='text/javascript'>" ;
										$output.="$(document).ready(function(){" ;
											$output.="$(\"#" . $rows[$i][0] . "-" . $columns[$n][0] . "\").click(function(){" ;
												$output.="if ($(\"#" . $rows[$i][0] . "-" . $columns[$n][0] . "\").css('background-color')==\"rgb(251, 251, 251)\" ) {" ;
													$output.="$(\"#" . $rows[$i][0] . "-" . $columns[$n][0] . "\").css('background', 'none').css('background-color', '#79FA74');" ;
													$output.="var request=$.ajax({ url: \"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Rubrics/rubrics_data_saveAjax.php\", type: \"GET\", data: {mode: \"Add\", gibbonRubricID : \"" . $gibbonRubricID . "\", gibbonPersonID : \"" . $gibbonPersonID . "\",gibbonRubricCellID : \"" . $cells[$rows[$i][0]][$columns[$n][0]][1] . "\",contextDBTable : \"" . $contextDBTable . "\",contextDBTableID : \"" . $contextDBTableID . "\"}, dataType: \"html\"});" ;
												$output.="}" ; 
												$output.="else {" ;
													$output.="$(\"#" . $rows[$i][0] . "-" . $columns[$n][0] . "\").css('background', 'none').css('background-color', '#fbfbfb');" ;
													$output.="var request=$.ajax({ url: \"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Rubrics/rubrics_data_saveAjax.php\", type: \"GET\", data: {mode: \"Remove\", gibbonRubricID : \"" . $gibbonRubricID . "\", gibbonPersonID : \"" . $gibbonPersonID . "\",gibbonRubricCellID : \"" . $cells[$rows[$i][0]][$columns[$n][0]][1] . "\",contextDBTable : \"" . $contextDBTable . "\",contextDBTableID : \"" . $contextDBTableID . "\"}, dataType: \"html\"});" ;
												$output.="}" ;
											 $output.="});" ;
										$output.="});" ;
									$output.="</script>" ;
								}
								
								try {
									$dataEntry=array("gibbonRubricCellID"=>$cells[$rows[$i][0]][$columns[$n][0]][1], "gibbonPersonID"=>$gibbonPersonID, "contextDBTable"=>$contextDBTable, "contextDBTableID"=>$contextDBTableID); 
									$sqlEntry="SELECT * FROM gibbonRubricEntry WHERE gibbonRubricCellID=:gibbonRubricCellID AND gibbonPersonID=:gibbonPersonID AND contextDBTable=:contextDBTable AND contextDBTableID=:contextDBTableID" ;
									$resultEntry=$connection2->prepare($sqlEntry);
									$resultEntry->execute($dataEntry);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								$bgcolor="#fbfbfb" ;
								if ($resultEntry->rowCount()==1) {
									$bgcolor="#79FA74" ;
								}
								$output.="<td id='" . $rows[$i][0] . "-" . $columns[$n][0] . "' style='background: none; background-color: $bgcolor; height: 100%; vertical-align: top'>" ;
									$output.="<div class='currentView' style='font-size: 90%'>" . $cells[$rows[$i][0]][$columns[$n][0]][0] . "</div>" ;
									$output.="<div class='historical' style='font-size: 90%'>" ;
										
										if (isset($cells[$rows[$i][0]][$columns[$n][0]][2])) {
											$arrayHistorical=explode("<br/>", $cells[$rows[$i][0]][$columns[$n][0]][2]) ;
											$countHistorical=count($arrayHistorical)-1 ;
										}
										else {
											$arrayHistorical=array() ;
											$countHistorical=0 ;
										}
										$countHistorical=count($arrayHistorical)-1 ;
										if ($countHistorical>0) {
											$output.="<b><u>" . _('Total Occurences:') . " " . $countHistorical . "</u></b><br/>" ;
											for ($h=0; $h<$countHistorical; $h++) {
												if ($h<7) {
													$output.=($h+1) . ") " . $arrayHistorical[$h] . "<br/>" ;
												}
											}
											if ($countHistorical>7) {
												$output.="<b>" . _('Older occurrences not shown...') . "</b>" ;
											}
										}
									$output.="</div>" ;
									$output.="<input type='hidden' name='gibbonRubricColumnID[]' value='" . $columns[$n][0] . "'>" ;
									$output.="<input type='hidden' name='gibbonRubricRowID[]' value='" . $rows[$i][0] . "'>" ;
									$output.="<input type='hidden' name='gibbonRubricCellID[]' value='" . $cells[$rows[$i][0]][$columns[$n][0]][1] . "'>" ;
								$output.="</td>" ;
							}
						$output.="</tr>" ;
					}
				$output.="</table>" ;
			$output.="</form>" ;
		}
	}
	
	return $output ;
}


?>
