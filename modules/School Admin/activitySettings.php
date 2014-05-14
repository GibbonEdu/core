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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/activitySettings.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('Manage Activity Settings') . "</div>" ;
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
			$updateReturnMessage=_("One or more of the fields in your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
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
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activitySettingsProcess.php" ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='dateType'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td style='width: 275px'> 
					<b><?php print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="Date") {print "selected ";} ?>value="Date"><?php print _('Date') ?></option>
						<option <?php if ($row["value"]=="Term") {print "selected ";} ?>value="Term"><?php print _('Term') ?></option>
					</select>
				</td>
			</tr>
			<script type="text/javascript">
				$(document).ready(function(){
					<?php if ($row["value"]=="Date") { ?> 
						$("#maxPerTermRow").css("display","none");
					<?php } ?>
							
					$("#dateType").change(function(){
						if ($('#dateType option:selected').val()=="Term" ) {
							$("#maxPerTermRow").slideDown("fast", $("#maxPerTermRow").css("display","table-row")); 
						}
						else {
							$("#maxPerTermRow").css("display","none");
						}
					 });
				});
			</script>
			<tr id='maxPerTermRow'>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='maxPerTerm'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="0") {print "selected ";} ?>value="0"><?php print _('0') ?></option>
						<option <?php if ($row["value"]=="1") {print "selected ";} ?>value="1"><?php print _('1') ?></option>
						<option <?php if ($row["value"]=="2") {print "selected ";} ?>value="2"><?php print _('2') ?></option>
						<option <?php if ($row["value"]=="3") {print "selected ";} ?>value="3"><?php print _('3') ?></option>
						<option <?php if ($row["value"]=="4") {print "selected ";} ?>value="4"><?php print _('4') ?></option>
						<option <?php if ($row["value"]=="5") {print "selected ";} ?>value="5"><?php print _('5') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='access'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="None") {print "selected ";} ?>value="None"><?php print _('None') ?></option>
						<option <?php if ($row["value"]=="View") {print "selected ";} ?>value="View"><?php print _('View') ?></option>
						<option <?php if ($row["value"]=="Register") {print "selected ";} ?>value="Register"><?php print _('Register') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='payment'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="None") {print "selected ";} ?>value="None"><?php print _('None') ?></option>
						<option <?php if ($row["value"]=="Single") {print "selected ";} ?>value="Single"><?php print _('Single') ?></option>
						<option <?php if ($row["value"]=="Per Activity") {print "selected ";} ?>value="Per Activity"><?php print _('Per Activity') ?></option>
						<option <?php if ($row["value"]=="Single + Per Activity") {print "selected ";} ?>value="Single + Per Activity"><?php print _('Single + Per Activity') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='enrolmentType'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="Competitive") {print "selected ";} ?>value="Competitive"><?php print _('Competitive') ?></option>
						<option <?php if ($row["value"]=="Selection") {print "selected ";} ?>value="Selection"><?php print _('Selection') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='backupChoice'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print _('No') ?></option>
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print _('Yes') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='activityTypes'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><?php print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<textarea name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" rows=4 type="text" style="width: 300px"><?php print $row["value"] ?></textarea>
				</td>
			</tr>
			
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='disableExternalProviderSignup'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print _('No') ?></option>
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print _('Yes') ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Activities' AND name='hideExternalProviderCost'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><?php print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print _('No') ?></option>
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print _('Yes') ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td>
					<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
<?php
}
?>