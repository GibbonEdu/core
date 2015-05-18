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

$proceed=FALSE ;

if (isset($_SESSION[$guid]["username"])==FALSE) {
	$enablePublicRegistration=getSettingByScope($connection2, 'User Admin', 'enablePublicRegistration') ;
	if ($enablePublicRegistration=="Y") {
		$proceed=TRUE ;
	}
}

if ($proceed==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > </div><div class='trailEnd'>" . $_SESSION[$guid]["organisationNameShort"] . " " . _('Public Registration') . "</div>" ;
	print "</div>" ;
	
	$publicRegistrationMinimumAge=getSettingByScope($connection2, 'User Admin', 'publicRegistrationMinimumAge') ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage=_("Your request failed because some inputs did not meet a requirement for uniqueness.") ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage=sprintf(_('Your request failed because you do not meet the minimum age for joining this site (%1$s years of age).'), $publicRegistrationMinimumAge) ;	
		}
		else if ($addReturn=="fail7") {
			$addReturnMessage=_("Your request failed because your password to not meet the minimum requirements for strength.") ;	
		}
		else if ($addReturn=="success1") {
			$addReturnMessage=_("Your registration was successfully submitted and is now pending approval. Our team will review your registration and be in touch in due course.") ;
			$class="success" ;
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=_("Your registration was successfully submitted, and you may now log into the system using your new username and password.") ;
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	//Get intro
	$intro=getSettingByScope($connection2, 'User Admin', 'publicRegistrationIntro') ;
	if ($intro!="") {
		print "<h3>" ; 
			print _("Introduction") ;
		print "</h3>" ;
		print "<p>" ;
			print $intro ;
		print "</p>" ;
	}
	
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/publicRegistrationProcess.php" ?>" enctype="multipart/form-data">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			
			
			<tr class='break'>
				<th colspan=2> 
					<?php print _("Account Details") ; ?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('First Name') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="firstName" id="firstName" maxlength=30 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var firstName=new LiveValidation('firstName');
						firstName.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Surname') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="surname" id="surname" maxlength=30 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var surname=new LiveValidation('surname');
						surname.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b><?php print _('Email') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Must be unique.') ?></i></span>
				</td>
				<td class="right">
					<input name="email" id="email" maxlength=50 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var email=new LiveValidation('email');
						email.add(Validate.Email);
						email.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b><?php print _('Gender') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="gender" id="gender" style="width: 302px">
						<option value="Please select..."><?php print _('Please select...') ?></option>
						<option value="F"><?php print _('Female') ?></option>
						<option value="M"><?php print _('Male') ?></option>
						<option value="F"><?php print _('Other') ?></option>
						<option value="M"><?php print _('Unspecified') ?></option>
					</select>
					<script type="text/javascript">
						var gender=new LiveValidation('gender');
						gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
					 </script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Date of Birth') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Format:') . " " . $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
				</td>
				<td class="right">
					<input name="dob" id="dob" maxlength=10 value="" type="text" style="width: 300px">
					<script type="text/javascript">
						var dob=new LiveValidation('dob');
						dob.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
					 	dob.add(Validate.Presence);
					 </script>
					 <script type="text/javascript">
						$(function() {
							$( "#dob" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Username') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _('Must be unique.') ?></i></span>
				</td>
				<td class="right">
					<input name="username" id="username" maxlength=20 value="" type="text" style="width: 300px">
					<?php
					$idList="" ;
					try {
						$dataSelect=array(); 
						$sqlSelect="SELECT username FROM gibbonPerson ORDER BY username" ;
						$resultSelect=$connection2->prepare($sqlSelect);
						$resultSelect->execute($dataSelect);
					}
					catch(PDOException $e) { }
					while ($rowSelect=$resultSelect->fetch()) {
						$idList.="'" . $rowSelect["username"]  . "'," ;
					}
					?>
					<script type="text/javascript">
						var username=new LiveValidation('username');
						username.add( Validate.Exclusion, { within: [<?php print $idList ;?>], failureMessage: "<?php print _('Value already in use!') ?>", partialMatch: false, caseSensitive: false } );
						username.add(Validate.Presence);
					 </script>
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
					<b><?php print _('Password') ?> *</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<input name="password" id="password" maxlength=20 value="" type="password" style="width: 300px">
					<script type="text/javascript">
						var password=new LiveValidation('password');
						password.add(Validate.Presence);
						<?php
						$alpha=getSettingByScope( $connection2, "System", "passwordPolicyAlpha" ) ;
						if ($alpha=="Y") {
							print "password.add( Validate.Format, { pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: \"" . _('Does not meet password policy.') . "\" } );" ;
						}
						$numeric=getSettingByScope( $connection2, "System", "passwordPolicyNumeric" ) ;
						if ($numeric=="Y") {
							print "password.add( Validate.Format, { pattern: /.*[0-9]/, failureMessage: \"" . _('Does not meet password policy.') . "\" } );" ;
						}
						$punctuation=getSettingByScope( $connection2, "System", "passwordPolicyNonAlphaNumeric" ) ;
						if ($punctuation=="Y") {
							print "password.add( Validate.Format, { pattern: /[^a-zA-Z0-9]/, failureMessage: \"" . _('Does not meet password policy.') . "\" } );" ;
						}
						$minLength=getSettingByScope( $connection2, "System", "passwordPolicyMinLength" ) ;
						if (is_numeric($minLength)) {
							print "password.add( Validate.Length, { minimum: " . $minLength . "} );" ;
						}
						?>
					 </script>
				</td>
			</tr>
			
			<?php
			//Privacy statement
			$privacyStatement=getSettingByScope($connection2, 'User Admin', 'publicRegistrationPrivacyStatement') ;
			if ($privacyStatement!="") {
				print "<tr class='break'>" ;
					print "<th colspan=2>" ; 
						print _("Privacy Statement") ;
					print "</th>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td colspan=2>" ; 
						print "<p>" ;
							print $privacyStatement ;
						print "</p>" ;
					print "</td>" ;
				print "</tr>" ;
			}
	
			//Get agreement
			$agreement=getSettingByScope($connection2, 'User Admin', 'publicRegistrationAgreement') ;
			if ($agreement!="") {
				print "<tr class='break'>" ;
					print "<th colspan=2>" ; 
						print _("Agreement") ;
					print "</td>" ;
				print "</tr>" ;
				
				print "<tr>" ;
					print "<td colspan=2>" ; 
						print $agreement ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td>" ; 
						print "<b>" . _('Do you agree to the above?') . "</b><br/>" ;
					print "</td>" ;
					print "<td class='right'>" ;
						print "Yes <input type='checkbox' name='agreement' id='agreement'>" ;
						?>
						<script type="text/javascript">
							var agreement=new LiveValidation('agreement');
							agreement.add( Validate.Acceptance );
						 </script>
						 <?php
					print "</td>" ;
				print "</tr>" ;
			}
			
			?>
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
	//Get postscrript
	$postscript=getSettingByScope($connection2, 'User Admin', 'publicRegistrationPostscript') ;
	if ($postscript!="") {
		print "<h2>" ; 
			print _("Further Information") ;
		print "</h2>" ;
		print "<p style='padding-bottom: 15px'>" ;
			print $postscript ;
		print "</p>" ;
	}
}
?>