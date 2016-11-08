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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/staffApplicationFormSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Staff Application Form Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }
    ?>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/staffApplicationFormSettingsProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'General Options') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormIntroduction'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {
                        echo __($guid, $row['description']);
                    }?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=12 class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormQuestions'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {echo __($guid, $row['description']);} ?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=12 class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormPostscript'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=12 class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormAgreement'";
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
                    echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=8 class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff Application Form' AND name='staffApplicationFormPublicApplications'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
						<option <?php if ($row['value'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormMilestones'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=4 type="text" class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>



			<?php
            try {
                $data = array();
                $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='applicationFormRefereeLink'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() == 1) {
                $row = $result->fetch();
                $applicationFormRefereeLink=unserialize($row['value']) ;
                ?>
                <tr class='break'>
                    <td colspan=2>
                        <h3><?php echo __($guid, $row['nameDisplay']) ?></h3>
                        <p><?php if ($row['description'] != '') {echo __($guid, $row['description']);}?></p>
                    </td>
                </tr>
                <?php
                $types=array() ;
                $types[0] = 'Teaching';
                $types[1] = 'Support';
                $typeCount = 2 ;
                try {
                    $dataSelect = array();
                    $sqlSelect = "SELECT * FROM gibbonRole WHERE category='Staff' ORDER BY name";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {}
                while ($rowSelect = $resultSelect->fetch()) {
                    $types[$typeCount] = $rowSelect['name'];
                    $typeCount++;
                }
                $typeCount=0 ;
                foreach ($types AS $type) {
                    ?>
                    <tr>
                        <td style='width: 275px'>
        					<b>
                                <?php
                                if ($typeCount==0 OR $typeCount==1) {
                                    print __($guid, "Staff Type") . ": " ;
                                }
                                else {
                                    print __($guid, "Staff Role") . ": " ;
                                }
                                print __($guid, $type)
                                ?>
                            </b><br/>
        				</td>
        				<td stclass="right">
                            <input name="types[]" maxlength=255 value="<?php print $type ?>" type="hidden">
        					<input id="type<?php print $type ?>" name="refereeLinks[]" maxlength=255 value="<?php if (isset($applicationFormRefereeLink[$type])) { print $applicationFormRefereeLink[$type] ; } ?>" type="text" class="standardWidth">
        					<script type="text/javascript">
        						var type<?php print $type ?>=new LiveValidation('type<?php print $type ?>');
        						type<?php print $type ?>.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
        					</script>
        				</td>
                    </tr>
                    <?php
                    $typeCount++;
                }
            }
            ?>
            <tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Required Documents Options') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormRequiredDocuments'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=4 type="text" class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormRequiredDocumentsText'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=4 type="text" class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormRequiredDocumentsCompulsory'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'N') {
                            echo 'selected ';
                        }
                            ?>value="N">No</option>
                        						<option <?php if ($row['value'] == 'Y') {
                            echo 'selected ';
                        }
                            ?>value="Y">Yes
                    </option>
					</select>
				</td>
			</tr>


			<tr class='break'>
				<td colspan=2>
					<h3><?php echo __($guid, 'Acceptance Options') ?></h3>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormUsernameFormat'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<input type='text' name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth" value='<?php echo htmlPrep($row['value']) ?>'>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormNotificationMessage'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=8 class="standardWidth"><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormNotificationDefault'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'Y') {
                            echo 'selected ';
                        }
                            ?>value="Y"><?php echo ynExpander($guid, 'Y') ?></option>
                        						<option <?php if ($row['value'] == 'N') {
                            echo 'selected ';
                        }
                            ?>value="N"><?php echo ynExpander($guid, 'N') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormDefaultEmail'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<input type='text' name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth" value='<?php echo htmlPrep($row['value']) ?>'>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Staff' AND name='staffApplicationFormDefaultWebsite'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $row = $result->fetch();
                ?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') {echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<input type='text' name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth" value='<?php echo htmlPrep($row['value']) ?>'>
				</td>
			</tr>

			<tr>
				<td>
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit');?>">
				</td>
			</tr>
		</table>
	</form>
<?php
}
?>
