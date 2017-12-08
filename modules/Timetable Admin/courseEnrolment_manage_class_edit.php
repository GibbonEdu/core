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

use Gibbon\Forms\Form;
use Gibbon\Forms\Prefab\BulkActionForm;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
    $gibbonCourseID = $_GET['gibbonCourseID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    if ($gibbonCourseClassID == '' or $gibbonCourseID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonCourseID' => $gibbonCourseID, 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourseClass, gibbonCourse, gibbonSchoolYear WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID';
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
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/courseEnrolment_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Enrolment by Class')."</a> > </div><div class='trailEnd'>".sprintf(__($guid, 'Edit %1$s.%2$s Enrolment'), $row['courseNameShort'], $row['name']).'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            echo '<h2>';
            echo __($guid, 'Add Participants');
            echo '</h2>'; ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_edit_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Participants') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
						</td>
						<td class="right">
							<select name="Members[]" id="Members[]" multiple class='standardWidth' style="height: 150px">
								<optgroup label='--<?php echo __($guid, 'Enrolable Students') ?>--'>
								<?php
                                try {
                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                    $sqlSelectWhere = '';
                                    if ($row['gibbonYearGroupIDList'] != '') {
                                        $years = explode(',', $row['gibbonYearGroupIDList']);
                                        for ($i = 0; $i < count($years); ++$i) {
                                            if ($i == 0) {
                                                $dataSelect[$years[$i]] = $years[$i];
                                                $sqlSelectWhere = $sqlSelectWhere.'AND (gibbonYearGroupID=:'.$years[$i];
                                            } else {
                                                $dataSelect[$years[$i]] = $years[$i];
                                                $sqlSelectWhere = $sqlSelectWhere.' OR gibbonYearGroupID=:'.$years[$i];
                                            }

                                            if ($i == (count($years) - 1)) {
                                                $sqlSelectWhere = $sqlSelectWhere.')';
                                            }
                                        }
                                    } else {
                                        $sqlSelectWhere = ' FALSE';
                                    }
                                    $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID $sqlSelectWhere ORDER BY name, surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
								while ($rowSelect = $resultSelect->fetch()) {
									echo "<option value='".$rowSelect['gibbonPersonID']."'>".htmlPrep($rowSelect['name']).' - '.formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
								}
								?>
								</optgroup>
								<optgroup label='--<?php echo __($guid, 'All Users') ?>--'>
								<?php
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = "SELECT gibbonPersonID, surname, preferredName, status, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
								while ($rowSelect = $resultSelect->fetch()) {
									$expected = '';
									if ($rowSelect['status'] == 'Expected') {
										$expected = ' (Expected)';
									}
									echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')'.$expected.'</option>';
								}
								?>
								</optgroup>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Role') ?> *</b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="role">
								<option value="Student"><?php echo __($guid, 'Student') ?></option>
								<option value="Teacher"><?php echo __($guid, 'Teacher') ?></option>
								<option value="Assistant"><?php echo __($guid, 'Assistant') ?></option>
								<option value="Technician"><?php echo __($guid, 'Technician') ?></option>
								<option value="Parent"><?php echo __($guid, 'Parent') ?></option>
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
            echo '<h2>';
            echo __($guid, 'Current Participants');
            echo '</h2>';

            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT * FROM gibbonPerson, gibbonCourseClassPerson WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) AND gibbonCourseClassID=:gibbonCourseClassID AND (status='Full' OR status='Expected') AND NOT role LIKE '%left' ORDER BY role DESC, surname, preferredName";
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
                $form = BulkActionForm::create('bulkAction', $_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/courseEnrolment_manage_class_editProcessBulk.php');
                $form->addHiddenValue('gibbonCourseID', $gibbonCourseID);
                $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
                $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

                $linkParams = array(
                    'gibbonCourseID'      => $gibbonCourseID,
                    'gibbonCourseClassID' => $gibbonCourseClassID,
                    'gibbonSchoolYearID'  => $gibbonSchoolYearID,
                );

                $bulkActions = array(
                    'Copy to class' => __('Copy to class'),
                    'Mark as left'  => __('Mark as left'),
                    'Delete'        => __('Delete'),
                );

                $row = $form->addBulkActionRow($bulkActions);
                    $data= array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID<>:gibbonCourseClassID ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort";
                    $row->addSelect('gibbonCourseClassIDCopyTo')->fromQuery($pdo, $sql, $data)->setClass('shortWidth copyTo');
                $row->addSubmit(__('Go'));

                $form->toggleVisibilityByClass('copyTo')->onSelect('action')->when('Copy to class');

                $table = $form->addRow()->addTable()->setClass('colorOddEven fullWidth');

                $header = $table->addHeaderRow();
                $header->addContent(__('Name'));
                $header->addContent(__('Email'));
                $header->addContent(__('Role'));
                $header->addContent(__('Reportable'));
                $header->addContent(__('Actions'));
                $header->addCheckAll();

                while ($student = $result->fetch()) {
                    $row = $table->addRow();
                    $name = formatName('', htmlPrep($student['preferredName']), htmlPrep($student['surname']), 'Student', true);
                    if ($student['role'] == 'Student') {
                        $row->addWebLink($name)
                            ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='. $student['gibbonPersonID'].'&subpage=Timetable');
                    } else {
                        $row->addContent($name);
                    }
                    
                    $row->addContent($student['email']);
                    $row->addContent($student['role']);
                    $row->addContent($student['reportable']);
                    $col = $row->addColumn()->addClass('inline');
                    $col->addWebLink('<img title="' . __('Edit') . '" src="./themes/' . $_SESSION[$guid]['gibbonThemeName'] . '/img/config.png"/>')
                        ->setURL($_SESSION[$guid]['absoluteURL'] . '/index.php?q=/modules/' . $_SESSION[$guid]['module'] . '/courseEnrolment_manage_class_edit_edit.php')
                        ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                        ->addParams($linkParams);
                    $col->addWebLink('<img title="' . __('Delete') . '" src="./themes/' . $_SESSION[$guid]['gibbonThemeName'] . '/img/garbage.png"/>')
                        ->setURL($_SESSION[$guid]['absoluteURL'] . '/fullscreen.php?q=/modules/' . $_SESSION[$guid]['module'] . '/courseEnrolment_manage_class_edit_delete.php&width=650&height=135')
                        ->setClass('thickbox')
                        ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                        ->addParams($linkParams);
                    $row->addCheckbox('gibbonPersonID[]')->setValue($student['gibbonPersonID'])->setClass('textCenter');
                }

                echo $form->getOutput();
            }

            echo '<h2>';
            echo __($guid, 'Former Participants');
            echo '</h2>';

            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT * FROM gibbonPerson, gibbonCourseClassPerson WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) AND gibbonCourseClassID=:gibbonCourseClassID AND (status='Full' OR status='Expected') AND role LIKE '%left' ORDER BY role DESC, surname, preferredName";
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
                echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_editProcessBulk.php'>";
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Email');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Class Role');
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
                    if ($row['role'] == 'Student - Left') {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."&subpage=Timetable'>".formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true).'</a>';
                    } else {
                        echo formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true);
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $row['email'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['role'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=".$row['gibbonPersonID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=".$row['gibbonPersonID']."&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</form>';
            }
        }
    }
}
?>
