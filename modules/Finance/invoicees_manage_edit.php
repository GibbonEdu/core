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


if (isActionAccessible($guid, $connection2, "/modules/Finance/invoicees_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/invoicees_manage.php'>Manage Invoicees</a> > </div><div class='trailEnd'>Edit Invoicee</div>" ;
	print "</div>" ;
	
	$updateReturn = $_GET["updateReturn"] ;
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage ="Update failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage ="Update failed because a required parameter was not set." ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage ="Update failed due to a database error." ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage ="Update failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage ="Update failed some values need to be unique but were not." ;	
		}
		else if ($updateReturn=="fail5") {
			$updateReturnMessage ="Update failed because your attachment could not be uploaded." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Update was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	if ($_GET["search"]!="" OR $_GET["allUsers"]=="on") {
		print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoicees_manage.php&search=" . $_GET["search"] . "&allUsers=" . $_GET["allUsers"] . "'>Back to Search Results</a>" ;
		print "</div>" ;
	}
	
	//Check if school year specified
	$gibbonFinanceInvoiceeID=$_GET["gibbonFinanceInvoiceeID"];
	if ($gibbonFinanceInvoiceeID=="") {
		print "<div class='error'>" ;
			print "You have not specified a category." ;
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
				print "The selected outcome does not exist." ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			print "<table style='width: 100%'>" ;
				print "<tr>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Name</span><br/>" ;
						print formatName("", $row["preferredName"], $row["surname"], "Student") ;
					print "</td>" ;
					print "<td style='width: 33%; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Status</span><br/>" ;
						print "<i>" . $row["status"] . "</i>" ;
					print "</td>" ;
					print "<td style='width: 34%; vertical-align: top'>" ;
						
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/invoicees_manage_editProcess.php?gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&search=" .$_GET["search"] . "&allUsers=" . $_GET["allUsers"] ; ?>">
				<table style="width: 100%">	
					<tr><td style="width: 30%"></td><td></td></tr>
					<script type="text/javascript">
						/* Resource 1 Option Control */
						$(document).ready(function(){
							if ($('input[name=invoiceTo]:checked').val() == "Family" ) {
								$("#companyNameRow").css("display","none");
								$("#companyContactRow").css("display","none");
								$("#companyAddressRow").css("display","none");
								$("#companyEmailRow").css("display","none");
								$("#companyPhoneRow").css("display","none");
								$("#companyAllRow").css("display","none");
								$("#companyCategoriesRow").css("display","none");
							}
							else {
								if ($('input[name=companyAll]:checked').val() == "Y" ) {
									$("#companyCategoriesRow").css("display","none");
								}
							}
							
							$(".invoiceTo").click(function(){
								if ($('input[name=invoiceTo]:checked').val() == "Family" ) {
									$("#companyNameRow").css("display","none");
									$("#companyContactRow").css("display","none");
									$("#companyAddressRow").css("display","none");
									$("#companyEmailRow").css("display","none");
									$("#companyPhoneRow").css("display","none");
									$("#companyAllRow").css("display","none");
									$("#companyCategoriesRow").css("display","none");
								} else {
									$("#companyNameRow").slideDown("fast", $("#companyNameRow").css("display","table-row")); 
									$("#companyContactRow").slideDown("fast", $("#companyContactRow").css("display","table-row")); 
									$("#companyAddressRow").slideDown("fast", $("#companyAddressRow").css("display","table-row")); 
									$("#companyEmailRow").slideDown("fast", $("#companyEmailRow").css("display","table-row")); 
									$("#companyPhoneRow").slideDown("fast", $("#companyPhoneRow").css("display","table-row")); 
									$("#companyAllRow").slideDown("fast", $("#companyAllRow").css("display","table-row")); 
									if ($('input[name=companyAll]:checked').val() == "Y" ) {
										$("#companyCategoriesRow").css("display","none");
									} else {
										$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row")); 
									}
								}
							 });
							 
							 $(".companyAll").click(function(){
								if ($('input[name=companyAll]:checked').val() == "Y" ) {
									$("#companyCategoriesRow").css("display","none");
								} else {
									$("#companyCategoriesRow").slideDown("fast", $("#companyCategoriesRow").css("display","table-row")); 
								}
							 });
						});
					</script>
					<tr id="familyRow">
						<td colspan=2'>
							<p>If you choose family, future invoices will be sent according to family contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.</p>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Send Invoices To</b><br/>
						</td>
						<td class="right">
							<input <? if ($row["invoiceTo"]=="Family") { print "checked" ; } ?> type="radio" name="invoiceTo" value="Family" class="invoiceTo" /> Family
							<input <? if ($row["invoiceTo"]=="Company") { print "checked" ; } ?> type="radio" name="invoiceTo" value="Company" class="invoiceTo" /> Company
						</td>
					</tr>
					<tr id="companyNameRow">
						<td> 
							<b>Company Name</b><br/>
						</td>
						<td class="right">
							<input name="companyName" id="companyName" maxlength=100 value="<? print $row["companyName"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr id="companyContactRow">
						<td> 
							<b>Company Contact Person</b><br/>
						</td>
						<td class="right">
							<input name="companyContact" id="companyContact" maxlength=100 value="<? print $row["companyContact"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr id="companyAddressRow">
						<td> 
							<b>Company Address</b><br/>
						</td>
						<td class="right">
							<input name="companyAddress" id="companyAddress" maxlength=255 value="<? print $row["companyAddress"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr id="companyEmailRow">
						<td> 
							<b>Company Email</b><br/>
						</td>
						<td class="right">
							<input name="companyEmail" id="companyEmail" maxlength=255 value="<? print $row["companyEmail"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var companyEmail = new LiveValidation('companyEmail');
								companyEmail.add(Validate.Email);
							 </script>
						</td>
					</tr>
					<tr id="companyPhoneRow">
						<td> 
							<b>Company Phone</b><br/>
						</td>
						<td class="right">
							<input name="companyPhone" id="companyPhone" maxlength=20 value="<? print $row["companyPhone"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<?
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
								<b>Company All?</b><br/>
								<span style="font-size: 90%"><i>Should all items be billed to the specified company, or just some?</i></span>
							</td>
							<td class="right">
								<input type="radio" name="companyAll" value="Y" class="companyAll" <? if ($row["companyAll"]=="Y" OR $row["companyAll"]=="") { print "checked" ; } ?> /> All
								<input type="radio" name="companyAll" value="N" class="companyAll" <? if ($row["companyAll"]=="N") { print "checked" ; } ?> /> Selected
							</td>
						</tr>
						<tr id="companyCategoriesRow">
							<td> 
								<b>Company Fee Categories</b><br/>
								<span style="font-size: 90%"><i>If the specified company is not paying all fees, which categories are they paying?</i></span>
							</td>
							<td class="right">
								<?
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
						<?
					}
					?>
					
					<tr>
						<td class="right" colspan=2>
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
?>