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

if (isActionAccessible($guid, $connection2, "/modules/Rubrics/rubrics.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Rubrics') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
		$deleteReturnMessage="" ;
		$class="error" ;
		if (!($deleteReturn=="")) {
			if ($deleteReturn=="success0") {
				$deleteReturnMessage=_("Your request was completed successfully.") ;		
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $deleteReturnMessage;
			print "</div>" ;
		} 
		
		//Set pagination variable
		$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
		if ((!is_numeric($page)) OR $page<1) {
			$page=1 ;
		}
		
		//Filter variables
		$where="WHERE " ;
		$data=array(); 
		$search=NULL ;
		if (isset($_POST["search"])) {
			$search=$_POST["search"] ;
		}
		else if (isset($_GET["search"])) {
			$search=$_GET["search"] ;
		}
		
		
		if ($search!="") {
			$data["name"]=$search ;
			$where.=" name LIKE CONCAT('%', :name, '%') AND " ;
		}
		
		$filter2=NULL ;
		if (isset($_POST["filter2"])) {
			$filter2=$_POST["filter2"] ;
		}
		if ($filter2!="") {
			$data["gibbonDepartmentID"]=$filter2 ;
			$where.=" gibbonDepartmentID=:gibbonDepartmentID" ;
		}
		
		if (substr($where, -5)==" AND ") {
			$where=substr($where, 0, -5) ;
		}
		
		if ($where=="WHERE ") {
			$where="" ;
		}
		
		try {
			$sql="SELECT * FROM gibbonRubric $where ORDER BY scope, category, name" ; 
			$sqlPage=$sql ." LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		print "<h3>" ;
		print _("Filter") ;
		print "</h3>" ;
		print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "'>" ;
			print"<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;	
				?>
				<tr>
					<td> 
						<b><?php print _('Search For') ?></b><br/>
						<span style="font-size: 90%"><i><?php print _('Rubric name.') ?></i></span>
					</td>
					<td class="right">
						<input name="search" id="search" maxlength=20 value="<?php print $search ?>" type="text" style="width: 300px">
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Learning Areas') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<?php
						print "<select name='filter2' id='filter2' style='width:302px'>" ;
							print "<option value=''>" . _('All Learning Areas') . "</option>" ;
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
						?>
					</td>
				</tr>
				<?php
				print "<tr>" ;
					print "<td class='right' colspan=2>" ;
						print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
						print "<input type='submit' value='" . _('Go') . "'>" ;
					print "</td>" ;
				print "</tr>" ;
			print"</table>" ;
		print "</form>" ;
		
		print "<h3>" ;
		print _("Rubrics") ;
		print "</h3>" ;
		if ($highestAction=="Manage Rubrics_viewEditAll" OR $highestAction=="Manage Rubrics_viewAllEditLearningArea") {
			print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rubrics_add.php&search=$search&filter2=$filter2'>" .  _('Add') . "<img style='margin-left: 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
			print "</div>" ;
		}
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
						print _("Scope") ;
					print "</th>" ;
					print "<th>" ;
						print _("Category") ;
					print "</th>" ;
					print "<th>" ;
						print _("Name") ;
					print "</th>" ;
					print "<th>" ;
						print _("Year Groups") ;
					print "</th>" ;
					print "<th>" ;
						print _("Active") ;
					print "</th>" ;
					print "<th style='width: 120px'>" ;
						print _("Actions") ;
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
							print ynExpander($row["active"]) ;
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
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rubrics_edit.php&gibbonRubricID=" . $row["gibbonRubricID"] . "&sidebar=false&search=$search&filter2=$filter2'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rubrics_delete.php&gibbonRubricID=" . $row["gibbonRubricID"] . "&search=$search&filter2=$filter2'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
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
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rubrics_edit.php&gibbonRubricID=" . $row["gibbonRubricID"] . "&sidebar=false&search=$search&filter2=$filter2'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rubrics_delete.php&gibbonRubricID=" . $row["gibbonRubricID"] . "&search=$search&filter2=$filter2'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
									}
								}
							}
							if ($row["description"]!="") {
								print "<a title='" . _('View Description') . "' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='" . _('Show Comment') . "' onclick='return false;' /></a>" ;
							}
						print "</td>" ;
					print "</tr>" ;
					if ($row["description"]!="") {
						print "<tr class='description-$count' id='description-$count'>" ;
							print "<td colspan=6>" ;
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