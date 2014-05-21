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

if (isActionAccessible($guid, $connection2, "/modules/Library/library_manage_catalog_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . _(getModuleName($_GET["q"])) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/library_manage_catalog.php'>" . _('Manage Catalog') . "</a> > </div><div class='trailEnd'>" . _('Add Item') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage=_("Your request was successful, but some data was not properly saved.") ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=_("Your request was completed successfully.You can now add another record if you wish.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	if ($_GET["name"]!="" OR $_GET["gibbonLibraryTypeID"]!="" OR $_GET["gibbonSpaceID"]!="" OR $_GET["status"]!="" OR $_GET["gibbonPersonIDOwnership"]!="" OR $_GET["typeSpecificFields"]!="") {
		print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Library/library_manage_catalog.php&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "&typeSpecificFields=" . $_GET["typeSpecificFields"] . "'>" . _('Back to Search Results') . "</a>" ;
		print "</div>" ;
	}
	
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/library_manage_catalog_addProcess.php?name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "&typeSpecificFields=" . $_GET["typeSpecificFields"] ?>" enctype="multipart/form-data">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2>
					<h3><?php print _('Catalog Type') ?></h3>
				</td>
			</tr>
			<script type="text/javascript">
				$(document).ready(function(){
					$("#type").change(function(){
						if ($("#type").val()=="Please select...") {
							$("#entryDisplayTitleRow").hide();
							$("#entryDisplayRow").hide();
						}
						else {
							$("#generalDetailsRow").slideDown("fast", $("#generalDetailsRow").css("display","table-row"));
							$("#nameRow").slideDown("fast", $("#nameRow").css("display","table-row"));
							$("#idRow").slideDown("fast", $("#idRow").css("display","table-row"));
							$("#producerRow").slideDown("fast", $("#producerRow").css("display","table-row"));
							$("#vendorRow").slideDown("fast", $("#vendorRow").css("display","table-row"));
							$("#purchaseDateRow").slideDown("fast", $("#purchaseDateRow").css("display","table-row"));
							$("#invoiceNumberRow").slideDown("fast", $("#invoiceNumberRow").css("display","table-row"));
							$("#imageTypeRow").slideDown("fast", $("#imageTypeRow").css("display","table-row"));
							$("#locationRow").slideDown("fast", $("#locationRow").css("display","table-row"));
							$("#locationDetailRow").slideDown("fast", $("#locationDetailRow").css("display","table-row"));
							$("#ownershipTypeRow").slideDown("fast", $("#ownershipTypeRow").css("display","table-row"));
							$("#gibbonDepartmentIDRow").slideDown("fast", $("#gibbonDepartmentIDRow").css("display","table-row"));
							$("#borrowableRow").slideDown("fast", $("#borrowableRow").css("display","table-row"));
							$("#statusRow").slideDown("fast", $("#statusRow").css("display","table-row"));
							$("#commentRow").slideDown("fast", $("#commentRow").css("display","table-row"));
							$("#entryDisplayTitleRow").slideDown("fast", $("#entryDisplayTitleRow").css("display","table-row"));
							$("#entryDisplayRow").slideDown("fast", $("#entryDisplayRow").css("display","table-row"));
							$("#details").load("<?php print $_SESSION[$guid]["absoluteURL"] ?>/modules/Library/library_manage_catalog_add_ajax.php","id=" + $("#type").val());
						}
					})
				});
			</script>
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Type') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="type" id="type" class='type' style="width: 302px">
						<option value="Please select..."><?php print _('Please select...') ?></option>
						<?php
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT * FROM gibbonLibraryType WHERE active='Y' ORDER BY name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["gibbonLibraryTypeID"] . "'>" . _($rowSelect["name"]) . "</option>" ; 
						}
						?>
					</select>
					<script type="text/javascript">
						var type=new LiveValidation('type');
						type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
					</script>
				</td>
			</tr>
			
			<tr class='break' id='generalDetailsRow' style='display: none'>
				<td colspan=2>
					<h3><?php print _('General Details') ?></h3>
				</td>
			</tr>
			<tr id='nameRow' style='display: none'>
				<td> 
					<b><?php print _('Name') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Volume or product name.') ?></i></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=255 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var name2=new LiveValidation('name');
						name2.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr id='idRow' style='display: none'>
				<td> 
					<b><?php print _('ID') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Must be unique.') ?></i></span>
				</td>
				<td class="right">
					<input name="id" id="id" maxlength=255 value="" type="text" style="width: 300px">
					<?php
					//Get list of all ids already in use
					$idList="" ;
					try {
						$dataSelect=array(); 
						$sqlSelect="SELECT id FROM gibbonLibraryItem ORDER BY id" ;
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
			<tr id='producerRow' style='display: none'>
				<td> 
					<b><?php print _('Author/Brand') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Who created the item?') ?></i></span>
				</td>
				<td class="right">
					<input name="producer" id="producer" maxlength=255 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var producer=new LiveValidation('producer');
						producer.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr id='vendorRow' style='display: none'>
				<td> 
					<b><?php print _('Vendor') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('Who supplied the item?') ?></i></span>
				</td>
				<td class="right">
					<input name="vendor" id="vendor" maxlength=100 value="" type="text" style="width: 300px">
				</td>
			</tr>
			<tr id='purchaseDateRow' style='display: none'>
				<td> 
					<b><?php print _('Purchase Date') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="purchaseDate" id="purchaseDate" maxlength=10 value="" type="text" style="width: 300px">
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
			<tr id='invoiceNumberRow' style='display: none'>
				<td> 
					<b><?php print _('Invoice Number') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="invoiceNumber" id="invoiceNumber" maxlength=50 value="" type="text" style="width: 300px">
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
			<tr id='imageTypeRow' style='display: none'>
				<td> 
					<b><?php print _('Image Type') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('240px x 240px or smaller.') ?></i></span>
				</td>
				<td class="right">
					<select name="imageType" id="imageType" class='imageType' style="width: 302px">
						<option value=""></option>
						<option value="File" /> <?php print _('File') ?>
						<option value="Link" /> <?php print _('Link') ?>
					</select>
				</td>
			</tr>
			<tr id="imageFileRow" style='display: none'>
				<td> 
					<b><?php print _('Image File') ?></b><br/>
				</td>
				<td class="right">
					<input type="file" name="imageFile" id="imageFile"><br/><br/>
					<script type="text/javascript">
						var imageFile=new LiveValidation('imageFile');
						imageFile.add( Validate.Inclusion, { within: ['.jpg','.jpeg','.png','.gif'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
						imageFile.disable();
					</script>	
					<?php
					print getMaxUpload() ;
					?>
				</td>
			</tr>
			<tr id="imageLinkRow" style='display: none'>
				<td> 
					<b><?php print _('Image Link') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="imageLink" id="imageLink" maxlength=255 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var imageLink=new LiveValidation('imageLink');
						imageLink.add(Validate.Presence);
						imageLink.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
						imageLink.disable();
					</script>	
				</td>
			</tr>
			
			<tr id="locationRow" style='display: none'>
				<td> 
					<b><?php print _('Location') ?> *</b><br/>
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
							print "<option value='" . $rowSelect["gibbonSpaceID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr id='locationDetailRow' style='display: none'>
				<td> 
					<b><?php print _('Location Detail') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('Shelf, cabinet, sector, etc') ?></i></span>
				</td>
				<td class="right">
					<input name="locationDetail" id="locationDetail" maxlength=255 value="" type="text" style="width: 300px">
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
			<tr id='ownershipTypeRow' style='display: none'>
				<td> 
					<b><?php print _('Ownership Type') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select name="ownershipType" id="ownershipType" class='ownershipType' style="width: 302px">
						<option value=""></option>
						<option value="School" /> <?php print _('School') ?>
						<option value="Individual" /> <?php print _('Individual') ?>
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
				$selectContents.="<optgroup label='--<?php print _('All Users') ?>--'>" ;
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
						$expected=" (" . _('Expected') . ")" ;
					}
					$selectContents.="<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "$expected</option>" ;
				}
				$selectContents.="</optgroup>" ;
			?>
			<tr id="ownershipTypeSchoolRow" style='display: none'>
				<td> 
					<b><?php print _('Main User') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('Person the device is assigned to.') ?></i></span>
				</td>
				<td class="right">
					<select name="gibbonPersonIDOwnershipSchool" id="gibbonPersonIDOwnershipSchool" style="width: 302px">
						<?php print $selectContents ?>
					</select>
				</td>
			</tr>
			<tr id="ownershipTypeIndividualRow" style='display: none'>
				<td> 
					<b><?php print _('Owner') ?></b><br/>
				</td>
				<td class="right">
					<select name="gibbonPersonIDOwnershipIndividual" id="gibbonPersonIDOwnershipIndividual" style="width: 302px">
						<?php print $selectContents ?>
					</select>
				</td>
			</tr>
			<tr id="gibbonDepartmentIDRow" style='display: none'>
				<td> 
					<b><?php print _('Department') ?></b><br/>
					<span style="font-size: 90%"><i><?php print _('Which department is responsible for the item?') ?></i></span>
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
							print "<option value='" . $rowSelect["gibbonDepartmentID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
						}
						?>
					</select>
				</td>
			</tr>
			<tr id='borrowableRow' style='display: none'>
				<td> 
					<b><?php print _('Borrowable?') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Is item available for loan?') ?></i></span>
				</td>
				<td class="right">
					<select name="borrowable" id="borrowable" style="width: 302px">
						<option value="Y" /> <?php print _('Yes') ?>
						<option value="N" /> <?php print _('No') ?>
					</select>
				</td>
			</tr>
			<tr id='statusRow' style='display: none'>
				<td> 
					<b><?php print _('Status?') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Initial availability.') ?></i></span>
				</td>
				<td class="right">
					<select name="status" id="status" style="width: 302px">
						<option value="Available" /> <?php print _('Available') ?>
						<option value="In Use" /> <?php print _('In Use') ?>
						<option value="Reserved" /> <?php print _('Reserved') ?>
						<option value="Decommissioned" /> <?php print _('Decommissioned') ?>
						<option value="Lost" /> <?php print _('Lost') ?>
						<option value="Repair" /> <?php print _('Repair') ?>
					</select>
				</td>
			</tr>
			
			<tr id='commentRow' style='display: none'>
				<td colspan=2> 
					<b><?php print _('Comments/Notes') ?></b> 
					<textarea name='comment' id='comment' rows=10 style='width: 300px'></textarea>
				</td>
			</tr>
			
			
			<tr class='break' id='entryDisplayTitleRow' style='display: none'>
				<td colspan=2>
					<h3><?php print _('Type-Specific Details') ?></h3>
				</td>
			</tr>
			<tr id='entryDisplayRow' style='display: none'>
				<td colspan=2 style='text-align: center'>
					<div id='details' name='details' style='min-height: 100px; text-align: center'>
						<img style='margin: 10px 0 5px 0' src='<?php print $_SESSION[$guid]["absoluteURL"] ?>/themes/Default/img/loading.gif' alt='Loading' onclick='return false;' /><br/>Loading
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
}
?>