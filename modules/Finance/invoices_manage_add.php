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

if (isActionAccessible($guid, $connection2, "/modules/Finance/invoices_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . _('Manage Invoices') . "</a> > </div><div class='trailEnd'>" . _('Add Fees & Invoices') . "</div>" ;
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
			$addReturnMessage=_("Some aspects of your update failed, effecting the following areas:") . "<ul>" ;	
			if ($_GET["studentFailCount"]) {
				$addReturnMessage.="<li>" . $_GET["studentFailCount"] . " " . _('students encountered problems.') . "</li>" ;
			}
			if ($_GET["invoiceFailCount"]) {
				$addReturnMessage.="<li>" . $_GET["invoiceFailCount"] . " " . _('invoices encountered problems.') . "</li>" ;
			}
			if ($_GET["invoiceFeeFailCount"]) {
				$addReturnMessage.="<li>" . $_GET["invoiceFeeFailCount"] . " " . _('fee entires encountered problems.') . "</li>" ;
			}
			$addReturnMessage.="</ul>" . _('It is recommended that you remove all pending invoices and try to recreate them.') ;
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	}
	
	print "<p>" ;
		print _("Here you can add fees to one or more students. These fees will be added to an existing invoice or used to form a new invoice, depending on the specified billing schedule and other details.") ;
	print "</p>" ; 
	
	//Check if school year specified
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$status=$_GET["status"] ;
	$gibbonFinanceInvoiceeID=$_GET["gibbonFinanceInvoiceeID"] ;
	$monthOfIssue=$_GET["monthOfIssue"] ;
	$gibbonFinanceBillingScheduleID=$_GET["gibbonFinanceBillingScheduleID"] ;
	if ($gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		if ($status!="" OR $gibbonFinanceInvoiceeID!="" OR $monthOfIssue!="" OR $gibbonFinanceBillingScheduleID!="") {
			print "<div class='linkTop'>" ;
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'>" . _('Back to Search Results') . "</a>" ;
			print "</div>" ;
		}
		?>
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID" ?>">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr class='break'>
					<td colspan=2> 
						<h3><?php print _('Basic Information') ?></h3>
					</td>
				</tr>
				<tr>
					<td style='width: 275px'> 
						<b><?php print _('School Year') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
					</td>
					<td class="right">
						<?php
						$yearName="" ;
						try {
							$dataYear=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
							$sqlYear="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
							$resultYear=$connection2->prepare($sqlYear);
							$resultYear->execute($dataYear);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultYear->rowCount()==1) {
							$rowYear=$resultYear->fetch() ;
							$yearName=$rowYear["name"] ;
						}
						?>
						<input readonly name="yearName" id="yearName" maxlength=20 value="<?php print $yearName ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var yearName=new LiveValidation('yearName');
							yearname2.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Invoicees') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?><br/><?php print sprintf(_('Visit %1$sManage Invoicees%2$s to automatically generate missing students.'), "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoicees_manage.php'>", "</a>") ?></i></span>
					</td>
					<td class="right">
						<select name="gibbonFinanceInvoiceeIDs[]" id="gibbonFinanceInvoiceeIDs[]" multiple style="width: 302px; height: 150px">
							<optgroup label='--<?php print _('All Enrolled Students by Roll Group') ?>--'>
							<?php
							$students=array() ;
							$count=0 ;
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
								$sqlSelect="SELECT gibbonFinanceInvoiceeID, preferredName, surname, gibbonRollGroup.name AS name, dayType FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup, gibbonFinanceInvoicee WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonFinanceInvoiceeID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
								$students[$count]["gibbonFinanceInvoiceeID"]=$rowSelect["gibbonFinanceInvoiceeID"] ;
								$students[$count]["student"]=formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) ;
								$students[$count]["rollGroup"]=htmlPrep($rowSelect["name"]) ;
								$students[$count]["dayType"]=htmlPrep($rowSelect["dayType"]) ;
								$count++ ;
							}
							?>
							</optgroup>
							<?php
							$dayTypeOptions=getSettingByScope($connection2, 'User Admin', 'dayTypeOptions') ;
							if ($dayTypeOptions!="") {
								$dayTypes=explode(",", $dayTypeOptions) ;
								foreach ($dayTypes as $dayType) {
									print "<optgroup label='--$dayType " . _('Students by Roll Groups') . "--'>" ; 
									foreach ($students AS $student) {
										if ($student["dayType"]==$dayType) {
											print "<option value='" . $student["gibbonFinanceInvoiceeID"] . "'>" . $student["rollGroup"] . " - " . $student["student"] . "</option>" ;
										}
									}
									print "</optgroup>" ;
								}
							}
							?>
							<optgroup label='--<?php print _('All Enrolled Students by Alphabet') ?>--'>
							<?php
							$students=array() ;
							$count=0 ;
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
								$sqlSelect="SELECT gibbonFinanceInvoiceeID, preferredName, surname, gibbonRollGroup.name AS name, dayType FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup, gibbonFinanceInvoicee WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonFinanceInvoiceeID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " - " . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
							?>
							</optgroup>
							
						</select>
					</td>
				</tr>
				<?php //BILLING TYPE CHOOSER ?>
				<tr>
					<td> 
						<b><?php print _('Scheduling') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('When using scheduled, invoice due date is linked to and determined by the schedule.') ?></i></span>
					</td>
					<td class="right">
						<input checked type="radio" name="scheduling" class="scheduling" value="Scheduled" /> Scheduled
						<input type="radio" name="scheduling" class="scheduling" value="Ad Hoc" /> Ad Hoc
					</td>
				</tr>
				<script type="text/javascript">
					$(document).ready(function(){
						$("#adHocRow").css("display","none");
						invoiceDueDate.disable() ;
						$("#schedulingRow").slideDown("fast", $("#schedulingRow").css("display","table-row")); 
						
						$(".scheduling").click(function(){
							if ($('input[name=scheduling]:checked').val()=="Scheduled" ) {
								$("#adHocRow").css("display","none");
								invoiceDueDate.disable() ;
								$("#schedulingRow").slideDown("fast", $("#schedulingRow").css("display","table-row")); 
								gibbonFinanceBillingScheduleID.enable() ;
							} else {
								$("#schedulingRow").css("display","none");
								gibbonFinanceBillingScheduleID.disable() ;
								$("#adHocRow").slideDown("fast", $("#adHocRow").css("display","table-row")); 
								invoiceDueDate.enable() ;
							}
						 });
					});
				</script>
				
				<tr id="schedulingRow">
					<td> 
						<b><?php print _('Billing Schedule') ?> *</b><br/>
					</td>
					<td class="right">
						<select name="gibbonFinanceBillingScheduleID" id="gibbonFinanceBillingScheduleID" style="width: 302px">
							<?php
							print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonFinanceBillingSchedule WHERE active='Y' ORDER BY name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonFinanceBillingScheduleID"] . "'>" . $rowSelect["name"] . "</option>" ;
							}
							?>				
						</select>
						<script type="text/javascript">
							var gibbonFinanceBillingScheduleID=new LiveValidation('gibbonFinanceBillingScheduleID');
							gibbonFinanceBillingScheduleID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
						 </script>
					</td>
				</tr>
				<tr id="adHocRow">
					<td> 
						<b><?php print _('Invoice Due Date') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('For fees added to existing invoice, specified date will override existing due date.') ?></i></span>
					</td>
					<td class="right">
						<input name="invoiceDueDate" id="invoiceDueDate" maxlength=10 value="" type="text" style="width: 300px">
							<script type="text/javascript">
							var invoiceDueDate=new LiveValidation('invoiceDueDate');
							invoiceDueDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							invoiceDueDate.add(Validate.Presence);
						 </script>
						 <script type="text/javascript">
							$(function() {
								$( "#invoiceDueDate" ).datepicker();
							});
						</script>
					</td>
				</tr>
				<tr>
					<td colspan=2> 
						<b><?php print _('Notes') ?></b> 
						<textarea name='notes' id='notes' rows=5 style='width: 300px'></textarea>
					</td>
				</tr>
				
				<tr class='break'>
					<td colspan=2> 
						<h3><?php print _('Fees') ?></h3>
					</td>
				</tr>
				<?php 
				$type="fee" ; 
				?> 
				<style>
					#<?php print $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
					#<?php print $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
					div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
					html>body #<?php print $type ?> li { min-height: 58px; line-height: 1.2em; }
					.<?php print $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
					.<?php print $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
				</style>
				<tr>
					<td colspan=2> 
						<div class="fee" id="fee" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
							<div id="feeOuter0">
								<div style='color: #ddd; font-size: 230%; margin: 15px 0 0 6px'><?php print _('Fees will be listed here...') ?></div>
							</div>
						</div>
						<div style='width: 100%; padding: 0px 0px 0px 0px'>
							<div class="ui-state-default_dud" style='padding: 0px; height: 40px'>
								<table class='blank' cellspacing='0' style='width: 100%'>
									<tr>
										<td style='width: 50%'>
											<script type="text/javascript">
												var feeCount=1 ;
											</script>
											<select id='newFee' onChange='feeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
												<option class='all' value='0'><?php print _('Choose a fee to add it') ?></option>
												<?php
												print "<option value='Ad Hoc'>Ad Hoc Fee</option>" ;
												$switchContents.="case \"Ad Hoc\": " ;
												$switchContents.="$(\"#fee\").append('<div id=\'feeOuter' + feeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');" ;
												$switchContents.="$(\"#feeOuter\" + feeCount).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Finance/invoices_manage_add_blockFeeAjax.php\",\"mode=add&id=\" + feeCount + \"&feeType=" . urlencode("Ad Hoc") . "&gibbonFinanceFeeID=&name=" . urlencode("Ad Hoc Fee") . "&description=&gibbonFinanceFeeCategoryID=1&fee=\") ;" ;
												$switchContents.="feeCount++ ;" ;
												$switchContents.="$('#newFee').val('0');" ;
												$switchContents.="break;" ;
												$currentCategory="" ;
												$lastCategory="" ;
												for ($i=0; $i<2; $i++) {
													try {
														$dataSelect=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
														if ($i==0) {
															$sqlSelect="SELECT gibbonFinanceFee.*, gibbonFinanceFeeCategory.name AS category FROM gibbonFinanceFee LEFT JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceFee.active='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonFinanceFee.gibbonFinanceFeeCategoryID=1 ORDER BY gibbonFinanceFee.gibbonFinanceFeeCategoryID, gibbonFinanceFee.name" ;
														}
														else {
															$sqlSelect="SELECT gibbonFinanceFee.*, gibbonFinanceFeeCategory.name AS category FROM gibbonFinanceFee LEFT JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceFee.active='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceFee.gibbonFinanceFeeCategoryID=1 ORDER BY gibbonFinanceFee.gibbonFinanceFeeCategoryID, gibbonFinanceFee.name" ;
														}
														$resultSelect=$connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
													while ($rowSelect=$resultSelect->fetch()) {
														$currentCategory=$rowSelect["category"] ;
														if (($currentCategory!=$lastCategory) AND $currentCategory!="") {
															print "<optgroup label='--" . $currentCategory . "--'>" ;
															$categories[$categoryCount]=$currentCategory ;
															$categoryCount++ ;
														}
														print "<option value='" . $rowSelect["gibbonFinanceFeeID"] . "'>" . $rowSelect["name"] . "</option>" ;
														$switchContents.="case \"" . $rowSelect["gibbonFinanceFeeID"] . "\": " ;
														$switchContents.="$(\"#fee\").append('<div id=\'feeOuter' + feeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');" ;
														$switchContents.="$(\"#feeOuter\" + feeCount).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Finance/invoices_manage_add_blockFeeAjax.php\",\"mode=add&id=\" + feeCount + \"&feeType=Standard&gibbonFinanceFeeID=" .  urlencode($rowSelect["gibbonFinanceFeeID"]) . "&name=" . urlencode($rowSelect["name"]) . "&description=" . urlencode($rowSelect["description"]) . "&gibbonFinanceFeeCategoryID=" . urlencode($rowSelect["gibbonFinanceFeeCategoryID"]) . "&fee=" . urlencode($rowSelect["fee"]) . "&category=" . urlencode($rowSelect["category"]) . "\") ;" ;
														$switchContents.="feeCount++ ;" ;
														$switchContents.="$('#newFee').val('0');" ;
														$switchContents.="break;" ;
														$lastCategory=$rowSelect["category"] ;
													}
												}
												?>
											</select>
											<script type='text/javascript'>
												function feeDisplayElements(number) {
													$("#<?php print $type ?>Outer0").css("display", "none") ;
													switch(number) {
														<?php print $switchContents ?>
													}
												}
											</script>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</td>
				</tr>
				
				<tr>
					<td>
						<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
					</td>
					<td class="right">
						<input name="gibbonFinanceInvoiceID" id="gibbonFinanceInvoiceID" value="<?php print $gibbonFinanceInvoiceID ?>" type="hidden">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<input type="submit" value="<?php print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
	}
}
?>