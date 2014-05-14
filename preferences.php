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

print "<div class='trail'>" ;
print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > </div><div class='trailEnd'>Preferences</div>" ;
print "</div>" ;
	
if (isset($_GET["forceReset"])) {
	$forceReset=$_GET["forceReset"] ;
}
else {
	$forceReset=NULL ;
}

if (isset($_GET["forceResetReturn"])) {
	$forceResetReturn=$_GET["forceResetReturn"] ;
}
else {
	$forceResetReturn="" ;
}
$forceResetReturnMessage="" ;
$class="error" ;
if ($forceResetReturn!="" OR $forceReset=="Y") {
	if ($forceReset=="Y") {
		$forceResetReturnMessage="<b><u>Your account has been flagged for a password reset. You cannot continue into the system until you change your password.</b></u>";
	}
	if ($forceResetReturn=="fail0") {
		$forceResetReturnMessage="<b><u>Your account status could not be updated, and so you cannot continue to use the system. Please contact <a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a> if you have any questions.</b></u>";
	}
	if ($forceResetReturn=="success0") {
		$forceResetReturnMessage="<b><u>Your account has been successfully updated. You can now continue to use the system as per normal.</b></u>";
		$class="success" ;
	}
	print "<div class='$class'>" ;
		print $forceResetReturnMessage ;
	print "</div>" ;
}


if (isset($_GET["editReturn"])) {
	$editReturn=$_GET["editReturn"] ;
}
else {
	$editReturn="" ;
}
$editReturnMessage="" ;
$class="error" ;
if (!($editReturn=="")) {
	if ($editReturn=="fail0") {
		$editReturnMessage="Required fields not set." ;	
	}
	else if ($editReturn=="fail1") {
		$editReturnMessage=_("Your request failed due to a database error.") ;	
	}
	else if ($editReturn=="fail2") {
		$editReturnMessage="Your request failed due to non-matching passwords." ;	
	}
	else if ($editReturn=="fail3") {
		$editReturnMessage="Your request failed due to incorrect current password." ;	
	}
	else if ($editReturn=="fail6") {
		$editReturnMessage="Your request failed because your password to not meet the minimum requirements for strength." ;	
	}
	else if ($editReturn=="fail7") {
		$editReturnMessage="Your request failed because your new password is the same as your current password." ;	
	}	
	else if ($editReturn=="success0") {
		$editReturnMessage=_("Your request was completed successfully.") ;	
		$class="success" ;
	}
	print "<div class='$class'>" ;
		print $editReturnMessage;
	print "</div>" ;
} 

try {
	$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
	$sql="SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
	$result=$connection2->prepare($sql);
	$result->execute($data);
}
catch(PDOException $e) { 
	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
}
if ($result->rowCount()==1) {
	$row=$result->fetch() ;
}
?>

<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] ?>/preferencesPasswordProcess.php">
	<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
		<tr class='break'>
			<td colspan=2>
				<h3>
					<?php print _("Reset Password") ; ?>
				</h3>
			</td>
		</tr>
		<tr>
			<td colspan=2>
				<?php
				$policy=getPasswordPolicy($connection2) ;
				if ($policy!=FALSE) {
					print "<div class='warning'>" ;
						print $policy ;
					print "</div>" ;
				}
				?>
			</td>
		</tr>
		<tr>
			<td> 
				<b><?php print _("Current Password") ; ?> *</b><br/>
				<span style="font-size: 90%"><i></i></span>
			</td>
			<td class="right">
				<input name="password" id="password" maxlength=20 value="" type="password" style="width: 300px">
				<script type="text/javascript">
					var password=new LiveValidation('password');
					password.add(Validate.Presence);
				 </script>
			</td>
		</tr>
		<tr>
			<td style='width: 275px'> 
				<b><?php print _("New Password") ; ?> *</b><br/>
				<span style="font-size: 90%"><i></i></span>
			</td>
			<td class="right">
				<input name="passwordNew" id="passwordNew" maxlength=20 value="" type="password" style="width: 300px">
				<script type="text/javascript">
					var passwordNew=new LiveValidation('passwordNew');
					passwordNew.add(Validate.Presence);
					<?php
					$alpha=getSettingByScope( $connection2, "System", "passwordPolicyAlpha" ) ;
					if ($alpha=="Y") {
						print "passwordNew.add( Validate.Format, { pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: \"Does not meet password policy.\" } );" ;
					}
					$numeric=getSettingByScope( $connection2, "System", "passwordPolicyNumeric" ) ;
					if ($numeric=="Y") {
						print "passwordNew.add( Validate.Format, { pattern: /.*[0-9]/, failureMessage: \"Does not meet password policy.\" } );" ;
					}
					$punctuation=getSettingByScope( $connection2, "System", "passwordPolicyNonAlphaNumeric" ) ;
					if ($punctuation=="Y") {
						print "passwordNew.add( Validate.Format, { pattern: /[^a-zA-Z0-9]/, failureMessage: \"Does not meet password policy.\" } );" ;
					}
					$minLength=getSettingByScope( $connection2, "System", "passwordPolicyMinLength" ) ;
					if (is_numeric($minLength)) {
						print "passwordNew.add( Validate.Length, { minimum: " . $minLength . "} );" ;
					}
					?>
				 </script>
			</td>
		</tr>
		<tr>
			<td> 
				<b><?php print _("Confirm New Password") ; ?> *</b><br/>
				<span style="font-size: 90%"><i></i></span>
			</td>
			<td class="right">
				<input name="passwordConfirm" id="passwordConfirm" maxlength=20 value="" type="password" style="width: 300px">
				<script type="text/javascript">
					var passwordConfirm=new LiveValidation('passwordConfirm');
					passwordConfirm.add(Validate.Presence);
					passwordConfirm.add(Validate.Confirmation, { match: 'passwordNew' } );
				 </script>
			</td>
		</tr>
		<tr>
			<td>
				<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
			</td>
			<td class="right">
				<?php
				if ($forceReset=="Y") {
					print "<input type='hidden' name='forceReset' value='$forceReset'>" ;
				}
				?>
				<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
				<input type="submit" value="<?php print _("Submit") ; ?>">
			</td>
		</tr>
	</table>
