<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Departments/department_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if courseschool year specified
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'] ?? '';
    if ($gibbonDepartmentID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
            $sql = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $values = $result->fetch();

            $urlParams = ['gibbonDepartmentID' => $gibbonDepartmentID];

            $page->breadcrumbs
                ->add($values['name'], 'department.php', $urlParams)
                ->add(__('Edit Department'));

            $page->return->addReturns(['error3' => __('Your request failed due to an attachment error.')]);

            //Get role within learning area
            $role = getRole($session->get('gibbonPersonID'), $gibbonDepartmentID, $connection2);

            if ($role != 'Coordinator' and $role != 'Assistant Coordinator' and $role != 'Teacher (Curriculum)' and $role != 'Director' and $role != 'Manager') {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {

				$form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/department_editProcess.php?gibbonDepartmentID='.$gibbonDepartmentID);

				$form->addHiddenValue('address', $session->get('address'));

				$form->addRow()->addHeading('Overview', __('Overview'));
				$form->addRow()->addEditor('blurb', $guid)->setRows(20)->setValue($values['blurb']);

				$form->addRow()->addHeading('Current Resources', __('Current Resources'));

				$data = array('gibbonDepartmentID' => $gibbonDepartmentID);
				$sql = 'SELECT * FROM gibbonDepartmentResource WHERE gibbonDepartmentID=:gibbonDepartmentID ORDER BY name';
				$result = $pdo->executeQuery($data, $sql);

				if ($result->rowCount() == 0) {
					$form->addRow()->addAlert(__('There are no records to display.'), 'error');
				} else {
					$table = $form->addRow()->addTable()->addClass('fullWidth colorOddEven');

					$row = $table->addHeaderRow();
						$row->addContent(__('Name'));
						$row->addContent(__('Type'));
						$row->addContent(__('Actions'))->setClass('shortWidth');

					while ($resource = $result->fetch()) {
						$href = ($resource['type'] == 'Link')? $resource['url'] : $session->get('absoluteURL').'/'.$resource['url'];

						$row = $table->addRow();
							$row->addContent($resource['name'])->wrap('<a href="'.$href.'" target="blank">', '</a>');
							$row->addContent($resource['type']);
							$row->addContent("<img title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/>")->wrap("<a onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")' href='".$session->get('absoluteURL').'/modules/'.$session->get('module').'/department_edit_resource_deleteProcess.php?gibbonDepartmentResourceID='.$resource['gibbonDepartmentResourceID'].'&gibbonDepartmentID='.$resource['gibbonDepartmentID'].'&address='.$_GET['q']."'>", '</a>');
					}
				}

				for ($i = 1; $i <= 3; $i++) {
					$row = $form->addRow();
						$row->addHeading(sprintf(__('New Resource %1$s'), $i));
						$row->addClass("resource{$i}Row resource{$i}RowTop");

					$row = $form->addRow()->addClass("resource{$i}Row resource{$i}RowTop");
						$row->addLabel("name{$i}", sprintf(__('Resource %1$s Name'), $i));
						$row->addTextField("name{$i}");

					$row = $form->addRow()->addClass("resource{$i}Row resource{$i}RowTop");
                        $row->addLabel("type{$i}", sprintf(__('Resource %1$s Type'), $i));
						$row->addRadio("type{$i}")->fromArray(array('Link' => __('Link'), 'File' => __('File')))->inline();

					$form->toggleVisibilityByClass("resource{$i}TypeLink")->onRadio("type{$i}")->when('Link');
					$row = $form->addRow()->addClass("resource{$i}Row resource{$i}TypeLink");
                        $row->addLabel("url{$i}", sprintf(__('Resource %1$s URL'), $i));
						$row->addURL("url{$i}");

					$form->toggleVisibilityByClass("resource{$i}TypeFile")->onRadio("type{$i}")->when('File');
					$row = $form->addRow()->addClass("resource{$i}Row resource{$i}TypeFile");
                        $row->addLabel("file{$i}", sprintf(__('Resource %1$s File'), $i));
						$row->addFileUpload("file{$i}");

					if ($i < 3) {
						$form->toggleVisibilityByClass("resource{$i}Button")->onRadio("type{$i}")->when(array('Link', 'File'));
						$row = $form->addRow()->addClass("resource{$i}Row resource{$i}Button");
						$row->addButton(__('Add Another Resource'))
							->onClick("$('.resource".($i+1)."RowTop').show();$('.resource".$i."Button').hide();")
							->addClass('right');
					}
				}

				$row = $form->addRow();
					$row->addSubmit();

				echo $form->getOutput();
				?>

				<script type="text/javascript">
				$(document).ready(function(){
					$('.resource2Row').hide();
					$('.resource3Row').hide();
				});
				</script>
				<?php
            }
        }
    }
}
