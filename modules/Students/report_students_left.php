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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Students/report_students_new")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Left Students</div>" ;
	print "</div>" ;
	
	print "<h2 class='top'>" ;
	print "Choose Options" ;
	print "</h2>" ;
	
	$endDateFrom=$_GET["endDateFrom"] ;
	$endDateTo=$_GET["endDateTo"] ;
	$ignoreStatus=$_GET["ignoreStatus"] ;
	?>
	
	<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table style="width: 100%">	
			<tr><td style="width: 30%"></td><td></td></tr>
			<tr>
				<td> 
					<b>From Date</b><br/>
					<span style="font-size: 90%"><i>Earlest student end date to include.<br/>dd/mm/yyyy</i></span>
				</td>
				<td class="right">
					<input name="endDateFrom" id="endDateFrom" maxlength=10 value="<? print $_GET["endDateFrom"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var endDateFrom = new LiveValidation('endDateFrom');
						endDateFrom.add(Validate.Presence);
						endDateFrom.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
					</script>
					<script type="text/javascript">
						$(function() {
							$( "#endDateFrom" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>To Date</b><br/>
					<span style="font-size: 90%"><i>Latest student end date to include.<br/>dd/mm/yyyy</i></span>
				</td>
				<td class="right">
					<input name="endDateTo" id="endDateTo" maxlength=10 value="<? print $_GET["endDateTo"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var endDateTo = new LiveValidation('endDateTo');
						endDateTo.add(Validate.Presence);
						endDateTo.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
					</script>
					<script type="text/javascript">
						$(function() {
							$( "#endDateTo" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Ignore Status</b><br/>
					<span style="font-size: 90%"><i>This is useful for picking up students who have not yet left, but have an End Date set.</span>
				</td>
				<td class="right">
					<input <? if ($ignoreStatus=="on") { print "checked" ; } ?> name="ignoreStatus" id="ignoreStatus" type="checkbox">
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/report_students_left.php">
					<input type="submit" value="Submit">
				</td>
			</tr>
		</table>
	</form>
	<?
	
	if ($endDateFrom!="" AND $endDateTo!="") {
		print "<h2 class='top'>" ;
		print "Results" ;
		print "</h2>" ;
		
		try {
			$data=array("endDateFrom"=>dateConvert($endDateFrom), "endDateTo"=>dateConvert($endDateTo)); 
			if ($ignoreStatus=="on") {
				$sql="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateEnd, nextSchool FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE dateEnd>=:endDateFrom AND dateEnd<=:endDateTo ORDER BY surname, preferredName" ;
			}
			else {
				$sql="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateEnd, nextSchool FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE dateEnd>=:endDateFrom AND dateEnd<=:endDateTo AND status='Left' ORDER BY surname, preferredName" ;
			}
			$result=$connection2->prepare($sql);
			$result->execute($data); 
		}
		catch(PDOException $e) { print "<div class='error'>" . $e->getMessage() . "</div>" ; }
		if ($result->rowCount()>0) {
			print "<table style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print "Count" ;
					print "</th>" ;
					print "<th>" ;
						print "Name" ;
					print "</th>" ;
					print "<th>" ;
						print "Username" ;
					print "</th>" ;
					print "<th>" ;
						print "End Date" ;
					print "</th>" ;
					print "<th>" ;
						print "Next School" ;
					print "</th>" ;
					print "<th>" ;
						print "Parents" ;
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
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print $count ;
						print "</td>" ;
						print "<td>" ;
							print formatName("", $row["preferredName"], $row["surname"], "Student", TRUE) ;
						print "</td>" ;
						print "<td>" ;
							print $row["username"] ;
						print "</td>" ;
						print "<td>" ;
							print dateConvertBack($row["dateEnd"]) ;
						print "</td>" ;
						print "<td>" ;
							print $row["nextSchool"] ;
						print "</td>" ;
						print "<td>" ;
							try {
								$dataFamily=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
								$sqlFamily="SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID" ;
								$resultFamily=$connection2->prepare($sqlFamily);
								$resultFamily->execute($dataFamily);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							while ($rowFamily=$resultFamily->fetch()) {
								try {
									$dataFamily2=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"]); 
									$sqlFamily2="SELECT gibbonPerson.* FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName" ;
									$resultFamily2=$connection2->prepare($sqlFamily2);
									$resultFamily2->execute($dataFamily2);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowFamily2=$resultFamily2->fetch()) {
									print "<u>" . formatName($rowFamily2["title"], $rowFamily2["preferredName"], $rowFamily2["surname"], "Parent") . "</u><br/>" ;
									$numbers=0 ;
									for ($i=1; $i<5; $i++) {
										if ($rowFamily2["phone" . $i]!="") {
											if ($rowFamily2["phone" . $i . "Type"]!="") {
												print "<i>" . $rowFamily2["phone" . $i . "Type"] . ":</i> " ;
											}
											if ($rowFamily2["phone" . $i . "CountryCode"]!="") {
												print "+" . $rowFamily2["phone" . $i . "CountryCode"] . " " ;
											}
											print $rowFamily2["phone" . $i] . "<br/>" ;
											$numbers++ ;
										}
									}
									if ($rowFamily2["citizenship1"]!="" OR $rowFamily2["citizenship1Passport"]!="") {
										print "<i>Passport</i>: " . $rowFamily2["citizenship1"] . " " . $rowFamily2["citizenship1Passport"] . "<br/>" ;
									}
									if ($rowFamily2["nationalIDCardNumber"]!="") {
										if ($_SESSION[$guid]["country"]=="") {
											print "<i>National ID Card</i>: " ;
										}
										else {
											print "<i>" . $_SESSION[$guid]["country"] . " ID Card</i>: " ;
										}
										print $rowFamily2["nationalIDCardNumber"] . "<br/>" ;
									}
								}
							}
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ; 
		}
		else {
			print "<div class='warning'>" ;
				print "There are no records in this report." ;
			print "</div>" ;
		}
	}
}
?>