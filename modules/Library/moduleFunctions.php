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

function getBorrowingRecord($guid, $connection2, $gibbonPersonID) {
	$output=FALSE ;
	
	try {
		$data=array("gibbonPersonID"=>$gibbonPersonID); 
		$sql="SELECT gibbonLibraryItem.*, gibbonLibraryType.fields AS typeFields, timestampOut FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) JOIN gibbonLibraryItemEvent ON (gibbonLibraryItemEvent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemID) WHERE gibbonLibraryItemEvent.gibbonPersonIDStatusResponsible=:gibbonPersonID ORDER BY timestampOut DESC" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { $output.="<div class='error'>" . $e->getMessage() . "</div>" ; }
	if ($result->rowCount()<1) {
		$output.="<div class='error'>" ;
			$output.="The selected student has not borrowed any items." ;
		$output.="</div>" ;
	}
	else {
		$output.="<table style='width: 100%'>" ;
			$output.="<tr class='head'>" ;
				$output.="<th style='text-align: center'>" ;
						
				$output.="</th>" ;
				$output.="<th>" ;
					$output.="Name<br/>" ;
					$output.="<span style='font-size: 85%; font-style: italic'>Author/Producer</span>" ;
				$output.="</th>" ;
				$output.="<th>" ;
					$output.="ID<br/>" ;
				$output.="</th>" ;
				$output.="<th>" ;
					$output.="Location" ;
				$output.="</th>" ;
				$output.="<th>" ;
					$output.="Borrow Date<br/>" ;
					$output.="<span style='font-size: 85%; font-style: italic'>Return Date</span>" ;
				$output.="</th>" ;
				$output.="<th>" ;
					$output.="Action" ;
				$output.="</th>" ;
			$output.="</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			while ($row=$result->fetch()) {
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				if ((strtotime(date("Y-m-d"))-strtotime($row["returnExpected"]))/(60*60*24)>0 AND $row["status"]=="On Loan") {
					$rowNum="error" ;
				}
				
				//COLOR ROW BY STATUS!
				$output.="<tr class=$rowNum style='opacity: 1.0'>" ;
					$output.="<td style='width: 260px'>" ;
						$output.=getImage($guid, $row["imageType"], $row["imageLocation"], false) ;
					$output.="</td>" ;
					$output.="<td style='width: 130px'>" ;
						$output.="<b>" . $row["name"] . "</b><br/>" ;
						$output.="<span style='font-size: 85%; font-style: italic'>" . $row["producer"] . "</span>" ;
					$output.="</td>" ;
					$output.="<td style='width: 130px'>" ;
						$output.="<b>" . $row["id"] . "</b><br/>" ;
					$output.="</td>" ;
					$output.="<td style='width: 130px'>" ;
						if ($row["gibbonSpaceID"]!="") {
							try {
								$dataSpace=array("gibbonSpaceID"=>$row["gibbonSpaceID"]); 
								$sqlSpace="SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID" ;
								$resultSpace=$connection2->prepare($sqlSpace);
								$resultSpace->execute($dataSpace);
							}
							catch(PDOException $e) { 
								$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultSpace->rowCount()==1) {
								$rowSpace=$resultSpace->fetch() ;
								$output.="<b>" . $rowSpace["name"] . "</b><br/>" ;
							}
						}
						if ($row["locationDetail"]!="") {
							$output.="<span style='font-size: 85%; font-style: italic'>" . $row["locationDetail"] . "</span>" ;
						}
					$output.="</td>" ;
					$output.="<td style='width: 130px'>" ;
						$output.=dateConvertBack(substr($row["timestampOut"],0,10)) . "<br/>" ;
						if ($row["status"]=="On Loan") {
							$output.="<span style='font-size: 85%; font-style: italic'>" . dateConvertBack($row["returnExpected"]) . "</span>" ;
						}
					$output.="</td>" ;
					$output.="<td>" ;
						$output.="<script type='text/javascript'>" ;	
							$output.="$(document).ready(function(){" ;
								$output.="\$(\".description-$count\").hide();" ;
								$output.="\$(\".show_hide-$count\").fadeIn(1000);" ;
								$output.="\$(\".show_hide-$count\").click(function(){" ;
								$output.="\$(\".description-$count\").fadeToggle(1000);" ;
								$output.="});" ;
							$output.="});" ;
						$output.="</script>" ;
						if ($row["fields"]!="") {
							$output.="<a title='View Description' class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='Show Details' onclick='return false;' /></a>" ;
						}
					$output.="</td>" ;
				$output.="</tr>" ;
				if ($row["fields"]!="") {
					$output.="<tr class='description-$count' id='fields-$count' style='background-color: #fff; display: none'>" ;
						$output.="<td style='border-bottom: 1px solid #333'></td>" ;
						
						$output.="<td style='border-bottom: 1px solid #333' colspan=4>" ;
							$output.="<table style='width: 100%'>" ;
								$typeFields=unserialize($row["typeFields"]) ;
								$fields=unserialize($row["fields"]) ;
								foreach ($typeFields as $typeField) {
									if($fields[$typeField["name"]]!="") {
										$output.="<tr>" ;
											$output.="<td style='vertical-align: top'>" ;
												$output.="<b>" . $typeField["name"] . "</b>" ;
											$output.="</td>" ;
											$output.="<td style='vertical-align: top'>" ;
												if ($typeField["type"]=="URL") {
													$output.="<a target='_blank' href='" . $fields[$typeField["name"]] . "'>" . $fields[$typeField["name"]] . "</a><br/>" ;
												}
												else {
													$output.=$fields[$typeField["name"]] . "<br/>" ;
												}
											$output.="</td>" ;
										$output.="</tr>" ;
									}
								}
							$output.="</table>" ;
						$output.="</td>" ;
					$output.="</tr>" ;
				}
				$output.="</tr>" ;
				
				$count++ ;
			}
		$output.="</table>" ; 
	}
	
	return $output ;
}

