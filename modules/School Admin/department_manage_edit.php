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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;


if (isActionAccessible($guid, $connection2, "/modules/School Admin/department_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/department_manage.php'>Manage Departments</a> > </div><div class='trailEnd'>Edit Department</div>" ;
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
		else if ($updateReturn=="fail5") {
			$updateReturnMessage="Your request failed due to an attachment error." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="fail0") {
			$deleteReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($deleteReturn=="fail1") {
			$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($deleteReturn=="fail2") {
			$deleteReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($deleteReturn=="fail3") {
			$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($deleteReturn=="success0") {
			$deleteReturnMessage=_("Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonDepartmentID=$_GET["gibbonDepartmentID"];
	if ($gibbonDepartmentID=="Y") {
		print "<div class='error'>" ;
			print "You have not specified one or more required parameters." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
			$sql="SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The selected learning areas does not exist or you do not have access to it." ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/department_manage_editProcess.php?gibbonDepartmentID=$gibbonDepartmentID&address=" . $_SESSION[$guid]["address"] ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2>
							<h3>General Information</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Type *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i><br/></span>
						</td>
						<td class="right">
							<? $type=$row["type"] ; ?>
							<input readonly name="type" id="type" value="<? print $type ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Name *</b><br/>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=40 value="<? print $row["name"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name=new LiveValidation('name');
								name.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Short Name *</b><br/>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=4 value="<? print $row["nameShort"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Subject Listing</b><br/>
						</td>
						<td class="right">
							<input name="subjectListing" id="subjectListing" maxlength=255 value="<? print $row["subjectListing"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<b>Blurb</b> 
							<? print getEditor($guid,  TRUE, "blurb", $row["blurb"], 20 ) ?>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Logo</b><br/>
							<span style="font-size: 90%"><i>125x125px jpg/png/gif</i><br/></span>
							<? if ($row["logo"]!="") { ?>
							<span style="font-size: 90%"><i>Will overwrite existing attachment</i></span>
							<? } ?>
						</td>
						<td class="right">
							<?
							if ($row["logo"]!="") {
								print "Current attachment: <a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["logo"] . "'>" . $row["logo"] . "</a><br/><br/>" ;
							}
							?>
							<input type="file" name="file" id="file"><br/><br/>
							<?
							print getMaxUpload() ;
							$ext="'.png','.jpeg','.jpg','.gif'" ;
							?>
							
							<script type="text/javascript">
								var file=new LiveValidation('file');
								file.add( Validate.Inclusion, { within: [<? print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2> 
							<h3>Current Staff</h3>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<?
							try {
								$data=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
								$sql="SELECT preferredName, surname, gibbonDepartmentStaff.* FROM gibbonDepartmentStaff JOIN gibbonPerson ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonDepartmentID=:gibbonDepartmentID AND gibbonPerson.status='Full' ORDER BY surname, preferredName" ; 
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}

							if ($result->rowCount()<1) {
								print "<div class='error'>" ;
								print _("There are no records to display.") ;
								print "</div>" ;
							}
							else {
								print "<i><b>Warning</b>: If you delete a guest, any unsaved changes to this planner entry will be lost!</i>" ;
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print "Name" ;
										print "</th>" ;
										print "<th>" ;
											print "Role" ;
										print "</th>" ;
										print "<th>" ;
											print "Action" ;
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
												print formatName("", $row["preferredName"], $row["surname"], "Staff", true, true) ;
											print "</td>" ;
											print "<td>" ;
												print $row["role"] ;
											print "</td>" ;
											print "<td>" ;
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/department_manage_edit_staff_deleteProcess.php?address=" . $_GET["q"] . "&gibbonDepartmentStaffID=" . $row["gibbonDepartmentStaffID"] . "&gibbonDepartmentID=$gibbonDepartmentID'><img title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
											print "</td>" ;
										print "</tr>" ;
									}
								print "</table>" ;
							}
							?>
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2> 
							<h3>New Staff</h3>
						</td>
					</tr>
					<tr>
					<td> 
						<b>Staff</b><br/>
						<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
					</td>
					<td class="right">
						<select name="staff[]" id="staff[]" multiple style="width: 302px; height: 150px">
							<?
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Staff", true, true) . "</option>" ;
							}
							?>
						</select>
						<? print $row["type"] ; ?>
					</td>
					
					<tr id='roleLARow'>
						<td> 
							<b>Role</b><br/>
						</td>
						<td class="right">
							<select name="role" id="role" style="width: 302px">
								<?
								if ($type=="Learning Area") {
									?>
									<option value="Coordinator">Coordinator</option>
									<option value="Assistant Coordinator">Assistant Coordinator</option>
									<option value="Teacher (Curriculum)">Teacher (Curriculum)</option>
									<option value="Teacher">Teacher</option>
									<option value="Other">Other</option>
									<?
								}
								else if ($type=="Administration") {
									?>
									<option value="Director">Director</option>
									<option value="Manager">Manager</option>
									<option value="Administrator">Administrator</option>
									<option value="Other">Other</option>
									<?
								}
								else {
									?>
									<option value="Other">Other</option>
									<?
								}
								?>
							</select>
						</td>
					</tr>
					
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
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