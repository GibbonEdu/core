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

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Library/library_browse.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Browse The Library') . "</div>" ;
	print "</div>" ;
	
	//Get display settings
	$browseBGColorStyle=NULL ;
	$browseBGColor=getSettingByScope($connection2, "Library", "browseBGColor") ;
	if ($browseBGColor!="") {
		$browseBGColorStyle="; background-color: #$browseBGColor" ;
	}
	$browseBGImageStyle=NULL ;
	$browseBGImage=getSettingByScope($connection2, "Library", "browseBGImage") ;
	if ($browseBGImage!="") {
		$browseBGImageStyle="; background-image: url(\"$browseBGImage\")" ;
	}
	
	print "<div style='width: 1050px; border: 1px solid #444; margin-bottom: 30px; background-repeat: no-repeat; min-height: 450px; $browseBGColorStyle $browseBGImageStyle'>" ;
		print "<div style='width: 762px; margin: 0 auto'>" ;
			//Display filters
			print "<table class='noIntBorder' cellspacing='0' style='width: 100%; background-color: rgba(255,255,255,0.8); border: 1px solid #444; margin-top: 30px'>" ;
				print "<tr>" ;
					print "<td style='width: 10px'></td>" ;
					print "<td style='width: 33%; padding-top: 5px; text-align: center; vertical-align: top'>" ;
						print "<div style='color: #CC0000; margin-bottom: -2px; font-weight: bold; font-size: 135%'>" . __($guid, 'All Time Top 5') . "</div>" ; 
						try {
							$dataTop=array(); 
							$sqlTop="SELECT gibbonLibraryItem.name, producer, COUNT( * ) AS count FROM gibbonLibraryItem JOIN gibbonLibraryItemEvent ON (gibbonLibraryItemEvent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE gibbonLibraryItem.borrowable='Y' AND gibbonLibraryItemEvent.type='Loan' AND gibbonLibraryType.name='Print Publication' GROUP BY producer, name ORDER BY count DESC LIMIT 0, 5" ;
							$resultTop=$connection2->prepare($sqlTop);
							$resultTop->execute($dataTop);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultTop->rowCount()<1) {
							print "<div class='warning'>" ;
								print __($guid, "There are no records to display.") ;
							print "</div>" ; 
						}
						else {
							$count=0 ;
							while ($rowTop=$resultTop->fetch()) {
								$count++ ;
								if ($rowTop["name"]!="") {
									if (strlen($rowTop["name"])>35) {
										print "<div style='margin-top: 6px; font-weight: bold'>$count. " . substr($rowTop["name"],0, 35) . "...</div>" ;
									}
									else {
										print "<div style='margin-top: 6px; font-weight: bold'>$count. " . $rowTop["name"] . "</div>" ;
									}
									if ($rowTop["producer"]!="") {
										if (strlen($rowTop["producer"])>35) {
											print "<div style='font-style: italic; font-size: 85%'> by " . substr($rowTop["producer"],0, 35) . "...</div>" ;
										}
										else {
											print "<div style='font-style: italic; font-size: 85%'> by " . $rowTop["producer"] . "</div>" ;
										}
									}
								}
							}
						}
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 5px; text-align: center; vertical-align: top'>" ;
						print "<div style='color: #CC0000; margin-bottom: -2px; font-weight: bold; font-size: 135%'>" . __($guid, 'Monthly Top 5') . "</div>" ; 
						try {
							$dataTop=array("timestampOut"=>date("Y-m-d H:i:s", (time()-(60*60*24*30)))); 
							$sqlTop="SELECT gibbonLibraryItem.name, producer, COUNT( * ) AS count FROM gibbonLibraryItem JOIN gibbonLibraryItemEvent ON (gibbonLibraryItemEvent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE timestampOut>=:timestampOut AND gibbonLibraryItem.borrowable='Y' AND gibbonLibraryItemEvent.type='Loan' AND gibbonLibraryType.name='Print Publication' GROUP BY producer, name ORDER BY count DESC LIMIT 0, 5" ;
							$resultTop=$connection2->prepare($sqlTop);
							$resultTop->execute($dataTop);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultTop->rowCount()<1) {
							print "<div class='warning'>" ;
								print __($guid, "There are no records to display.") ;
							print "</div>" ; 
						}
						else {
							$count=0 ;
							while ($rowTop=$resultTop->fetch()) {
								$count++ ;
								if ($rowTop["name"]!="") {
									if (strlen($rowTop["name"])>35) {
										print "<div style='margin-top: 6px; font-weight: bold'>$count. " . substr($rowTop["name"],0, 35) . "...</div>" ;
									}
									else {
										print "<div style='margin-top: 6px; font-weight: bold'>$count. " . $rowTop["name"] . "</div>" ;
									}
									if ($rowTop["producer"]!="") {
										if (strlen($rowTop["producer"])>35) {
											print "<div style='font-style: italic; font-size: 85%'> by " . substr($rowTop["producer"],0, 35) . "...</div>" ;
										}
										else {
											print "<div style='font-style: italic; font-size: 85%'> by " . $rowTop["producer"] . "</div>" ;
										}
									}
								}
							}
						}
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 5px; text-align: center; vertical-align: top'>" ;
						print "<div style='color: #CC0000; margin-bottom: -5px; font-weight: bold; font-size: 135%'>" . __($guid, 'New Titles') . "</div>" ;  
						try {
							$dataTop=array(); 
							$sqlTop="SELECT gibbonLibraryItem.name, producer FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE gibbonLibraryItem.borrowable='Y' AND gibbonLibraryType.name='Print Publication'  ORDER BY timestampCreator DESC LIMIT 0, 5" ;
							$resultTop=$connection2->prepare($sqlTop);
							$resultTop->execute($dataTop);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultTop->rowCount()<1) {
							print "<div class='warning'>" ;
								print __($guid, "There are no records to display.") ;
							print "</div>" ; 
						}
						else {
							$count=0 ;
							while ($rowTop=$resultTop->fetch()) {
								$count++ ;
								if ($rowTop["name"]!="") {
									if (strlen($rowTop["name"])>35) {
										print "<div style='margin-top: 6px; font-weight: bold'>$count. " . substr($rowTop["name"],0, 35) . "...</div>" ;
									}
									else {
										print "<div style='margin-top: 6px; font-weight: bold'>$count. " . $rowTop["name"] . "</div>" ;
									}
									if ($rowTop["producer"]!="") {
										if (strlen($rowTop["producer"])>35) {
											print "<div style='font-style: italic; font-size: 85%'> by " . substr($rowTop["producer"],0, 35) . "...</div>" ;
										}
										else {
											print "<div style='font-style: italic; font-size: 85%'> by " . $rowTop["producer"] . "</div>" ;
										}
									}
								}
							}
						}
					print "</td>" ;
					print "<td style='width: 5px'></td>" ;
				print "</tr>" ;
			print "</table>" ;
			
			//Get current filter values
			$name=NULL ;
			if (isset($_POST["name"])) {
				$name=trim($_POST["name"]) ;
			}
			if ($name=="") {
				if (isset($_GET["name"])) {
					$name=trim($_GET["name"]) ;
				}
			}
			$producer=NULL ;
			if (isset($_POST["producer"])) {
				$producer=trim($_POST["producer"]) ;
			}
			if ($producer=="") {
				if (isset($_GET["producer"])) {
					$producer=trim($_GET["producer"]) ;
				}
			}
			$category=NULL ;
			if (isset($_POST["category"])) {
				$category=trim($_POST["category"]) ;
			}
			if ($category=="") {
				if (isset($_GET["category"])) {
					$category=trim($_GET["category"]) ;
				}
			}
			$collection=NULL ;
			if (isset($_POST["collection"])) {
				$collection=trim($_POST["collection"]) ;
			}
			if ($collection=="") {
				if (isset($_GET["collection"])) {
					$collection=trim($_GET["collection"]) ;
				}
			}
			$everything=NULL ;
			if (isset($_POST["everything"])) {
				$everything=trim($_POST["everything"]) ;
			}
			if ($everything=="") {
				if (isset($_GET["everything"])) {
					$everything=trim($_GET["everything"]) ;
				}
			}
			$gibbonLibraryItemID=NULL ;
			if (isset($_GET["gibbonLibraryItemID"])) {
				$gibbonLibraryItemID=trim($_GET["gibbonLibraryItemID"]) ;
			}
			
			//Display filters
			print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_browse.php'>" ;
				print "<table class='noIntBorder' cellspacing='0' style='width: 100%; background-color: rgba(255,255,255,0.8); border: 1px solid #444; margin-top: 30px'>" ;
					print "<tr>" ;
						print "<td style='width: 10px'></td>" ;
						print "<td style='padding-top: 10px'>" ;
							print "<b>" . __($guid, 'Title') . "</b>" ;
						print "</td>" ;
						print "<td style='padding-top: 10px'>" ;
							print "<b>" . __($guid, 'Author/Producer') . "</b>" ;
						print "</td>" ;
						print "<td style='padding-top: 10px'>" ;
							print "<b>" . __($guid, 'Category') . "</b>" ;
						print "</td>" ;
						print "<td style='padding-top: 10px'>" ;
							print "<b>" . __($guid, 'Collection') . "</b>" ;
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td style='width: 10px'></td>" ;
						print "<td style='padding: 0px 2px 3px 0px'>" ;
							print "<input type='text' name='name' id='name' value='" . htmlPrep($name) . "' style='width:165px; height: 27px; margin-left: 0px; float: left'/>" ;
						print "</td>" ;
						print "<td style='padding: 0px 2px 3px 0px'>" ;
							print "<input type='text' name='producer' id='producer' value='" . htmlPrep($producer) . "' style='width:165px; height: 27px; margin-left: 0px; float: left'/>" ;
						print "</td>" ;
						print "<td style='padding: 0px 0px 3px 2px'>" ;
							$collections=array() ;
							$count=0 ;
							print "<select name='category' id='category' style='width:170px; height: 29px; margin-left: -2px; float: left'>" ;
								print "<option value=''></option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonLibraryType WHERE active='Y' ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["gibbonLibraryTypeID"]==$category) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonLibraryTypeID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
									$fields=unserialize($rowSelect["fields"]) ;
										foreach ($fields as $field) {
										if ($field["name"]=="Collection" AND $field["type"]=="Select") {
											$collectionTemps=explode(",", $field["options"]) ;
											foreach ($collectionTemps as $collectionTemp) {
												$collections[$count][0]=$rowSelect["gibbonLibraryTypeID"] ;
												$collections[$count][1]=$collectionTemp ;
												$count++ ;
											}
										}
									}
								}				
							print "</select>" ;
						print "</td>" ;
						print "<td style='padding: 0px 0px 3px 2px'>" ;
							print "<select name='collection' id='collection' style='width:190px; height: 29px; margin-left: 0px; float: left'>" ;
								for ($i=0; $i<count($collections); $i++) {
									$selected="" ;
									if ($collections[$i][0]==$category AND trim($collections[$i][1])==$collection) {
										$selected="selected" ;
									}
									print "<option $selected class='" . $collections[$i][0] . "' value='" . trim($collections[$i][1]) . "'>" . trim($collections[$i][1]) . "</option>" ;
								}
							print "</select>" ;
							 print "<script type=\"text/javascript\">" ;
								print "$(\"#collection\").chainedTo(\"#category\");" ;
							print "</script>" ;
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td style='width: 10px'></td>" ;
						print "<td style='padding-top: 10px' colspan=4>" ;
							print "<b>" . __($guid, 'All Fields') . "</b>" ;
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td style='width: 10px'></td>" ;
						print "<td style='padding: 0px 2px 3px 0px' colspan=4>" ;
							print "<input type='text' name='everything' id='everything' value='" . htmlPrep($everything) . "' style='width:728px; height: 27px; margin-left: 0px; float: left'/>" ;
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td style='padding: 0px 2px 10px 0px; text-align: right' colspan=5>" ;
							print "<input type='hidden' name='q' value='/modules/Library/library_lending.php'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_browse.php'>" . __($guid, 'Clear Filters') . "</a> " ;
							print "<input style='height: 27px; width: 20px!important; margin: 0px;' type='submit' value='" . __($guid, 'Go') . "'>" ;
						print "</td>" ;
					print "</tr>" ;
				print "</table>" ;
			print "</form>" ;
			
			//Set pagination variable
			$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
			if ((!is_numeric($page)) OR $page<1) {
				$page=1 ;
			}
			
			//Search with filters applied
			try {
				$data=array(); 
				$sqlWhere="AND " ;
				if ($name!="") {
					$data["name"]="%" . $name . "%" ;
					$sqlWhere.="gibbonLibraryItem.name LIKE :name AND " ; 
				}
				if ($producer!="") {
					$data["producer"]="%" . $producer . "%" ;
					$sqlWhere.="producer LIKE :producer AND " ; 
				}
				if ($category!="") {
					$data["category"]=$category;
					$sqlWhere.="gibbonLibraryItem.gibbonLibraryTypeID=:category AND " ; 
					if ($collection!="") {
						$data["collection"]="%s:10:\"Collection\";s:" . strlen($collection) . ":\"" . $collection . "\";%" ;
						$sqlWhere.="gibbonLibraryItem.fields LIKE :collection AND " ;
					}
				}
				if ($gibbonLibraryItemID!="") {
					$data["gibbonLibraryItemID"]=$gibbonLibraryItemID ;
					$sqlWhere.="gibbonLibraryItem.gibbonLibraryItemID=:gibbonLibraryItemID AND " ; 
				}
				if ($sqlWhere=="AND ") {
					$sqlWhere="" ;
				}
				else {
					$sqlWhere=substr($sqlWhere,0,-5) ;
				}
				
				//SEARCH ALL FIELDS (a.k.a everything)
				try {
					$dataEverything=array(); 
					$sqlEverything="SHOW COLUMNS FROM gibbonLibraryItem";
					$resultEverything=$connection2->prepare($sqlEverything);
					$resultEverything->execute($dataEverything);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$everythingCount=0 ;
				$everythingTokens=explode(" ", $everything) ;
				$everythingSQL="" ;
				while ($rowEverything=$resultEverything->fetch()) {
					$tokenCount=0 ;
					foreach ($everythingTokens AS $everythingToken) {
						if (count($everythingTokens)==1) { //Deal with single search token
							$data["data" . $everythingCount]="%" . trim($everythingToken) . "%" ;
							$everythingSQL.="gibbonLibraryItem." . $rowEverything["Field"] . " LIKE :data" . $everythingCount . " OR " ;
							$everythingCount++ ;
						}
						else { //Deal with multiple search token, ANDing them within ORs
							if ($tokenCount==0) { //First in a set of AND within ORs
								$data["data" . $everythingCount]="%" . trim($everythingToken) . "%" ;
								$everythingSQL.="(gibbonLibraryItem." . $rowEverything["Field"] . " LIKE :data" . $everythingCount . " AND " ;
								$everythingCount++ ;
							}
							else if (($tokenCount+1)==count($everythingTokens)) { //Last in a set of AND within ORs
								$data["data" . $everythingCount]="%" . trim($everythingToken) . "%" ;
								$everythingSQL.="gibbonLibraryItem." . $rowEverything["Field"] . " LIKE :data" . $everythingCount . ") OR " ;
								$everythingCount++ ;
							}
							else { //All others in a set of AND within ORs
								$data["data" . $everythingCount]="%" . trim($everythingToken) . "%" ;
								$everythingSQL.="gibbonLibraryItem." . $rowEverything["Field"] . " LIKE :data" . $everythingCount . " AND " ;
								$everythingCount++ ;
							}
							$tokenCount++ ;
						}
					}
				}
				//Find prep for search all fields
				if (strlen($everythingSQL)>0) {
					if (count($everythingTokens)==1) {
						$everythingSQL=" AND (" . substr($everythingSQL, 0, -5) . ")" ;
					}
					else {
						$everythingSQL=" AND (" . substr($everythingSQL, 0, -4) . ")" ;
					}
					$sqlWhere.=$everythingSQL ;
				}
				
				
				$sql="SELECT gibbonLibraryItem.*, gibbonLibraryType.fields AS typeFields FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE (status='Available' OR status='On Loan' OR status='Repair' OR status='Reserved') AND NOT ownershipType='Individual' AND borrowable='Y' $sqlWhere ORDER BY id" ; 
				$sqlPage=$sql ." LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
				
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
				print __($guid, "There are no records to display.") ;
				print "</div>" ;
			}
			else {
				if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
					printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "name=$name&producer=$producer&category=$category&collection=$collection") ;
				}
			
				print "<table class='smallIntBorder' cellspacing='0' style='width: 100%; border: 1px solid #444'>" ;
					print "<tr class='head' style='opacity: 0.7'>" ;
						print "<th style='text-align: center'>" ;
						
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Name") . "<br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Author/Producer') . "</span>" ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "ID") . "<br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Status') . "</span>" ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Location") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Actions") ;
						print "</th>" ;
					print "</tr>" ;
					
					$count=0;
					$rowNum="odd" ;
					try {
						$resultPage=$connection2->prepare($sqlPage);
						$resultPage->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					while ($row=$resultPage->fetch()) {
						if ($count%2==0) {
							$rowNum="even" ;
						}
						else {
							$rowNum="odd" ;
						}
						
						//COLOR ROW BY STATUS!
						print "<tr class=$rowNum style='opacity: 1.0'>" ;
							print "<td style='width: 260px'>" ;
								print getImage($guid, $row["imageType"], $row["imageLocation"], false) ;
							print "</td>" ;
							print "<td style='width: 130px'>" ;
								print "<b>" . $row["name"] . "</b><br/>" ;
								print "<span style='font-size: 85%; font-style: italic'>" . $row["producer"] . "</span>" ;
							print "</td>" ;
							print "<td style='width: 130px'>" ;
								print "<b>" . $row["id"] . "</b><br/>" ;
								print "<span style='font-size: 85%; font-style: italic'>" . $row["status"] . "</span>" ;
							print "</td>" ;
							print "<td style='width: 130px'>" ;
								if ($row["gibbonSpaceID"]!="") {
									try {
										$dataSpace=array("gibbonSpaceID"=>$row["gibbonSpaceID"]); 
										$sqlSpace="SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID" ;
										$resultSpace=$connection2->prepare($sqlSpace);
										$resultSpace->execute($dataSpace);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultSpace->rowCount()==1) {
										$rowSpace=$resultSpace->fetch() ;
										print "<b>" . $rowSpace["name"] . "</b><br/>" ;
									}
								}
								if ($row["locationDetail"]!="") {
									print "<span style='font-size: 85%; font-style: italic'>" . $row["locationDetail"] . "</span>" ;
								}
							print "</td>" ;
							print "<td>" ;
								print "<script type='text/javascript'>" ;	
									print "$(document).ready(function(){" ;
										print "\$(\".description-$count\").hide();" ;
										print "\$(\".show_hide-$count\").fadeIn(1000);" ;
										print "\$(\".show_hide-$count\").click(function(){" ;
										print "\$(\".description-$count\").fadeToggle(1000);" ;
										print "});" ;
									print "});" ;
								print "</script>" ;
								if ($row["fields"]!="") {
									print "<a title='" . __($guid, 'View Description') . "' class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='Show Details' onclick='return false;' /></a>" ;
								}
							print "</td>" ;
						print "</tr>" ;
						if ($row["fields"]!="") {
							print "<tr class='description-$count' id='fields-$count' style='background-color: #fff; display: none'>" ;
								print "<td colspan=5>" ;
									print "<table cellspacing='0' style='width: 100%'>" ;
										$typeFields=unserialize($row["typeFields"]) ;
										$fields=unserialize($row["fields"]) ;
										foreach ($typeFields as $typeField) {
											if($fields[$typeField["name"]]!="") {
												print "<tr>" ;
													print "<td style='vertical-align: top; width: 200px'>" ;
														print "<b>" . ($typeField["name"]) . "</b>" ;
													print "</td>" ;
													print "<td style='vertical-align: top'>" ;
														if ($typeField["type"]=="URL") {
															print "<a target='_blank' href='" . $fields[$typeField["name"]] . "'>" . $fields[$typeField["name"]] . "</a><br/>" ;
														}
														else {
															print $fields[$typeField["name"]] . "<br/>" ;
														}
													print "</td>" ;
												print "</tr>" ;
											}
										}
									print "</table>" ;
								print "</td>" ;
							print "</tr>" ;
						}
						
						$count++ ;
					}
				print "</table>" ;
				
				if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
					printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "name=$name&producer=$producer&category=$category&collection=$collection") ;
				}
			}
		print "</div>" ;
	print "</div>" ;
}
?>