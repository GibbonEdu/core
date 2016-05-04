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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/department_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/department_manage.php'>".__($guid, 'Manage Departments')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Learning Area').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/department_manage_edit.php&gibbonDepartmentID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    ?>
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/department_manage_addProcess.php?address='.$_SESSION[$guid]['address'] ?>" enctype="multipart/form-data">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<!-- FIELDS & CONTROLS FOR TYPE -->
			<script type="text/javascript">
				$(document).ready(function(){
					$("#type").change(function(){
						if ($('select.type option:selected').val()=="Learning Area" ) {
							$("#roleAdminRow").css("display","none");
							$("#roleLARow").slideDown("fast", $("#roleLARow").css("display","table-row")); 
						} else if ($('select.type option:selected').val()=="Administration" ) {
							$("#roleLARow").css("display","none");
							$("#roleAdminRow").slideDown("fast", $("#roleAdminRow").css("display","table-row")); 
						} 
					 });
				});
			</script>
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Type') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="type" id="type" class='type standardWidth'>
						<option value='Learning Area'>Learning Area</option>
						<option value='Administration'>Administration</option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Name') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=40 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var name2=new LiveValidation('name');
						name2.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Short Name') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="nameShort" id="nameShort" maxlength=4 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var nameShort=new LiveValidation('nameShort');
						nameShort.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Subject Listing') ?></b><br/>
				</td>
				<td class="right">
					<input name="subjectListing" id="subjectListing" maxlength=255 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<b><?php echo __($guid, 'Blurb') ?></b> 
					<?php echo getEditor($guid,  true, 'blurb', '', 20) ?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Logo') ?></b><br/>
					<span class="emphasis small">125x125px jpg/png/gif</span>
				</td>
				<td class="right">
					<input type="file" name="file" id="file"><br/><br/>
					<?php
                    echo getMaxUpload($guid);
    $ext = "'.png','.jpeg','.jpg','.gif'";
    ?>
					
					<script type="text/javascript">
						var file=new LiveValidation('file');
						file.add( Validate.Inclusion, { within: [<?php echo $ext;
    ?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Staff') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
				</td>
				<td class="right">
					<select name="staff[]" id="staff[]" multiple style="width: 302px; height: 150px">
						<?php
                        try {
                            $dataSelect = array();
                            $sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                        }
    while ($rowSelect = $resultSelect->fetch()) {
        echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Staff', true, true).'</option>';
    }
    ?>
					</select>
				</td>
			</tr>
			<tr id='roleLARow'>
				<td> 
					<b><?php echo __($guid, 'Role') ?></b><br/>
				</td>
				<td class="right">
					<select name="roleLA" id="roleLA" class="standardWidth">
						<option value="Coordinator"><?php echo __($guid, 'Coordinator') ?></option>
						<option value="Assistant Coordinator"><?php echo __($guid, 'Assistant Coordinator') ?></option>
						<option value="Teacher (Curriculum)"><?php echo __($guid, 'Teacher (Curriculum)') ?></option>
						<option value="Teacher"><?php echo __($guid, 'Teacher') ?></option>
						<option value="Other"><?php echo __($guid, 'Other') ?></option>
					</select>
				</td>
			</tr>
			<tr id='roleAdminRow' style='display: none'>
				<td> 
					<b>Role</b><br/>
				</td>
				<td class="right">
					<select name="roleAdmin" id="roleAdmin" class="standardWidth">
						<option value="Director"><?php echo __($guid, 'Director') ?></option>
						<option value="Manager"><?php echo __($guid, 'Manager') ?></option>
						<option value="Administrator"><?php echo __($guid, 'Administrator') ?></option>
						<option value="Other"><?php echo __($guid, 'Other') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
				</td>
				<td class="right">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

}
?>