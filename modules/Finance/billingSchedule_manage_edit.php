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

if (isActionAccessible($guid, $connection2, "/modules/Finance/billingSchedule_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/billingSchedule_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>Manage Billing Schedule</a> > </div><div class='trailEnd'>Edit Entry</div>" ;
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
		else if ($updateReturn=="fail4") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
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
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$gibbonFinanceBillingScheduleID=$_GET["gibbonFinanceBillingScheduleID"] ;
	$search=$_GET["search"] ;
	if ($gibbonFinanceBillingScheduleID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceBillingScheduleID"=>$gibbonFinanceBillingScheduleID); 
			$sql="SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID" ; 
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
			//Let's go!
			$row=$result->fetch() ;
			
			if ($search!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/billingSchedule_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>" . _('Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/billingSchedule_manage_editProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b><? print _('School Year') ?> *</b><br/>
							<span style="font-size: 90%"><i><? print _('This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<?
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
							<input readonly name="yearName" id="yearName" maxlength=20 value="<? print $yearName ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var yearName=new LiveValidation('yearName');
								yearName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<? print "<b>" . _('Name') . " *</b><br/>" ; ?>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=100 value="<? print htmlPrep($row["name"])?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name=new LiveValidation('name');
								name.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><? print _('Active') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="active" id="active" style="width: 302px">
								<option <? if ($row["active"]=="Y") { print "selected" ; } ?> value="Y"><? print _('Yes') ?></option>
								<option <? if ($row["active"]=="N") { print "selected" ; } ?> value="N"><? print _('No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><? print _('Description') ?></b><br/>
						</td>
						<td class="right">
							<textarea name='description' id='description' rows=5 style='width: 300px'><? print htmlPrep($row["description"])?></textarea>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Invoice Issue Date *</b><br/>
							<span style="font-size: 90%"><i>Intended date. dd/mm/yyyy</i></span>
						</td>
						<td class="right">
							<input name="invoiceIssueDate" id="invoiceIssueDate" maxlength=10 value="<? print dateConvertBack($guid, $row["invoiceIssueDate"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var invoiceIssueDate=new LiveValidation('invoiceIssueDate');
								invoiceIssueDate.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
								invoiceIssueDate.add(Validate.Presence);
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#invoiceIssueDate" ).datepicker();
								});
							</script>
						</td>
					</tr>
				
					<tr>
						<td> 
							<b>Invoice Due Date *</b><br/>
							<span style="font-size: 90%"><i>Final Payment Date. dd/mm/yyyy</i></span>
						</td>
						<td class="right">
							<input name="invoiceDueDate" id="invoiceDueDate" maxlength=10 value="<? print dateConvertBack($guid, $row["invoiceDueDate"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var invoiceDueDate=new LiveValidation('invoiceDueDate');
								invoiceDueDate.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
						<td>
							<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input name="gibbonFinanceBillingScheduleID" id="gibbonFinanceBillingScheduleID" value="<? print $gibbonFinanceBillingScheduleID ?>" type="hidden">
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