</form>
	
	
<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] ?>/preferencesProcess.php">
	<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
		<tr class='break'>
			<td colspan=2>
				<h3>
					<?php print _("Settings") ; ?>
				</h3>
			</td>
		</tr>
		<tr>
			<td> 
				<b><?php print _("Personal Calendar Feed") ; ?></b><br/>
				<span style="font-size: 90%"><i><?php print _("XML feed for the your calendar (Google Calendar only)") ; ?></i></span>
			</td>
			<td class="right">
				<input name="calendarFeedPersonal" id="calendarFeedPersonal" value="<?php print $row["calendarFeedPersonal"] ?>" type="text" style="width: 300px">
				<script type="text/javascript">
					var calendarFeedPersonal=new LiveValidation('calendarFeedPersonal');
					calendarFeedPersonal.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http://" } );
				</script>	
			</td>
		</tr>
		
		<?php
		$personalBackground=getSettingByScope($connection2, "User Admin", "personalBackground") ;
		if ($personalBackground=="Y") {
			?>
			<tr>
				<td> 
					<b><?php print _("Personal Background") ; ?></b><br/>
					<span style="font-size: 90%"><i><?php print _("Set your own custom background image.") . "<br/>" . _("Please provide URL to image.") ; ?></i></span>
				</td>
				<td class="right">
					<input name="personalBackground" id="personalBackground" value="<?php print $row["personalBackground"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var personalBackground=new LiveValidation('personalBackground');
						personalBackground.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http://" } );
					</script>	
				</td>
			</tr>
			<?php
		}
		?>
		
		<tr>
			<td> 
				<b><?php print _("Personal Theme") ; ?></b><br/>
				<span style="font-size: 90%"><i><?php print _("Override the system theme.") ; ?></i></span>
			</td>
			<td class="right">
				<select name="gibbonThemeIDPersonal" id="gibbonThemeIDPersonal" style="width: 302px">
					<?php
					print "<option value=''></option>" ;
					try {
						$dataSelect=array(); 
						$sqlSelect="SELECT * FROM gibbonTheme ORDER BY name" ;
						$resultSelect=$connection2->prepare($sqlSelect);
						$resultSelect->execute($dataSelect);
					}
					catch(PDOException $e) { }
					while ($rowSelect=$resultSelect->fetch()) {
						$selected="" ;
						if ($_SESSION[$guid]["gibbonThemeIDPersonal"]==$rowSelect["gibbonThemeID"]) {
							$selected="selected" ;
						}
						$default="" ;
						if ($rowSelect["active"]=="Y") {
							$default=" (System Default)" ;
						}
						print "<option $selected value='" . $rowSelect["gibbonThemeID"] . "'>" . $rowSelect["name"] . " $default</option>" ;
					}
					?>				
				</select>
			</td>
		</tr>
		
		<tr>
			<td> 
				<b><?php print _("Personal Language") ; ?></b><br/>
				<span style="font-size: 90%"><i><?php print _("Override the system default language.") ; ?></i></span>
			</td>
			<td class="right">
				<select name="gibboni18nIDPersonal" id="gibboni18nIDPersonal" style="width: 302px">
					<?php
					print "<option value=''></option>" ;
					try {
						$dataSelect=array(); 
						$sqlSelect="SELECT * FROM gibboni18n WHERE active='Y' ORDER BY name" ;
						$resultSelect=$connection2->prepare($sqlSelect);
						$resultSelect->execute($dataSelect);
					}
					catch(PDOException $e) { }
					while ($rowSelect=$resultSelect->fetch()) {
						$selected="" ;
						if ($_SESSION[$guid]["gibboni18nIDPersonal"]==$rowSelect["gibboni18nID"]) {
							$selected="selected" ;
						}
						$default="" ;
						if ($rowSelect["systemDefault"]=="Y") {
							$default=" (System Default)" ;
						}
						print "<option $selected value='" . $rowSelect["gibboni18nID"] . "'>" . $rowSelect["name"] . " $default</option>" ;
					}
					?>				
				</select>
			</td>
		</tr>
		
		
		<tr>
			<td>
				<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
			</td>
			<td class='right'>
				<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
				<input type="submit" value="<?php print _("Submit") ; ?>">
			</td>
		</tr>
	</table>
</form>