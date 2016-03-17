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


if (isActionAccessible($guid, $connection2, "/modules/Library/library_manage_catalog_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_manage_catalog.php'>" . __($guid, 'Manage Catalog') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Item') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
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
			print __($guid, "You have not specified one or more required parameters.") ;
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
				print __($guid, "The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			if ($_GET["name"]!="" OR $_GET["gibbonLibraryTypeID"]!="" OR $_GET["gibbonSpaceID"]!="" OR $_GET["status"]!="" OR $_GET["gibbonPersonIDOwnership"]!="" OR $_GET["typeSpecificFields"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_manage_catalog.php&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "&typeSpecificFields=" . $_GET["typeSpecificFields"] . "'>" . __($guid, 'Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/library_manage_catalog_editProcess.php?name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "&typeSpecificFields=" . $_GET["typeSpecificFields"] ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2>
							<h3><?php print __($guid, 'Catalog Type') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Type') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly style='width: 300px' type='text' value='<?php print __($guid, $row["type"]) ?>' />
							<input type='hidden' name='gibbonLibraryTypeID' value='<?php print $row["gibbonLibraryTypeID"] ?>'>
						</td>
					</tr>
					
					<tr class='break' id='generalDetailsRow'>
						<td colspan=2>
							<h3><?php print __($guid, 'General Details') ?></h3>
						</td>
					</tr>
					<tr id='nameRow'>
						<td> 
							<b><?php print __($guid, 'Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Volume or product name.') ?></i></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=255 value="<?php print $row["name"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id='idRow'>
						<td> 
							<b><?php print __($guid, 'ID') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'School-unique ID or barcode.') ?></i></span>
						</td>
						<td class="right">
							<input name="id" id="id" maxlength=255 value="<?php print $row["id"] ?>" type="text" style="width: 300px">
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
								var id=new LiveValidation('id');
								id.add( Validate.Exclusion, { within: [<?php print $idList ;?>], failureMessage: "ID already in use!", partialMatch: false, caseSensitive: false } );
								id.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id='producerRow'>
						<td> 
							<b><?php print __($guid, 'Author/Brand') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Who created the item?') ?></i></span>
						</td>
						<td class="right">
							<input name="producer" id="producer" maxlength=255 value="<?php print $row["producer"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var producer=new LiveValidation('producer');
								producer.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id='vendorRow'>
						<td> 
							<b><?php print __($guid, 'Vendor') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Who supplied the item?') ?></i></span>
						</td>
						<td class="right">
							<input name="vendor" id="vendor" maxlength=100 value="<?php print $row["vendor"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr id='purchaseDateRow'>
						<td> 
							<b><?php print __($guid, 'Purchase Date') ?></b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input name="purchaseDate" id="purchaseDate" maxlength=10 value="<?php print dateConvertBack($guid, $row["purchaseDate"]) ?>" type="text" style="width: 300px">
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
					<tr id='invoiceNumberRow'>
						<td> 
							<b><?php print __($guid, 'Invoice Number') ?></b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input name="invoiceNumber" id="invoiceNumber" maxlength=50 value="<?php print $row["invoiceNumber"] ?>" type="text" style="width: 300px">
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
							<b><?php print __($guid, 'Image Type') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, '240px x 240px or smaller.') ?></i></span>
						</td>
						<td class="right">
							<select name="imageType" id="imageType" class='imageType' style="width: 302px">
								<option value=""></option>
								<option <?php if ($row["imageType"]=="File") { print "selected" ; } ?> value="File" /> <?php print __($guid, 'File') ?>
								<option <?php if ($row["imageType"]=="Link") { print "selected" ; } ?> value="Link" /> <?php print __($guid, 'Link') ?>
							</select>
						</td>
					</tr>
					<tr id="imageFileRow" <?php if ($row["imageType"]!="File") { print "style='display: none'" ; }?>>
						<td> 
							<b><?php print __($guid, 'Image File') ?></b><br/>
						</td>
						<td class="right">
							<?php
							if ($row["imageType"]=="File" AND $row["imageLocation"]!="") {
								print __($guid, "Current attachment:") . " <a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["imageLocation"] . "'>" . $row["imageLocation"] . "</a><br/><br/>" ;
							}
							?>
							<input type="file" name="imageFile" id="imageFile"><br/><br/>
							<script type="text/javascript">
								var imageFile=new LiveValidation('imageFile');
								imageFile.add( Validate.Inclusion, { within: ['.jpg','.jpeg','.png','.gif'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
								<?php if ($row["imageType"]!="File") { print "imageFile.disable();" ; } ?>
							</script>	
							<?php
							print getMaxUpload() ;
							?>
						</td>
					</tr>
					<tr id="imageLinkRow" <?php if ($row["imageType"]!="Link") { print "style='display: none'" ; }?>>
						<td> 
							<b><?php print __($guid, 'Image Link') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="imageLink" id="imageLink" maxlength=255 value="<?php if ($row["imageType"]=="Link") { print $row["imageLocation"] ; } ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var imageLink=new LiveValidation('imageLink');
								imageLink.add(Validate.Presence);
								imageLink.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
								<?php if ($row["imageType"]!="Link") { print "imageLink.disable();" ; } ?>
							</script>	
						</td>
					</tr>
					
					
					<tr id="locationRow">
						<td> 
							<b><?php print __($guid, 'Location') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="gibbonSpaceID" id="gibbonSpaceID" style="width: 302px">
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
					<tr id='locationDetailRow'>
						<td> 
							<b><?php print __($guid, 'Location Detail') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Shelf, cabinet, sector, etc') ?></i></span>
						</td>
						<td class="right">
							<input name="locationDetail" id="locationDetail" maxlength=255 value="<?php print $row["locationDetail"] ?>" type="text" style="width: 300px">
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
							<b><?php print __($guid, 'Ownership Type') ?></b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="ownershipType" id="ownershipType" class='ownershipType' style="width: 302px">
								<option value=""></option>
								<option <?php if ($row["ownershipType"]=="School") { print "selected" ; } ?> value="School" /> <?php print __($guid, 'School') ?>
								<option <?php if ($row["ownershipType"]=="Individual") { print "selected" ; } ?> value="Individual" /> <?php print __($guid, 'Individual') ?>
							</select>
						</td>
					</tr>
					<?php
					$selectContents="<option value=''></option>" ;
					$selectContents.="<optgroup label='--" . __($guid, 'Students By Roll Group') . "--'>" ;
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
					$selectContents.="<optgroup label='--<?php print __($guid, 'All Users') ?>--'>" ;
					try {
						$dataSelect=array(); 
						$sqlSelect="SELECT gibbonPersonID, surname, preferredName, status, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName" ;
						$resultSelect=$connection2->prepare($sqlSelect);
						$resultSelect->execute($dataSelect);
					}
					catch(PDOException $e) { }
					while ($rowSelect=$resultSelect->fetch()) {
						$expected="" ;
						if ($rowSelect["status"]=="Expected") {
							$expected=" " . __($guid, "(Expected)") ;
						}
						$selected="" ;
						if ($row["gibbonPersonIDOwnership"]==$rowSelect["gibbonPersonID"]) {
							$selected="selected" ;
						}
						$selectContents.="<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . $rowSelect["username"] . ")" . $expected . "</option>" ;
					}
					$selectContents.="</optgroup>" ;
					?>
					<tr id="ownershipTypeSchoolRow" <?php if ($row["ownershipType"]!="School") { print "style='display: none'" ; }?>>
						<td> 
							<b><?php print __($guid, 'Main User') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Person the device is assigned to.') ?></i></span>
						</td>
						<td class="right">
							<select name="gibbonPersonIDOwnershipSchool" id="gibbonPersonIDOwnershipSchool" style="width: 302px">
								<?php print $selectContents ?>
							</select>
						</td>
					</tr>
					<tr id="ownershipTypeIndividualRow" <?php if ($row["ownershipType"]!="Individual") { print "style='display: none'" ; }?>>
						<td> 
							<b><?php print __($guid, 'Owner') ?></b><br/>
						</td>
						<td class="right">
							<select name="gibbonPersonIDOwnershipIndividual" id="gibbonPersonIDOwnershipIndividual" style="width: 302px">
								<?php print $selectContents ?>
							</select>
						</td>
					</tr>
					<tr id="gibbonDepartmentIDRow">
						<td> 
							<b><?php print __($guid, 'Department') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Which department is responsible for the item?') ?></i></span>
						</td>
						<td class="right">
							<select name="gibbonDepartmentID" id="gibbonDepartmentID" style="width: 302px">
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
					<tr id='borrowableRow'>
						<td> 
							<b><?php print __($guid, 'Borrowable?') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Is item available for loan?') ?></i></span>
						</td>
						<td class="right">
							<select name="borrowable" id="borrowable" class="borrowable" style="width: 302px">
								<option <?php if ($row["borrowable"]=="Y") { print "selected" ; } ?> value="Y" /> <?php print __($guid, 'Yes') ?>
								<option <?php if ($row["borrowable"]=="N") { print "selected" ; } ?> value="N" /> <?php print __($guid, 'No') ?>
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
					<tr id='statusRowBorrowable' <?php if ($row["borrowable"]=="N") { print "style='display: none'" ; } ?>>
						<td> 
							<b><?php print __($guid, 'Status') ?>? *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name='statusBorrowable' style='width: 300px' type='text' value='<?php print $row["status"] ?>' />
						</td>
					</tr>
					<tr id="statusRowNotBorrowable" <?php if ($row["borrowable"]=="Y") { print "style='display: none'" ; } ?>>
						<td> 
							<b>Status? *</b><br/>
						</td>
						<td class="right">
							<select name="statusNotBorrowable" id="status" style="width: 302px">
								<option <?php if ($row["status"]=="Available") { print "selected" ; } ?> value="Available" /> <?php print __($guid, 'Available') ?>
								<option <?php if ($row["status"]=="In Use") { print "selected" ; } ?> value="In Use" /> <?php print __($guid, 'In Use') ?>
								<option <?php if ($row["status"]=="Reserved") { print "selected" ; } ?> value="Reserved" /> <?php print __($guid, 'Reserved') ?>
								<option <?php if ($row["status"]=="Decommissioned") { print "selected" ; } ?> value="Decommissioned" /> <?php print __($guid, 'Decommissioned') ?>
								<option <?php if ($row["status"]=="Lost") { print "selected" ; } ?> value="Lost" /> <?php print __($guid, 'Lost') ?>
								<option <?php if ($row["status"]=="Repair") { print "selected" ; } ?> value="Repair" /> <?php print __($guid, 'Repair') ?>
							</select>
						</td>
					</tr>
					
					<tr id='gibbonSchoolYearIDReplacement'>
						<td> 
							<b><?php print __($guid, "Replacement Year") ; ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'When is this item scheduled for replacement.') ?></i></span>
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
							<b><?php print __($guid, "Replacement Cost") ; ?></b><br/>
							<span style="font-size: 90%">
								<i>
								<?php
								if ($_SESSION[$guid]["currency"]!="") {
									print sprintf(__($guid, 'Numeric value of the replacement cost in %1$s.'), $_SESSION[$guid]["currency"]) ;
								}
								else {
									print __($guid, "Numeric value of the replacement cost.") ;
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
					<tr id='physicalConditionRow'>
						<td> 
							<b><?php print __($guid, 'Physical Condition') ?></b><br/>
						</td>
						<td class="right">
							<select name="physicalCondition" id="physicalCondition" style="width: 302px">
								<option <?php if ($row["physicalCondition"]=="") { print "selected" ; } ?> value="" />
								<option <?php if ($row["physicalCondition"]=="As New") { print "selected" ; } ?> value="As New" /> <?php print __($guid, 'As New') ?>
								<option <?php if ($row["physicalCondition"]=="Lightly Worn") { print "selected" ; } ?> value="Lightly Worn" /> <?php print __($guid, 'Lightly Worn') ?>
								<option <?php if ($row["physicalCondition"]=="Moderately Worn") { print "selected" ; } ?> value="Moderately Worn" /> <?php print __($guid, 'Moderately Worn') ?>
								<option <?php if ($row["physicalCondition"]=="Damaged") { print "selected" ; } ?> value="Damaged" /> <?php print __($guid, 'Damaged') ?>
								<option <?php if ($row["physicalCondition"]=="Unusable") { print "selected" ; } ?> value="Unusable" /> <?php print __($guid, 'Unusable') ?>
							</select>
						</td>
					</tr>
					
					
					<tr id='commentRow'>
						<td colspan=2> 
							<b><?php print __($guid, 'Comments/Notes') ?></b> 
							<textarea name='comment' id='comment' rows=10 style='width: 300px'><?php print htmlPreP($row["comment"]) ?></textarea>
						</td>
					</tr>
					
					
					<tr class='break' id='entryDisplayTitleRow'>
						<td colspan=2>
							<h3><?php print __($guid, 'Type-Specific Details') ?></h3>
						</td>
					</tr>
					
					<?php
					try {
						$dataFields=array("gibbonLibraryTypeID"=>$row["gibbonLibraryTypeID"]); 
						$sqlFields="SELECT * FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID AND active='Y' ORDER BY name" ;
						$resultFields=$connection2->prepare($sqlFields);
						$resultFields->execute($dataFields);
					}
					catch(PDOException $e) { }
					
					if ($resultFields->rowCount()!=1) {
						print "<div class='error'>" ;
							print __($guid, "The specified record cannot be found.") ;
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
									print "<b>" . __($guid, $field["name"]) . "</b>" ;
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
												print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
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
							<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input type="hidden" name="gibbonLibraryItemID" value="<?php print $row["gibbonLibraryItemID"] ?>">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
}
?>