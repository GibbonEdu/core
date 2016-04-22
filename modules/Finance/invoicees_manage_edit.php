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


if (isActionAccessible($guid, $connection2, "/modules/Finance/invoicees_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/invoicees_manage.php'>" . __($guid, 'Manage Invoicees') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Invoicee') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	if ($_GET["search"]!="" OR $_GET["allUsers"]=="on") {
		print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoicees_manage.php&search=" . $_GET["search"] . "&allUsers=" . $_GET["allUsers"] . "'>" . __($guid, 'Back to Search Results') . "</a>" ;
		print "</div>" ;
	}
	
	//Check if school year specified
	$gibbonFinanceInvoiceeID=$_GET["gibbonFinanceInvoiceeID"];
	if ($gibbonFinanceInvoiceeID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID); 
			$sql="SELECT surname, preferredName, status, gibbonFinanceInvoicee.* FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID" ;
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
			
			print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
				print "<tr>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Name') . "</span><br/>" ;
						print formatName("", $row["preferredName"], $row["surname"], "Student") ;
					print "</td>" ;
					print "<td style='width: 33%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Status') . "</span><br/>" ;
						print "<i>" . $row["status"] . "</i>" ;
					print "</td>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/invoicees_manage_editProcess.php?gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&search=" .$_GET["search"] . "&allUsers=" . $_GET["allUsers"] ; ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<script type="text/javascript">
						/* Resource 1 Option Control */
						$(document).ready(function(){
							if ($('input[name=invoiceTo]:checked').val()=="Family" ) {
								$("#companyNameRow").css("display","none");
								$("#companyContactRow").css("display","none");
								$("#companyAddressRow").css("display","none");
								$("#companyEmailRow").css("display","none");
								$("#companyCCFamilyRow").css("display","none");
								$("#companyPhoneRow").css("display","none");
								$("#companyAllRow").css("display","none");
								$("#companyCategoriesRow").css("display","none");
								companyEmail.disable() ;
								companyAddress.disable() ;
								companyContact.disable() ;
								companyName.disable() ;
							}
							else {
								if ($('input[name=companyAll]:checked').val()=="Y" ) {
									$("#companyCategoriesRow").css("display","none");
								}
							}
							
							$(".invoiceTo").click(function(){
								if ($('input[name=invoiceTo]:checked').val()=="Family" ) {
									$("#companyNameRow").css("display","none");
									$("#companyContactRow").css("display","none");
									$("#companyAddressRow").css("display","none");
									$("#companyEmailRow").css("display","none");
									$("#companyCCFamilyRow").css("display","none");
									$("#companyPhoneRow").css("display","none");
									$("#companyAllRow").css("display","none");
									$("#companyCategoriesRow").css("display","none");
									companyEmail.disable() ;
									companyAddress.disable() ;
									companyContact.disable() ;
									companyName.disable() ;
								} else {
									$("#companyNameRow").slideDown("fast", $("#companyNameRow").css("display","table-row")); 
									$("#companyContactRow").slideDown("fast", $("#companyContactRow").css("display","table-row")); 
									$("#companyAddressRow").slideDown("fast", $("#companyAddressRow").css("display","table-row")); 
									$("#companyEmailRow").slideDown("fast", $("#companyEmailRow").css("display","table-row")); 
									$("#companyCCFamilyRow").slideDown("fast", $("#companyCCFamilyRow").css("display","table-row")); 
									$("#companyPhoneRow").slideDown("fast", $("#companyPhoneRow").css("display","table-row")); 
									$("#companyAllRow").slideDown("fast", $("#companyAllRow").css("display","table-row")); 
									if ($('input[name=companyAll]:checked').val()=="Y" ) {
										$("#companyCategoriesRow").css("display","none");
									} else {
										$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row")); 
									}
									companyEmail.enable() ;
									companyAddress.enable() ;
									companyContact.enable() ;
									companyName.enable() ;
								}
							 });
							 
							 $(".companyAll").click(function(){
								if ($('input[name=companyAll]:checked').val()=="Y" ) {
									$("#companyCategoriesRow").css("display","none");
								} else {
									$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row")); 
								}
							 });
						});
					</script>
					<tr id="familyRow">
						<td colspan=2'>
							<p><?php print __($guid, 'If you choose family, future invoices will be sent according to family contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.') ?></p>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Send Invoices To') ?></b><br/>
						</td>
						<td class="right">
							<input <?php if ($row["invoiceTo"]=="Family") { print "checked" ; } ?> type="radio" name="invoiceTo" value="Family" class="invoiceTo" /> <?php print __($guid, 'Family') ?>
							<input <?php if ($row["invoiceTo"]=="Company") { print "checked" ; } ?> type="radio" name="invoiceTo" value="Company" class="invoiceTo" /> <?php print __($guid, 'Company') ?>
						</td>
					</tr>
					<tr id="companyNameRow">
						<td> 
							<b><?php print __($guid, 'Company Name') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="companyName" id="companyName" maxlength=100 value="<?php print $row["companyName"] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var companyName=new LiveValidation('companyName');
								companyName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id="companyContactRow">
						<td> 
							<b><?php print __($guid, 'Company Contact Person') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="companyContact" id="companyContact" maxlength=100 value="<?php print $row["companyContact"] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var companyContact=new LiveValidation('companyContact');
								companyContact.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id="companyAddressRow">
						<td> 
							<b><?php print __($guid, 'Company Address') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="companyAddress" id="companyAddress" maxlength=255 value="<?php print $row["companyAddress"] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var companyAddress=new LiveValidation('companyAddress');
								companyAddress.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id="companyEmailRow">
						<td> 
							<b><?php print __($guid, 'Company Emails') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Comma-separated list of email address.') ?></span>
						</td>
						<td class="right">
							<input name="companyEmail" id="companyEmail" value="<?php print $row["companyEmail"] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var companyEmail=new LiveValidation('companyEmail');
								companyEmail.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr id="companyCCFamilyRow">
						<td> 
							<b><?php print __($guid, 'CC Family?') ?></b><br/>
							<span class="emphasis small"><?php print __($guid, 'Should the family be sent a copy of billing emails?') ?></span>
						</td>
						<td class="right">
							<select name="companyCCFamily" id="companyCCFamily" class="standardWidth">
								<option <?php if ($row["companyCCFamily"]=="N") { print "selected" ; } ?> value="N" /> <?php print __($guid, 'No') ?>
								<option <?php if ($row["companyCCFamily"]=="Y") { print "selected" ; } ?> value="Y" /> <?php print __($guid, 'Yes') ?>
							</select>
						</td>
					</tr>
					<tr id="companyPhoneRow">
						<td> 
							<b><?php print __($guid, 'Company Phone') ?></b><br/>
						</td>
						<td class="right">
							<input name="companyPhone" id="companyPhone" maxlength=20 value="<?php print $row["companyPhone"] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<?php
					try {
						$dataCat=array(); 
						$sqlCat="SELECT * FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name" ;
						$resultCat=$connection2->prepare($sqlCat);
						$resultCat->execute($dataCat);
					}
					catch(PDOException $e) { }
					if ($resultCat->rowCount()<1) {
						print "<input type=\"hidden\" name=\"companyAll\" value=\"Y\" class=\"companyAll\"/>" ;
					}
					else {
						?>
						<tr id="companyAllRow">
							<td> 
								<b><?php print __($guid, 'Company All?') ?></b><br/>
								<span class="emphasis small"><?php print __($guid, 'Should all items be billed to the specified company, or just some?') ?></span>
							</td>
							<td class="right">
								<input type="radio" name="companyAll" value="Y" class="companyAll" <?php if ($row["companyAll"]=="Y" OR $row["companyAll"]=="") { print "checked" ; } ?> /> <?php print __($guid, 'All') ?>
								<input type="radio" name="companyAll" value="N" class="companyAll" <?php if ($row["companyAll"]=="N") { print "checked" ; } ?> /> <?php print __($guid, 'Selected') ?>
							</td>
						</tr>
						<tr id="companyCategoriesRow">
							<td> 
								<b><?php print __($guid, 'Company Fee Categories') ?></b><br/>
								<span class="emphasis small"><?php print __($guid, 'If the specified company is not paying all fees, which categories are they paying?') ?></span>
							</td>
							<td class="right">
								<?php
								while ($rowCat=$resultCat->fetch()) {
									$checked="" ;
									if (strpos($row["gibbonFinanceFeeCategoryIDList"], $rowCat["gibbonFinanceFeeCategoryID"])!==FALSE) {
										$checked="checked" ;
									}
									print $rowCat["name"] . " <input $checked type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='" . $rowCat["gibbonFinanceFeeCategoryID"] . "'/><br/>" ;
								}
								$checked="" ;
								if (strpos($row["gibbonFinanceFeeCategoryIDList"], "0001")!==FALSE) {
									$checked="checked" ;
								}
								print "Other <input $checked type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='0001'/><br/>" ;
								?>
							</td>
						</tr>
						<?php
					}
					?>
					
					<tr>
						<td>
							<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
						</td>
						<td class="right">
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