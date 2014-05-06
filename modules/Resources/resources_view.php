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

if (isActionAccessible($guid, $connection2, "/modules/Resources/resources_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('View Resources') . "</div>" ;
	print "</div>" ;
	
	print "<h3>" ;
		print _("Filters") ;
	print "</h3>" ;
	
	//Get current filter values
	$tags=NULL ; 
	if (isset($_POST["tag"])) {
		$tags=trim($_POST["tag"]) ;
	}
	else if (isset($_GET["tag"])) {
		$tags=trim($_GET["tag"]) ;
	}
	$category=NULL ;
	if (isset($_POST["category"])) {
		$category=trim($_POST["category"]) ;
	}
	$purpose=NULL ;
	if (isset($_POST["purpose"])) {
		$purpose=trim($_POST["purpose"]) ;
	}
	$gibbonYearGroupID=NULL ;
	if (isset($_POST["gibbonYearGroupID"])) {
		$gibbonYearGroupID=$_POST["gibbonYearGroupID"] ;
	}
	
	//Display filters
	print "<form method='post'>" ;
		print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
			print "<tr>" ;
				print "<td>" ;
					print "<b>" . _('Tags') . "</b>" ;
				print "</td>" ;
				print "<td style='padding: 0px 2px 0px 0px'>" ;
					//Tag selector
					try {
						$dataList=array(); 
						$sqlList="SELECT * FROM gibbonResourceTag WHERE count>0 ORDER BY tag" ; 
						$resultList=$connection2->prepare($sqlList);
						$resultList->execute($dataList);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}

					$list="" ;
					while ($rowList=$resultList->fetch()) {
						$list=$list . "{id: \"" . $rowList["tag"] . "\", name: \"" . $rowList["tag"] . " <i>(" . $rowList["count"] . ")</i>\"}," ;
					}
					?>
					<style>
						ul.token-input-list-facebook { width: 300px; height: 25px!important; float: right }
						div.token-input-dropdown-facebook { width: 300px }
					</style>
					<input type="text" id="tag" name="tag" />
					<script type="text/javascript">
						$(document).ready(function() {
							 $("#tag").tokenInput([
								<?php print substr($list,0,-1) ?>
							], 
								{theme: "facebook",
								hintText: "Type a tag...",
								allowCreation: false,
								preventDuplicates: true,
								<?php
								$tagString="" ;
								if ($tags!="") {
									$tagList=explode(",", $tags) ;
									foreach ($tagList as $tag) {
										$tagString.="{id: '$tag', name: '$tag'}," ;
									}
								}
								print "prePopulate: [" . substr($tagString,0,-1) . "]," ;
								?>
								tokenLimit: null});
						});
					</script>
					<?php
				print "</td>" ;
			print "</tr>" ;
			print "<tr>" ;
				print "<td>" ;
					print "<b>" . _('Category') . "</b>" ;
				print "</td>" ;
				print "<td style='padding: 0px 2px 0px 0px'>" ;
					try {
						$dataCategory=array(); 
						$sqlCategory="SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='categories'" ;
						$resultCategory=$connection2->prepare($sqlCategory);
						$resultCategory->execute($dataCategory);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					print "<select name='category' id='category' style='width:302px'>" ;
						print "<option value=''></option>" ;
						if ($resultCategory->rowCount()==1) {
							$rowCategory=$resultCategory->fetch() ;
							$options=$rowCategory["value"] ;
							if ($options!="") {
								$options=explode(",", $options) ;
								
								for ($i=0; $i<count($options); $i++) {
									$selected="" ;
									if (trim($options[$i])==$category) {
										$selected="selected" ;
									}
									print "<option $selected value='" . trim($options[$i]) . "'>" . trim($options[$i]) . "</option>" ;
								}
							}
						}
					print "</select>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr>" ;
				print "<td>" ;
					print "<b>" . _('Purpose') . "</b>" ;
				print "</td>" ;
				print "<td style='padding: 0px 2px 0px 0px'>" ;
					try {
						$dataPurpose=array(); 
						$sqlPurpose="(SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='purposesGeneral') UNION (SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='purposesRestricted')" ;
						$resultPurpose=$connection2->prepare($sqlPurpose);
						$resultPurpose->execute($dataPurpose);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultPurpose->rowCount()>0) {
						$options="" ;
						while($rowPurpose=$resultPurpose->fetch()) {
							$options.=$rowPurpose["value"] . "," ;
						}
						$options=substr($options,0,-1) ;
						
						if ($options!="") {
							$options=explode(",", $options) ;
						}
					}
					print "<select name='purpose' id='purpose' style='width:302px'>" ;
						print "<option value=''></option>" ;
						for ($i=0; $i<count($options); $i++) {
							$selected="" ;
							if (trim($options[$i])==$purpose) {
								$selected="selected" ;
							}
							print "<option $selected value='" . trim($options[$i]) . "'>" . trim($options[$i]) . "</option>" ;
						}
					print "</select>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr>" ;
				print "<td>" ;
					print "<b>" . _('Year Group') . "</b>" ;
				print "</td>" ;
				print "<td style='padding: 0px 2px 0px 0px'>" ;
					try {
						$dataPurpose=array(); 
						$sqlPurpose="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
						$resultPurpose=$connection2->prepare($sqlPurpose);
						$resultPurpose->execute($dataPurpose);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					print "<select name='gibbonYearGroupID' id='gibbonYearGroupID' style='width:302px'>" ;
						print "<option value=''></option>" ;
						while ($rowPurpose=$resultPurpose->fetch()) {
							$selected="" ;
							if ($rowPurpose["gibbonYearGroupID"]==$gibbonYearGroupID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowPurpose["gibbonYearGroupID"] . "'>" . $rowPurpose["name"] . "</option>" ;
						}
					print "</select>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr>" ;
				print "<td class='right' colspan=2>" ;
					print "<input type='hidden' name='q' value='/modules/Resources/resources_view.php'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Resources/resources_view.php'>" . _('Clear Filters') . "</a> " ;
					print "<input style='height: 27px; width: 20px!important; margin: 0px;' type='submit' value='" . _('Go') . "'>" ;
				print "</td>" ;
			print "</tr>" ;
		print "</table>" ;
	print "</form>" ;
	
	//Set pagination variable
	$page=NULL ;
	if (isset($_GET["page"])) {
		$page=$_GET["page"] ;
	}
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
		
	print "<h3>" ;
		print "View" ;
	print "</h3>" ;
	
	//Search with filters applied
	try {
		$data=array(); 
		$sqlWhere="WHERE " ;
		if ($tags!="") {
			$tagCount=0 ;
			$tagArray=explode(",", $tags) ;
			foreach ($tagArray as $atag) {
				$data["tag" . $tagCount]="'%" . $atag . "%'";
				$sqlWhere.="tags LIKE :tag" . $tagCount . " AND " ;
				$tagCount++ ;
			}	 
		}
		if ($category!="") {
			$data["category"]=$category;
			$sqlWhere.="category=:category AND " ; 
		}
		if ($purpose!="") {
			$data["purpose"]=$purpose;
			$sqlWhere.="purpose=:purpose AND " ; 
		}
		if ($gibbonYearGroupID!="") {
			$data["gibbonYearGroupIDList"]="%$gibbonYearGroupID%";
			$sqlWhere.="gibbonYearGroupIDList LIKE :gibbonYearGroupIDList AND " ; 
		}
		if ($sqlWhere=="WHERE ") {
			$sqlWhere="" ;
		}
		else {
			$sqlWhere=substr($sqlWhere,0,-5) ;
		}
		$sql="SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) $sqlWhere ORDER BY timestamp DESC" ; 
		$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	print "<div class='linkTop'>" ;
		print " <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/resources_manage_add.php'><img title='" . _('Add New Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
	print "</div>" ;
	
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print _("There are no records to display.") ;
		print "</div>" ;
	}
	else {
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top") ;
		}
	
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print _("Name") . "<br/>";
					print "<span style='font-size: 85%; font-style: italic'>" . _('Contributor') . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print _("Type") ;
				print "</th>" ;
				print "<th>" ;
					print _("Category") . "<br/>";
					print "<span style='font-size: 85%; font-style: italic'>" . _('Purpose') . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print _("Tags") ;
				print "</th>" ;
				print "<th>" ;
					print _("Year Groups") ;
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
				$count++ ;
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print getResourceLink($guid, $row["gibbonResourceID"], $row["type"], $row["name"], $row["content"]) ;
						print "<span style='font-size: 85%; font-style: italic'>" . formatName($row["title"], $row["preferredName"], $row["surname"], "Staff") . "</span>" ;
					print "</td>" ;
					print "<td>" ;
						print $row["type"] ;
					print "</td>" ;
					print "<td>" ;
						print "<b>" . $row["category"] . "</b><br/>" ;
						print "<span style='font-size: 85%; font-style: italic'>" . $row["purpose"] . "</span>" ;
					print "</td>" ;
					print "<td>" ;
						$output="" ;
						$tags=explode(",", $row["tags"]) ;
						natcasesort($tags) ;
						foreach ($tags AS $tag) {
							$output.=substr(trim($tag),1,-1) . "<br/>" ;
						}
						print substr($output,0,-2) ;
					print "</td>" ;
					print "<td>" ;
						try {
							$dataYears=array(); 
							$sqlYears="SELECT gibbonYearGroupID, nameShort, sequenceNumber FROM gibbonYearGroup ORDER BY sequenceNumber" ;
							$resultYears=$connection2->prepare($sqlYears);
							$resultYears->execute($dataYears);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						$years=explode(",", $row["gibbonYearGroupIDList"]) ;
						if (count($years)>0 AND $years[0]!="") {
							if (count($years)==$resultYears->rowCount()) {
								print "<i>All Years</i>" ;
							}
							else {
								$count3=0 ;
								$count4=0 ;
								while ($rowYears=$resultYears->fetch()) {
									for ($i=0; $i<count($years); $i++) {
										if ($rowYears["gibbonYearGroupID"]==$years[$i]) {
											if ($count3>0 AND $count4>0) {
												print ", " ;
											}
											print $rowYears["nameShort"] ;
											$count4++ ;
										}
									}
									$count3++ ;
								}
							}
						}
						else {
							print "<i>" . _('None') . "</i>" ;
						}
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
		
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom") ;
		}
	}
	
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2) ;
}
?>