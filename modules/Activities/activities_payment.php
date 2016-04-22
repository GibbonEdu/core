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

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_payment.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Create Invoices') . "</div>" ;
	print "</div>" ;

	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	print "<h2>" ;
		print __($guid, "Invoices Not Yet Generated") ;
	print "</h2>" ;
	print "<p>" ;
		print sprintf(__($guid, 'The list below shows students who have been accepted for an activity in the current year, who have yet to have invoices generated for them. You can generate invoices to a given %1$sBilling Schedule%2$s, or you can simulate generation (e.g. mark them as generated, but not actually produce an invoice).'), "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/billingSchedule_manage.php'>", "</a>") ;
	print "</p>" ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonSchoolYearID2"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sql="SELECT gibbonActivityStudentID, gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, gibbonActivityStudent.status, payment, gibbonActivity.name, programStart, programEnd FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonActivityStudent.status='Accepted' AND payment>0 AND invoiceGenerated='N' ORDER BY surname, preferredName, name" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
			print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		$lastPerson="" ;
		
		print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_paymentProcessBulk.php'>" ;
			print "<fieldset style='border: none'>" ;
				print "<div class='linkTop' style='height: 27px'>" ;
					?>
					<input style='margin-top: 0px; float: right' type='submit' value='<?php print __($guid, 'Go') ?>'>
					<select name="action" id="action" style='width:120px; float: right; margin-right: 1px;'>
						<option value="Select action"><?php print __($guid, 'Select action') ?></option>
						<?php
						try {
							$dataSchedule=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlSchedule="SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
							$resultSchedule=$connection2->prepare($sqlSchedule);
							$resultSchedule->execute($dataSchedule);
						}
						catch(PDOException $e) { }
						while ($rowSchedule=$resultSchedule->fetch()) {
							print "<option value='" . $rowSchedule["gibbonFinanceBillingScheduleID"] . "'>" . sprintf(__($guid, 'Generate Invoices To %1$s'), $rowSchedule["name"]) . "</option>" ;
						
						}
						?>
						<option value="Generate Invoice - Simulate"><?php print __($guid, 'Generate Invoice - Simulate') ?></option>
					</select>
					<script type="text/javascript">
						var action=new LiveValidation('action');
						action.add(Validate.Exclusion, { within: ['Select action'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>
					<?php
				print "</div>" ;
				
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print __($guid, "Roll Group") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Student") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Activity") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Cost") . "<br/>" ;
							print "<span style='font-style: italic; font-size: 85%'>" . $_SESSION[$guid]["currency"] . "</span>" ;
						print "</th>" ;
						print "<th>" ;
							?>
							<script type="text/javascript">
								$(function () {
									$('.checkall').click(function () {
										$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
									});
								});
							</script>
							<?php
							print "<input type='checkbox' class='checkall'>" ;
						print "</th>" ;
					print "</tr>" ;
			
					$count=0;
					$rowNum="odd" ;
					while ($row=$result->fetch()) {
						if ($count%2==0) {
							$rowNum="even" ;
						}
						else {
							$rowNum="odd" ;
						}
						$count++ ;
				
						//COLOR ROW BY STATUS!
						print "<tr class=$rowNum>" ;
							print "<td>" ;
								print $row["rollGroup"] ;
							print "</td>" ;
							print "<td>" ;
								print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
							print "</td>" ;
							print "<td>" ;
								print $row["name"] ;
							print "</td>" ;
							print "<td style='text-align: left'>" ;
								if (substr($_SESSION[$guid]["currency"],4)!="") {
									print substr($_SESSION[$guid]["currency"],4) ;
								}
								print number_format($row["payment"]) ;
							print "</td>" ;
							print "<td>" ;
								print "<input type='checkbox' name='gibbonActivityStudentID-$count' id='gibbonActivityStudentID-$count' value='" . $row["gibbonActivityStudentID"] . "'>" ;
							print "</td>" ;
						print "</tr>" ;
				
						$lastPerson=$row["gibbonPersonID"] ;
					}
					if ($count==0) {
						print "<tr class=$rowNum>" ;
							print "<td colspan=4>" ;
								print __($guid, "There are no records to display.") ;
							print "</td>" ;
						print "</tr>" ;
					}
				print "</table>" ;
					
				print "<input name='count' value='$count' type='hidden'>" ;
				print "<input name='address' value='" . $_GET["q"] . "' type='hidden'>" ;	
			print "</fieldset>" ;
		print "</form>" ;
	}
	
	print "<h2>" ;
		print __($guid, "Invoices Generated") ;
	print "</h2>" ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonSchoolYearID2"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sql="SELECT gibbonPerson.gibbonPersonID, studentID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, gibbonActivityStudent.status, payment, gibbonActivity.name, programStart, programEnd, gibbonFinanceInvoiceID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonActivityStudent.status='Accepted' AND payment>0 AND invoiceGenerated='Y' ORDER BY surname, preferredName, name" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
			print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		$lastPerson="" ;
		
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Roll Group") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Student") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Activity") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Cost") . "<br/>" ;
					print "<span style='font-style: italic; font-size: 85%'>" . $_SESSION[$guid]["currency"] . "</span>" ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Invoice Number") . "<br/>" ;
				print "</th>" ;
			print "</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			while ($row=$result->fetch()) {
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				$count++ ;
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print $row["rollGroup"] ;
					print "</td>" ;
					print "<td>" ;
						print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
					print "</td>" ;
					print "<td>" ;
						print $row["name"] ;
					print "</td>" ;
					print "<td style='text-align: left'>" ;
						if (substr($_SESSION[$guid]["currency"],4)!="") {
							print substr($_SESSION[$guid]["currency"],4) ;
						}
						print number_format($row["payment"]) ;
					print "</td>" ;
					print "<td>" ;
						$invoiceNumber=getSettingByScope( $connection2, "Finance", "invoiceNumber" ) ;
						if ($invoiceNumber=="Person ID + Invoice ID") {
							print ltrim($row["gibbonPersonID"],"0") . "-" . ltrim($row["gibbonFinanceInvoiceID"], "0") ;
						}
						else if ($invoiceNumber=="Student ID + Invoice ID") {
							print ltrim($row["studentID"],"0") . "-" . ltrim($row["gibbonFinanceInvoiceID"], "0") ;
						}
						else {
							print ltrim($row["gibbonFinanceInvoiceID"], "0") ;
						}
					print "</td>" ;
				print "</tr>" ;
				
				$lastPerson=$row["gibbonPersonID"] ;
			}
			if ($count==0) {
				print "<tr class=$rowNum>" ;
					print "<td colspan=4>" ;
						print __($guid, "There are no records to display.") ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>