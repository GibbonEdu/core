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

if (isActionAccessible($guid, $connection2, "/modules/Library/library_lending_item_signOut.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	$gibbonLibraryItemID=$_GET["gibbonLibraryItemID"] ;
	
	if ($gibbonLibraryItemID=="") {
		print "<div class='error'>" ;
			print "You have not specified an item." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonLibraryItemID"=>$gibbonLibraryItemID); 
			$sql="SELECT * FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The specified grade scale does not exist." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_lending.php'>Lending & Activity Log</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_lending_item.php&gibbonLibraryItemID=$gibbonLibraryItemID'>View Item</a> > </div><div class='trailEnd'>Sign Out</div>" ;
			print "</div>" ;
			
			if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
			$addReturnMessage ="" ;
			$class="error" ;
			if (!($addReturn=="")) {
				if ($addReturn=="fail0") {
					$addReturnMessage ="Sign out failed because you do not have access to this action." ;	
				}
				else if ($addReturn=="fail2") {
					$addReturnMessage ="Sign out failed due to a database error." ;	
				}
				else if ($addReturn=="fail3") {
					$addReturnMessage ="Sign out failed because your inputs were invalid." ;	
				}
				else if ($addReturn=="fail4") {
					$addReturnMessage ="Sign out failed some values need to be unique but were not." ;	
				}
				else if ($addReturn=="fail5") {
					$addReturnMessage ="Sign out failed some values need to be unique but were not." ;	
				}
				print "<div class='$class'>" ;
					print $addReturnMessage;
				print "</div>" ;
			} 
	
			
			if ($row["returnAction"]!="") { 
				if ($row["gibbonPersonIDReturnAction"]!="") {
					try {
						$dataPerson=array("gibbonPersonID"=>$row["gibbonPersonIDReturnAction"]); 
						$sqlPerson="SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
						$resultPerson=$connection2->prepare($sqlPerson);
						$resultPerson->execute($dataPerson);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}

					if ($resultPerson->rowCount()==1) {
						$rowPerson=$resultPerson->fetch() ;
						$person=formatName("", htmlPrep($rowPerson["preferredName"]), htmlPrep($rowPerson["surname"]), "Student") ;
					}
				}
			
				print "<div class='warning'>" ;
					if ($row["returnAction"]=="Make Available") { 
						print "This item has been marked to be <u>made available</u> for loan on return." ;
					} 
					if ($row["returnAction"]=="Reserve" AND $row["gibbonPersonIDReturnAction"]!="") { 
						print "This item has been marked to be <u>reserved</u> for <u>$person</u> on return." ;
					} 
					if ($row["returnAction"]=="Decommission" AND $row["gibbonPersonIDReturnAction"]!="") { 
						print "This item has been marked to be <u>decommissioned</u> by <u>$person</u> on return." ;
					} 
					if ($row["returnAction"]=="Repair" AND $row["gibbonPersonIDReturnAction"]!="") { 
						print "This item has been marked to be <u>repaired</u> by <u>$person</u> on return." ;
					} 
					print "You can change this below if you wish." ;
				print "</div>" ;
			}
								
			if ($_GET["name"]!="" OR $_GET["gibbonLibraryTypeID"]!="" OR $_GET["gibbonSpaceID"]!="" OR $_GET["status"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_lending_item.php&name=" . $_GET["name"] . "&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "'>Back</a>" ;
				print "</div>" ;
			}
								
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/library_lending_item_signoutProcess.php?name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2>
							<h3>Item Details</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>ID *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<input readonly name="name" id="id" value="<? print $row["id"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Name *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<input readonly name="name" id="name" value="<? print $row["name"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Current Status *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<input readonly name="statusCurrent" id="statusCurrent" value="<? print $row["status"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2>
							<h3>This Event</h3>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b>New Status *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="status" id="status" style="width: 302px">
								<option value="On Loan" /> On Loan
								<option value="Reserved" /> Reserved
								<option value="Decommissioned" /> Decommissioned
								<option value="Lost" /> Lost
								<option value="Repair" /> Repair
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Responsible User *</b><br/>
							<span style="font-size: 90%"><i>Who is responsible for this new status?</i></span>
						</td>
						<td class="right">
							<?
							print "<select name='gibbonPersonIDStatusResponsible' id='gibbonPersonIDStatusResponsible' style='width: 300px'>" ;
								print "<option value='Please select...'>Please select...</option>" ;
								print "<optgroup label='--Students By Roll Group--'>" ;
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
								}
								print "</optgroup>" ;
								print "<optgroup label='--All Users--'>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["gibbonPersonIDStatusResponsible"]==$rowSelect["gibbonPersonID"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "$expected</option>" ;
								}
								print "</optgroup>" ;
							print "</select>" ;
							?>
							<script type="text/javascript">
								var gibbonPersonIDStatusResponsible=new LiveValidation('gibbonPersonIDStatusResponsible');
								gibbonPersonIDStatusResponsible.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<tr>
						<?
						$loanLength=getSettingByScope($connection2, "Library", "defaultLoanLength") ;
						if (is_numeric($loanLength)==FALSE OR $loanLength<1) {
							$loanLength=7 ;
						}
						?>
						<td> 
							<b>Expected Return Date</b><br/>
							<span style="font-size: 90%"><i>Default loan length is <? print $loanLength . " day"; if ($loanLength>1) { print "s" ; } ?>.</i></span>
						</td>
						<td class="right">
							<input name="returnExpected" id="returnExpected" maxlength=10 value="<? print dateConvertBack(date("Y-m-d", (time()+(24*60*60*$loanLength)))) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var returnExpected=new LiveValidation('returnExpected');
								returnExpected.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#returnExpected" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2>
							<h3>On Return</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Action</b><br/>
							<span style="font-size: 90%"><i>What to do when item is next returned.<br/></i></span>
						</td>
						<td class="right">
							<select name="returnAction" id="returnAction" style="width: 302px">
								<option value="" />
								<option value="Reserve" /> Reserve
								<option value="Decommission" /> Decommission
								<option value="Repair" /> Repair
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Responsible User *</b><br/>
							<span style="font-size: 90%"><i>Who will be responsible for the future status?</i></span>
						</td>
						<td class="right">
							<?
							print "<select name='gibbonPersonIDReturnAction' id='gibbonPersonIDReturnAction' style='width: 300px'>" ;
								print "<option value=''></option>" ;
								print "<optgroup label='--Students By Roll Group--'>" ;
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
								}
								print "</optgroup>" ;
								print "<optgroup label='--All Users--'>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "$expected</option>" ;
								}
								print "</optgroup>" ;
							print "</select>" ;
							?>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* denotes a required field</i></span>
						</td>
						<td class="right">
							<input name="gibbonLibraryItemID" id="gibbonLibraryItemID" value="<? print $gibbonLibraryItemID ?>" type="hidden">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="Sign Out">
						</td>
					</tr>
				</table>
			</form>
			<?
		}	
	}
}
?>