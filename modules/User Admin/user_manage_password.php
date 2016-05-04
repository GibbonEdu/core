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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_password.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage.php'>".__($guid, 'Manage Users')."</a> > </div><div class='trailEnd'>".__($guid, 'Reset User Password').'</div>';
    echo '</div>';

    $returns = array();
    $returns['error5'] = __($guid, 'Your request failed because your passwords did not match.');
    $returns['error6'] = __($guid, 'Your request failed because your password to not meet the minimum requirements for strength.');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    //Check if school year specified
    $gibbonPersonID = $_GET['gibbonPersonID'];
    if ($gibbonPersonID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch();
            if ($_GET['search'] != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/user_manage.php&search='.$_GET['search']."'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            $policy = getPasswordPolicy($guid, $connection2);
            if ($policy != false) {
                echo "<div class='warning'>";
                echo $policy;
                echo '</div>';
            }
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/user_manage_passwordProcess.php?gibbonPersonID='.$gibbonPersonID.'&search='.$_GET['search'] ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Username') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input readonly name="username" id="username" maxlength=20 value="<?php echo htmlPrep($row['username']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var username=new LiveValidation('username');
								username.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Password') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input type='button' class="generatePassword" value="<?php echo __($guid, 'Generate Password') ?>"/>
							<input name="passwordNew" id="passwordNew" maxlength=20 value="" type="password" class="standardWidth"><br/>
							
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
							<b><?php echo __($guid, 'Confirm Password') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="passwordConfirm" id="passwordConfirm" maxlength=20 value="" type="password" class="standardWidth">
							<script type="text/javascript">
								var passwordConfirm=new LiveValidation('passwordConfirm');
								passwordConfirm.add(Validate.Presence);
								passwordConfirm.add(Validate.Confirmation, { match: 'passwordNew' } );
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Force Reset Password?') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'User will be prompted on next login.') ?></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="passwordForceReset">
								<option <?php if ($row['passwordForceReset'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['passwordForceReset'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
							</select>
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
}
?>