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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/family_manage_edit_editAdult.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/family_manage.php'>" . _('Manage Families') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID=" . $_GET["gibbonFamilyID"] . "'>" . _('Edit Family') . "</a> > </div><div class='trailEnd'>" . _('Edit Adult') . "</div>" ;
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
	$gibbonFamilyID=$_GET["gibbonFamilyID"] ;
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	$search=$_GET["search"] ;
	if ($gibbonPersonID=="" OR $gibbonFamilyID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonID"=>$gibbonPersonID); 
			$sql="SELECT * FROM gibbonPerson, gibbonFamily, gibbonFamilyAdult WHERE gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID AND gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')" ;
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
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID=$gibbonFamilyID&search=$search'>" . _('Back') . "</a>" ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/family_manage_edit_editAdultProcess.php?gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=$gibbonPersonID&search=$search" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b><? print _('Adult\'s Name') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input readonly name="child" id="child" maxlength=200 value="<? print formatName(htmlPrep($row["title"]), htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Parent") ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><? print _('Comment') ?></b><br/>
							<span style="font-size: 90%"><i><? print _('Data displayed in full Student Profile') ?><br/></i></span>
						</td>
						<td class="right">
							<textarea name="comment" id="comment" rows=8 style="width: 300px"><? print $row["comment"] ?></textarea>
						</td>
					</tr>
					<tr>
						<td> 
							<b><? print _('Data Access?') ?></b><br/>
							<span style="font-size: 90%"><i><? print _('Access data on family\'s children?') ?></i></span>
						</td>
						<td class="right">
							<select name="childDataAccess" id="childDataAccess" style="width: 302px">
								<option <? if ($row["childDataAccess"]=="Y") { print "selected ";} ?>value="Y"><? print _('Y') ?></option>
								<option <? if ($row["childDataAccess"]=="N") { print "selected ";} ?>value="N"><? print _('N') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><? print _('Contact Priority') ?></b><br/>
							<span style="font-size: 90%"><i><? print _('The order in which school should contact family members.') ?></i></span>
						</td>
						<td class="right">
							<select name="contactPriority" id="contactPriority" style="width: 302px">
								<option <? if ($row["contactPriority"]=="1") { print "selected ";} ?>value="1"><? print _('1') ?></option>
								<option <? if ($row["contactPriority"]=="2") { print "selected ";} ?>value="2"><? print _('2') ?></option>
								<option <? if ($row["contactPriority"]=="3") { print "selected ";} ?>value="3"><? print _('3') ?></option>
							</select>
							<script type="text/javascript">
								/* Advanced Options Control */
								$(document).ready(function(){
									<? 
									if ($row["contactPriority"]=="1") {
										print "$(\"#contactCall\").attr(\"disabled\", \"disabled\");" ;
										print "$(\"#contactSMS\").attr(\"disabled\", \"disabled\");" ;
										print "$(\"#contactEmail\").attr(\"disabled\", \"disabled\");" ;
										print "$(\"#contactMail\").attr(\"disabled\", \"disabled\");" ;
									}
									?>	
									$("#contactPriority").change(function(){
										if ($('#contactPriority').val()=="1" ) {
											$("#contactCall").attr("disabled", "disabled");
											$("#contactCall").val("Y");
											$("#contactSMS").attr("disabled", "disabled");
											$("#contactSMS").val("Y");
											$("#contactEmail").attr("disabled", "disabled");
											$("#contactEmail").val("Y");
											$("#contactMail").attr("disabled", "disabled");
											$("#contactMail").val("Y");
										} 
										else {
											$("#contactCall").removeAttr("disabled");
											$("#contactSMS").removeAttr("disabled");
											$("#contactEmail").removeAttr("disabled");
											$("#contactMail").removeAttr("disabled");
										}
									 });
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><? print _('Call?') ?></b><br/>
							<span style="font-size: 90%"><i><? print _('Receive non-emergency phone calls from school?') ?></i></span>
						</td>
						<td class="right">
							<select name="contactCall" id="contactCall" style="width: 302px">
								<option <? if ($row["contactCall"]=="Y") { print "selected ";} ?>value="Y"><? print _('Y') ?></option>
								<option <? if ($row["contactCall"]=="N") { print "selected ";} ?>value="N"><? print _('N') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><? print _('SMS?') ?></b><br/>
							<span style="font-size: 90%"><i><? print _('Receive non-emergency SMS messages from school?') ?></i></span>
						</td>
						<td class="right">
							<select name="contactSMS" id="contactSMS" style="width: 302px">
								<option <? if ($row["contactSMS"]=="Y") { print "selected ";} ?>value="Y"><? print _('Y') ?></option>
								<option <? if ($row["contactSMS"]=="N") { print "selected ";} ?>value="N"><? print _('N') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><? print _('Email?') ?></b><br/>
							<span style="font-size: 90%"><i><? print _('Receive non-emergency emails from school?') ?></i></span>
						</td>
						<td class="right">
							<select name="contactEmail" id="contactEmail" style="width: 302px">
								<option <? if ($row["contactEmail"]=="Y") { print "selected ";} ?>value="Y"><? print _('Y') ?></option>
								<option <? if ($row["contactEmail"]=="N") { print "selected ";} ?>value="N"><? print _('N') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><? print _('Mail?') ?></b><br/>
							<span style="font-size: 90%"><i><? print _('Receive postage mail from school?') ?></i></span>
						</td>
						<td class="right">
							<select name="contactMail" id="contactMail" style="width: 302px">
								<option <? if ($row["contactMail"]=="Y") { print "selected ";} ?>value="Y"><? print _('Y') ?></option>
								<option <? if ($row["contactMail"]=="N") { print "selected ";} ?>value="N"><? print _('N') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
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