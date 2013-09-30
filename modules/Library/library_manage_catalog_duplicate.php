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


if (isActionAccessible($guid, $connection2, "/modules/Library/library_manage_catalog_duplicate.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_manage_catalog.php'>Manage Catalog</a> > </div><div class='trailEnd'>Duplicate Item</div>" ;
	print "</div>" ;
	
	$duplicateReturn = $_GET["duplicateReturn"] ;
	$duplicateReturnMessage ="" ;
	$class="error" ;
	if (!($duplicateReturn=="")) {
		if ($duplicateReturn=="fail0") {
			$duplicateReturnMessage ="Duplicate failed because you do not have access to this action." ;	
		}
		else if ($duplicateReturn=="fail1") {
			$duplicateReturnMessage ="Duplicate failed because a required parameter was not set." ;	
		}
		else if ($duplicateReturn=="fail2") {
			$duplicateReturnMessage ="Duplicate failed due to a database error." ;	
		}
		else if ($duplicateReturn=="fail3") {
			$duplicateReturnMessage ="Duplicate failed because your inputs were invalid." ;	
		}
		else if ($duplicateReturn=="fail3") {
			$duplicateReturnMessage ="Some aspects of the duplicate failed." ;	
		}
		else if ($duplicateReturn=="success0") {
			$duplicateReturnMessage ="Duplicate was successful." ;	
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
			print "You have not specified a library item." ;
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
				print "The selected rubric does not exist." ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			$step=$_GET["step"] ;
			if ($step!=1 AND $step!=2) {
				$step=1 ;
			}
			
			//Step 1
			if ($step==1) {
				?>
				<h2>
					Step 1 - Quantity
				</h2> 
				<?
				if ($_GET["name"]!="" OR $_GET["gibbonLibraryTypeID"]!="" OR $_GET["gibbonSpaceID"]!="" OR $_GET["status"]!="" OR $_GET["gibbonPersonIDOwnership"]!="") {
					print "<div class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_manage_catalog.php&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "'>Back to Search Results</a>" ;
					print "</div>" ;
				}
				?>
				<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/library_manage_catalog_duplicate.php&step=2&gibbonLibraryItemID=" . $row["gibbonLibraryItemID"] . "&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] ?>">
					<table cellspacing='0' style="width: 100%">	
						<tr><td style="width: 30%"></td><td></td></tr>
						<tr>
							<td> 
								<b>Type *</b><br/>
								<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
							</td>
							<td class="right">
								<input readonly style='width: 300px' type='text' value='<? print $row["type"] ?>' />
								<input type='hidden' name='gibbonLibraryTypeID' value='<? print $row["gibbonLibraryTypeID"] ?>'>
							</td>
						</tr>
						<tr>
							<td> 
								<b>Name *</b><br/>
								<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
							</td>
							<td class="right">
								<input readonly name="name" id="name" maxlength=255 value="<? print $row["name"] ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b>ID *</b><br/>
								<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
							</td>
							<td class="right">
								<input readonly name="id" id="id" maxlength=255 value="<? print $row["id"] ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b>Author/Brand *</b><br/>
								<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
							</td>
							<td class="right">
								<input readonly name="producer" id="producer" maxlength=255 value="<? print $row["producer"] ?>" type="text" style="width: 300px">
							</td>
						</tr>
						
						<tr>
							<td> 
								<b>Number of Copies *</b><br/>
								<span style="font-size: 90%"><i>How many copies do you want?</i></span>
							</td>
							<td class="right">
								<select name='number' id='number' style='width: 304px'>
									<?
										for ($i=1; $i<21; $i++) {
											print "<option value='$i'>$i</option>" ;
										}
									?>
								</select>
							</td>
						</tr>
						
						<tr>
							<td class="right" colspan=2>
								<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
								<input type="reset" value="Reset"> <input type="submit" value="Next">
							</td>
						</tr>
						<tr>
							<td class="right" colspan=2>
								<span style="font-size: 90%"><i>* denotes a required field</i></span>
							</td>
						</tr>
					</table>
				</form>
				<?
			}
			//Step 1
			else if ($step==2) {
				?>
				<h2>
					Step 2 - Details
				</h2> 
				<?
				if ($_GET["name"]!="" OR $_GET["gibbonLibraryTypeID"]!="" OR $_GET["gibbonSpaceID"]!="" OR $_GET["status"]!="" OR $_GET["gibbonPersonIDOwnership"]!="") {
					print "<div class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_manage_catalog.php&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "'>Back to Search Results</a>" ;
					print "</div>" ;
				}
				?>
				<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/library_manage_catalog_duplicateProcess.php" ?>">
					<table cellspacing='0' style="width: 100%">	
						<tr><td style="width: 30%"></td><td></td></tr>
						<?
						$number=$_POST["number"] ;
						for ($i=1; $i<=$number; $i++) {
							print "<tr>" ;
								print "<td colspan=2>" ; 
									print "<h3>" ;
										print "Copy $i" ;
									print "</h3>" ;
								print "</td>" ;
							print "</tr>" ; 
							//GENERAL FIELDS
							?>
							<tr>
								<td> 
									<b>Name *</b><br/>
									<span style="font-size: 90%"><i>Volume or product name.</i></span>
								</td>
								<td class="right">
									<input name="name<? print $i ?>" id="name<? print $i ?>" maxlength=255 value="<? print $row["name"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var name<? print $i ?> = new LiveValidation('name<? print $i ?>');
										name<? print $i ?>.add(Validate.Presence);
									 </script>
								</td>
							</tr>
							<tr>
								<td> 
									<b>ID *</b><br/>
									<span style="font-size: 90%"><i>School-unique ID or barcode.</i></span>
								</td>
								<td class="right">
									<input name="id<? print $i ?>" id="id<? print $i ?>" maxlength=255 value="<? print $row["id"] ?>" type="text" style="width: 300px">
									<?
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
										var id<? print $i ?> = new LiveValidation('id<? print $i ?>');
										id<? print $i ?>.add( Validate.Exclusion, { within: [<? print $idList ;?>], failureMessage: "ID already in use!", partialMatch: false, caseSensitive: false } );
										id<? print $i ?>.add(Validate.Presence);
									 </script>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Author/Brand *</b><br/>
									<span style="font-size: 90%"><i>Who created the item?</i></span>
								</td>
								<td class="right">
									<input name="producer<? print $i ?>" id="producer<? print $i ?>" maxlength=255 value="<? print $row["producer"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var producer<? print $i ?> = new LiveValidation('producer<? print $i ?>');
										producer<? print $i ?>.add(Validate.Presence);
									 </script>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Vendor</b><br/>
									<span style="font-size: 90%"><i>Who supplied the item?</i></span>
								</td>
								<td class="right">
									<input name="vendor<? print $i ?>" id="vendor<? print $i ?>" maxlength=100 value="<? print $row["vendor"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td> 
									<b>Purchase Date</b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<input name="purchaseDate<? print $i ?>" id="purchaseDate<? print $i ?>" maxlength=10 value="<? print dateConvertBack($row["purchaseDate"]) ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var purchaseDate = new LiveValidation('purchaseDate');
										purchaseDate.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
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
									<b>Invoice Number</b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<input name="invoiceNumber<? print $i ?>" id="invoiceNumber<? print $i ?>" maxlength=50 value="<? print $row["invoiceNumber"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr id="locationRow">
								<td> 
									<b>Location</b><br/>
									<span style="font-size: 90%"><i>Item's main location.</i></span>
								</td>
								<td class="right">
									<select name="gibbonSpaceID<? print $i ?>" id="gibbonSpaceID<? print $i ?>" style="width: 302px">
										<?
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
									<b>Location Detail</b><br/>
									<span style="font-size: 90%"><i>Shelf, cabinet, sector, etc</i></span>
								</td>
								<td class="right">
									<input name="locationDetail<? print $i ?>" id="locationDetail<? print $i ?>" maxlength=255 value="<? print $row["locationDetail"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							
							<!-- FIELDS & CONTROLS FOR OWNERSHIP -->
							<script type="text/javascript">
								$(document).ready(function(){
									$("#ownershipType<? print $i ?>").change(function(){
										if ($('select.ownershipType<? print $i ?> option:selected').val() == "School" ) {
											$("#ownershipTypeIndividualRow<? print $i ?>").css("display","none");
											$("#ownershipTypeSchoolRow<? print $i ?>").slideDown("fast", $("#ownershipTypeSchoolRow<? print $i ?>").css("display","table-row")); 
										} else if ($('select.ownershipType<? print $i ?> option:selected').val() == "Individual" ) {
											$("#ownershipTypeSchoolRow<? print $i ?>").css("display","none");
											$("#ownershipTypeIndividualRow<? print $i ?>").slideDown("fast", $("#ownershipTypeIndividualRow<? print $i ?>).css("display","table-row")); 
										} 
										else {
											$("#ownershipTypeIndividualRow<? print $i ?>").css("display","none");
											$("#ownershipTypeSchoolRow<? print $i ?>").css("display","none");
										}
									 });
								});
							</script>
							<tr id='ownershipTypeRow<? print $i ?>'>
								<td> 
									<b>Ownership Type</b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<select name="ownershipType<? print $i ?>" id="ownershipType<? print $i ?>" class='ownershipType<? print $i ?>' style="width: 302px">
										<option value=""></option>
										<option <? if ($row["ownershipType"]=="School") { print "selected" ; } ?> value="School" /> School
										<option <? if ($row["ownershipType"]=="Individual") { print "selected" ; } ?> value="Individual" /> Individual
									</select>
								</td>
							</tr>
							<?
							$selectContents="<option value=''></option>" ;
							$selectContents.="<optgroup label='--Students By Roll Group--'>" ;
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
							$selectContents.="<optgroup label='--All Users--'>" ;
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
							<tr id="ownershipTypeSchoolRow<? print $i ?>" <? if ($row["ownershipType"]!="School") { print "style='display: none'" ; }?>>
								<td> 
									<b>Main User</b><br/>
									<span style="font-size: 90%"><i>Person the device is assigned to.</i></span>
								</td>
								<td class="right">
									<select name="gibbonPersonIDOwnershipSchool<? print $i ?>" id="gibbonPersonIDOwnershipSchool<? print $i ?>" style="width: 302px">
										<? print $selectContents ?>
									</select>
								</td>
							</tr>
							<tr id="ownershipTypeIndividualRow<? print $i ?>" <? if ($row["ownershipType"]!="Individual") { print "style='display: none'" ; }?>>
								<td> 
									<b>Owner</b><br/>
								</td>
								<td class="right">
									<select name="gibbonPersonIDOwnershipIndividual<? print $i ?>" id="gibbonPersonIDOwnershipIndividual<? print $i ?>" style="width: 302px">
										<? print $selectContents ?>
									</select>
								</td>
							</tr>
							<tr id="gibbonDepartmentIDRow">
								<td> 
									<b>Department</b><br/>
									<span style="font-size: 90%"><i>Which department is responsible for the item?</i></span>
								</td>
								<td class="right">
									<select name="gibbonDepartmentID<? print $i ?>" id="gibbonDepartmentID<? print $i ?>" style="width: 302px">
										<?
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
									<b>Borrowable? *</b><br/>
									<span style="font-size: 90%"><i>Is item available for loan?</i></span>
								</td>
								<td class="right">
									<select name="borrowable<? print $i ?>" id="borrowable<? print $i ?>" style="width: 302px">
										<option <? if ($row["borrowable"]=="Y") { print "selected" ; } ?> value="Y" /> Yes
										<option <? if ($row["borrowable"]=="N") { print "selected" ; } ?> value="N" /> No
									</select>
								</td>
							</tr>
							<tr>
								<td> 
									<b>Status? *</b><br/>
									<span style="font-size: 90%"><i>This value cannot be changed here.</i></span>
								</td>
								<td class="right">
									<input readonly style='width: 300px' type='text' value='<? print $row["status"] ?>' />
								</td>
							</tr>
							<tr>
								<td colspan=2> 
									<b>Comments/Notes</b> 
									<textarea name='comment<? print $i ?>' id='comment<? print $i ?>' rows=10 style='width: 300px'><? print htmlPreP($row["comment"]) ?></textarea>
								</td>
							</tr>
							<?
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
											print "Type fields cannot be displayed." ;
										print "</div>" ;
									print "</td> " ;
								print "</tr> " ;
							}
							else {
								foreach ($fields as $field) {
									$fieldName=preg_replace("/ /", "", $field["name"]) ;
									print "<tr>" ;
										print "<td> " ;
											print "<b>" . $field["name"] . "</b>" ;
											if ($field["required"]=="Y") {
												print " *" ;
											}
											print "<br/><span style='font-size: 90%'><i>" . $field["description"] . "</i></span>" ;
										print "</td>" ;
										print "<td class='right'>" ;
											if ($field["type"]=="Text") {
												print "<input maxlength='" . $field["options"] . "' name='field" . $fieldName . $i . "' id='field" . $fieldName . $i . "' value='" . htmlPrep($fieldValues[$field["name"]]) . "' type='text' style='width: 300px'>" ;
											}
											else if ($field["type"]=="Select") {
												print "<select name='field" . $fieldName . $i . "' id='field" . $fieldName . $i . "' type='text' style='width: 300px'>" ;
													if ($field["required"]=="Y") {
														print "<option value='Please select...'>Please select...</option>" ;
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
											if ($field["type"]=="Date") {
												print "<input name='field" . $fieldName . $i . "' id='field" . $fieldName . $i . "' maxlength=10 value='" . dateConvertBack($fieldValues[$field["name"]]) . "' type='text' style='width: 300px'>" ;
												print "<script type='text/javascript'>" ;
													print "var field" . $fieldName . $i . " = new LiveValidation('field" . $fieldName . $i . "');" ;
													print "field" . $fieldName . $i . ".add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: 'Use dd/mm/yyyy.' } );" ; 
												print "</script>" ;
												print "<script type='text/javascript'>" ;
													print "$(function() {" ;
														print "$( '#field" . $fieldName . $i . "' ).datepicker();" ;
													print "});" ;
												print "</script>" ;
											}
										print "</td>" ;
									print "</tr>" ;
									//NEED LIVE VALIDATION
									if ($field["required"]=="Y") {
										if ($field["type"]=="Text" OR $field["type"]=="Textarea" OR $field["type"]=="Date") {
											print "<script type='text/javascript'>" ;
												print "var field" . $fieldName . $i . " = new LiveValidation('field" . $fieldName . $i . "');" ;
												print "field" . $fieldName . $i . ".add(Validate.Presence);" ;
											print "</script>" ;
										}
										else if ($field["type"]=="Select") {
											print "<script type='text/javascript'>" ;
												print "var field" . $fieldName . $i . " = new LiveValidation('field" . $fieldName . $i . "');" ;
												print "field" . $fieldName . $i . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: 'Select something!'});" ;
											print "</script>" ;
										}
									}
								}
							}
						}
						?>
						<tr>
							<td class="right" colspan=2>
								<input type="hidden" name="count" value="<? print $number ?>">
								<input type='hidden' name='gibbonLibraryTypeID' value='<? print $_POST["gibbonLibraryTypeID"] ?>'>
								<input type="hidden" name="gibbonLibraryItemID" value="<? print $row["gibbonLibraryItemID"] ?>">
								<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
								<input type="reset" value="Reset"> <input type="submit" value="Submit">
							</td>
						</tr>
						<tr>
							<td class="right" colspan=2>
								<span style="font-size: 90%"><i>* denotes a required field</i></span>
							</td>
						</tr>
					</table>
				</form>
				<?
			}
		}
	}
}
?>