function getImage($guid, $type, $location, $border=true ) {
	$output=FALSE ;
	
	$borderStyle="" ;
	if ($border==true) {
		$borderStyle="; border: 1px dashed #666" ;
	}
	
	if ($location=="") {
		$output.="<img style='height: 240px; width: 240px; opacity: 1.0' class='user' title='Anonymous Photo' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_240_square.jpg'/><br/>" ;
	}
	else {
		if ($type=="Link") {
			if (is_array(getimagesize($location))) {
				$output.="<div style='height: 240px; width: 240px; display:table-cell; vertical-align:middle; text-align:center $borderStyle'>" ;
					$output.="<img class='user' style='max-height: 240px; max-width: 240px; opacity: 1.0; margin: auto' title='" . htmlPrep($row["name"]) . "' src='" . $location . "'/><br/>" ;
				$output.="</div>" ;
			}
			else {
				$output.="<img style='height: 240px; width: 240px; opacity: 1.0' class='user' title='Anonymous Photo' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_240_square.jpg'/><br/>" ;
			}
		}
		if ($type=="File") {
			if (is_file($_SESSION[$guid]["absoluteURL"] . "/" . $location)) {
				$output.="<div style='height: 240px; width: 240px; display:table-cell; vertical-align:middle; text-align:center; $borderStyle'>" ;
					$output.="<img class='user' style='max-height: 240px; max-width: 240px; opacity: 1.0; margin: auto' title='" . htmlPrep($row["name"]) . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/" . $location . "'/><br/>" ;
				$output.="</div>" ;
			}
			else {
				$output.="<img style='height: 240px; width: 240px; opacity: 1.0' class='user' title='Anonymous Photo' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_240_square.jpg'/><br/>" ;
			}
		}
	}
	
	return $output ;
}

?>
