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

if (isActionAccessible($guid, $connection2, "/modules/Finance/fees_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/fees_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>Manage Fees</a> > </div><div class='trailEnd'>Add Fee</div>" ;
	print "</div>" ;
	
	$addReturn = $_GET["addReturn"] ;
	$addReturnMessage ="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage ="Add failed because you do not have access to this action." ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage ="Add failed due to a database error." ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage ="Add failed because your inputs were invalid." ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage ="Add failed because some values need to be unique but were not." ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage ="Add failed because the passwords did not match." ;	
		}
		else if ($addReturn=="fail6") {
			$addReturnMessage ="Add failed because the student is already registered in the specified year." ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage ="Add was successful. You can add another record if you wish." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$search=$_GET["search"] ;
	if ($gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print "You have not specified a school year." ;
		print "</div>" ;
	}
	else {
		if ($search!="") {
			print "<div class='linkTop'>" ;
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/fees_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>Back to Search Results</a>" ;
			print "</div>" ;
		}
		?>
		<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/fees_manage_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ?>">
			<table style="width: 100%">	
				<tr><td style="width: 30%"></td><td></td></tr>
				<tr>
					<td> 
						<b>School Year *</b><br/>
						<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
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
							var yearName = new LiveValidation('yearName');
							yearName.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Name *</b><br/>
					</td>
					<td class="right">
						<input name="name" id="name" maxlength=100 value="" type="text" style="width: 300px">
						<script type="text/javascript">
							var name = new LiveValidation('name');
							name.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Name Short *</b><br/>
					</td>
					<td class="right">
						<input name="nameShort" id="nameShort" maxlength=6 value="" type="text" style="width: 300px">
						<script type="text/javascript">
							var nameShort = new LiveValidation('nameShort');
							nameShort.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Active *</b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<select name="active" id="active" style="width: 302px">
							<option value="Y">Y</option>
							<option value="N">N</option>
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Description</b><br/>
					</td>
					<td class="right">
						<textarea name='description' id='description' rows=5 style='width: 300px'></textarea>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Category *</b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<select name="gibbonFinanceFeeCategoryID" id="gibbonFinanceFeeCategoryID" style="width: 302px">
							<?
							print "<option value='Please select...'>Please select...</option>" ;
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonFinanceFeeCategoryID"] . "'>" . $rowSelect["name"] . "</option>" ;
							}
							print "<option value='1'>Other</option>" ;
							?>				
						</select>
						<script type="text/javascript">
							var gibbonFinanceFeeCategoryID = new LiveValidation('gibbonFinanceFeeCategoryID');
							gibbonFinanceFeeCategoryID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
						 </script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Fee *</b><br/>
						<span style="font-size: 90%">
							<i>Numeric value of the fee
							<?
							if ($_SESSION[$guid]["currency"]!="") {
								print " in " . $_SESSION[$guid]["currency"] ."." ;
							}
							else {
								print "." ;
							}
							?>
							</i>
						</span>
					</td>
					<td class="right">
						<input name="fee" id="fee" maxlength=13 value="" type="text" style="width: 300px">
						<script type="text/javascript">
							var fee = new LiveValidation('fee');
							fee.add(Validate.Presence);
							fee.add( Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
						</script>
					</td>
				</tr>
				
				<tr>
					<td class="right" colspan=2>
						<input name="gibbonFinanceFeeID" id="gibbonFinanceFeeID" value="<? print $gibbonFinanceFeeID ?>" type="hidden">
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
?>