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


if (isActionAccessible($guid, $connection2, "/modules/Library/library_manage_catalog_duplicate.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_manage_catalog.php'>" . _('Manage Catalog') . "</a> > </div><div class='trailEnd'>" . _('Duplicate Item') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["duplicateReturn"])) { $duplicateReturn=$_GET["duplicateReturn"] ; } else { $duplicateReturn="" ; }
	$duplicateReturnMessage="" ;
	$class="error" ;
	if (!($duplicateReturn=="")) {
		if ($duplicateReturn=="fail0") {
			$duplicateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($duplicateReturn=="fail1") {
			$duplicateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($duplicateReturn=="fail2") {
			$duplicateReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($duplicateReturn=="fail3") {
			$duplicateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($duplicateReturn=="fail3") {
			$duplicateReturnMessage="Your request was successful, but some data was not properly saved." ;	
			$class="success" ;
		}
		else if ($duplicateReturn=="success0") {
			$duplicateReturnMessage=_("Your request was successful.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $duplicateReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonLibraryItemID=$_GET["gibbonLibraryItemID"];
	if ($gibbonLibraryItemID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonLibraryItemID"=>$gibbonLibraryItemID); 
			$sql="SELECT gibbonLibraryItem.*, gibbonLibraryType.name AS type FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE gibbonLibraryItemID=:gibbonLibraryItemID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			$step=NULL ;
			if (isset($_GET["step"])) {
				$step=$_GET["step"] ;
			}
			if ($step!=1 AND $step!=2) {
				$step=1 ;
			}
			
			//Step 1
			if ($step==1) {
				?>
				<h2>
					<?php print _('Step 1 - Quantity') ?>
				</h2> 
				<?php
				if ($_GET["name"]!="" OR $_GET["gibbonLibraryTypeID"]!="" OR $_GET["gibbonSpaceID"]!="" OR $_GET["status"]!="" OR $_GET["gibbonPersonIDOwnership"]!="" OR $_GET["typeSpecificFields"]!="") {
					print "<div class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_manage_catalog.php&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "&typeSpecificFields=" . $_GET["typeSpecificFields"] . "'>" . _('Back to Search Results') . "</a>" ;
					print "</div>" ;
				}
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/library_manage_catalog_duplicate.php&step=2&gibbonLibraryItemID=" . $row["gibbonLibraryItemID"] . "&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "&typeSpecificFields=" . $_GET["typeSpecificFields"] ?>">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr>
							<td style='width: 275px'> 
								<b><?php print _('Type') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
							</td>
							<td class="right">
								<input readonly style='width: 300px' type='text' value='<?php print _($row["type"]) ?>' />
								<input type='hidden' name='gibbonLibraryTypeID' value='<?php print $row["gibbonLibraryTypeID"] ?>'>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Name') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
							</td>
							<td class="right">
								<input readonly name="name" id="name" maxlength=255 value="<?php print $row["name"] ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('ID') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
							</td>
							<td class="right">
								<input readonly name="id" id="id" maxlength=255 value="<?php print $row["id"] ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php print _('Author/Brand') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
							</td>
							<td class="right">
								<input readonly name="producer" id="producer" maxlength=255 value="<?php print $row["producer"] ?>" type="text" style="width: 300px">
							</td>
						</tr>
						
						<tr>
							<td> 
								<b><?php print _('Number of Copies') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('How many copies do you want to make of this item?') ?></i></span>
							</td>
							<td class="right">
								<select name='number' id='number' style='width: 304px'>
									<?php
										for ($i=1; $i<21; $i++) {
											print "<option value='$i'>$i</option>" ;
										}
									?>
								</select>
							</td>
						</tr>
						
						<tr>
							<td>
								<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
							</td>
							<td class="right">
								<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
								<input type="submit" value="<?php print _('Next') ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php
			}
			//Step 1
			else if ($step==2) {
				?>
				<h2>
					<?php print _('Step 2 - Details' ) ?>
				</h2> 
				<?php
				if ($_GET["name"]!="" OR $_GET["gibbonLibraryTypeID"]!="" OR $_GET["gibbonSpaceID"]!="" OR $_GET["status"]!="" OR $_GET["gibbonPersonIDOwnership"]!="" OR $_GET["typeSpecificFields"]!="") {
					print "<div class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_manage_catalog.php&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "&typeSpecificFields=" . $_GET["typeSpecificFields"] . "'>" . _('Back to Search Results') . "</a>" ;
					print "</div>" ;
				}
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/library_manage_catalog_duplicateProcess.php?gibbonLibraryItemID=" . $row["gibbonLibraryItemID"] . "&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "&typeSpecificFields=" . $_GET["typeSpecificFields"] ?>">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<?php
						$number=$_POST["number"] ;
						for ($i=1; $i<=$number; $i++) {
							print "<tr class='break'>" ;
								print "<td colspan=2>" ; 
									print "<h3>" ;
										print "Copy $i" ;
									print "</h3>" ;
								print "</td>" ;
							print "</tr>" ; 
							//GENERAL FIELDS
							?>
							<tr>
								<td style='width: 275px'> 
									<b><?php print _('Name') ?> *</b><br/>
									<span style="font-size: 90%"><i>Volume or product name.</i></span>
								</td>
								<td class="right">
									<input name="name<?php print $i ?>" id="name<?php print $i ?>" maxlength=255 value="<?php print $row["name"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var name<?php print $i ?>=new LiveValidation('name<?php print $i ?>');
										name<?php print $i ?>.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('ID') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('School-unique ID or barcode.') ?></i></span>
								</td>
								<td class="right">
									<input name="id<?php print $i ?>" id="id<?php print $i ?>" maxlength=255 value="" type="text" style="width: 300px">
									<?php
									//Get list of all ids already in use
									$idList="" ;
									try {
										$dataSelect=array(); 
										$sqlSelect="SELECT id FROM gibbonLibraryItem WHERE NOT id='" . $row["id"] . "' ORDER BY id" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										$idList.="'" . $rowSelect["id"]  . "'," ;
									}
									?>
									<script type="text/javascript">
										var id<?php print $i ?>=new LiveValidation('id<?php print $i ?>');
										id<?php print $i ?>.add( Validate.Exclusion, { within: [<?php print $idList ;?>], failureMessage: "ID already in use!", partialMatch: false, caseSensitive: false } );
										id<?php print $i ?>.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Author/Brand') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('Who created the item?') ?></i></span>
								</td>
								<td class="right">
									<input name="producer<?php print $i ?>" id="producer<?php print $i ?>" maxlength=255 value="<?php print $row["producer"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var producer<?php print $i ?>=new LiveValidation('producer<?php print $i ?>');
										producer<?php print $i ?>.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Vendor') ?></b><br/>
									<span style="font-size: 90%"><i><?php print _('Who supplied the item?') ?></i></span>
								</td>
								<td class="right">
									<input name="vendor<?php print $i ?>" id="vendor<?php print $i ?>" maxlength=100 value="<?php print $row["vendor"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Purchase Date') ?></b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<input name="purchaseDate<?php print $i ?>" id="purchaseDate<?php print $i ?>" maxlength=10 value="<?php print dateConvertBack($guid, $row["purchaseDate"]) ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var purchaseDate=new LiveValidation('purchaseDate');
										purchaseDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
									</script>
									 <script type="text/javascript">
										$(function() {
											$( "#purchaseDate" ).datepicker();
										});
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Invoice Number') ?></b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<input name="invoiceNumber<?php print $i ?>" id="invoiceNumber<?php print $i ?>" maxlength=50 value="<?php print $row["invoiceNumber"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr id="locationRow">
								<td> 
									<b><?php print _('Location') ?> *</b><br/>
								</td>
								<td class="right">
									<select name="gibbonSpaceID<?php print $i ?>" id="gibbonSpaceID<?php print $i ?>" style="width: 302px">
										<?php
										print "<option value=''></option>" ;
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT * FROM gibbonSpace ORDER BY name" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($row["gibbonSpaceID"]==$rowSelect["gibbonSpaceID"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonSpaceID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
										}
										?>				
									</select>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Location Detail') ?></b><br/>
									<span style="font-size: 90%"><i><?php print _('Shelf, cabinet, sector, etc') ?></i></span>
								</td>
								<td class="right">
									<input name="locationDetail<?php print $i ?>" id="locationDetail<?php print $i ?>" maxlength=255 value="<?php print $row["locationDetail"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							
							<!-- FIELDS & CONTROLS FOR OWNERSHIP -->
							<script type="text/javascript">
								$(document).ready(function(){
									$("#ownershipType<?php print $i ?>").change(function(){
										if ($('select.ownershipType<?php print $i ?> option:selected').val()=="School" ) {
											$("#ownershipTypeIndividualRow<?php print $i ?>").css("display","none");
											$("#ownershipTypeSchoolRow<?php print $i ?>").slideDown("fast", $("#ownershipTypeSchoolRow<?php print $i ?>").css("display","table-row")); 
										} else if ($('select.ownershipType<?php print $i ?> option:selected').val()=="Individual" ) {
											$("#ownershipTypeSchoolRow<?php print $i ?>").css("display","none");
											$("#ownershipTypeIndividualRow<?php print $i ?>").slideDown("fast", $("#ownershipTypeIndividualRow<?php print $i ?>).css("display","table-row")); 
										} 
										else {
											$("#ownershipTypeIndividualRow<?php print $i ?>").css("display","none");
											$("#ownershipTypeSchoolRow<?php print $i ?>").css("display","none");
										}
									 });
								});
							</script>
							<tr id='ownershipTypeRow<?php print $i ?>'>
								<td> 
									<b><?php print _('Ownership Type') ?></b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<select name="ownershipType<?php print $i ?>" id="ownershipType<?php print $i ?>" class='ownershipType<?php print $i ?>' style="width: 302px">
										<option value=""></option>
										<option <?php if ($row["ownershipType"]=="School") { print "selected" ; } ?> value="School" /> <?php print _('School') ?>
										<option <?php if ($row["ownershipType"]=="Individual") { print "selected" ; } ?> value="Individual" /> <?php print _('Individual') ?>
									</select>
								</td>
							</tr>
							<?php
							$selectContents="<option value=''></option>" ;
							$selectContents.="<optgroup label='--" . _('Students By Roll Group') . "--'>" ;
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								$selectContents.="<option value='" . $rowSelect["gibbonPersonID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
							}
							$selectContents.="</optgroup>" ;
							$selectContents.="<optgroup label='--" . _('All Users') . "--'>" ;
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								$expected="" ;
								if ($rowSelect["status"]=="Expected") {
									$expected=" (Expected)" ;
								}
								$selected="" ;
								if ($row["gibbonPersonIDOwnership"]==$rowSelect["gibbonPersonID"]) {
									$selected="selected" ;
								}
								$selectContents.="<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "$expected</option>" ;
							}
							$selectContents.="</optgroup>" ;
							?>
							<tr id="ownershipTypeSchoolRow<?php print $i ?>" <?php if ($row["ownershipType"]!="School") { print "style='display: none'" ; }?>>
								<td> 
									<b><?php print _('Main User') ?></b><br/>
									<span style="font-size: 90%"><i><?php print _('Person the device is assigned to.') ?></i></span>
								</td>
								<td class="right">
									<select name="gibbonPersonIDOwnershipSchool<?php print $i ?>" id="gibbonPersonIDOwnershipSchool<?php print $i ?>" style="width: 302px">
										<?php print $selectContents ?>
									</select>
								</td>
							</tr>
							<tr id="ownershipTypeIndividualRow<?php print $i ?>" <?php if ($row["ownershipType"]!="Individual") { print "style='display: none'" ; }?>>
								<td> 
									<b><?php print _('Owner') ?></b><br/>
								</td>
								<td class="right">
									<select name="gibbonPersonIDOwnershipIndividual<?php print $i ?>" id="gibbonPersonIDOwnershipIndividual<?php print $i ?>" style="width: 302px">
										<?php print $selectContents ?>
									</select>
								</td>
							</tr>
							<tr id="gibbonDepartmentIDRow">
								<td> 
									<b><?php print _('Department') ?></b><br/>
									<span style="font-size: 90%"><i><?php print _('Which department is responsible for the item?') ?></i></span>
								</td>
								<td class="right">
									<select name="gibbonDepartmentID<?php print $i ?>" id="gibbonDepartmentID<?php print $i ?>" style="width: 302px">
										<?php
										print "<option value=''></option>" ;
										try {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
											$sqlSelect="SELECT * FROM gibbonDepartment ORDER BY name" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($row["gibbonDepartmentID"]==$rowSelect["gibbonDepartmentID"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonDepartmentID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
										}
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Borrowable?') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('Is item available for loan?') ?></i></span>
								</td>
								<td class="right">
									<select name="borrowable<?php print $i ?>" id="borrowable<?php print $i ?>" style="width: 302px">
										<option <?php if ($row["borrowable"]=="Y") { print "selected" ; } ?> value="Y" /> <?php print _('Yes') ?>
										<option <?php if ($row["borrowable"]=="N") { print "selected" ; } ?> value="N" /> <?php print _('No') ?>
									</select>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Status?') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
								</td>
								<td class="right">
									<input readonly style='width: 300px' type='text' value='Available' />
								</td>
							</tr>
							
							<tr id='gibbonSchoolYearIDReplacement'>
								<td> 
									<b><?php print _("Replacement Year") ; ?></b><br/>
									<span style="font-size: 90%"><i><?php print _('When is this item scheduled for replacement.') ?></i></span>
								</td>
								<td class="right">
									<select name="gibbonSchoolYearIDReplacement" id="gibbonSchoolYearIDReplacement" style="width: 302px">
										<?php
										try {
											$dataSelect=array(); 
											$sqlSelect="SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber DESC" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { }
										print "<option value=''></option>" ;
										while ($rowSelect=$resultSelect->fetch()) {
											$selected="" ;
											if ($rowSelect["gibbonSchoolYearID"]==$row["gibbonSchoolYearIDReplacement"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
										}
										?>				
									</select>
								</td>
							</tr>
							<tr id='replacementCostRow'>
								<td> 
									<b><?php print _("Replacement Cost") ; ?></b><br/>
									<span style="font-size: 90%">
										<i>
										<?php
										if ($_SESSION[$guid]["currency"]!="") {
											print sprintf(_('Numeric value of the replacement cost in %1$s.'), $_SESSION[$guid]["currency"]) ;
										}
										else {
											print _("Numeric value of the replacement cost.") ;
										}
										?>
										</i>
									</span>
								</td>
								<td class="right">
									<input name="replacementCost" id="replacementCost" maxlength=13 value="<?php print $row["replacementCost"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var replacementCost=new LiveValidation('replacementCost');
										replacementCost.add(Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
									</script>
								</td>
							</tr>
					
							<tr>
								<td colspan=2> 
									<b><?php print _('Comments/Notes') ?></b> 
									<textarea name='comment<?php print $i ?>' id='comment<?php print $i ?>' rows=10 style='width: 300px'><?php print htmlPreP($row["comment"]) ?></textarea>
								</td>
							</tr>
							<?php
							//TYPE SPECIFIC FIELDS
							if ($i==1) {
								try {
									$dataFields=array("gibbonLibraryTypeID"=>$row["gibbonLibraryTypeID"]); 
									$sqlFields="SELECT * FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID AND active='Y' ORDER BY name" ;
									$resultFields=$connection2->prepare($sqlFields);
									$resultFields->execute($dataFields);
								}
								catch(PDOException $e) { }
								
								if ($resultFields->rowCount()==1) {
									$rowFields=$resultFields->fetch() ;
									$fields=unserialize($rowFields["fields"]) ;
									$fieldValues=unserialize($row["fields"]) ;
								}
							}
							
							
							if (count($fields)<1 OR is_array($fields)==FALSE) {
								print "<tr>" ;
									print "<td colspan='2'> " ;
										print "<div class='error'>" ;
											print _("Your request failed due to a database error.") ;
										print "</div>" ;
									print "</td> " ;
								print "</tr> " ;
							}
							else {
								$output="" ;
								foreach ($fields as $field) {
									$fieldName=preg_replace("/ /", "", $field["name"]) ;
									print "<tr>" ;
										print "<td> " ;
											print "<b>" . _($field["name"]) . "</b>" ;
											if ($field["required"]=="Y") {
												print " *" ;
											}
											$output.="<br/><span style='font-size: 90%'><i>" . str_replace("dd/mm/yyyy", $_SESSION[$guid]["i18n"]["dateFormat"], $field["description"]) . "</i></span>" ;
										print "</td>" ;
										print "<td class='right'>" ;
											if ($field["type"]=="Text") {
												print "<input maxlength='" . $field["options"] . "' name='field" . $fieldName . $i . "' id='field" . $fieldName . $i . "' value='" . htmlPrep($fieldValues[$field["name"]]) . "' type='text' style='width: 300px'>" ;
											}
											else if ($field["type"]=="Select") {
												print "<select name='field" . $fieldName . $i . "' id='field" . $fieldName . $i . "' type='text' style='width: 300px'>" ;
													if ($field["required"]=="Y") {
														print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
													}
													$options=explode(",", $field["options"]) ;
													foreach ($options as $option) {
														$option=trim($option) ;
														$selected="" ;
														if ($option==$fieldValues[$field["name"]]) {
															$selected="selected" ;
														}
														print "<option $selected value='$option'>$option</option>" ;
													}
												print "</select>" ;
											}
											else if ($field["type"]=="Textarea") {
												print "<textarea rows='" . $field["options"] . "' name='field" . $fieldName . $i . "' id='field" . $fieldName . $i . "' style='width: 300px'>" . htmlPrep($fieldValues[$field["name"]]) . "</textarea>" ;
											}
											else if ($field["type"]=="Date") {
												print "<input name='field" . $fieldName . $i . "' id='field" . $fieldName . $i . "' maxlength=10 value='" . dateConvertBack($guid, $fieldValues[$field["name"]]) . "' type='text' style='width: 300px'>" ;
												print "<script type='text/javascript'>" ;
													print "var field" . $fieldName . $i . "=new LiveValidation('field" . $fieldName . $i . "');" ;
													$output.="field" . $fieldName . ".add( Validate.Format, {pattern:" ; if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  $output.="/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { $output.=$_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } $output.=", failureMessage: 'Use " . $_SESSION[$guid]["i18n"]["dateFormat"] . ".' } );" ; 
												print "</script>" ;
												print "<script type='text/javascript'>" ;
													print "$(function() {" ;
														print "$( '#field" . $fieldName . $i . "' ).datepicker();" ;
													print "});" ;
												print "</script>" ;
											}
											else if ($field["type"]=="URL") {
												print "<input maxlength='" . $field["options"] . "' name='field" . $fieldName . $i . "' id='field" . $fieldName . $i . "' value='" . htmlPrep($fieldValues[$field["name"]]) . "' type='text' style='width: 300px'>" ;
												print "<script type='text/javascript'>" ;
													print "var field" . $fieldName . $i . "=new LiveValidation('field" . $fieldName . $i . "');" ;
													print "field" . $fieldName . $i . ".add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: \"Must start with http://\" } );" ;
												print "</script>" ;
											}
										print "</td>" ;
									print "</tr>" ;
									//NEED LIVE VALIDATION
									if ($field["required"]=="Y") {
										if ($field["type"]=="Text" OR $field["type"]=="Textarea" OR $field["type"]=="Date") {
											print "<script type='text/javascript'>" ;
												print "var field" . $fieldName . $i . "=new LiveValidation('field" . $fieldName . $i . "');" ;
												print "field" . $fieldName . $i . ".add(Validate.Presence);" ;
											print "</script>" ;
										}
										else if ($field["type"]=="Select") {
											print "<script type='text/javascript'>" ;
												print "var field" . $fieldName . $i . "=new LiveValidation('field" . $fieldName . $i . "');" ;
												print "field" . $fieldName . $i . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: 'Select something!'});" ;
											print "</script>" ;
										}
									}
								}
							}
						}
						?>
						<tr>
							<td>
								<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
							</td>
							<td class="right">
								<input type="hidden" name="count" value="<?php print $number ?>">
								<input type='hidden' name='gibbonLibraryTypeID' value='<?php print $_POST["gibbonLibraryTypeID"] ?>'>
								<input type="hidden" name="gibbonLibraryItemID" value="<?php print $row["gibbonLibraryItemID"] ?>">
								<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
								<input type="submit" value="<?php print _("Submit") ; ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php
			}
		}
	}
}
?>