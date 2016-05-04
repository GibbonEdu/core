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
echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>Preferences</div>";
echo '</div>';

//Deal with force reset notification
if (isset($_GET['forceReset'])) {
    $forceReset = $_GET['forceReset'];
} else {
    $forceReset = null;
}
if ($forceReset == 'Y') {
    $forceResetReturnMessage = '<b><u>'.__($guid, 'Your account has been flagged for a password reset. You cannot continue into the system until you change your password.').'</b></u>';
    echo "<div class='error'>";
    echo $forceResetReturnMessage;
    echo '</div>';
}

$returns = array();
$returns['errora'] = sprintf(__($guid, 'Your account status could not be updated, and so you cannot continue to use the system. Please contact %1$s if you have any questions.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>');
$returns['successa'] = __($guid, 'Your account has been successfully updated. You can now continue to use the system as per normal.');
$returns['error4'] = __($guid, 'Your request failed due to non-matching passwords.');
$returns['error3'] = __($guid, 'Your request failed due to incorrect current password.');
$returns['error6'] = __($guid, 'Your request failed because your password to not meet the minimum requirements for strength.');
$returns['error7'] = __($guid, 'Your request failed because your new password is the same as your current password.');
if (isset($_GET['return'])) {
    returnProcess($guid, $_GET['return'], null, $returns);
}

try {
    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
    $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
    $result = $connection2->prepare($sql);
    $result->execute($data);
} catch (PDOException $e) {
    echo "<div class='error'>".$e->getMessage().'</div>';
}
if ($result->rowCount() == 1) {
    $row = $result->fetch();
}
?>

<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/preferencesPasswordProcess.php">
	<table class='smallIntBorder fullWidth' cellspacing='0'>	
		<tr class='break'>
			<td colspan=2>
				<h3>
					<?php echo __($guid, 'Reset Password'); ?>
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
				<b><?php echo __($guid, 'Current Password'); ?> *</b><br/>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<input name="password" id="password" maxlength=30 value="" type="password" class="standardWidth">
				<script type="text/javascript">
					var password=new LiveValidation('password');
					password.add(Validate.Presence);
				</script>
			</td>
		</tr>
		<tr>
			<td> 
				<b><?php echo __($guid, 'New Password') ?> *</b><br/>
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
				<?php
                if ($forceReset == 'Y') {
                    echo "<input type='hidden' name='forceReset' value='$forceReset'>";
                }
                ?>
				<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
				<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
			</td>
		</tr>
	</table>
</form>
	
	
<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/preferencesProcess.php">
	<table class='smallIntBorder fullWidth' cellspacing='0'>	
		<tr class='break'>
			<td colspan=2>
				<h3>
					<?php echo __($guid, 'Settings'); ?>
				</h3>
			</td>
		</tr>
		<tr>
			<td> 
				<b><?php echo __($guid, 'Personal Google Calendar ID'); ?></b><br/>
				<span class="emphasis small"><?php echo __($guid, 'Google Calendar ID for your personal calendar.').'<br/>'.__($guid, 'Only enables timetable integration when logging in via Google.'); ?></span>
			</td>
			<td class="right">
				<input name="calendarFeedPersonal" id="calendarFeedPersonal" value="<?php echo $row['calendarFeedPersonal'] ?>" type="text" class="standardWidth">
			</td>
		</tr>
		
		<?php
        $personalBackground = getSettingByScope($connection2, 'User Admin', 'personalBackground');
        if ($personalBackground == 'Y') {
            ?>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Personal Background'); ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Set your own custom background image.').'<br/>'.__($guid, 'Please provide URL to image.'); ?></span>
				</td>
				<td class="right">
					<input name="personalBackground" id="personalBackground" value="<?php echo $row['personalBackground'] ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var personalBackground=new LiveValidation('personalBackground');
						personalBackground.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
					</script>	
				</td>
			</tr>
			<?php

        }
        ?>
		
		<tr>
			<td> 
				<b><?php echo __($guid, 'Personal Theme'); ?></b><br/>
				<span class="emphasis small"><?php echo __($guid, 'Override the system theme.'); ?></span>
			</td>
			<td class="right">
				<select name="gibbonThemeIDPersonal" id="gibbonThemeIDPersonal" class="standardWidth">
					<?php
                    echo "<option value=''></option>";
                    try {
                        $dataSelect = array();
                        $sqlSelect = 'SELECT * FROM gibbonTheme ORDER BY name';
                        $resultSelect = $connection2->prepare($sqlSelect);
                        $resultSelect->execute($dataSelect);
                    } catch (PDOException $e) {
                    }
                    while ($rowSelect = $resultSelect->fetch()) {
                        $selected = '';
                        if ($_SESSION[$guid]['gibbonThemeIDPersonal'] == $rowSelect['gibbonThemeID']) {
                            $selected = 'selected';
                        }
                        $default = '';
                        if ($rowSelect['active'] == 'Y') {
                            $default = ' (System Default)';
                        }
                        echo "<option $selected value='".$rowSelect['gibbonThemeID']."'>".$rowSelect['name']." $default</option>";
                    }
                    ?>				
				</select>
			</td>
		</tr>
		
		<tr>
			<td> 
				<b><?php echo __($guid, 'Personal Language'); ?></b><br/>
				<span class="emphasis small"><?php echo __($guid, 'Override the system default language.'); ?></span>
			</td>
			<td class="right">
				<select name="gibboni18nIDPersonal" id="gibboni18nIDPersonal" class="standardWidth">
					<?php
                    echo "<option value=''></option>";
                    try {
                        $dataSelect = array();
                        $sqlSelect = "SELECT * FROM gibboni18n WHERE active='Y' ORDER BY name";
                        $resultSelect = $connection2->prepare($sqlSelect);
                        $resultSelect->execute($dataSelect);
                    } catch (PDOException $e) {
                    }
                    while ($rowSelect = $resultSelect->fetch()) {
                        $selected = '';
                        if ($_SESSION[$guid]['gibboni18nIDPersonal'] == $rowSelect['gibboni18nID']) {
                            $selected = 'selected';
                        }
                        $default = '';
                        if ($rowSelect['systemDefault'] == 'Y') {
                            $default = ' (System Default)';
                        }
                        echo "<option $selected value='".$rowSelect['gibboni18nID']."'>".$rowSelect['name']." $default</option>";
                    }
                    ?>				
				</select>
			</td>
		</tr>
		
		<tr>
			<td> 
				<b><?php echo __($guid, 'Receive Email Notifications?'); ?></b><br/>
				<span class="emphasis small"><?php echo __($guid, 'Notifications can always be viewed on screen.'); ?></span>
			</td>
			<td class="right">
				<select name="receiveNotificationEmails" id="receiveNotificationEmails" class="standardWidth">
					<?php
                    echo '<option ';
                    if ($_SESSION[$guid]['receiveNotificationEmails'] == 'N') {
                        echo ' selected ';
                    }
                    echo "value='N'>".ynExpander($guid, 'N').'</option>';
                    echo '<option ';
                    if ($_SESSION[$guid]['receiveNotificationEmails'] == 'Y') {
                        echo ' selected ';
                    }
                    echo "value='Y'>".ynExpander($guid, 'Y').'</option>';
                    ?>				
				</select>
			</td>
		</tr>
		
		
		<tr>
			<td>
				<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
			</td>
			<td class='right'>
				<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
				<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
			</td>
		</tr>
	</table>
</form>