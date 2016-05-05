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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userFields_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/userFields.php'>".__($guid, 'Manage Custom Fields')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Custom Field').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/userFields_edit.php&	gibbonPersonFieldID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    ?>
	
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/userFields_addProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td> 
					<b><?php echo __($guid, 'Name') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="name" id="name2" maxlength=50 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var name2=new LiveValidation('name2');
						name2.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Active') ?> *</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="active">
						<?php
                        echo "<option value='Y'>".__($guid, 'Yes').'</option>';
    					echo "<option value='N'>".__($guid, 'No').'</option>';?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Description') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="description" id="description" maxlength=255 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var description=new LiveValidation('description');
						description.add(Validate.Presence);
					</script>
				</td>
			</tr>
			
			<script type="text/javascript">
				$(document).ready(function(){
					$("#optionsRow").css("display","none");
						
					$("#type").change(function(){
						//varchar = chars
						//text = rows
						//select = csl of options
						if ($('select.type option:selected').val()=="varchar" || $('select.type option:selected').val()=="text" || $('select.type option:selected').val()=="select") {
							$("#optionsRow").slideDown("fast", $("#optionsRow").css("display","table-row")); 
						}
						else {
							$("#optionsRow").css("display","none");
						}
					 });
				});
			</script>
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Type') ?> *</b><br/>
				</td>
				<td class="right">
					<select class="standardWidth" name="type" id="type" class="type">
						<?php
                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
							echo "<option value='varchar'>Short Text (max 255 characters)</option>";
							echo "<option value='text'>Long Text</option>";
							echo "<option value='date'>Date</option>";
							echo "<option value='url'>Link</option>";
							echo "<option value='select'>Dropdown</option>"; ?>				
					</select>
					<script type="text/javascript">
						var type=new LiveValidation('type');
						type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<tr id="optionsRow">
				<td> 
					<b><?php echo __($guid, 'Options') ?> *</b><br/>
					<span class="emphasis small">
						<?php 
						echo __($guid, 'Short Text: number of characters, up to 255.').'<br/>';
						echo __($guid, 'Long Text: number of rows for field.').'<br/>';
						echo __($guid, 'Dropdown: comma separated list of options.').'<br/>';?>
					</span>
				</td>
				<td class="right">
					<textarea name="options" id="options" class="standardWidth" rows='3'></textarea>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Required') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Is this field compulsory?') ?></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="required">
						<?php
                        echo "<option value='Y'>".__($guid, 'Yes').'</option>';
    					echo "<option value='N'>".__($guid, 'No').'</option>';?>				
					</select>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Role Categories') ?></b><br/>
				</td>
				<td class="right">
					<?php
                        echo __($guid, 'Student')." <input checked type='checkbox' name='activePersonStudent' value='1'/><br/>";
						echo __($guid, 'Staff')." <input type='checkbox' name='activePersonStaff' value='1'/><br/>";
						echo __($guid, 'Parent')." <input type='checkbox' name='activePersonParent' value='1'/><br/>";
						echo __($guid, 'Other')." <input type='checkbox' name='activePersonOther' value='1'/><br/>"; ?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Include In Data Updater?') ?> *</b><br/>
				</td>
				<td class="right">
					<select class="standardWidth" name="activeDataUpdater">
						<?php
                        echo "<option value='1'>".__($guid, 'Yes').'</option>';
    					echo "<option value='0'>".__($guid, 'No').'</option>';?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Include In Application Form?') ?> *</b><br/>
				</td>
				<td class="right">
					<select class="standardWidth" name="activeApplicationForm">
						<?php
                        echo "<option value='1'>".__($guid, 'Yes').'</option>';
   						 echo "<option selected value='0'>".__($guid, 'No').'</option>';?>				
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