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

if (isActionAccessible($guid, $connection2, "/modules/Library/library_lending_item_signOut.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	$gibbonLibraryItemID=$_GET["gibbonLibraryItemID"] ;
	
	if ($gibbonLibraryItemID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
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
				print __($guid, "The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_lending.php'>" . __($guid, 'Lending & Activity Log') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_lending_item.php&gibbonLibraryItemID=$gibbonLibraryItemID'>" . __($guid, 'View Item') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Sign Out') . "</div>" ;
			print "</div>" ;

			if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
			
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
						print __($guid, "This item has been marked to be <u>made available</u> for loan on return.") ;
					} 
					if ($row["returnAction"]=="Reserve" AND $row["gibbonPersonIDReturnAction"]!="") { 
						print __($guid, "This item has been marked to be <u>reserved</u> for <u>$person</u> on return.") ;
					} 
					if ($row["returnAction"]=="Decommission" AND $row["gibbonPersonIDReturnAction"]!="") { 
						print __($guid, "This item has been marked to be <u>decommissioned</u> by <u>$person</u> on return.") ;
					} 
					if ($row["returnAction"]=="Repair" AND $row["gibbonPersonIDReturnAction"]!="") { 
						print __($guid, "This item has been marked to be <u>repaired</u> by <u>$person</u> on return.") ;
					} 
					print " " . __($guid, "You can change this below if you wish.") ;
				print "</div>" ;
			}
								
			if ($_GET["name"]!="" OR $_GET["gibbonLibraryTypeID"]!="" OR $_GET["gibbonSpaceID"]!="" OR $_GET["status"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_lending_item.php&name=" . $_GET["name"] . "&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "'>" . __($guid, 'Back') . "</a>" ;
				print "</div>" ;
			}
								
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/library_lending_item_signoutProcess.php?name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr class='break'>
						<td colspan=2>
							<h3><?php print __($guid, 'Item Details') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'ID') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="name" id="id" value="<?php print $row["id"] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="name" id="name" value="<?php print $row["name"] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Current Status') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="statusCurrent" id="statusCurrent" value="<?php print $row["status"] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2>
							<h3><?php print __($guid, 'This Event') ?></h3>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print __($guid, 'New Status') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="status" id="status" class="standardWidth">
								<option value="On Loan" /> <?php print __($guid, 'On Loan') ?>
								<option value="Reserved" /> <?php print __($guid, 'Reserved') ?>
								<option value="Decommissioned" /> <?php print __($guid, 'Decommissioned') ?>
								<option value="Lost" /> <?php print __($guid, 'Lost') ?>
								<option value="Repair" /> <?php print __($guid, 'Repair') ?>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Responsible User') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Who is responsible for this new status?') ?></span>
						</td>
						<td class="right">
							<?php
							print "<select name='gibbonPersonIDStatusResponsible' id='gibbonPersonIDStatusResponsible' style='width: 300px'>" ;
								print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
								print "<optgroup label='--" . __($guid, 'Students By Roll Group') . "--'>" ;
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
								print "<optgroup label='--" . __($guid, 'All Users') . "--'>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
								}
								print "</optgroup>" ;
							print "</select>" ;
							?>
							<script type="text/javascript">
								var gibbonPersonIDStatusResponsible=new LiveValidation('gibbonPersonIDStatusResponsible');
								gibbonPersonIDStatusResponsible.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<?php
						$loanLength=getSettingByScope($connection2, "Library", "defaultLoanLength") ;
						if (is_numeric($loanLength)==FALSE OR $loanLength<0) {
							$loanLength=7 ;
						}
						?>
						<td> 
							<b><?php print __($guid, 'Expected Return Date') ?></b><br/>
							<span class="emphasis small"><?php print sprintf(__($guid, 'Default loan length is %1$s day(s).'), $loanLength) ?></span>
						</td>
						<td class="right">
							<input name="returnExpected" id="returnExpected" maxlength=10 value="<?php print dateConvertBack($guid, date("Y-m-d", (time()+(24*60*60*$loanLength)))) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var returnExpected=new LiveValidation('returnExpected');
								returnExpected.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
							<h3><?php print __($guid, 'On Return') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Action') ?></b><br/>
							<span class="emphasis small"><?php print __($guid, 'What to do when item is next returned.') ?><br/></span>
						</td>
						<td class="right">
							<select name="returnAction" id="returnAction" class="standardWidth">
								<option value="" />
								<option value="Reserve" /> <?php print __($guid, 'Reserve') ?>
								<option value="Decommission" /> <?php print __($guid, 'Decommission') ?>
								<option value="Repair" /> <?php print __($guid, 'Repair') ?>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Responsible User') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Who will be responsible for the future status?') ?></span>
						</td>
						<td class="right">
							<?php
							print "<select name='gibbonPersonIDReturnAction' id='gibbonPersonIDReturnAction' style='width: 300px'>" ;
								print "<option value=''></option>" ;
								print "<optgroup label='--" . __($guid, 'Students By Roll Group') . "--'>" ;
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
								print "<optgroup label='--" . __($guid, 'All Users') . "--'>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
								}
								print "</optgroup>" ;
							print "</select>" ;
							?>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
						</td>
						<td class="right">
							<input name="gibbonLibraryItemID" id="gibbonLibraryItemID" value="<?php print $gibbonLibraryItemID ?>" type="hidden">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="Sign Out">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}	
	}
}
?>