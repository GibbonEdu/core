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

@session_start();

$proceed = false;

if (isset($_SESSION[$guid]['username']) == false) {
    $enablePublicRegistration = getSettingByScope($connection2, 'User Admin', 'enablePublicRegistration');
    if ($enablePublicRegistration == 'Y') {
        $proceed = true;
    }
}

if ($proceed == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".$_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Public Registration').'</div>';
    echo '</div>';

    $publicRegistrationMinimumAge = getSettingByScope($connection2, 'User Admin', 'publicRegistrationMinimumAge');

    $returns = array();
    $returns['fail5'] = sprintf(__($guid, 'Your request failed because you do not meet the minimum age for joining this site (%1$s years of age).'), $publicRegistrationMinimumAge);
    $returns['fail7'] = __($guid, 'Your request failed because your password to not meet the minimum requirements for strength.');
    $returns['success1'] = __($guid, 'Your registration was successfully submitted and is now pending approval. Our team will review your registration and be in touch in due course.');
    $returns['success0'] = __($guid, 'Your registration was successfully submitted, and you may now log into the system using your new username and password.');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    //Get intro
    $intro = getSettingByScope($connection2, 'User Admin', 'publicRegistrationIntro');
    if ($intro != '') {
        echo '<h3>';
        echo __($guid, 'Introduction');
        echo '</h3>';
        echo '<p>';
        echo $intro;
        echo '</p>';
    }
    ?>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/publicRegistrationProcess.php' ?>" enctype="multipart/form-data">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
	        <tr class='break'>
				<th colspan=2>
					<?php echo __($guid, 'Account Details'); ?>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'First Name') ?> *</b><br/>
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
					<b><?php echo __($guid, 'Surname') ?> *</b><br/>
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
					<b><?php echo __($guid, 'Email') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Must be unique.') ?></span>
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
					<b><?php echo __($guid, 'Gender') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="gender" id="gender" class="standardWidth">
						<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
						<option value="F"><?php echo __($guid, 'Female') ?></option>
						<option value="M"><?php echo __($guid, 'Male') ?></option>
						<option value="Other"><?php echo __($guid, 'Other') ?></option>
						<option value="Unspecified"><?php echo __($guid, 'Unspecified') ?></option>
					</select>
					<script type="text/javascript">
						var gender=new LiveValidation('gender');
						gender.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Date of Birth') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Format:').' '.$_SESSION[$guid]['i18n']['dateFormat']  ?></span>
				</td>
				<td class="right">
					<input name="dob" id="dob" maxlength=10 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var dob=new LiveValidation('dob');
						dob.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
							echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
						} else {
							echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
						}
							?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
							echo 'dd/mm/yyyy';
						} else {
							echo $_SESSION[$guid]['i18n']['dateFormat'];
						}
						?>." } );
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
					<b><?php echo __($guid, 'Username') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Must be unique.') ?></span>
				</td>
				<td class="right">
                    <input name="username" id="username" maxlength=20 value="" type="text" class="standardWidth"><br/><br/><br/>
                    <div class="LV_validation_message LV_invalid" id='username_availability_result'></div><br/>
                    <script type="text/javascript">
                        $(document).ready(function(){
                            $('#username').on('input', function(){
                                if ($('#username').val() == '') {
                                    $('#username_availability_result').html('');
                                    return;
                                }
                                $('#username_availability_result').html('<?php echo __($guid, "Checking availability...") ?>');
                                $.ajax({
                                    type : 'POST',
                                    data : { username: $('#username').val() },
                                    url: "./publicRegistrationCheck.php",
                                    success: function(responseText){
                                        if(responseText == 0){
                                            $('#username_availability_result').html('<?php echo __('Username available'); ?>');
                                            $('#username_availability_result').switchClass('LV_invalid', 'LV_valid');
                                        }else if(responseText > 0){
                                            $('#username_availability_result').html('<?php echo __('Username already taken'); ?>');
                                            $('#username_availability_result').switchClass('LV_valid', 'LV_invalid');
                                        }
                                    }
                                });
                            });
                        });


                        // Validation
                        var username =  new LiveValidation('username');
                        username.add(Validate.Presence);
                    </script>
				</td>
			</tr>
			<tr>
				<td colspan=2>
					<?php
                    $policy = getPasswordPolicy($guid, $connection2);
					if ($policy != false) {
						echo "<div class='warning'>";
						echo $policy;
						echo '</div>';
					}
					?>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Password') ?> *</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<input type='button' class="generatePassword" value="<?php echo __($guid, 'Generate Password') ?>"/>
					<input name="passwordNew" id="passwordNew" maxlength=30 value="" type="password" class="standardWidth"><br/>

					<script type="text/javascript">
						var passwordNew=new LiveValidation('passwordNew');
						passwordNew.add(Validate.Presence);
						<?php
                        $alpha = getSettingByScope($connection2, 'System', 'passwordPolicyAlpha');
						$numeric = getSettingByScope($connection2, 'System', 'passwordPolicyNumeric');
						$punctuation = getSettingByScope($connection2, 'System', 'passwordPolicyNonAlphaNumeric');
						$minLength = getSettingByScope($connection2, 'System', 'passwordPolicyMinLength');
						if ($alpha == 'Y') {
							echo 'passwordNew.add( Validate.Format, { pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: "'.__($guid, 'Does not meet password policy.').'" } );';
						}
						if ($numeric == 'Y') {
							echo 'passwordNew.add( Validate.Format, { pattern: /.*[0-9]/, failureMessage: "'.__($guid, 'Does not meet password policy.').'" } );';
						}
						if ($punctuation == 'Y') {
							echo 'passwordNew.add( Validate.Format, { pattern: /[^a-zA-Z0-9]/, failureMessage: "'.__($guid, 'Does not meet password policy.').'" } );';
						}
						if (is_numeric($minLength)) {
							echo 'passwordNew.add( Validate.Length, { minimum: '.$minLength.'} );';
						}
						?>

						$(".generatePassword").click(function(){
							var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789![]{}()%&*$#^<>~@|';
							var text = '';
							for(var i=0; i < <?php echo $minLength + 4 ?>; i++) {
								for(var i=0; i < <?php echo $minLength + 4 ?>; i++) {
									if (i==0) { text += chars.charAt(Math.floor(Math.random() * 26)); }
									else if (i==1) { text += chars.charAt(Math.floor(Math.random() * 26)+26); }
									else if (i==2) { text += chars.charAt(Math.floor(Math.random() * 10)+52); }
									else if (i==3) { text += chars.charAt(Math.floor(Math.random() * 19)+62); }
									else { text += chars.charAt(Math.floor(Math.random() * chars.length)); }
								}
							}
							$('input[name="passwordNew"]').val(text);
							alert('<?php echo __($guid, 'Copy this password if required:') ?>' + '\n\n' + text) ;
						});
					</script>
				</td>
			</tr>

			<?php
            //Privacy statement
            $privacyStatement = getSettingByScope($connection2, 'User Admin', 'publicRegistrationPrivacyStatement');
			if ($privacyStatement != '') {
				echo "<tr class='break'>";
				echo '<th colspan=2>';
				echo __($guid, 'Privacy Statement');
				echo '</th>';
				echo '</tr>';
				echo '<tr>';
				echo '<td colspan=2>';
				echo '<p>';
				echo $privacyStatement;
				echo '</p>';
				echo '</td>';
				echo '</tr>';
			}

            //Get agreement
            $agreement = getSettingByScope($connection2, 'User Admin', 'publicRegistrationAgreement');
			if ($agreement != '') {
				echo "<tr class='break'>";
				echo '<th colspan=2>';
				echo __($guid, 'Agreement');
				echo '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td colspan=2>';
				echo $agreement;
				echo '</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>';
				echo '<b>'.__($guid, 'Do you agree to the above?').'</b><br/>';
				echo '</td>';
				echo "<td class='right'>";
				echo "Yes <input type='checkbox' name='agreement' id='agreement'>";
				?>
				<script type="text/javascript">
					var agreement=new LiveValidation('agreement');
					agreement.add( Validate.Acceptance );
				</script>
				 <?php
			echo '</td>';
        echo '</tr>';
    }

    ?>
			<tr>
				<td>
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>

	<?php
    //Get postscrript
    $postscript = getSettingByScope($connection2, 'User Admin', 'publicRegistrationPostscript');
    if ($postscript != '') {
        echo '<h2>';
        echo __($guid, 'Further Information');
        echo '</h2>';
        echo "<p style='padding-bottom: 15px'>";
        echo $postscript;
        echo '</p>';
    }
}
?>
