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

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Set returnTo point for upcoming pages
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . _(getModuleName($_GET["q"])) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('Manage Activities') . "</div>" ;
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
	
	print "<h2>" ;
	print _("Search") ;
	print "</h2>" ;
	
	$search=NULL ;
	if (isset($_GET["search"])) {
		$search=$_GET["search"] ;
	}
	
	?>
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='noIntBorder' cellspacing='0' style="width: 100%">
			<tr><td style="width: 30%"></td><td></td></tr>
			<tr>
				<td> 
					<b><?php print _('Search For Activity') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('Activity name.') ?></i></span>
				</td>
				<td class="right">
					<input name="search" id="search" maxlength=20 value="<?php print $search ?>" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/activities_manage.php">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<?php
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_manage.php'>" . _('Clear Search') . "</a>" ;
					?>
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	print "<h2>" ;
	print _("Activities") ;
	print "</h2>" ;
	
	//Set pagination variable
	$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	//Should we show date as term or date?
	$dateType=getSettingByScope( $connection2, "Activities", "dateType" ) ; 
	if ($dateType!="Date") {
		if ($search=="") { 
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonSchoolYearTermIDList, name" ; 
		}
		else {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "search"=>"%$search%"); 
			$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND name LIKE :search ORDER BY gibbonSchoolYearTermIDList, name" ; 
		}
	}
	else {
		if ($search=="") { 
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY programStart DESC, name" ; 
		}
		else {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "search"=>"%$search%"); 
			$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND name LIKE :search ORDER BY programStart DESC, name" ; 
		}
	}
	$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ;
	try {
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { 
		print "<div class='error'>" ;
		print _("Your request failed due to a database error.") ;
		print "</div>" ;
	}
	
	if ($result) {
		print "<div class='linkTop'>" ;
		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_add.php&search=" . $search . "'><img title='" . _('Add New Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
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
						print _("Activity") ;
					print "</th>" ;
					print "<th>" ;
						print _("Days") ;
					print "</th>" ;
					print "<th>" ;
						print _("Years") ;
					print "</th>" ;
					print "<th>" ;
						if ($dateType!="Date") {
							print _("Term") ;
						}
						else {
							print _("Dates") ;
						}
					print "</th>" ;
					print "<th>" ;
						print _("Cost") ;
					print "</th>" ;
					print "<th>" ;
						print _("Provider") ;
					print "</th>" ;
					print "<th style='width: 80px'>" ;
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
					print "<div class='error'>" ;
					print _("Your request failed due to a database error.") ;
					print "</div>" ;
				}
	
				if ($result) {
					while ($row=$resultPage->fetch()) {
						if ($count%2==0) {
							$rowNum="even" ;
						}
						else {
							$rowNum="odd" ;
						}
						$count++ ;
						
						if ($row["active"]=="N") {
							$rowNum="error" ;
						}
		
						//COLOR ROW BY STATUS!
						print "<tr class=$rowNum>" ;
							print "<td>" ;
								print $row["name"] . "<br/>" ;
								print "<i>" . trim($row["type"]) . "</i>" ;
							print "</td>" ;
							print "<td>" ;
								try {
									$dataSlots=array("gibbonActivityID"=>$row["gibbonActivityID"]); 
									$sqlSlots="SELECT DISTINCT nameShort, sequenceNumber FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY sequenceNumber" ;
									$resultSlots=$connection2->prepare($sqlSlots);
									$resultSlots->execute($dataSlots);
								}
								catch(PDOException $e) { }
								
								$count2=0 ;
								while ($rowSlots=$resultSlots->fetch()) {
									if ($count2>0) {
										print ", " ;
									}
									print _($rowSlots["nameShort"]) ;
									$count2++ ;
								}
								if ($count2==0) {
									print "<i>" . _('None') . "</i>" ;
								}
							print "</td>" ;
							print "<td>" ;
								print getYearGroupsFromIDList($connection2, $row["gibbonYearGroupIDList"]) ;
							print "</td>" ;
							print "<td>" ;
								if ($dateType!="Date") {
									$terms=getTerms($connection2, $_SESSION[$guid]["gibbonSchoolYearID"], true) ;
									$termList="" ;
									for ($i=0; $i<count($terms); $i=$i+2) {
										if (is_numeric(strpos($row["gibbonSchoolYearTermIDList"], $terms[$i]))) {
											$termList.=$terms[($i+1)] . "<br/>" ;
										}
									}
									print $termList ;
								}
								else {
									if (substr($row["programStart"],0,4)==substr($row["programEnd"],0,4)) {
										if (substr($row["programStart"],5,2)==substr($row["programEnd"],5,2)) {
											print date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " " . substr($row["programStart"],0,4) ;
										}
										else {
											print date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " - " . date("F", mktime(0, 0, 0, substr($row["programEnd"],5,2))) . " " . substr($row["programStart"],0,4) ;
										}
									}
									else {
										print date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " " . substr($row["programStart"],0,4) . " - " . date("F", mktime(0, 0, 0, substr($row["programEnd"],5,2))) . " " . substr($row["programEnd"],0,4) ;
									}
								}
							print "</td>" ;
							print "<td>" ;
								if ($row["payment"]==0) {
									print "<i>" . _('None') . "</i>" ;
								}
								else {
									print "$" . $row["payment"] ;
								}
							print "</td>" ;
							print "<td>" ;
								if ($row["provider"]=="School") { print $_SESSION[$guid]["organisationNameShort"] ; } else { print _("External") ; }
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_edit.php&gibbonActivityID=" . $row["gibbonActivityID"] . "&search=" . $search . "'><img title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_delete.php&gibbonActivityID=" . $row["gibbonActivityID"] . "&search=" . $search . "'><img title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_enrolment.php&gibbonActivityID=" . $row["gibbonActivityID"] . "&search=" . $search . "'><img title='" . _('Enrolment') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a> " ;
							print "</td>" ;
						print "</tr>" ;
					}
				}
			print "</table>" ;
			
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom") ;
			}
		}
	}
}	
?>