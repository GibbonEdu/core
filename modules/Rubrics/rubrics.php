<?
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

session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/Rubrics/rubrics.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Manage Rubrics</div>" ;
		print "</div>" ;
		
		$deleteReturn = $_GET["deleteReturn"] ;
		$deleteReturnMessage ="" ;
		$class="error" ;
		if (!($deleteReturn=="")) {
			if ($deleteReturn=="success0") {
				$deleteReturnMessage ="Delete was successful." ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $deleteReturnMessage;
			print "</div>" ;
		} 
		
		//Set pagination variable
		$page=$_GET["page"] ;
		if ((!is_numeric($page)) OR $page<1) {
			$page=1 ;
		}
		
		//Filter variables
		$where="" ;
		$filter2=$_POST["filter2"] ;
		if ($filter2!="") {
			$where.=" WHERE gibbonDepartmentID='$filter2'" ;
		}
		
		try {
			$data=array(); 
			$sql="SELECT * FROM gibbonRubric $where ORDER BY scope, category, name" ; 
			$sqlPage=$sql ." LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
			
		if ($highestAction=="Manage Rubrics_viewEditAll" OR $highestAction=="Manage Rubrics_viewAllEditLearningArea") {
			print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rubrics_add.php'><img title='New' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
			print "</div>" ;
		}
		print "<div style='width: 100%; height: 20px'>" ;
			print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "'>" ;
				print"<table style='float: right; margin: 0px 0px'>" ;	
					print"<tr>" ;
						print"<td style='vertical-align: top'>" ; 
							print "<select name='filter2' id='filter2' style='width:160px'>" ;
								print "<option value=''>All Learning Areas</option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["gibbonDepartmentID"]==$filter2) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonDepartmentID"] . "'>" . $rowSelect["name"] . "</option>" ;
								}
							print "</select>" ;
						print"</td>" ;
						print"<td class='right' style='vertical-align: top; width: 54px'>" ;
							?>
							<input style='margin-top: 0px; margin-right: -2px' type='submit' value='Filter'>
							<?
						print"</td>" ;
					print"</tr>" ;
				print"</table>" ;
			print "</form>" ;
		print "</div>" ;
			
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print "There are no rubrics to display." ;
			print "</div>" ;
		}
		else {
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top") ;
			}
		
			print "<table style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print "Scope" ;
					print "</th>" ;
					print "<th>" ;
						print "Category" ;
					print "</th>" ;
					print "<th>" ;
						print "Name" ;
					print "</th>" ;
					print "<th>" ;
						print "Year Groups" ;
					print "</th>" ;
					print "<th>" ;
						print "Active" ;
					print "</th>" ;
					print "<th>" ;
						print "Actions" ;
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
					
					if ($row["active"]!="Y") {
						$rowNum="error" ;
					}
					
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print "<b>" . $row["scope"] . "</b><br/>" ;
							if ($row["scope"]=="Learning Area" AND $row["gibbonDepartmentID"]!="") {
								try {
									$dataLearningArea=array("gibbonDepartmentID"=>$row["gibbonDepartmentID"]); 
									$sqlLearningArea="SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID" ;
									$resultLearningArea=$connection2->prepare($sqlLearningArea);
									$resultLearningArea->execute($dataLearningArea);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultLearningArea->rowCount()==1) {
									$rowLearningAreas=$resultLearningArea->fetch() ;
									print "<span style='font-size: 75%; font-style: italic'>" . $rowLearningAreas["name"] . "</span>" ;
								}
							}
						print "</td>" ;
						print "<td>" ;
							print "<b>" . $row["category"] . "</b><br/>" ;
						print "</td>" ;
						print "<td>" ;
							print "<b>" . $row["name"] . "</b><br/>" ;
						print "</td>" ;
						print "<td>" ;
							print getYearGroupsFromIDList($connection2, $row["gibbonYearGroupIDList"]) ;
						print "</td>" ;
						print "<td>" ;
							print $row["active"] ;
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
							
							if ($highestAction=="Manage Rubrics_viewEditAll") {
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rubrics_edit.php&gibbonRubricID=" . $row["gibbonRubricID"] . "&sidebar=false'><img title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rubrics_delete.php&gibbonRubricID=" . $row["gibbonRubricID"] . "'><img title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
							}
							else if ($highestAction=="Manage Rubrics_viewAllEditLearningArea") {
								if ($row["scope"]=="Learning Area" AND $row["gibbonDepartmentID"]!="") {
									try {	
										$dataLearningAreaStaff=array("gibbonDepartmentID"=>$row["gibbonDepartmentID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
										$sqlLearningAreaStaff="SELECT * FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID AND gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Teacher (Curriculum)')" ;
										$resultLearningAreaStaff=$connection2->prepare($sqlLearningAreaStaff);
										$resultLearningAreaStaff->execute($dataLearningAreaStaff);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									
									if ($resultLearningAreaStaff->rowCount()>0) {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rubrics_edit.php&gibbonRubricID=" . $row["gibbonRubricID"] . "&sidebar=false'><img title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rubrics_delete.php&gibbonRubricID=" . $row["gibbonRubricID"] . "'><img title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
									}
								}
							}
							if ($row["description"]!="") {
								print "<a title='View Description' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='Show Comment' onclick='return false;' /></a>" ;
							}
						print "</td>" ;
					print "</tr>" ;
					if ($row["description"]!="") {
						print "<tr class='description-$count' id='description-$count'>" ;
							print "<td style='border-bottom: 1px solid #333' colspan=6>" ;
								print $row["description"] ;
							print "</td>" ;
						print "</tr>" ;
					}
					print "</tr>" ;
					
					$count++ ;
				}
			print "</table>" ;
			
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom") ;
			}
		}
	}
}
?>