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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userFields_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/userFields.php'>".__($guid, 'Manage Custom Fields')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Custom Field').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonPersonFieldID = $_GET['gibbonPersonFieldID'];
    if ($gibbonPersonFieldID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonPersonFieldID' => $gibbonPersonFieldID);
            $sql = 'SELECT * FROM gibbonPersonField WHERE gibbonPersonFieldID=:gibbonPersonFieldID';
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

            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/userFields_editProcess.php?gibbonPersonFieldID='.$row['gibbonPersonFieldID'] ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td> 
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="name" id="name2" maxlength=50 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
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
                                echo '<option ';
            if ($row['active'] == 'Y') {
                echo 'selected';
            }
            echo " value='Y'>".__($guid, 'Yes').'</option>';
            echo '<option ';
            if ($row['active'] == 'N') {
                echo 'selected';
            }
            echo " value='N'>".__($guid, 'No').'</option>'; ?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Description') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="description" id="description" maxlength=255 value="<?php echo $row['description'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var description=new LiveValidation('description');
								description.add(Validate.Presence);
							</script>
						</td>
					</tr>
			
					<script type="text/javascript">
						$(document).ready(function(){
							<?php
                                if ($row['type'] != 'varchar' and $row['type'] != 'text' and $row['type'] != 'select') {
                                    echo '$("#optionsRow").css("display","none");';
                                }
           	 					?>
							
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
            echo '<option ';
            if ($row['type'] == 'varchar') {
                echo 'selected';
            }
            echo " value='varchar'>Short Text (max 255 characters)</option>";
            echo '<option ';
            if ($row['type'] == 'text') {
                echo 'selected';
            }
            echo " value='text'>Long Text</option>";
            echo '<option ';
            if ($row['type'] == 'date') {
                echo 'selected';
            }
            echo " value='date'>Date</option>";
            echo '<option ';
            if ($row['type'] == 'url') {
                echo 'selected';
            }
            echo " value='url'>Link</option>";
            echo '<option ';
            if ($row['type'] == 'select') {
                echo 'selected';
            }
            echo " value='select'>Dropdown</option>";
            ?>				
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
            echo __($guid, 'Dropdown: comma separated list of options.').'<br/>'; ?>
								</span>
						</td>
						<td class="right">
							<textarea name="options" id="options" class="standardWidth" rows='3'><?php echo $row['options'] ?></textarea>
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
                                echo '<option ';
            if ($row['required'] == 'Y') {
                echo 'selected';
            }
            echo " value='Y'>".__($guid, 'Yes').'</option>';
            echo '<option ';
            if ($row['required'] == 'N') {
                echo 'selected';
            }
            echo " value='N'>".__($guid, 'No').'</option>'; ?>				
							</select>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Role Categories') ?></b><br/>
						</td>
						<td class="right">
							<?php
                                echo __($guid, 'Student').' <input ';
            if ($row['activePersonStudent'] == '1') {
                echo 'checked';
            }
            echo " type='checkbox' name='activePersonStudent' value='1'/><br/>";
            echo __($guid, 'Staff').' <input ';
            if ($row['activePersonStaff'] == '1') {
                echo 'checked';
            }
            echo " type='checkbox' name='activePersonStaff' value='1'/><br/>";
            echo __($guid, 'Parent').' <input ';
            if ($row['activePersonParent'] == '1') {
                echo 'checked';
            }
            echo " type='checkbox' name='activePersonParent' value='1'/><br/>";
            echo __($guid, 'Other').' <input ';
            if ($row['activePersonOther'] == '1') {
                echo 'checked';
            }
            echo " type='checkbox' name='activePersonOther' value='1'/><br/>";
            ?>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Include In Data Updater?') ?> *</b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="activeDataUpdater">
								<?php
                                echo '<option ';
            if ($row['activeDataUpdater'] == '1') {
                echo 'selected';
            }
            echo " value='1'>".__($guid, 'Yes').'</option>';
            echo '<option ';
            if ($row['activeDataUpdater'] == '0') {
                echo 'selected';
            }
            echo " value='0'>".__($guid, 'No').'</option>'; ?>				
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
                                echo '<option ';
            if ($row['activeApplicationForm'] == '1') {
                echo 'selected';
            }
            echo " value='1'>".__($guid, 'Yes').'</option>';
            echo '<option ';
            if ($row['activeApplicationForm'] == '0') {
                echo 'selected';
            }
            echo " value='0'>".__($guid, 'No').'</option>'; ?>				
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