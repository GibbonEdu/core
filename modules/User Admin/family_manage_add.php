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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage.php'>".__($guid, 'Manage Families')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Family').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID='.$_GET['editID'].'&search='.$_GET['search'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $search = $_GET['search'];
    if ($search != '') {
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a>';
        echo '</div>';
    }
    ?>
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_addProcess.php?search=$search" ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr class='break'>
				<td colspan=2>
					<h3>
						<?php echo __($guid, 'General Information') ?>
					</h3>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Name') ?> *</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=100 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var name2=new LiveValidation('name');
						name2.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Status') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="status" id="status" class="standardWidth">
						<option value="Married"><?php echo __($guid, 'Married') ?></option>
						<option value="Separated"><?php echo __($guid, 'Separated') ?></option>
						<option value="Divorced"><?php echo __($guid, 'Divorced') ?></option>
						<option value="De Facto"><?php echo __($guid, 'De Facto') ?></option>
						<option value="Other"><?php echo __($guid, 'Other') ?></option>	
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Home Language - Primary') ?></b><br/>
				</td>
				<td class="right">
					<select name="languageHomePrimary" id="languageHomePrimary" class="standardWidth">
						<?php
                        echo "<option value=''></option>";
						try {
							$dataSelect = array();
							$sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
						}
						while ($rowSelect = $resultSelect->fetch()) {
							echo "<option value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Home Language - Secondary') ?></b><br/>
				</td>
				<td class="right">
					<select name="languageHomeSecondary" id="languageHomeSecondary" class="standardWidth">
						<?php
                        echo "<option value=''></option>";
						try {
							$dataSelect = array();
							$sqlSelect = 'SELECT name FROM gibbonLanguage ORDER BY name';
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
						}
						while ($rowSelect = $resultSelect->fetch()) {
							echo "<option value='".$rowSelect['name']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Address Name') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Formal name to address parents with.') ?></span>
				</td>
				<td class="right">
					<input name="nameAddress" id="nameAddress" maxlength=100 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var nameAddress=new LiveValidation('nameAddress');
						nameAddress.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Home Address') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Unit, Building, Street') ?></span>
				</td>
				<td class="right">
					<input name="homeAddress" id="homeAddress" maxlength=255 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Home Address (District)') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'County, State, District') ?></span>
				</td>
				<td class="right">
					<input name="homeAddressDistrict" id="homeAddressDistrict" maxlength=30 value="" type="text" class="standardWidth">
				</td>
				<script type="text/javascript">
					$(function() {
						var availableTags=[
							<?php
                            try {
                                $dataAuto = array();
                                $sqlAuto = 'SELECT DISTINCT name FROM gibbonDistrict ORDER BY name';
                                $resultAuto = $connection2->prepare($sqlAuto);
                                $resultAuto->execute($dataAuto);
                            } catch (PDOException $e) {
                            }
							while ($rowAuto = $resultAuto->fetch()) {
								echo '"'.$rowAuto['name'].'", ';
							}
							?>
						];
						$( "#homeAddressDistrict" ).autocomplete({source: availableTags});
					});
				</script>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Home Address (Country)') ?></b><br/>
				</td>
				<td class="right">
					<select name="homeAddressCountry" id="homeAddressCountry" class="standardWidth">
						<?php
                        echo "<option value=''></option>";
						try {
							$dataSelect = array();
							$sqlSelect = 'SELECT printable_name FROM gibbonCountry ORDER BY printable_name';
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
						}
						while ($rowSelect = $resultSelect->fetch()) {
							echo "<option value='".$rowSelect['printable_name']."'>".htmlPrep(__($guid, $rowSelect['printable_name'])).'</option>';
						}
						?>				
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
?>