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

if (isActionAccessible($guid, $connection2, '/modules/Departments/department_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if courseschool year specified
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'];
    if ($gibbonDepartmentID == '') {
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
            $row = $result->fetch();

            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/departments.php'>".__($guid, 'View All')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/department.php&gibbonDepartmentID='.$_GET['gibbonDepartmentID']."'>".$row['name']."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Department').'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, array('error3' => 'Your request failed due to an attachment error.'));
            }

            //Get role within learning area
            $role = getRole($_SESSION[$guid]['gibbonPersonID'], $gibbonDepartmentID, $connection2);

            if ($role != 'Coordinator' and $role != 'Assistant Coordinator' and $role != 'Teacher (Curriculum)' and $role != 'Director' and $role != 'Manager') {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/department_editProcess.php?gibbonDepartmentID=$gibbonDepartmentID&address=".$_GET['q'] ?>" enctype="multipart/form-data">
					<table class='smallIntBorder fullWidth' cellspacing='0'>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Overview') ?></h3>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<?php echo getEditor($guid,  true, 'blurb', $row['blurb'], 20) ?>
							</td>
						</tr>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Current Resources') ?></h3>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<?php
                                try {
                                    $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
                                    $sql = 'SELECT * FROM gibbonDepartmentResource WHERE gibbonDepartmentID=:gibbonDepartmentID ORDER BY name';
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
									echo '<i>'.__($guid, 'Warning: If you delete a resource, any unsaved changes to this planner entry will be lost!').'</i>';
									echo "<table cellspacing='0' style='width: 100%'>";
									echo "<tr class='head'>";
									echo '<th>';
									echo __($guid, 'Name');
									echo '</th>';
									echo '<th>';
									echo __($guid, 'Type');
									echo '</th>';
									echo '<th>';
									echo __($guid, 'Actions');
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
										if ($row['type'] == 'Link') {
											echo "<a target='_blank' href='".$row['url']."'>".$row['name'].'</a>';
										} else {
											echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['url']."'>".$row['name'].'</a>';
										}
										echo '</td>';
										echo '<td>';
										echo $row['type'];
										echo '</td>';
										echo '<td>';
										echo "<a onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")' href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/department_edit_resource_deleteProcess.php?gibbonDepartmentResourceID='.$row['gibbonDepartmentResourceID'].'&gibbonDepartmentID='.$row['gibbonDepartmentID'].'&address='.$_GET['q']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
										echo '</td>';
										echo '</tr>';
									}
									echo '</table>';
								}
								?>
							</td>
						</tr>

						<script type="text/javascript">
							/* Resource 1 Option Control */
							$(document).ready(function(){
								$("#resource1URL").css("display","none");
								$("#resource1File").css("display","none");
								$("#resource1ButtonRow").css("display","none");

								$(".type1").click(function(){
									if ($('input[name=type1]:checked').val()=="Link" ) {
										$("#resource1File").css("display","none");
										$("#resource1URL").slideDown("fast", $("#resource1URL").css("display","table-row"));
										$("#resource1ButtonRow").slideDown("fast", $("#resource1ButtonRow").css("display","table-row"));
									} else {
										$("#resource1URL").css("display","none");
										$("#resource1File").slideDown("fast", $("#resource1File").css("display","table-row"));
										$("#resource1ButtonRow").slideDown("fast", $("#resource1ButtonRow").css("display","table-row"));
									}
								 });
							});

							/* Resource 2 Display Control */
							$(document).ready(function(){
								$("#resource2").css("display","none");
								$("#resource2Name").css("display","none");
								$("#resource2Students").css("display","none");
								$("#type2").css("display","none");
								$("#resource2Parents").css("display","none");
								$("#resource2URL").css("display","none");
								$("#resource2File").css("display","none");
								$("#resource2ButtonRow").css("display","none");

								$("#resource1Button").click(function(){
									$("#resource1Button").css("display","none");
									$("#resource2").slideDown("fast", $("#resource2").css("display","table-row"));
									$("#resource2Name").slideDown("fast", $("#resource2Name").css("display","table-row"));
									$("#resource2Students").slideDown("fast", $("#resource2Students").css("display","table-row"));
									$("#resource2Parents").slideDown("fast", $("#resource2Parents").css("display","table-row"));
									$("#type2").slideDown("fast", $("#type2").css("display","table-row"));
								});
							});

							/* Resource 2 Option Control */
							$(document).ready(function(){
								$(".type2").click(function(){
									if ($('input[name=type2]:checked').val()=="Link" ) {
										$("#resource2File").css("display","none");
										$("#resource2URL").slideDown("fast", $("#resource2URL").css("display","table-row"));
										$("#resource2ButtonRow").slideDown("fast", $("#resource2ButtonRow").css("display","table-row"));
									} else {
										$("#resource2URL").css("display","none");
										$("#resource2File").slideDown("fast", $("#resource2File").css("display","table-row"));
										$("#resource2ButtonRow").slideDown("fast", $("#resource2ButtonRow").css("display","table-row"));
									}
								 });
							});

							/* Resource 3 Display Control */
							$(document).ready(function(){
								$("#resource3").css("display","none");
								$("#resource3Name").css("display","none");
								$("#resource3Students").css("display","none");
								$("#type3").css("display","none");
								$("#resource3Parents").css("display","none");
								$("#resource3URL").css("display","none");
								$("#resource3File").css("display","none");
								$("#resource3ButtonRow").css("display","none");

								$("#resource2Button").click(function(){
									$("#resource2Button").css("display","none");
									$("#resource3").slideDown("fast", $("#resource3").css("display","table-row"));
									$("#resource3Name").slideDown("fast", $("#resource3Name").css("display","table-row"));
									$("#resource3Students").slideDown("fast", $("#resource3Students").css("display","table-row"));
									$("#resource3Parents").slideDown("fast", $("#resource3Parents").css("display","table-row"));
									$("#type3").slideDown("fast", $("#type3").css("display","table-row"));
								});
							});

							/* Resource 3 Option Control */
							$(document).ready(function(){
								$(".type3").click(function(){
									if ($('input[name=type3]:checked').val()=="Link" ) {
										$("#resource3File").css("display","none");
										$("#resource3URL").slideDown("fast", $("#resource3URL").css("display","table-row"));
										$("#resource3ButtonRow").slideDown("fast", $("#resource3ButtonRow").css("display","table-row"));
									} else {
										$("#resource3URL").css("display","none");
										$("#resource3File").slideDown("fast", $("#resource3File").css("display","table-row"));
										$("#resource3ButtonRow").slideDown("fast", $("#resource3ButtonRow").css("display","table-row"));
									}
								 });
							});

						</script>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo sprintf(__($guid, 'New Resource %1$s'), '1') ?></h3>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo sprintf(__($guid, 'Resource %1$s Name'), '1') ?></b><br/>
							</td>
							<td class="right">
								<input name="name1" id="name1" maxlength=100 value="" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo sprintf(__($guid, 'Resource %1$s Type'), '1') ?></b><br/>
							</td>
							<td class="right">
								<input type="radio" name="type1" value="Link" class="type1" /> Link
								<input type="radio" name="type1" value="File" class="type1" /> File
							</td>
						</tr>
						<tr id="resource1URL">
							<td>
								<b><?php echo sprintf(__($guid, 'Resource %1$s URL'), '1') ?></b><br/>
							</td>
							<td class="right">
								<input name="url1" id="url1" maxlength=255 value="" type="text" class="standardWidth">
								<script type="text/javascript">
									var url1=new LiveValidation('url1');
									url1.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
								</script>
							</td>
						</tr>
						<tr id="resource1File">
							<td>
								<b><?php echo sprintf(__($guid, 'Resource %1$s File'), '1') ?></b><br/>
							</td>
							<td class="right">
								<input type="file" name="file1" id="file1">
								<?php
                                //Get list of acceptable file extensions
                                try {
                                    $dataExt = array();
                                    $sqlExt = 'SELECT * FROM gibbonFileExtension';
                                    $resultExt = $connection2->prepare($sqlExt);
                                    $resultExt->execute($dataExt);
                                } catch (PDOException $e) {
                                }
								$ext = '';
								while ($rowExt = $resultExt->fetch()) {
									$ext = $ext."'.".$rowExt['extension']."',";
								}
								?>
								<script type="text/javascript">
									var file1=new LiveValidation('file1');
									file1.add( Validate.Inclusion, { within: [<?php echo $ext;
									?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
								</script>
							</td>
						</tr>
						<tr id="resource1ButtonRow">
							<td>
							</td>
							<td class="right">
								<input class="buttonAsLink" id="resource1Button" type="button" value="Add Another Resource">
								<a href=""></a>
							</td>
						</tr>
						<tr class='break' id="resource2">
							<td colspan=2>
								<h3><?php echo sprintf(__($guid, 'New Resource %1$s'), '2') ?></h3>
							</td>
						</tr>
						<tr id="resource2Name">
							<td>
								<b><?php echo sprintf(__($guid, 'Resource %1$s Name'), '2') ?></b><br/>
							</td>
							<td class="right">
								<input name="name2" id="name2" maxlength=100 value="" type="text" class="standardWidth">
							</td>
						</tr>
						<tr id="type2">
							<td>
								<b><?php echo sprintf(__($guid, 'Resource %1$s Type'), '2') ?></b><br/>
							</td>
							<td class="right">
								<input type="radio" name="type2" value="Link" class="type2" /> Link
								<input type="radio" name="type2" value="File" class="type2" /> File
							</td>
						</tr>
						<tr id="resource2URL">
							<td>
								<b><?php echo sprintf(__($guid, 'Resource %1$s URL'), '2') ?></b><br/>
							</td>
							<td class="right">
								<input name="url2" id="url2" maxlength=255 value="" type="text" class="standardWidth">
								<script type="text/javascript">
									var url2=new LiveValidation('url2');
									url2.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
								</script>
							</td>
						</tr>
						<tr id="resource2File">
							<td>
								<b><?php echo sprintf(__($guid, 'Resource %1$s File'), '2') ?></b><br/>
							</td>
							<td class="right">
								<input type="file" name="file2" id="file2">
								<script type="text/javascript">
									var file2=new LiveValidation('file2');
									file2.add( Validate.Inclusion, { within: [<?php echo $ext;
                					?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
								</script>
							</td>
						</tr>
						<tr id="resource2ButtonRow">
							<td>
							</td>
							<td class="right">
								<input class="buttonAsLink" id="resource2Button" type="button" value="Add Another Resource">
								<a href=""></a>
							</td>
						</tr>

						<tr class='break' id="resource3">
							<td colspan=2>
								<h3><?php echo sprintf(__($guid, 'New Resource %1$s'), '3') ?></h3>
							</td>
						</tr>
						<tr id="resource3Name">
							<td>
								<b><?php echo sprintf(__($guid, 'Resource %1$s Name'), '3') ?></b><br/>
							</td>
							<td class="right">
								<input name="name3" id="name3" maxlength=100 value="" type="text" class="standardWidth">
							</td>
						</tr>
						<tr id="type3">
							<td>
								<b><?php echo sprintf(__($guid, 'Resource %1$s Type'), '3') ?></b><br/>
							</td>
							<td class="right">
								<input type="radio" name="type3" value="Link" class="type3" /> Link
								<input type="radio" name="type3" value="File" class="type3" /> File
							</td>
						</tr>
						<tr id="resource3URL">
							<td>
								<b><?php echo sprintf(__($guid, 'Resource %1$s URL'), '3') ?></b><br/>
							</td>
							<td class="right">
								<input name="url3" id="url3" maxlength=255 value="" type="text" class="standardWidth">
								<script type="text/javascript">
									var url3=new LiveValidation('url3');
									url3.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://" } );
								</script>
							</td>
						</tr>
						<tr id="resource3File">
							<td>
								<b><?php echo sprintf(__($guid, 'Resource %1$s File'), '3') ?></b><br/>
							</td>
							<td class="right">
								<input type="file" name="file3" id="file3">
								<script type="text/javascript">
									var file3=new LiveValidation('file3');
									file3.add( Validate.Inclusion, { within: [<?php echo $ext;
                					?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
								</script>
							</td>
						</tr>
						<tr>
							<td class="right" colspan=2>
								<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php

            }
        }
    }
}
?>
