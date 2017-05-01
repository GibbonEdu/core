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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/department_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/department_manage.php'>".__($guid, 'Manage Departments')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Department').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'];
    if ($gibbonDepartmentID == 'Y') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
            $sql = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('departmentManageRecord', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/department_manage_editProcess.php?gibbonDepartmentID=$gibbonDepartmentID&address=".$_SESSION[$guid]['address']);

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $types = array(
                'Learning Area' => __('Learning Area'),
                'Administration' => __('Administration'),
            );

            $typesLA = array(
                'Coordinator'           => __('Coordinator'),
                'Assistant Coordinator' => __('Assistant Coordinator'),
                'Teacher (Curriculum)'  => __('Teacher (Curriculum)'),
                'Teacher'               => __('Teacher'),
                'Other'                 => __('Other'),
            );

            $typesAdmin = array(
                'Director'      => __('Director'),
                'Manager'       => __('Manager'),
                'Administrator' => __('Administrator'),
                'Other'         => __('Other'),
            );

            $row = $form->addRow();
                $row->addLabel('type', 'Type');
                $row->addSelect('type')->fromArray($types)->isRequired()->selected($values['type']);

            $row = $form->addRow();
                $row->addLabel('name', 'Name');
                $row->addTextField('name')->maxLength(40)->isRequired()->setValue($values['name']);

            $row = $form->addRow();
                $row->addLabel('nameShort', 'Short Name');
                $row->addTextField('nameShort')->maxLength(4)->isRequired()->setValue($values['nameShort']);

            $row = $form->addRow();
                $row->addLabel('subjectListing', 'Subject Listing');
                $row->addTextField('subjectListing')->maxLength(255)->setValue($values['subjectListing']);

            $row = $form->addRow();
               $column = $row->addColumn()->setClass('');
               $column->addLabel('blurb', 'Blurb');
               $column->addEditor('blurb', $guid)->setValue($values['blurb']);

            $row = $form->addRow();
                $row->addLabel('file', 'Logo')->description('125x125px jpg/png/gif');
                $row->addFileUpload('file')
                    ->accepts('.jpg,.jpeg,.gif,.png')
                    ->append('<br/><br/>'.getMaxUpload($guid))
                    ->addClass('right')
                    ->setValue($values['logo']);

            $row = $form->addRow();
                $row->addLabel('staff', 'Staff')->description('Use Control, Command and/or Shift to select multiple.');
                $row->addSelectStaff('staff')->selectMultiple();

            $form->toggleVisibilityByClass('roleLARow')->onSelect('type')->when('Learning Area');

            $row = $form->addRow()->setClass('roleLARow');
                $row->addLabel('roleLA', 'Role');
                $row->addSelect('roleLA')->fromArray($typesLA)->selected($values['roleLA']);

            $form->toggleVisibilityByClass('roleAdmin')->onSelect('type')->when('Administration');

            $row = $form->addRow()->setClass('roleAdmin');
                $row->addLabel('roleAdmin', 'Role');
                $row->addSelect('roleAdmin')->fromArray($typesAdmin)->selected($values['roleAdmin']);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();


            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/department_manage_editProcess.php?gibbonDepartmentID=$gibbonDepartmentID&address=".$_SESSION[$guid]['address'] ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'General Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Type') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></i><br/></span>
						</td>
						<td class="right">
							<?php $type = $row['type']; ?>
							<input readonly name="type" id="type" value="<?php echo $type ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=40 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
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
							<input name="nameShort" id="nameShort" maxlength=4 value="<?php echo $row['nameShort'] ?>" type="text" class="standardWidth">
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
							<input name="subjectListing" id="subjectListing" maxlength=255 value="<?php echo $row['subjectListing'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<b><?php echo __($guid, 'Blurb') ?></b>
							<?php echo getEditor($guid,  true, 'blurb', $row['blurb'], 20) ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Logo') ?></b><br/>
							<span class="emphasis small">125x125px jpg/png/gif</i><br/></span>
							<?php if ($row['logo'] != '') { ?>
							<span class="emphasis small"><?php echo __($guid, 'Will overwrite existing attachment.') ?></span>
							<?php
							}
            				?>
						</td>
						<td class="right">
							<?php
                            if ($row['logo'] != '') {
                                echo __($guid, 'Current attachment:')." <a href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['logo']."'>".$row['logo'].'</a><br/><br/>';
                            }
            				?>
							<input type="file" name="file" id="file"><br/><br/>
							<?php
                            echo getMaxUpload($guid);
                            $ext = "'.png','.jpeg','.jpg','.gif'";
                            ?>

							<script type="text/javascript">
								var file=new LiveValidation('file');
								file.add( Validate.Inclusion, { within: [<?php echo $ext; ?>], failureMessage: "<?php echo __($guid, 'Illegal file type!') ?>", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Current Staff') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<?php
                            try {
                                $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
                                $sql = "SELECT preferredName, surname, gibbonDepartmentStaff.* FROM gibbonDepartmentStaff JOIN gibbonPerson ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonDepartmentID=:gibbonDepartmentID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

							if ($result->rowCount() < 1) {
								echo "<div class='error'>";
								echo __($guid, 'There are no records to display.');
								echo '</div>';
							} else {
								echo '<i><b>Warning</b>: If you delete a member of staff, any unsaved changes to this record will be lost!</i>';
								echo "<table cellspacing='0' style='width: 100%'>";
								echo "<tr class='head'>";
								echo '<th>';
								echo __($guid, 'Name');
								echo '</th>';
								echo '<th>';
								echo __($guid, 'Role');
								echo '</th>';
								echo '<th>';
								echo __($guid, 'Action');
								echo '</th>';
								echo '</tr>';

								$count = 0;
								$rowNum = 'odd';
								while ($row = $result->fetch()) {
									if ($count % 2 == 0) {
										$rowNum = 'even';
									} else {
										$rowNum = 'odd';
									}
									++$count;

									//COLOR ROW BY STATUS!
									echo "<tr class=$rowNum>";
									echo '<td>';
									echo formatName('', $row['preferredName'], $row['surname'], 'Staff', true, true);
									echo '</td>';
									echo '<td>';
									echo $row['role'];
									echo '</td>';
									echo '<td>';
									echo "<a onclick='return confirm(\"".__($guid, 'Are you sure you wish to delete this record?')."\")' href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/department_manage_edit_staff_deleteProcess.php?address='.$_GET['q'].'&gibbonDepartmentStaffID='.$row['gibbonDepartmentStaffID']."&gibbonDepartmentID=$gibbonDepartmentID'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
									echo '</td>';
									echo '</tr>';
								}
								echo '</table>';
							}
							?>
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'New Staff') ?></h3>
						</td>
					</tr>
					<tr>
    					<td>
    						<b>Staff</b><br/>
    						<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
    					</td>
    					<td class="right">
    						<select name="staff[]" id="staff[]" multiple class='standardWidth' style="height: 150px">
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
							<select name="role" id="role" class="standardWidth">
								<?php
                                if ($type == 'Learning Area') {
                                    ?>
									<option value="Coordinator"><?php echo __($guid, 'Coordinator') ?></option>
									<option value="Assistant Coordinator"><?php echo __($guid, 'Assistant Coordinator') ?></option>
									<option value="Teacher (Curriculum)"><?php echo __($guid, 'Teacher (Curriculum)') ?></option>
									<option value="Teacher"><?php echo __($guid, 'Teacher') ?></option>
									<option value="Other"><?php echo __($guid, 'Other') ?></option>
									<?php

                                } elseif ($type == 'Administration') {
                                    ?>
									<option value="Director"><?php echo __($guid, 'Director') ?></option>
									<option value="Manager"><?php echo __($guid, 'Manager') ?></option>
									<option value="Administrator"><?php echo __($guid, 'Administrator') ?></option>
									<option value="Other"><?php echo __($guid, 'Other') ?></option>
									<?php

                                } else {
                                    ?>
									<option value="Other"><?php echo __($guid, 'Other') ?></option>
									<?php
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
