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
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $allStudents = $_GET['allStudents'] ?? '';
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? '';

    $enableStudentNotes = getSettingByScope($connection2, 'Students', 'enableStudentNotes');
    if ($enableStudentNotes != 'Y') {
        echo "<div class='error'>";
        echo __('You do not have access to this action.');
        echo '</div>';
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $subpage = $_GET['subpage'] ?? '';
        if ($gibbonPersonID == '' or $subpage == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            
                $data = array('gibbonPersonID' => $gibbonPersonID);
                $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $student = $result->fetch();

                //Proceed!
                $page->breadcrumbs
                    ->add(__('View Student Profiles'), 'student_view.php')
                    ->add(Format::name('', $student['preferredName'], $student['surname'], 'Student'), 'student_view_details.php', ['gibbonPersonID' => $gibbonPersonID, 'subpage' => $subpage, 'allStudents' => $allStudents])
                    ->add(__('Add Student Note'));

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }

                if ($_GET['search'] != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&subpage=$subpage&category=".$_GET['category']."&allStudents=$allStudents'>".__('Back to Search Results').'</a>';
                    echo '</div>';
				}
				
				$form = Form::create('notes', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/student_view_details_notes_addProcess.php?gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&subpage=$subpage&category=".$_GET['category']."&allStudents=$allStudents");

				$form->addHiddenValue('address', $_SESSION[$guid]['address']);

				$row = $form->addRow();
					$row->addLabel('title', __('Title'));
					$row->addTextField('title')->required()->maxLength(100);

				$sql = "SELECT gibbonStudentNoteCategoryID as value, name FROM gibbonStudentNoteCategory WHERE active='Y' ORDER BY name";
				$row = $form->addRow();
					$row->addLabel('gibbonStudentNoteCategoryID', __('Category'));
					$row->addSelect('gibbonStudentNoteCategoryID')->fromQuery($pdo, $sql)->required()->placeholder();

				$row = $form->addRow();
					$column = $row->addColumn();
					$column->addLabel('note', __('Note'));
					$column->addEditor('note', $guid)->required()->setRows(25)->showMedia();
								
				$row = $form->addRow();
					$row->addFooter();
					$row->addSubmit();
				
				echo $form->getOutput();
				?>

				<script type="text/javascript">
				$("#gibbonStudentNoteCategoryID").change(function() {
					if ($("#gibbonStudentNoteCategoryID").val() != "Please select...") {
						$.get('<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/Students/student_view_details_notes_addAjax.php?gibbonStudentNoteCategoryID=' ?>' + $("#gibbonStudentNoteCategoryID").val(), function(data){
							if (tinyMCE.activeEditor==null) {
								if ($("textarea#note").val()=="") {
									$("textarea#note").val(data) ;
								}
							} else {
								if (tinyMCE.get('note').getContent()=="") {
									tinyMCE.get('note').setContent(data) ;
								}
							}
						});
					
					}
				});
				</script>

				<?php
            }
        }
    }
}
