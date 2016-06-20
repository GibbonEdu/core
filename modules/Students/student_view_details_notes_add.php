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

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $allStudents = '';
    if (isset($_GET['allStudents'])) {
        $allStudents = $_GET['allStudents'];
    }

    $enableStudentNotes = getSettingByScope($connection2, 'Students', 'enableStudentNotes');
    if ($enableStudentNotes != 'Y') {
        echo "<div class='error'>";
        echo __($guid, 'You do not have access to this action.');
        echo '</div>';
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'];
        $subpage = $_GET['subpage'];
        if ($gibbonPersonID == '' or $subpage == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID);
                $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID';
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

                //Proceed!
                echo "<div class='trail'>";
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/student_view.php'>".__($guid, 'View Student Profiles')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/student_view_details.php&gibbonPersonID=$gibbonPersonID&subpage=$subpage&allStudents=$allStudents'>".formatName('', $row['preferredName'], $row['surname'], 'Student')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Student Note').'</div>';
                echo '</div>';

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }

                if ($_GET['search'] != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&subpage=$subpage&category=".$_GET['category']."&allStudents=$allStudents'>".__($guid, 'Back to Search Results').'</a>';
                    echo '</div>';
                }

                ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/student_view_details_notes_addProcess.php?gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&subpage=$subpage&category=".$_GET['category']."&allStudents=$allStudents" ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'Title') ?> *</b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<input name="title" id="title" maxlength=100 value="" type="text" class="standardWidth">
								<script type="text/javascript">
									var title=new LiveValidation('title');
									title.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<?php
                        try {
                            $dataCategories = array();
                            $sqlCategories = "SELECT * FROM gibbonStudentNoteCategory WHERE active='Y' ORDER BY name";
                            $resultCategories = $connection2->prepare($sqlCategories);
                            $resultCategories->execute($dataCategories);
                        } catch (PDOException $e) {
                        }
						if ($resultCategories->rowCount() > 0) {
							?>
							<tr>
								<td style='width: 275px'> 
									<b><?php echo __($guid, 'Category') ?> *</b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<select name="gibbonStudentNoteCategoryID" id="gibbonStudentNoteCategoryID" class="standardWidth">
										<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
										<?php
                                        while ($rowCategories = $resultCategories->fetch()) {
                                            echo "<option value='".$rowCategories['gibbonStudentNoteCategoryID']."'>".$rowCategories['name'].'</option>';
                                        }
                    					?>
									</select>
									<script type="text/javascript">
										var gibbonStudentNoteCategoryID=new LiveValidation('gibbonStudentNoteCategoryID');
										gibbonStudentNoteCategoryID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
									</script>
									 <script type="text/javascript">
										$("#gibbonStudentNoteCategoryID").change(function() {
											if ($("#gibbonStudentNoteCategoryID").val()!="Please select...") {
												$.get('<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/Students/student_view_details_notes_addAjax.php?gibbonStudentNoteCategoryID=' ?>' + $("#gibbonStudentNoteCategoryID").val(), function(data){
													if (tinyMCE.activeEditor==null) {
														if ($("textarea#note").val()=="") {
															$("textarea#note").val(data) ;
														}
													}
													else {
														if (tinyMCE.get('note').getContent()=="") {
															tinyMCE.get('note').setContent(data) ;
														}
													}
												});
											
											}
										});
									</script>
								</td>
							</tr>
							<?php

						}
						?>
						<tr>
							<td colspan=2 style='padding-top: 15px;'> 
								<b><?php echo __($guid, 'Note') ?> *</b><br/>
								<?php echo getEditor($guid,  true, 'note', '', 25, true, true, false) ?>
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
}
?>