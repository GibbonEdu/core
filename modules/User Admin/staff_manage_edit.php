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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/staff_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/staff_manage.php'>Manage Staff</a> > </div><div class='trailEnd'>Edit Staff</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage ="Your request failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage ="Your request failed due to a database error." ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Your request was completed successfully." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonStaffID=$_GET["gibbonStaffID"] ;
	$search=$_GET["search"] ;
	if ($gibbonStaffID=="") {
		print "<div class='error'>" ;
			print "You have not specified a staff member." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonStaffID"=>$gibbonStaffID); 
			$sql="SELECT gibbonStaff.*, surname, preferredName, initials FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffID=:gibbonStaffID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The specified school year cannot be found." ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			if ($search!="") {
					print "<div class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/staff_manage.php&search=$search'>Back to Search Results</a>" ;
					print "</div>" ;
				}
				?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/staff_manage_editProcess.php?gibbonStaffID=" . $row["gibbonStaffID"] . "&search=$search" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3>Basic Information</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Person *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<input readonly name="person" id="person" maxlength=255 value="<? print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Staff", false, true) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Initials</b><br/>
							<span style="font-size: 90%"><i>Needs to be unique if set.</i></span>
						</td>
						<td class="right">
							<input name="initials" id="initials" maxlength=4 value="<? print $row["initials"] ?>" type="text" style="width: 300px">
							<?
							$idList="" ;
							try {
								$dataSelect=array("initials"=>$row["initials"]); 
								$sqlSelect="SELECT initials FROM gibbonStaff WHERE NOT initials=:initials ORDER BY initials" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								$idList.="'" . $rowSelect["initials"]  . "'," ;
							}
							?>
							<script type="text/javascript">
								var initials=new LiveValidation('initials');
								initials.add( Validate.Exclusion, { within: [<? print $idList ;?>], failureMessage: "Initials already in use!", partialMatch: false, caseSensitive: false } );
							</script>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b>Type *</b><br/>
						</td>
						<td class="right">
							<select name="type" id="type" style="width: 302px">
								<option value="Please select...">Please select...</option>
								<option <? if ($row["type"]=="Teaching") { print "selected " ;} ?>value="Teaching">Teaching</option>
								<option <? if ($row["type"]=="Support") { print "selected " ;}?>value="Support">Support</option>
							</select>
							<script type="text/javascript">
								var type=new LiveValidation('type');
								type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Job Title</b><br/>
						</td>
						<td class="right">
							<input name="jobTitle" id="jobTitle" maxlength=100 value="<? print htmlPrep($row["jobTitle"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3>First Aid</h3>
						</td>
					</tr>
					<!-- FIELDS & CONTROLS FOR TYPE -->
					<script type="text/javascript">
						$(document).ready(function(){
							$("#firstAidQualified").change(function(){
								if ($('select.firstAidQualified option:selected').val() == "Y" ) {
									$("#firstAidExpiryRow").slideDown("fast", $("#firstAidExpiryRow").css("display","table-row")); 
								} else {
									$("#firstAidExpiryRow").css("display","none");
								} 
							 });
						});
					</script>
					<tr>
						<td> 
							<b>First Aid Qualified?</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select style="width: 302px" name="firstAidQualified" id="firstAidQualified" class="firstAidQualified">
								<option <? if ($row["firstAidQualified"]=="") { print "selected" ; } ?> value=""></option>
								<option <? if ($row["firstAidQualified"]=="Y") { print "selected" ; } ?> value="Y">Y</option>
								<option <? if ($row["firstAidQualified"]=="N") { print "selected" ; } ?> value="N">N</option>
							</select>
						</td>
					</tr>
					<tr id='firstAidExpiryRow' <? if ($row["firstAidQualified"]!="Y") { print "style='display: none'" ; } ?>>
						<td> 
							<b>First Aid Expiry</b><br/>
							<span style="font-size: 90%"><i>When is first aid certification set to expire.<br/>dd/mm/yyyy</i></span>
						</td>
						<td class="right">
							<input name="firstAidExpiry" id="firstAidExpiry" maxlength=10 value="<? print dateConvertBack($row["firstAidExpiry"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								$(function() {
									$( "#firstAidExpiry" ).datepicker();
								});
							</script>
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3>Biography</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Country Of Origin</b><br/>
							<span style="font-size: 90%"><i>Where does this staff member come from?</i></span>
						</td>
						<td class="right">
							<select name="countryOfOrigin" id="countryOfOrigin" style="width: 302px">
								<?
								print "<option value=''></option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT printable_name FROM gibbonCountry ORDER BY printable_name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["printable_name"]==$row["countryOfOrigin"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["printable_name"] . "'>" . htmlPrep($rowSelect["printable_name"]) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Qualifications</b><br/>
						</td>
						<td class="right">
							<input name="qualifications" id="qualifications" maxlength=100 value="<? print htmlPrep($row["qualifications"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Grouping</b><br/>
							<span style="font-size: 90%"><i>Used for group staff when creating a staff directory.</i></span>
						</td>
						<td class="right">
							<input name="biographicalGrouping" id="biographicalGrouping" maxlength=100 value="<? print htmlPrep($row["biographicalGrouping"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Grouping Priority</b><br/>
							<span style="font-size: 90%"><i>Higher numbers move teachers up the order within their grouping.</i></span>
						</td>
						<td class="right">
							<input name="biographicalGroupingPriority" id="biographicalGroupingPriority" maxlength=4 value="<? print htmlPrep($row["biographicalGroupingPriority"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var biographicalGroupingPriority=new LiveValidation('biographicalGroupingPriority');
								biographicalGroupingPriority.add(Validate.Numericality);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Biography</b><br/>
						</td>
						<td class="right">
							<textarea name='biography' id='biography' rows=10 style='width: 300px'><? print htmlPrep($row["biography"]) ?></textarea>
						</td>
					</tr>
			
					<tr>
						<td>
							<span style="font-size: 90%"><i>* denotes a required field</i></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="Submit">
						</td>
					</tr>
				</table>
			</form>
			<?
		}
	}
}
?>