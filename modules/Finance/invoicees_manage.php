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

if (isActionAccessible($guid, $connection2, "/modules/Finance/invoicees_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Manage Invoicees</div>" ;
	print "</div>" ;
	
	print "<p>" ;
	print "The table below shows all student invoicees within the school. A red row in the table below indicates that an invoicee's status is not \"Full\" or that their start or end dates are greater or less than than the current date." ;
	print "</p>" ;
	
	//Check for missing students from studentEnrolment and add a gibbonFinanceInvoicee record for them.
	$addFail=FALSE ;
	$addCount=0 ;
	try {
		$dataCur=array(); 
		$sqlCur="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonFinanceInvoiceeID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID)" ; 
		$resultCur=$connection2->prepare($sqlCur);
		$resultCur->execute($dataCur);
	}
	catch(PDOException $e) { 
		$addFail=TRUE ;
	}
	if ($resultCur->rowCount()>0) {
		while ($rowCur=$resultCur->fetch()) {
			if (is_null($rowCur["gibbonFinanceInvoiceeID"])) {
				try {
					$dataAdd=array("gibbonPersonID"=>$rowCur["gibbonPersonID"]); 
					$sqlAdd="INSERT INTO gibbonFinanceInvoicee SET gibbonPersonID=:gibbonPersonID, invoiceTo='Family'" ;
					$resultAdd=$connection2->prepare($sqlAdd);
					$resultAdd->execute($dataAdd);
				}
				catch(PDOException $e) { 
					$addFail=TRUE ;
				}
				$addCount++ ;
			}
		}
		
		if ($addCount>0) {
			if ($addFail==TRUE) {
				print "<div class='error'>" ;
					print "It was detected that some students did not have invoicee records. The system tried to create these, but some of more creations failed." ;
				print "</div>" ;
			}
			else {
				print "<div class='success'>" ;
					if ($addCount==1) {
						print "It was detected that some students did not have invoicee records. The system has successfully created $addCount record for you." ;
					}
					else {
						print "It was detected that some students did not have invoicee records. The system has successfully created $addCount records for you." ;
					}
				print "</div>" ;
			}
		}
	}
	
	
	print "<h2>" ;
	print "Filters" ;
	print "</h2>" ;
	
	$search=NULL ;
	if (isset($_GET["search"])) {
		$search=$_GET["search"] ;
	}
	$allUsers=NULL ;
	if (isset($_GET["allUsers"])) {
		$allUsers=$_GET["allUsers"] ;
	}
	?>
	<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='noIntBorder' cellspacing='0' style="width: 100%">	
			<tr><td style="width: 30%"></td><td></td></tr>
			<tr>
				<td> 
					<b>Search For</b><br/>
					<span style="font-size: 90%"><i>Preferred, surname, username.</i></span>
				</td>
				<td class="right">
					<input name="search" id="search" maxlength=20 value="<? print $search ?>" type="text" style="width: 300px">
				</td>
			</tr>
			<tr>
				<td> 
					<b>All Students</b><br/>
					<span style="font-size: 90%"><i>Include students whose status is not "Full".</i></span>
				</td>
				<td class="right">
					<?
					$checked="" ;
					if ($allUsers=="on") {
						$checked="checked" ;
					}
					print "<input $checked name=\"allUsers\" id=\"allUsers\" type=\"checkbox\">" ;
					?>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/invoicees_manage.php">
					<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
					<?
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoicees_manage.php'>Clear Filters</a>" ;
					?>
					<input type="submit" value="<? print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?
	
	print "<h2>" ;
	print "Choose A Person" ;
	print "</h2>" ;
	
	//Set pagination variable
	$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	try {
		$where="" ;
		if ($allUsers!="on") {
			$where=" AND status='Full'" ;
		}
		$data=array(); 
		$sql="SELECT surname, preferredName, dateStart, dateEnd, status, gibbonFinanceInvoicee.* FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT surname='' $where ORDER BY surname, preferredName" ; 
		if ($search!="") {
			$data=array("search1"=>"%$search%", "search2"=>"%$search%", "search3"=>"%$search%"); 
			$sql="SELECT surname, preferredName, dateStart, dateEnd, status, gibbonFinanceInvoicee.* FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT surname='' AND ((preferredName LIKE :search1) OR (surname LIKE :search2) OR (username LIKE :search3)) $where ORDER BY surname, preferredName" ; 
		}
		$sqlPage=$sql ." LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
					print _("Name") ;
				print "</th>" ;
				print "<th>" ;
					print _("Status") ;
				print "</th>" ;
				print "<th>" ;
					print "Invoice To" ;
				print "</th>" ;
				print "<th>" ;
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
				
				//Color rows based on start and end date
				if ($row["status"]!="Full" OR (!($row["dateStart"]=="" OR $row["dateStart"]<=date("Y-m-d")) AND ($row["dateEnd"]=="" OR $row["dateEnd"]>=date("Y-m-d")))) {
					$rowNum="error" ;
				}
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print "<b>" . formatName("", $row["preferredName"], $row["surname"], "Student", TRUE) . "</b><br/>" ;
					print "</td>" ;
					print "<td>" ;
						print $row["status"] ;
					print "</td>" ;
					print "<td>" ;
						if ($row["invoiceTo"]=="Family") {
							print "Family" ;
						}
						else if ($row["invoiceTo"]=="Company" AND $row["companyAll"]=="Y" ) {
							print "Company" ;
						}
						else if ($row["invoiceTo"]=="Company" AND $row["companyAll"]=="N" ) {
							print "Family + Company" ;
						}
						else {
							print "<i>Unknown</i>" ;
						}
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoicees_manage_edit.php&gibbonFinanceInvoiceeID=" . $row["gibbonFinanceInvoiceeID"] . "&search=$search&allUsers=$allUsers'><img title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
					print "</td>" ;
				print "</tr>" ;
				
				$count++ ;
			}
		print "</table>" ;
		
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom") ;
		}
	}
}
?>