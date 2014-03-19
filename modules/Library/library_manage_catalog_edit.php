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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;


if (isActionAccessible($guid, $connection2, "/modules/Library/library_manage_catalog_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_manage_catalog.php'>Manage Catalog</a> > </div><div class='trailEnd'>Edit Item</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage="Your request failed because the selected ID is already in use." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
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
			
			if ($_GET["name"]!="" OR $_GET["gibbonLibraryTypeID"]!="" OR $_GET["gibbonSpaceID"]!="" OR $_GET["status"]!="" OR $_GET["gibbonPersonIDOwnership"]!="" OR $_GET["typeSpecificFields"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_manage_catalog.php&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "&typeSpecificFields=" . $_GET["typeSpecificFields"] . "'>Back to Search Results</a>" ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/library_manage_catalog_editProcess.php?name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "&typeSpecificFields=" . $_GET["typeSpecificFields"] ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2>
							<h3>Catalog Type</h3>
						</td>
					</tr>
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
					
					<tr class='break' id='generalDetailsRow'>
						<td colspan=2>
							<h3>General Details</h3>
						</td>
					</tr>
					<tr id='nameRow'>
						<td> 
							<? print "<b>" . _('Name') . " *</b><br/>" ; ?>
							<span style="font-size: 90%"><i>Volume or product name.</i></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=255 value="<? print $row["name"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name=new LiveValidation('name');
								name.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr id='idRow'>
						<td> 
							<b>ID *</b><br/>
							<span style="font-size: 90%"><i>School-unique ID or barcode.</i></span>
						</td>
						<td class="right">
							<input name="id" id="id" maxlength=255 value="<? print $row["id"] ?>" type="text" style="width: 300px">
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
								var id=new LiveValidation('id');
								id.add( Validate.Exclusion, { within: [<? print $idList ;?>], failureMessage: "ID already in use!", partialMatch: false, caseSensitive: false } );
								id.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr id='producerRow'>
						<td> 
							<b>Author/Brand *</b><br/>
							<span style="font-size: 90%"><i>Who created the item?</i></span>
						</td>
						<td class="right">
							<input name="producer" id="producer" maxlength=255 value="<? print $row["producer"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var producer=new LiveValidation('producer');
								producer.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr id='vendorRow'>
						<td> 
							<b>Vendor</b><br/>
							<span style="font-size: 90%"><i>Who supplied the item?</i></span>
						</td>
						<td class="right">
							<input name="vendor" id="vendor" maxlength=100 value="<? print $row["vendor"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr id='purchaseDateRow'>
						<td> 
							<b>Purchase Date</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input name="purchaseDate" id="purchaseDate" maxlength=10 value="<? print dateConvertBack($guid, $row["purchaseDate"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var purchaseDate=new LiveValidation('purchaseDate');
								purchaseDate.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#purchaseDate" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr id='invoiceNumberRow'>
						<td> 
							<b>Invoice Number</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input name="invoiceNumber" id="invoiceNumber" maxlength=50 value="<? print $row["invoiceNumber"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					
					<!-- FIELDS & CONTROLS FOR IMAGE -->
					<script type="text/javascript">
						$(document).ready(function(){
							$("#imageType").change(function(){
								if ($('select.imageType option:selected').val()=="Link" ) {
									$("#imageFileRow").css("display","none");
									$("#imageLinkRow").slideDown("fast", $("#imageLinkRow").css("display","table-row")); 
									imageLink.enable();
									imageFile.disable();
								} else if ($('select.imageType option:selected').val()=="File" ) {
									$("#imageLinkRow").css("display","none");
									$("#imageFileRow").slideDown("fast", $("#imageFileRow").css("display","table-row")); 
									imageFile.enable();
									imageLink.disable();
								} 
								else {
									$("#imageFileRow").css("display","none");
									$("#imageLinkRow").css("display","none");
									imageFile.disable();
									imageLink.disable();
								}
							 });
						});
					</script>
					<tr id='imageTypeRow'>
						<td> 
							<b>Image Type</b><br/>
							<span style="font-size: 90%"><i>240px x 240ox or smaller.</i></span>
						</td>
						<td class="right">
							<select name="imageType" id="imageType" class='imageType' style="width: 302px">
								<option value=""></option>
								<option <? if ($row["imageType"]=="File") { print "selected" ; } ?> value="File" /> File
								<option <? if ($row["imageType"]=="Link") { print "selected" ; } ?> value="Link" /> Link
							</select>
						</td>
					</tr>
					<tr id="imageFileRow" <? if ($row["imageType"]!="File") { print "style='display: none'" ; }?>>
						<td> 
							<b>Image File</b><br/>
						</td>
						<td class="right">
							<?
							if ($row["imageType"]=="File" AND $row["imageLocation"]!="") {
								print "Current attachment: <a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["imageLocation"] . "'>" . $row["imageLocation"] . "</a><br/><br/>" ;
							}
							?>
							<input type="file" name="imageFile" id="imageFile"><br/><br/>
							<script type="text/javascript">
								var imageFile=new LiveValidation('imageFile');
								imageFile.add( Validate.Inclusion, { within: ['.jpg','.jpeg','.png','.gif'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
								<? if ($row["imageType"]!="File") { print "imageFile.disable();" ; } ?>
							</script>	
							<?
							print getMaxUpload() ;
							?>
						</td>
					</tr>
					<tr id="imageLinkRow" <? if ($row["imageType"]!="Link") { print "style='display: none'" ; }?>>
						<td> 
							<b>Image Link *</b><br/>
						</td>
						<td class="right">
							<input name="imageLink" id="imageLink" maxlength=255 value="<? if ($row["imageType"]=="Link") { print $row["imageLocation"] ; } ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var imageLink=new LiveValidation('imageLink');
								imageLink.add(Validate.Presence);
								imageLink.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http://" } );
								<? if ($row["imageType"]!="Link") { print "imageLink.disable();" ; } ?>
							</script>	
						</td>
					</tr>
					
					
					<tr id="locationRow">
						<td> 
							<b>Location</b><br/>
							<span style="font-size: 90%"><i>Item's main location.</i></span>
						</td>
						<td class="right">
							<select name="gibbonSpaceID" id="gibbonSpaceID" style="width: 302px">
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
					<tr id='locationDetailRow'>
						<td> 
							<b>Location Detail</b><br/>
							<span style="font-size: 90%"><i>Shelf, cabinet, sector, etc</i></span>
						</td>
						<td class="right">
							<input name="locationDetail" id="locationDetail" maxlength=255 value="<? print $row["locationDetail"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					
					<!-- FIELDS & CONTROLS FOR OWNERSHIP -->
					<script type="text/javascript">
						$(document).ready(function(){
							$("#ownershipType").change(function(){
								if ($('select.ownershipType option:selected').val()=="School" ) {
									$("#ownershipTypeIndividualRow").css("display","none");
									$("#ownershipTypeSchoolRow").slideDown("fast", $("#ownershipTypeSchoolRow").css("display","table-row")); 
								} else if ($('select.ownershipType option:selected').val()=="Individual" ) {
									$("#ownershipTypeSchoolRow").css("display","none");
									$("#ownershipTypeIndividualRow").slideDown("fast", $("#ownershipTypeIndividualRow").css("display","table-row")); 
								} 
								else {
									$("#ownershipTypeIndividualRow").css("display","none");
									$("#ownershipTypeSchoolRow").css("display","none");
								}
							 });
						});
					</script>
					<tr id='ownershipTypeRow'>
						<td> 
							<b>Ownership Type</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="ownershipType" id="ownershipType" class='ownershipType' style="width: 302px">
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
					<tr id="ownershipTypeSchoolRow" <? if ($row["ownershipType"]!="School") { print "style='display: none'" ; }?>>
						<td> 
							<b>Main User</b><br/>
							<span style="font-size: 90%"><i>Person the device is assigned to.</i></span>
						</td>
						<td class="right">
							<select name="gibbonPersonIDOwnershipSchool" id="gibbonPersonIDOwnershipSchool" style="width: 302px">
								<? print $selectContents ?>
							</select>
						</td>
					</tr>
					<tr id="ownershipTypeIndividualRow" <? if ($row["ownershipType"]!="Individual") { print "style='display: none'" ; }?>>
						<td> 
							<b>Owner</b><br/>
						</td>
						<td class="right">
							<select name="gibbonPersonIDOwnershipIndividual" id="gibbonPersonIDOwnershipIndividual" style="width: 302px">
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
							<select name="gibbonDepartmentID" id="gibbonDepartmentID" style="width: 302px">
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
					<tr id='borrowableRow'>
						<td> 
							<b>Borrowable? *</b><br/>
							<span style="font-size: 90%"><i>Is item available for loan?</i></span>
						</td>
						<td class="right">
							<select name="borrowable" id="borrowable" class="borrowable" style="width: 302px">
								<option <? if ($row["borrowable"]=="Y") { print "selected" ; } ?> value="Y" /> Yes
								<option <? if ($row["borrowable"]=="N") { print "selected" ; } ?> value="N" /> No
							</select>
						</td>
					</tr>
					
					<!-- FIELDS & CONTROLS FOR IMAGE -->
					<script type="text/javascript">
						$(document).ready(function(){
							$("#borrowable").change(function(){
								if ($('select.borrowable option:selected').val()=="Y" ) {
									$("#statusRowNotBorrowable").css("display","none");
									$("#statusRowBorrowable").slideDown("fast", $("#statusRowBorrowable").css("display","table-row")); 
								} else if ($('select.borrowable option:selected').val()=="N" ) {
									$("#statusRowBorrowable").css("display","none");
									$("#statusRowNotBorrowable").slideDown("fast", $("#statusRowNotBorrowable").css("display","table-row")); 
								}
							 });
						});
					</script>
					<tr id='statusRowBorrowable' <? if ($row["borrowable"]=="N") { print "style='display: none'" ; } ?>>
						<td> 
							<b>Status? *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed here.</i></span>
						</td>
						<td class="right">
							<input readonly name='statusBorrowable' style='width: 300px' type='text' value='<? print $row["status"] ?>' />
						</td>
					</tr>
					<tr id="statusRowNotBorrowable" <? if ($row["borrowable"]=="Y") { print "style='display: none'" ; } ?>>
						<td> 
							<b>Status? *</b><br/>
						</td>
						<td class="right">
							<select name="statusNotBorrowable" id="status" style="width: 302px">
								<option <? if ($row["status"]=="Available") { print "selected" ; } ?> value="Available" /> Available
								<option <? if ($row["status"]=="In Use") { print "selected" ; } ?> value="In Use" /> In Use
								<option <? if ($row["status"]=="Reserved") { print "selected" ; } ?> value="Reserved" /> Reserved
								<option <? if ($row["status"]=="Decommissioned") { print "selected" ; } ?> value="Decommissioned" /> Decommissioned
								<option <? if ($row["status"]=="Lost") { print "selected" ; } ?> value="Lost" /> Lost
								<option <? if ($row["status"]=="Repair") { print "selected" ; } ?> value="Repair" /> Repair
							</select>
						</td>
					</tr>
					
					
					<tr id='commentRow'>
						<td colspan=2> 
							<b>Comments/Notes</b> 
							<textarea name='comment' id='comment' rows=10 style='width: 300px'><? print htmlPreP($row["comment"]) ?></textarea>
						</td>
					</tr>
					
					
					<tr class='break' id='entryDisplayTitleRow'>
						<td colspan=2>
							<h3>Type-Specific Details</h3>
						</td>
					</tr>
					
					<?
					try {
						$dataFields=array("gibbonLibraryTypeID"=>$row["gibbonLibraryTypeID"]); 
						$sqlFields="SELECT * FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID AND active='Y' ORDER BY name" ;
						$resultFields=$connection2->prepare($sqlFields);
						$resultFields->execute($dataFields);
					}
					catch(PDOException $e) { }
					
					if ($resultFields->rowCount()!=1) {
						print "<div class='error'>" ;
							print _("The specified record cannot be found.") ;
						print "</div>" ;
					}
					else {
						$rowFields=$resultFields->fetch() ;
						$fields=unserialize($rowFields["fields"]) ;
						$fieldValues=unserialize($row["fields"]) ;
						$output="" ;
						foreach ($fields as $field) {
							$fieldName=preg_replace("/ /", "", $field["name"]) ;
							print "<tr>" ;
								print "<td> " ;
									print "<b>" . $field["name"] . "</b>" ;
									if ($field["required"]=="Y") {
										print " *" ;
									}
									$output.="<br/><span style='font-size: 90%'><i>" . str_replace("dd/mm/yyyy", $_SESSION[$guid]["i18n"]["dateFormat"], $field["description"]) . "</i></span>" ;
								print "</td>" ;
								print "<td class='right'>" ;
									if ($field["type"]=="Text") {
										print "<input maxlength='" . $field["options"] . "' name='field" . $fieldName . "' id='field" . $fieldName . "' value='" ;
										if (isset($fieldValues[$field["name"]])) {
										 	print htmlPrep($fieldValues[$field["name"]]) ; 
										 }
										 print "' type='text' style='width: 300px'>" ;
									}
									else if ($field["type"]=="Select") {
										print "<select name='field" . $fieldName . "' id='field" . $fieldName . "' type='text' style='width: 300px'>" ;
											if ($field["required"]=="Y") {
												print "<option value='Please select...'>Please select...</option>" ;
											}
											$options=explode(",", $field["options"]) ;
											foreach ($options as $option) {
												$option=trim($option) ;
												$selected="" ;
												if (isset($fieldValues[$field["name"]])) {
													if ($option==$fieldValues[$field["name"]]) {
														$selected="selected" ;
													}
												}
												print "<option $selected value='$option'>$option</option>" ;
											}
										print "</select>" ;
									}
									else if ($field["type"]=="Textarea") {
										print "<textarea rows='" . $field["options"] . "' name='field" . $fieldName . "' id='field" . $fieldName . "' style='width: 300px'>" ;
										if (isset($fieldValues[$field["name"]])) {
											print htmlPrep($fieldValues[$field["name"]]) ;
										}
										print "</textarea>" ;
									}
									else if ($field["type"]=="Date") {
										print "<input name='field" . $fieldName . "' id='field" . $fieldName . "' maxlength=10 value='" ;
										if (isset($fieldValues[$field["name"]])) {
											print dateConvertBack($guid, $fieldValues[$field["name"]]) ;
										}
										print "' type='text' style='width: 300px'>" ;
										print "<script type='text/javascript'>" ;
											print "var field" . $fieldName . "=new LiveValidation('field" . $fieldName . "');" ;
											$output.="field" . $fieldName . ".add( Validate.Format, {pattern:" ; if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  $output.="/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { $output.=$_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } $output.=", failureMessage: 'Use " . $_SESSION[$guid]["i18n"]["dateFormat"] . ".' } );" ; 
										print "</script>" ;
										print "<script type='text/javascript'>" ;
											print "$(function() {" ;
												print "$( '#field" . $fieldName . "' ).datepicker();" ;
											print "});" ;
										print "</script>" ;
									}
									else if ($field["type"]=="URL") {
										print "<input maxlength='" . $field["options"] . "' name='field" . $fieldName . "' id='field" . $fieldName . "' value='" ;
										if (isset($fieldValues[$field["name"]])) {
											htmlPrep($fieldValues[$field["name"]]) ;
										}
										print "' type='text' style='width: 300px'>" ;
										print "<script type='text/javascript'>" ;
											print "var field" . $fieldName . "=new LiveValidation('field" . $fieldName . "');" ;
											print "field" . $fieldName . ".add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: \"Must start with http://\" } );" ;
										print "</script>" ;
									}
								print "</td>" ;
							print "</tr>" ;
							//NEED LIVE VALIDATION
							if ($field["required"]=="Y") {
								if ($field["type"]=="Text" OR $field["type"]=="Textarea" OR $field["type"]=="Date" OR $field["type"]=="URL") {
									print "<script type='text/javascript'>" ;
										print "var field" . $fieldName . "=new LiveValidation('field" . $fieldName . "');" ;
										print "field" . $fieldName . ".add(Validate.Presence);" ;
									print "</script>" ;
								}
								else if ($field["type"]=="Select") {
									print "<script type='text/javascript'>" ;
										print "var field" . $fieldName . "=new LiveValidation('field" . $fieldName . "');" ;
										print "field" . $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: 'Select something!'});" ;
									print "</script>" ;
								}
							}	
						}
						print "<script type='text/javascript'>" ;
							print "$(document).ready(function(){" ;
								print "$('#type').change(function(){" ;
									foreach ($fields as $field) {
										if ($field["required"]=="Y") {
											$fieldName=preg_replace("/ /", "", $field["name"]) ;
											print "field" . $fieldName . ".disable() ;" ;
										}
									}
								print "})" ;
							print "});" ;
						print "</script>" ;
					}
					?>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input type="hidden" name="gibbonLibraryItemID" value="<? print $row["gibbonLibraryItemID"] ?>">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<? print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?
		}
	}
}
?>