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

if (isActionAccessible($guid, $connection2, "/modules/Data Updater/data_finance.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Update Finance Data') . "</div>" ;
		print "</div>" ;
	
		if ($highestAction=="Update Finance Data_any") {
			print "<p>" ;
			print _("This page allows a user to request selected finance data updates for any user. If a user does not appear in the list, please visit the Manage Invoicees page to create any missing students.") ;
			print "</p>" ;
		}
		else {
			print "<p>" ;
			print sprintf(_('This page allows any adult with data access permission to request selected finance data updates for any children in their family. If any of your children do not appear in this list, please contact %1$s.'), "<a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a>") ;
			print "</p>" ;
		}
		
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
			else if ($updateReturn=="fail4") {
				$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($updateReturn=="fail5") {
				$updateReturnMessage=_("Your request was successful, but some data was not properly saved. An administrator will process your request as soon as possible. <u>You will not see the updated data in the system until it has been processed and approved.</u>") ; 
				if ($_SESSION[$guid]["organisationDBAEmail"]!="" AND $_SESSION[$guid]["organisationDBAName"]!="") {
					$updateReturnMessage.=" " . sprintf(_('Please contact %1$s if you have any questions.'), "<a href='mailto:" . $_SESSION[$guid]["organisationDBAEmail"] . "'>" . $_SESSION[$guid]["organisationDBAName"] . "</a>") ;	
				}
			}
			else if ($updateReturn=="success0") {
				$updateReturnMessage=_("Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed and approved.") ; 
				if ($_SESSION[$guid]["organisationDBAEmail"]!="" AND $_SESSION[$guid]["organisationDBAName"]!="") {
					$updateReturnMessage.=" " . sprintf(_('Please contact %1$s if you have any questions.'), "<a href='mailto:" . $_SESSION[$guid]["organisationDBAEmail"] . "'>" . $_SESSION[$guid]["organisationDBAName"] . "</a>") ;	
				}
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $updateReturnMessage;
			print "</div>" ;
		} 
		
		print "<h2>" ;
		print _("Choose User") ;
		print "</h2>" ;
		
		$gibbonFinanceInvoiceeID=NULL ;
		if (isset($_GET["gibbonFinanceInvoiceeID"])) {
			$gibbonFinanceInvoiceeID=$_GET["gibbonFinanceInvoiceeID"] ;
		}
		?>
		
		<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr>
					<td style='width: 275px'> 
						<b><?php print _('Invoicee') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('Individual for whom invoices are generated.') ?></i></span>
					</td>
					<td class="right">
						<select style="width: 302px" name="gibbonFinanceInvoiceeID">
							<?php
							if ($highestAction=="Update Finance Data_any") {
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFinanceInvoiceeID FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								print "<option value=''></option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									if ($gibbonFinanceInvoiceeID==$rowSelect["gibbonFinanceInvoiceeID"]) {
										print "<option selected value='" . $rowSelect["gibbonFinanceInvoiceeID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
									}
									else {
										print "<option value='" . $rowSelect["gibbonFinanceInvoiceeID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
									}
								}
							}
							else {
								try {
									$dataSelect=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
									$sqlSelect="SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								print "<option value=''></option>" ;
								while ($rowSelect=$resultSelect->fetch()) {
									try {
										$dataSelect2=array("gibbonFamilyID"=>$rowSelect["gibbonFamilyID"]); 
										$sqlSelect2="SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID, gibbonFinanceInvoiceeID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID" ;
										$resultSelect2=$connection2->prepare($sqlSelect2);
										$resultSelect2->execute($dataSelect2);
									}
									catch(PDOException $e) { }
									while ($rowSelect2=$resultSelect2->fetch()) {
										if ($gibbonFinanceInvoiceeID==$rowSelect2["gibbonFinanceInvoiceeID"]) {
											print "<option selected value='" . $rowSelect2["gibbonFinanceInvoiceeID"] . "'>" . formatName("", htmlPrep($rowSelect2["preferredName"]), htmlPrep($rowSelect2["surname"]), "Student", true) . "</option>" ;
										}
										else {
											print "<option value='" . $rowSelect2["gibbonFinanceInvoiceeID"] . "'>" . formatName("", htmlPrep($rowSelect2["preferredName"]), htmlPrep($rowSelect2["surname"]), "Student", true) . "</option>" ;
										}
									}
								}
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/data_finance.php">
						<input type="submit" value="<?php print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
		
		if ($gibbonFinanceInvoiceeID!="") {
			print "<h2>" ;
			print _("Update Data") ;
			print "</h2>" ;
			
			//Check access to person
			$checkCount=0 ;
			if ($highestAction=="Update Finance Data_any") {
				try {
					$dataSelect=array("gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID); 
					$sqlSelect="SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFinanceInvoiceeID FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID ORDER BY surname, preferredName" ;
					$resultSelect=$connection2->prepare($sqlSelect);
					$resultSelect->execute($dataSelect);
				}
				catch(PDOException $e) { }
				$checkCount=$resultSelect->rowCount() ;
			}
			else {
				try {
					$dataCheck=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sqlCheck="SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name" ;
					$resultCheck=$connection2->prepare($sqlCheck);
					$resultCheck->execute($dataCheck);
				}
				catch(PDOException $e) { }
				while ($rowCheck=$resultCheck->fetch()) {
					try {
						$dataCheck2=array("gibbonFamilyID"=>$rowCheck["gibbonFamilyID"]); 
						$sqlCheck2="SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID, gibbonFinanceInvoiceeID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID" ;
						$resultCheck2=$connection2->prepare($sqlCheck2);
						$resultCheck2->execute($dataCheck2);
					}
					catch(PDOException $e) { }
					while ($rowCheck2=$resultCheck2->fetch()) {
						if ($gibbonFinanceInvoiceeID==$rowCheck2["gibbonFinanceInvoiceeID"]) {
							$checkCount++ ;
						}
					}
				}
			}
			
			if ($checkCount<1) {
				print "<div class='error'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				//Check if there is already a pending form for this user
				$existing=FALSE ;
				$proceed=FALSE;
				try {
					$data=array("gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID, "gibbonPersonIDUpdater"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT * FROM gibbonFinanceInvoiceeUpdate WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND gibbonPersonIDUpdater=:gibbonPersonIDUpdater AND status='Pending'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($result->rowCount()>1) {
					print "<div class='error'>" ;
						print _("Your request failed due to a database error.") ;
					print "</div>" ;
				}
				else if ($result->rowCount()==1) {
					$existing=TRUE ;
					$proceed=FALSE;
					if ($updateReturn=="") {
						print "<div class='warning'>" ;
							print _("You have already submitted a form, which is pending approval by an administrator. If you wish to make changes, please edited the data below, but remember your data will not appear in the system until it has been approved.") ;
						print "</div>" ;
					}
					$proceed=TRUE;
				}
				else {
					//Get user's data
					try {
						$data=array("gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID); 
						$sql="SELECT * FROM gibbonFinanceInvoicee WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($result->rowCount()!=1) {
						print "<div class='error'>" ;
							print _("The specified record cannot be found.") ;
						print "</div>" ;
					}
					else {
						$proceed=TRUE;
					}
				}
			
				if ($proceed==TRUE) {
					//Let's go!
					$row=$result->fetch() ;
					?>
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/data_financeProcess.php?gibbonFinanceInvoiceeID=" . $gibbonFinanceInvoiceeID ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
							<tr class='break'>
								<td colspan=2> 
									<h4><?php print _('Invoice To') ?></h4>
								</td>
							</tr>
							
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
									<p><?php print _('If you choose family, future invoices will be sent according to family contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.') ?></p>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'> 
									<b><?php print _('Send Invoices To') ?></b><br/>
								</td>
								<td class="right">
									<input <?php if ($row["invoiceTo"]=="Family" OR $row["invoiceTo"]=="") { print "checked" ; } ?> type="radio" name="invoiceTo" value="Family" class="invoiceTo" /> <?php print _('Family') ?>
									<input <?php if ($row["invoiceTo"]=="Company") { print "checked" ; } ?> type="radio" name="invoiceTo" value="Company" class="invoiceTo" /> <?php print _('Company') ?>
								</td>
							</tr>
							<tr id="companyNameRow">
								<td> 
									<b><?php print _('Company Name') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="companyName" id="companyName" maxlength=100 value="<?php print $row["companyName"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var companyName=new LiveValidation('companyName');
										companyName.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr id="companyContactRow">
								<td> 
									<b><?php print _('Company Contact Person') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="companyContact" id="companyContact" maxlength=100 value="<?php print $row["companyContact"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var companyContact=new LiveValidation('companyContact');
										companyContact.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr id="companyAddressRow">
								<td> 
									<b><?php print _('Company Address') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="companyAddress" id="companyAddress" maxlength=255 value="<?php print $row["companyAddress"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var companyAddress=new LiveValidation('companyAddress');
										companyAddress.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr id="companyEmailRow">
								<td> 
									<b><?php print _('Company Email') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="companyEmail" id="companyEmail" maxlength=255 value="<?php print $row["companyEmail"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var companyEmail=new LiveValidation('companyEmail');
										companyEmail.add(Validate.Presence);
										companyEmail.add(Validate.Email);
									</script>
								</td>
							</tr>
							<tr id="companyCCFamilyRow">
								<td> 
									<b><?php print _('CC Family?') ?></b><br/>
									<span style="font-size: 90%"><i><?php print _('Should the family be sent a copy of billing emails?') ?></i></span>
								</td>
								<td class="right">
									<select name="companyCCFamily" id="companyCCFamily" style="width: 302px">
										<option <?php if ($row["companyCCFamily"]=="N") { print "selected" ; } ?> value="N" /> <?php print _('No') ?>
										<option <?php if ($row["companyCCFamily"]=="Y") { print "selected" ; } ?> value="Y" /> <?php print _('Yes') ?>
									</select>
								</td>
							</tr>
							<tr id="companyPhoneRow">
								<td> 
									<b><?php print _('Company Phone') ?></b><br/>
								</td>
								<td class="right">
									<input name="companyPhone" id="companyPhone" maxlength=20 value="<?php print $row["companyPhone"] ?>" type="text" style="width: 300px">
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
										<b><?php print _('Company All?') ?></b><br/>
										<span style="font-size: 90%"><i><?php print _('Should all items be billed to the specified company, or just some?') ?></i></span>
									</td>
									<td class="right">
										<input type="radio" name="companyAll" value="Y" class="companyAll" <?php if ($row["companyAll"]=="Y" OR $row["companyAll"]=="") { print "checked" ; } ?> /> <?php print _('All') ?>
										<input type="radio" name="companyAll" value="N" class="companyAll" <?php if ($row["companyAll"]=="N") { print "checked" ; } ?> /> <?php print _('Selected') ?>
									</td>
								</tr>
								<tr id="companyCategoriesRow">
									<td> 
										<b><?php print _('Company Fee Categories') ?></b><br/>
										<span style="font-size: 90%"><i><?php print _('If the specified company is not paying all fees, which categories are they paying?') ?></i></span>
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
										print _("Other") . " <input $checked type='checkbox' name='gibbonFinanceFeeCategoryIDList[]' value='0001'/><br/>" ;
										?>
									</td>
								</tr>
								<?php
							}
							?>							
							
							<tr>
								<td>
									<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
								</td>
								<td class="right">
									<?php
									if ($existing) {
										print "<input type='hidden' name='existing' value='" . $row["gibbonFinanceInvoiceeUpdateID"] . "'>" ;
									}
									else {
										print "<input type='hidden' name='existing' value='N'>" ;
									}
									?>
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
}
?>