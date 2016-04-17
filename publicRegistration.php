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
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > </div><div class='trailEnd'>" . $_SESSION[$guid]["organisationNameShort"] . " " . __($guid, 'Public Registration') . "</div>" ;
	print "</div>" ;
	
	$publicRegistrationMinimumAge=getSettingByScope($connection2, 'User Admin', 'publicRegistrationMinimumAge') ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage=__($guid, "Your request failed because some inputs did not meet a requirement for uniqueness.") ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage=sprintf(__($guid, 'Your request failed because you do not meet the minimum age for joining this site (%1$s years of age).'), $publicRegistrationMinimumAge) ;	
		}
		else if ($addReturn=="fail7") {
			$addReturnMessage=__($guid, "Your request failed because your password to not meet the minimum requirements for strength.") ;	
		}
		else if ($addReturn=="success1") {
			$addReturnMessage=__($guid, "Your registration was successfully submitted and is now pending approval. Our team will review your registration and be in touch in due course.") ;
			$class="success" ;
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=__($guid, "Your registration was successfully submitted, and you may now log into the system using your new username and password.") ;
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
			print __($guid, "Introduction") ;
		print "</h3>" ;
		print "<p>" ;
			print $intro ;
		print "</p>" ;
	}
	
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/publicRegistrationProcess.php" ?>" enctype="multipart/form-data">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			
			
			<tr class='break'>
				<th colspan=2> 
					<?php print __($guid, "Account Details") ; ?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'First Name') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="firstName" id="firstName" maxlength=30 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var firstName=new LiveValidation('firstName');
						firstName.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Surname') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="surname" id="surname" maxlength=30 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var surname=new LiveValidation('surname');
						surname.add(Validate.Presence);
					</script>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b><?php print __($guid, 'Email') ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, 'Must be unique.') ?></span>
				</td>
				<td class="right">
					<input name="email" id="email" maxlength=50 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var email=new LiveValidation('email');
						email.add(Validate.Email);
						email.add(Validate.Presence);
					</script>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b><?php print __($guid, 'Gender') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="gender" id="gender" class="standardWidth">
						<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
						<option value="F"><?php print __($guid, 'Female') ?></option>
						<option value="M"><?php print __($guid, 'Male') ?></option>
						<option value="F"><?php print __($guid, 'Other') ?></option>
						<option value="M"><?php print __($guid, 'Unspecified') ?></option>
					</select>
					<script type="text/javascript">
						var gender=new LiveValidation('gender');
						gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Date of Birth') ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, 'Format:') . " " . $_SESSION[$guid]["i18n"]["dateFormat"]  ?></span>
				</td>
				<td class="right">
					<input name="dob" id="dob" maxlength=10 value="" type="text" class="standardWidth">
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
					<b><?php print __($guid, 'Username') ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, 'Must be unique.') ?></span>
				</td>
				<td class="right">
					<input name="username" id="username" maxlength=20 value="" type="text" class="standardWidth">
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
						username.add( Validate.Exclusion, { within: [<?php print $idList ;?>], failureMessage: "<?php print __($guid, 'Value already in use!') ?>", partialMatch: false, caseSensitive: false } );
						username.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td colspan=2>
					<?php
					$policy=getPasswordPolicy($guid, $connection2) ;
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
					<b><?php print __($guid, 'Password') ?> *</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<input type='button' class="generatePassword" value="<?php print __($guid, "Generate Password") ?>"/>
					<input name="passwordNew" id="passwordNew" maxlength=20 value="" type="password" class="standardWidth"><br/>
					
					<script type="text/javascript">
						var passwordNew=new LiveValidation('passwordNew');
						passwordNew.add(Validate.Presence);
						<?php
						$alpha=getSettingByScope( $connection2, "System", "passwordPolicyAlpha" ) ;
						$numeric=getSettingByScope( $connection2, "System", "passwordPolicyNumeric" ) ;
						$punctuation=getSettingByScope( $connection2, "System", "passwordPolicyNonAlphaNumeric" ) ;
						$minLength=getSettingByScope( $connection2, "System", "passwordPolicyMinLength" ) ;
						if ($alpha=="Y") {
							print "passwordNew.add( Validate.Format, { pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: \"" . __($guid, 'Does not meet password policy.') . "\" } );" ;
						}
						if ($numeric=="Y") {
							print "passwordNew.add( Validate.Format, { pattern: /.*[0-9]/, failureMessage: \"" . __($guid, 'Does not meet password policy.') . "\" } );" ;
						}
						if ($punctuation=="Y") {
							print "passwordNew.add( Validate.Format, { pattern: /[^a-zA-Z0-9]/, failureMessage: \"" . __($guid, 'Does not meet password policy.') . "\" } );" ;
						}
						if (is_numeric($minLength)) {
							print "passwordNew.add( Validate.Length, { minimum: " . $minLength . "} );" ;
						}
						?>
						
						$(".generatePassword").click(function(){
							var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789![]{}()%&*$#^<>~@|';
							var text = '';
							for(var i=0; i < <?php print ($minLength+4) ?>; i++) {
								for(var i=0; i < <?php print ($minLength+4) ?>; i++) {
									if (i==0) { text += chars.charAt(Math.floor(Math.random() * 26)); }
									else if (i==1) { text += chars.charAt(Math.floor(Math.random() * 26)+26); }
									else if (i==2) { text += chars.charAt(Math.floor(Math.random() * 10)+52); }
									else if (i==3) { text += chars.charAt(Math.floor(Math.random() * 19)+62); }
									else { text += chars.charAt(Math.floor(Math.random() * chars.length)); }
								}
							}
							$('input[name="passwordNew"]').val(text);
							alert('<?php print __($guid, "Copy this password if required:") ?>' + '\n\n' + text) ;
						});
					</script>
				</td>
			</tr>
			
			<?php
			//Privacy statement
			$privacyStatement=getSettingByScope($connection2, 'User Admin', 'publicRegistrationPrivacyStatement') ;
			if ($privacyStatement!="") {
				print "<tr class='break'>" ;
					print "<th colspan=2>" ; 
						print __($guid, "Privacy Statement") ;
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
						print __($guid, "Agreement") ;
					print "</td>" ;
				print "</tr>" ;
				
				print "<tr>" ;
					print "<td colspan=2>" ; 
						print $agreement ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td>" ; 
						print "<b>" . __($guid, 'Do you agree to the above?') . "</b><br/>" ;
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
					<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>	
	
	<?php
	//Get postscrript
	$postscript=getSettingByScope($connection2, 'User Admin', 'publicRegistrationPostscript') ;
	if ($postscript!="") {
		print "<h2>" ; 
			print __($guid, "Further Information") ;
		print "</h2>" ;
		print "<p style='padding-bottom: 15px'>" ;
			print $postscript ;
		print "</p>" ;
	}
}
?>