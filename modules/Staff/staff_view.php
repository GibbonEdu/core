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

if (isActionAccessible($guid, $connection2, "/modules/Staff/staff_view.php")==FALSE) {
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
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('View Staff Profiles') . "</div>" ;
		print "</div>" ;
		
		$search=NULL ;
		if (isset($_GET["search"])) {
			$search=$_GET["search"] ;
		}
		$allStaff="" ;
		if (isset($_GET["allStaff"])) {
			$allStaff=$_GET["allStaff"] ;
		}
		
		print "<h2>" ;
		print _("Search") ;
		print "</h2>" ;
		?>
		<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
			<table class='noIntBorder' cellspacing='0' style="width: 100%">	
				<tr><td style="width: 30%"></td><td></td></tr>
				<tr>
					<td> 
						<b><?php print _('Search For') ?></b><br/>
						<span style="font-size: 90%"><i><?php print _('Preferred, surname, username.') ?></i></span>
					</td>
					<td class="right">
						<input name="search" id="search" maxlength=20 value="<?php print $search ?>" type="text" style="width: 300px">
					</td>
				</tr>
				<?php if ($highestAction=="View Staff Profile_full") { ?>
					<tr>
						<td> 
							<b><?php print _('All Staff') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Include all staff, regardless of status, start date, end date, etc.') ?></i></span>
						</td>
						<td class="right">
							<?php
							$checked="" ;
							if ($allStaff=="on") {
								$checked="checked" ;
							}
							print "<input $checked name=\"allStaff\" id=\"allStaff\" type=\"checkbox\">" ;
							?>
						</td>
					</tr>
				<?php } ?>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/staff_view.php">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<?php
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/staff_view.php'>" . _('Clear Search') . "</a>" ;
						?>
						<input type="submit" value="<?php print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
		
		print "<h2>" ;
		print _("Choose A Staff Member") ;
		print "</h2>" ;
		
		//Set pagination variable
		$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
		if ((!is_numeric($page)) OR $page<1) {
			$page=1 ;
		}
		
		try {
			if ($allStaff!="on") {
				$data=array(); 
				$sql="SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, initials, type, gibbonStaff.jobTitle FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ; 
				if ($search!="") {
					$data=array("search1"=>"%$search%", "search2"=>"%$search%", "search3"=>"%$search%"); 
					$sql="SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, initials, type, gibbonStaff.jobTitle FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND (preferredName LIKE :search1 OR surname LIKE :search2 OR username LIKE :search3) ORDER BY surname, preferredName" ; 
				}
			}
			else {
				$data=array(); 
				$sql="SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, initials, type, gibbonStaff.jobTitle FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) ORDER BY surname, preferredName" ; 
				if ($search!="") {
					$data=array("search1"=>"%$search%", "search2"=>"%$search%", "search3"=>"%$search%"); 
					$sql="SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, initials, type, gibbonStaff.jobTitle FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE (preferredName LIKE :search1 OR surname LIKE :search2 OR username LIKE :search3) ORDER BY surname, preferredName" ; 
				}
			}
			$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowcount()<1) {
			print "<div class='error'>" ;
			print _("There are no records to display.") ;
			print "</div>" ;
		}
		else {
			if ($result->rowcount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowcount(), $page, $_SESSION[$guid]["pagination"], "top", "search=$search") ;
			}
		
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print _("Name") . "<br/>" ;
						print "<span style='font-size: 85%; font-style: italic'>" . _('Initials') . "</span>" ;
					print "</th>" ;
					print "<th>" ;
						print _("Staff Type") ;
					print "</th>" ;
					print "<th>" ;
						print _("Job Title") ;
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
					if ($row["status"]!="Full") {
						$rowNum="error" ;
					}
					$count++ ;
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print formatName("", $row["preferredName"],$row["surname"], "Student", true) . "<br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . $row["initials"] . "</span>" ;
						print "</td>" ;
						print "<td>" ;
							print $row["type"] ;
						print "</td>" ;
						print "<td>" ;
							print $row["jobTitle"] ;
						print "</td>" ;
						print "<td>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/staff_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&search=$search&allStaff=$allStaff'><img title='" . _('View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
			
			if ($result->rowcount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowcount(), $page, $_SESSION[$guid]["pagination"], "bottom", "search=$search") ;
			}
		}
	}
}
?>