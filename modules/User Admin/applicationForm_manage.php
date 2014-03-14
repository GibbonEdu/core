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

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/applicationForm_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Manage Application Forms</div>" ;
	print "</div>" ;
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage ="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage ="Your request was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	}
	
	if (isset($_GET["rejectReturn"])) { $rejectReturn=$_GET["rejectReturn"] ; } else { $rejectReturn="" ; }
	$rejectReturnMessage ="" ;
	$class="error" ;
	if (!($rejectReturn=="")) {
		if ($rejectReturn=="success0") {
			$rejectReturnMessage ="Application was sucessfully rejected." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $rejectReturnMessage;
		print "</div>" ;
	} 
	
	$gibbonSchoolYearID="" ;
	if (isset($_GET["gibbonSchoolYearID"])) {
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	}
	if ($gibbonSchoolYearID=="" OR $gibbonSchoolYearID==$_SESSION[$guid]["gibbonSchoolYearID"]) {
		$gibbonSchoolYearID=$_SESSION[$guid]["gibbonSchoolYearID"] ;
		$gibbonSchoolYearName=$_SESSION[$guid]["gibbonSchoolYearName"] ;
	}
	
	if ($gibbonSchoolYearID!=$_SESSION[$guid]["gibbonSchoolYearID"]) {
		try {
			$data=array("gibbonSchoolYearID"=>$_GET["gibbonSchoolYearID"]); 
			$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowcount()!=1) {
			print "<div class='error'>" ;
				print "The specified year does not exist." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			$gibbonSchoolYearID=$row["gibbonSchoolYearID"] ;
			$gibbonSchoolYearName=$row["name"] ;
		}
	}
	
	if ($gibbonSchoolYearID!="") {
		print "<h2>" ;
			print $gibbonSchoolYearName ;
		print "</h2>" ;
		
		print "<div class='linkTop'>" ;
			//Print year picker
			if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage.php&gibbonSchoolYearID=" . getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) . "'>Previous Year</a> " ;
			}
			else {
				print "Previous Year " ;
			}
			print " | " ;
			if (getNextSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage.php&gibbonSchoolYearID=" . getNextSchoolYearID($gibbonSchoolYearID, $connection2) . "'>Next Year</a> " ;
			}
			else {
				print "Next Year " ;
			}
		print "</div>" ;
	
		//Set pagination variable
		$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
		if ((!is_numeric($page)) OR $page<1) {
			$page=1 ;
		}
		
		$search="" ;
		if (isset($_GET["search"])) {
			$search=$_GET["search"] ;
		}
		
		print "<h4>" ;
		print "Search" ;
		print "</h2>" ;
		?>
		<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
			<table class='noIntBorder' cellspacing='0' style="width: 100%">	
				<tr><td style="width: 40%"></td><td></td></tr>
				<tr>
					<td> 
						<b>Search For</b><br/>
						<span style="font-size: 90%"><i>Application ID, preferred, surname, PayPal transaction ID</i></span>
					</td>
					<td class="right">
						<input name="search" id="search" maxlength=20 value="<? print $search ?>" type="text" style="width: 300px">
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/applicationForm_manage.php">
						<input type="hidden" name="gibbonSchoolYearID" value="<? print $gibbonSchoolYearID ?>">
						<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
						<?
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID'>Clear Search</a>" ;
						?>
						<input type="submit" value="<? print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?
		
		print "<h4>" ;
		print "View" ;
		print "</h2>" ;
		
		
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
			$sql="SELECT * FROM gibbonApplicationForm LEFT JOIN gibbonYearGroup ON (gibbonApplicationForm.gibbonYearGroupIDEntry=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonSchoolYearIDEntry=:gibbonSchoolYearID ORDER BY status, priority DESC, timestamp DESC" ; 
			if ($search!="") {
				$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "search1"=>"%$search%", "search2"=>"%$search%", "search3"=>"%$search%", "search4"=>"%$search%"); 
				$sql="SELECT * FROM gibbonApplicationForm LEFT JOIN gibbonYearGroup ON (gibbonApplicationForm.gibbonYearGroupIDEntry=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonSchoolYearIDEntry=:gibbonSchoolYearID AND (preferredName LIKE :search1 OR surname LIKE :search2 OR gibbonApplicationFormID LIKE :search3 OR paypalPaymentTransactionID LIKE :search4) ORDER BY status, priority DESC, timestamp DESC" ; 
			}
			$sqlPage= $sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print "There are no records display." ;
			print "</div>" ;
		}
		else {
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search") ;
			}
		
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print "ID</span>" ;
					print "</th>" ;
					print "<th>" ;
						print "Student<br/><span style='font-style: italic; font-size: 85%'>Application Date</span>" ;
					print "</th>" ;
					print "<th>" ;
						print "Birth Year<br/><span style='font-style: italic; font-size: 85%'>Entry Year</span>" ;
					print "</th>" ;
					print "<th>" ;
						print "Parents" ;
					print "</th>" ;
					print "<th>" ;
						print "Last School" ;
					print "</th>" ;
					print "<th>" ;
						print "Status<br/><span style='font-style: italic; font-size: 85%'>Milestones</span>" ;
					print "</th>" ;
					print "<th>" ;
						print "Priority" ;
					print "</th>" ;
					print "<th style='width: 80px'>" ;
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
					
					if ($row["status"]=="Accepted") {
						$rowNum="current" ;
					}
					else if ($row["status"]=="Rejected" OR $row["status"]=="Withdrawn") {
						$rowNum="error" ;
					}
					
					$count++ ;
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print ltrim($row["gibbonApplicationFormID"], "0") ;
						print "</td>" ;
						print "<td>" ;
							print "<b>" . formatName("", $row["preferredName"], $row["surname"], "Student", true) . "</b><br/>" ;
							print "<span style='font-style: italic; font-size: 85%'>" . dateConvertBack($guid, substr($row["timestamp"],0,10))  . "</span>" ;
						print "</td>" ;
						print "<td>" ;
							print substr($row["dob"],0,4) . "<br/>" ;
							print "<span style='font-style: italic; font-size: 85%'>" . $row["name"] . "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["gibbonFamilyID"]!="") {
								try {
									$dataFamily2=array("gibbonFamilyID"=>$row["gibbonFamilyID"]); 
									$sqlFamily2="SELECT title, surname, preferredName, email FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName" ;
									$resultFamily2=$connection2->prepare($sqlFamily2);
									$resultFamily2->execute($dataFamily2);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowFamily2=$resultFamily2->fetch()) {
									$name=formatName($rowFamily2["title"], $rowFamily2["preferredName"], $rowFamily2["surname"], "Parent", true) ;
									if ($rowFamily2["email"]!="") {
										print "<a href='mailto:" . $rowFamily2["email"] . "'>" . $name . "</a><br/>" ;
									}
									else {
										print $name . "<br/>" ;
									}
								}
							}
							else {
								$name=formatName($row["parent1title"], $row["parent1preferredName"], $row["parent1surname"], "Parent", true) ;
								if ($row["parent1email"]!="") {
									print "<a href='mailto:" . $row["parent1email"] . "'>" . $name . "</a><br/>" ;
								}
								else {
									print $name . "<br/>" ;
								}
								
								if ($row["parent2surname"]!="" AND $row["parent2preferredName"]!="") {
									$name=formatName($row["parent2title"], $row["parent2preferredName"], $row["parent2surname"], "Parent", true) ;
									if ($row["parent2email"]!="") {
										print "<a href='mailto:" . $row["parent2email"] . "'>" . $name . "</a><br/>" ;
									}
									else {
										print $name . "<br/>" ;
									}
									
								}
							}
						print "</td>" ;
						print "<td>" ;
							$school="" ;
							if ($row["schoolDate1"]>$row["schoolDate2"] AND $row["schoolName1"]!="") {
								$school=$row["schoolName1"] ;
							}
							else if ($row["schoolDate2"]>$row["schoolDate1"] AND $row["schoolName2"]!="") {
								$school=$row["schoolName2"] ;
							}
							else if ($row["schoolName1"]!="") {
								$school=$row["schoolName1"] ;
							}
							
							if ($school!="") {
								if (strlen($school)<=15) {
									print $school ;
								}
								else {
									print "<span title='" . $school . "'>" . substr($school, 0, 15) . "...</span>" ;
								}
							}
						print "</td>" ;
						print "<td>" ;
							print "<b>" . $row["status"] . "</b>" ;
							if ($row["status"]=="Pending") {
								$milestones=explode(",", $row["milestones"]) ;
								foreach ($milestones as $milestone) {
									print "<br/><span style='font-style: italic; font-size: 85%'>" . trim($milestone) . "</span>" ;
								}
							}
						print "</td>" ;
						print "<td>" ;
							print $row["priority"] ;
						print "</td>" ;
						print "<td>" ;
							if ($row["status"]=="Pending") {
								print "<a style='margin-left: 1px' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_accept.php&gibbonApplicationFormID=" . $row["gibbonApplicationFormID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'><img title='Accept' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/></a>" ;
								print "<a style='margin-left: 5px' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_reject.php&gibbonApplicationFormID=" . $row["gibbonApplicationFormID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'><img title='Reject' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/></a>" ;
								print "<br/>" ;
							}
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_edit.php&gibbonApplicationFormID=" . $row["gibbonApplicationFormID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'><img title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print " <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/applicationForm_manage_delete.php&gibbonApplicationFormID=" . $row["gibbonApplicationFormID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'><img style='margin-left: 4px' title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
							
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
			
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search") ;
			}
		}
	}
}
?>