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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage_password.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage.php'>" . _('Manage Users') . "</a> > </div><div class='trailEnd'>" . _('Reset User Password') . "</div>" ;
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
			$updateReturnMessage=_("Your request failed because your passwords did not match.") ;	
		}
		else if ($updateReturn=="fail6") {
			$updateReturnMessage=_("Your request failed because your password to not meet the minimum requirements for strength.") ;	
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
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	if ($gibbonPersonID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonPersonID"=>$gibbonPersonID); 
			$sql="SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
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
			if ($_GET["search"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/user_manage.php&search=" . $_GET["search"] . "'>" . _('Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			
			$policy=getPasswordPolicy($connection2) ;
			if ($policy!=FALSE) {
				print "<div class='warning'>" ;
					print $policy ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/user_manage_passwordProcess.php?gibbonPersonID=" . $gibbonPersonID . "&search=" . $_GET["search"] ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('Username') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input readonly name="username" id="username" maxlength=20 value="<?php print htmlPrep($row["username"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var username=new LiveValidation('username');
								username.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Password') ?> *</b><br/>
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
									print "passwordNew.add( Validate.Format, { pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: \"" . _('Does not meet password policy.') . "\" } );" ;
								}
								$numeric=getSettingByScope( $connection2, "System", "passwordPolicyNumeric" ) ;
								if ($numeric=="Y") {
									print "passwordNew.add( Validate.Format, { pattern: /.*[0-9]/, failureMessage: \"" . _('Does not meet password policy.') . "\" } );" ;
								}
								$punctuation=getSettingByScope( $connection2, "System", "passwordPolicyNonAlphaNumeric" ) ;
								if ($punctuation=="Y") {
									print "passwordNew.add( Validate.Format, { pattern: /[^a-zA-Z0-9]/, failureMessage: \"" . _('Does not meet password policy.') . "\" } );" ;
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
							<b><?php print _('Confirm Password') ?> *</b><br/>
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
							<b><?php print _('Force Reset Password?') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('User will be prompted on next login.') ?></i></span>
						</td>
						<td class="right">
							<select style="width: 302px" name="passwordForceReset">
								<option <?php if ($row["passwordForceReset"]=="Y") {print "selected ";}?>value="Y"><?php print _('Yes') ?></option>
								<option <?php if ($row["passwordForceReset"]=="N") {print "selected ";}?>value="N"><?php print _('No') ?></option>
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
	}
}
?>