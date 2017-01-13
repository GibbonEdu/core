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

echo "<div class='trail'>";
echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".__($guid, 'Parent Confirmation').'</div>';
echo '</div>';

$step = 1;
if (isset($_GET['step'])) {
	if ($_GET['step'] == 2) {
		$step = 2;
	}
}

$email = (isset($_GET['email']))? $_GET['email'] : '';

if ($step == 1) { ?>

	<h3>
		Welcome to Gibbon
	</h3>
<?
	$returns = array();
	$returns['error0'] = __($guid, 'Email address not set.');
	$returns['error3'] = __($guid, 'Failed to send update email.');
	$returns['error4'] = __($guid, 'Your request failed due to non-matching passwords.');
	$returns['error5'] = __($guid, 'Your request failed due to incorrect or non-existent or non-unique email address.');
    $returns['error6'] = __($guid, 'Your request failed because your password to not meet the minimum requirements for strength.');
    $returns['error7'] = __($guid, 'Your request failed because your new password is the same as your current password.');
    $returns['error8'] = __($guid, 'Your request failed because the birthdate supplied does not match the one in our records, or your family data could not be located. Please try again, and if the problem persists contact support at <a mailto="'.$_SESSION[$guid]['organisationDBAEmail'].'">'.$_SESSION[$guid]['organisationDBAEmail'].'</a>');
    $returns['error9'] = __($guid, 'Your request failed because the email address supplied does not match the one in our records. Please try again, and if the problem persists contact support at <a mailto="'.$_SESSION[$guid]['organisationDBAEmail'].'">'.$_SESSION[$guid]['organisationDBAEmail'].'</a>');
    $returns['success0'] = __($guid, 'Account confirmation successfully initiated, please check your email. If you do not receive an email within a few minutes please check your spam folder as some emails may end up there.');
	if (isset($_GET['return'])) {
	    returnProcess($guid, $_GET['return'], null, $returns);
	    return;
	}	
	?>
	<p>
		Before you can login we'd like to request a few details confirm your account. After submitting this form you'll receive a link to set your password, then you'll be able to login to Gibbon with the blue Login form on the right.
	</p>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/parentConfirmationProcess.php?step=1">
		<table cellspacing='0' style="width: 100%">
			<tr>
				<td style="width:160px;">
					<b><?php echo __($guid, "Your Email Address");?></b>
				</td>
				<td class="right">
					<input name="email" id="email" type="text" style="width:97.5%" value="<?php echo $email; ?>">
					<script type="text/javascript">
    					var email=new LiveValidation('email');
    					email.add(Validate.Presence);
    				</script>
				</td>
			</tr>
			<tr>
				<td colspan="2">
				<?php echo __($guid, "Please confirm your account by entering the birthdate of your child at TIS. If there is more than one child in your family, please enter the birthdate of the oldest child currently enrolled at TIS.");?>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, "Child's Birthdate");?></b>
				</td>
				<td class="right">
					
				<table class="blank mini">
					<tr>
						<td>
							<select id="birthyear" name="birthyear">
							<?php
								for ($i = 2016; $i>=(2016-20);$i--) {
									echo '<option value='.$i.'>'.$i;
								}
							?>
							</select>
						</td>
						<td>
							<select id="birthmonth" name="birthmonth">
								<option value="01">January</option>
								<option value="02">February</option>
								<option value="03">March</option>
								<option value="04">April</option>
								<option value="05">May</option>
								<option value="06">June</option>
								<option value="07">July</option>
								<option value="08">August</option>
								<option value="09">September</option>
								<option value="10">October</option>
								<option value="11">November</option>
								<option value="12">December</option>
							</select>
						</td>
						<td>
							<input id="birthday" name="birthday" type="text" style="width:30px" value="" maxlength=2>
						</td>
						<script type="text/javascript">
	    					var birthday=new LiveValidation('birthday');
	    					birthday.add(Validate.Presence);
	    				</script>
					</tr>
				</table>
				</td>
			</tr>
			<tr>
				<td colspan="2">
				<?php echo __($guid, "After pressing submit <b style='color:#c0292d;'>a unique link will be emailed to you</b> to set your password. If you do not receive an email within a few minutes please check your spam folder as some emails may end up there.");?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
}
else {
	//Get URL parameters
	$input = $_GET['input'];
	$key = $_GET['key'];
	$gibbonPersonResetID = $_GET['gibbonPersonResetID'];

	//Verify authenticity of this request and check it is fresh (within 48 hours)
	try {
        $data = array('key' => $key, 'gibbonPersonResetID' => $gibbonPersonResetID);
        $sql = "SELECT * FROM gibbonPersonReset WHERE `key`=:key AND gibbonPersonResetID=:gibbonPersonResetID AND (timestamp > DATE_SUB(now(), INTERVAL 2 DAY))";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

	if ($result->rowCount() != 1) {
		echo "<div class='error'>";
		echo __($guid, 'Your reset request is invalid: you may not proceed.');
		echo '</div>';
	} else {
		echo "<div class='success'>";
		echo __($guid, 'Your reset request is valid: you may proceed.');
		echo '</div>';

		//Show form
		echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL']."/parentConfirmationProcess.php?input=$input&step=2&gibbonPersonResetID=$gibbonPersonResetID&key=$key'>";
			?>
			<table class='smallIntBorder fullWidth' cellspacing='0'>
	    		<tr class='break'>
	    			<td colspan=2>
	    				<h3>
	    					<?php echo __($guid, 'Set your Password'); ?>
	    				</h3>
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
	    				<b><?php echo __($guid, 'New Password') ?> *</b><br/>
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
	    							if (i==0) { text += chars.charAt(Math.floor(Math.random() * 26)); }
	    							else if (i==1) { text += chars.charAt(Math.floor(Math.random() * 26)+26); }
	    							else if (i==2) { text += chars.charAt(Math.floor(Math.random() * 10)+52); }
	    							else if (i==3) { text += chars.charAt(Math.floor(Math.random() * 19)+62); }
	    							else { text += chars.charAt(Math.floor(Math.random() * chars.length)); }
	    						}
	    						$('input[name="passwordNew"]').val(text);
	    						$('input[name="passwordConfirm"]').val(text);
	    						alert('<?php echo __($guid, 'Copy this password if required:') ?>' + '\n\n' + text) ;
	    					});
	    				</script>
	    			</td>
	    		</tr>
	    		<tr>
	    			<td>
	    				<b><?php echo __($guid, 'Confirm New Password'); ?> *</b><br/>
	    				<span class="emphasis small"></span>
	    			</td>
	    			<td class="right">
	    				<input name="passwordConfirm" id="passwordConfirm" maxlength=30 value="" type="password" class="standardWidth">
	    				<script type="text/javascript">
	    					var passwordConfirm=new LiveValidation('passwordConfirm');
	    					passwordConfirm.add(Validate.Presence);
	    					passwordConfirm.add(Validate.Confirmation, { match: 'passwordNew' } );
	    				</script>
	    			</td>
	    		</tr>
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
	}
}
?>
