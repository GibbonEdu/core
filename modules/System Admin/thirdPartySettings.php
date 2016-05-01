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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/thirdPartySettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Third Party Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }
    ?>
	
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/thirdPartySettingsProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php echo __($guid, 'Google Integration') ?></h3>
					<?php echo sprintf(__($guid, 'If your school uses Google Apps, you can enable single sign on and calendar integreation with Gibbon. This process makes use of Google\'s APIs, and allows a user to access Gibbon without a username and password, provided that their listed email address is a Google account to which they have access. For configuration instructions, %1$sclick here%2$s.'), "<a href='https://gibbonedu.org/support/administrators/authenticating-with-google-oauth/' target='_blank'>", '</a>') ?>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='googleOAuth'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'N') {
    echo 'selected ';
}
    ?>value="N"><?php echo __($guid, 'No') ?></option>
						<option <?php if ($row['value'] == 'Y') {
    echo 'selected ';
}
    ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
            
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='googleClientName'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=4 type="text" class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
            
            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='googleClientID'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=4 type="text" class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
            
            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='googleClientSecret'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=4 type="text" class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
			
            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='googleRedirectUri'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=4 type="text" class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
            
            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='googleDeveloperKey'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=4 type="text" class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='calendarFeed'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=255 value="<?php echo $row['value'] ?>" type="text" class="standardWidth">
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php echo __($guid, 'PayPal Payment Gateway') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='enablePayments'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<?php
                        $selected = '';
    if ($row['value'] == 'Y') {
        $selected = 'selected';
    }
    echo "<option $selected value='Y'>".ynExpander($guid, 'Y').'</option>';
    $selected = '';
    if ($row['value'] == 'N') {
        $selected = 'selected';
    }
    echo "<option $selected value='N'>".ynExpander($guid, 'N').'</option>';
    ?>			
					</select>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='paypalAPIUsername'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=255 value="<?php echo $row['value'] ?>" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='paypalAPIPassword'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=255 value="<?php echo $row['value'] ?>" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='System' AND name='paypalAPISignature'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=255 value="<?php echo $row['value'] ?>" type="text" class="standardWidth">
				</td>
			</tr>
			
			<tr class='break'>
				<td colspan=2> 
					<h3><?php echo __($guid, 'SMS Settings') ?></h3>
					<?php echo sprintf(__($guid, 'Gibbon is designed to use the %1$sOne Way SMS%2$s gateway to send out SMS messages. This is a paid service, not affiliated with Gibbon, and you must create your own account with them before being able to send out SMSs using the Messenger module. It is possible that completing the fields below with details from other gateways may work.'), "<a href='http://onewaysms.com' target='_blank'>", '</a>') ?>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Messenger' AND name='smsUsername'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td style='width: 275px'> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=50 value="<?php echo htmlPrep($row['value']) ?>" type="text" class="standardWidth">
				</td>
			</tr>
			
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Messenger' AND name='smsPassword'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=50 value="<?php echo htmlPrep($row['value']) ?>" type="password" class="standardWidth">
				</td>
			</tr>
			
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Messenger' AND name='smsURL'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=255 value="<?php echo htmlPrep($row['value']) ?>" type="text" class="standardWidth">
				</td>
			</tr>
			
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Messenger' AND name='smsURLCredit'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
    $row = $result->fetch();
    ?>
				<td> 
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
    echo __($guid, $row['description']);
}
    ?></span>
				</td>
				<td class="right">
					<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=255 value="<?php echo htmlPrep($row['value']) ?>" type="text" class="standardWidth">
				</td>
			</tr>
			
			<tr>
				<td>
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
    ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit');
    ?>">
				</td>
			</tr>
		</table>
	</form>
<?php

}
?>