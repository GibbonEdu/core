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

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_manage_enrolment.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage.php'>" . __($guid, 'Manage Activities') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Activity Enrolment') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($_GET["return"], null, null); }
	
	//Check if school year specified
	$gibbonActivityID=$_GET["gibbonActivityID"];
	if ($gibbonActivityID=="Y") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonActivityID"=>$gibbonActivityID); 
			$sql="SELECT * FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The selected record does not exist, or you do not have access to it.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			$dateType=getSettingByScope($connection2, "Activities", "dateType") ;
			if ($_GET["search"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage.php&search=" .$_GET["search"] . "'>" . __($guid, 'Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/tt_editProcess.php?gibbonTTID=$gibbonTTID&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&search=" . $_GET["search"] ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Name') ?></b><br/>
						</td>
						<td class="right">
							<input readonly name="name" id="name" maxlength=20 value="<?php print $row["name"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<?php
					if ($dateType=="Date") {
						?>
						<tr>
							<td> 
								<b><?php print __($guid, 'Listing Dates') ?></b><br/>
							</td>
							<td class="right">
								<input readonly name="name" id="name" maxlength=20 value="<?php print dateConvertBack($guid, $row["listingStart"]) . "-" . dateConvertBack($guid, $row["listingEnd"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print __($guid, 'Program Dates') ?></b><br/>
							</td>
							<td class="right">
								<input readonly name="name" id="name" maxlength=20 value="<?php print dateConvertBack($guid, $row["programStart"]) . "-" . dateConvertBack($guid, $row["programEnd"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?php
					}
					else {
						?>
						<tr>
							<td> 
								<b><?php print __($guid, 'Terms') ?></b><br/>
							</td>
							<td class="right">
								<?php
								$terms=getTerms($connection2, $_SESSION[$guid]["gibbonSchoolYearID"], true) ;
								$termList="" ;
								for ($i=0; $i<count($terms); $i=$i+2) {
									if (is_numeric(strpos($row["gibbonSchoolYearTermIDList"], $terms[$i]))) {
										$termList.=$terms[($i+1)] . ", " ;
									}
								}
								if ($termList=="") {
									$termList="-, " ;
								}
								?>
								<input readonly name="name" id="name" maxlength=20 value="<?php print substr($termList,0,-2) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?php
					}
					?>
				</table>
			</form>
			
			<?php
			$enrolment=getSettingByScope($connection2, "Activities", "enrolmentType") ;
			try {
				if ($enrolment=="Competitive") {
					$data=array("gibbonActivityID"=>$gibbonActivityID); 
					$sql="SELECT gibbonActivityStudent.*, surname, preferredName FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND NOT gibbonActivityStudent.status='Pending' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY gibbonActivityStudent.status, timestamp" ; 
				}
				else {
					$data=array("gibbonActivityID"=>$gibbonActivityID); 
					$sql="SELECT gibbonActivityStudent.*, surname, preferredName FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND NOT gibbonActivityStudent.status='Waiting List' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY gibbonActivityStudent.status, timestamp" ; 
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_enrolment_add.php&gibbonActivityID=$gibbonActivityID&search=" . $_GET["search"] . "'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
			print "</div>" ;
			
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
				print __($guid, "There are no records to display.") ;
				print "</div>" ;
			}
			else {
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print __($guid, "Student") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Status") ;
						print "</th>" ;
						print "<th>" ;
							print "Timestamp" ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Actions") ;
						print "</th>" ;
					print "</tr>" ;
					
					$count=0;
					$rowNum="odd" ;
					while ($row=$result->fetch()) {
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
								print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
							print "</td>" ;
							print "<td>" ;
								print $row["status"] ;
							print "</td>" ;
							print "<td>" ;
								print dateConvertBack($guid, substr($row["timestamp"],0,10)) . " at " . substr($row["timestamp"],11,5) ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_enrolment_edit.php&gibbonActivityID=" . $row["gibbonActivityID"] . "&gibbonPersonID=" . $row["gibbonPersonID"] . "&search=" . $_GET["search"] . "'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_enrolment_delete.php&gibbonActivityID=" . $row["gibbonActivityID"] . "&gibbonPersonID=" . $row["gibbonPersonID"] . "&search=" . $_GET["search"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
							print "</td>" ;
						print "</tr>" ;
					}
				print "</table>" ;
			}
		}
	}
}
?>