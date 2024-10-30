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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

//Search & Filters
$search = null;
if (isset($_GET['search'])) {
    $search = $_GET['search'] ?? '';
}
$filter2 = null;
if (isset($_GET['filter2'])) {
    $filter2 = $_GET['filter2'] ?? '';
}

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        if ($highestAction != 'Manage Rubrics_viewEditAll' and $highestAction != 'Manage Rubrics_viewAllEditLearningArea') {
            $page->addError(__('You do not have access to this action.'));
        } else {
            //Proceed!
            $page->breadcrumbs
                ->add(__('Manage Rubrics'), 'rubrics.php', ['search' => $search, 'filter2' => $filter2])
                ->add(__('Edit Rubric'));

            if (isset($_GET['addReturn'])) {
                $addReturn = $_GET['addReturn'] ?? '';
            } else {
                $addReturn = '';
            }
            $addReturnMessage = '';
            $class = 'error';
            if (!($addReturn == '')) {
                if ($addReturn == 'success0') {
                    $addReturnMessage = __('Your request was completed successfully.');
                    $class = 'success';
                }
                echo "<div class='$class'>";
                echo $addReturnMessage;
                echo '</div>';
            }

            if (isset($_GET['columnDeleteReturn'])) {
                $columnDeleteReturn = $_GET['columnDeleteReturn'] ?? '';
            } else {
                $columnDeleteReturn = '';
            }
            $columnDeleteReturnMessage = '';
            $class = 'error';
            if (!($columnDeleteReturn == '')) {
                if ($columnDeleteReturn == 'fail0') {
                    $columnDeleteReturnMessage = __('Your request failed because you do not have access to this action.');
                } elseif ($columnDeleteReturn == 'fail1') {
                    $columnDeleteReturnMessage = __('Your request failed because your inputs were invalid.');
                } elseif ($columnDeleteReturn == 'fail2') {
                    $columnDeleteReturnMessage = __('Your request failed due to a database error.');
                } elseif ($columnDeleteReturn == 'fail3') {
                    $columnDeleteReturnMessage = __('Your request failed because your inputs were invalid.');
                } elseif ($columnDeleteReturn == 'success0') {
                    $columnDeleteReturnMessage = __('Your request was completed successfully.');
                    $class = 'success';
                }
                echo "<div class='$class'>";
                echo $columnDeleteReturnMessage;
                echo '</div>';
            }

            if (isset($_GET['rowDeleteReturn'])) {
                $rowDeleteReturn = $_GET['rowDeleteReturn'] ?? '';
            } else {
                $rowDeleteReturn = '';
            }
            $rowDeleteReturnMessage = '';
            $class = 'error';
            if (!($rowDeleteReturn == '')) {
                if ($rowDeleteReturn == 'fail0') {
                    $rowDeleteReturnMessage = __('Your request failed because you do not have access to this action.');
                } elseif ($rowDeleteReturn == 'fail1') {
                    $rowDeleteReturnMessage = __('Your request failed because your inputs were invalid.');
                } elseif ($rowDeleteReturn == 'fail2') {
                    $rowDeleteReturnMessage = __('Your request failed due to a database error.');
                } elseif ($rowDeleteReturn == 'fail3') {
                    $rowDeleteReturnMessage = __('Your request failed because your inputs were invalid.');
                } elseif ($rowDeleteReturn == 'success0') {
                    $rowDeleteReturnMessage = __('Your request was completed successfully.');
                    $class = 'success';
                }
                echo "<div class='$class'>";
                echo $rowDeleteReturnMessage;
                echo '</div>';
            }

            if (isset($_GET['cellEditReturn'])) {
                $cellEditReturn = $_GET['cellEditReturn'] ?? '';
            } else {
                $cellEditReturn = '';
            }
            $cellEditReturnMessage = '';
            $class = 'error';
            if (!($cellEditReturn == '')) {
                if ($cellEditReturn == 'fail0') {
                    $cellEditReturnMessage = __('Your request failed because you do not have access to this action.');
                } elseif ($cellEditReturn == 'fail1') {
                    $cellEditReturnMessage = __('Your request failed because your inputs were invalid.');
                } elseif ($cellEditReturn == 'fail2') {
                    $cellEditReturnMessage = __('Your request failed due to a database error.');
                } elseif ($cellEditReturn == 'fail3') {
                    $cellEditReturnMessage = __('Your request failed because your inputs were invalid.');
                } elseif ($cellEditReturn == 'fail5') {
                    $cellEditReturnMessage = __('Your request was successful, but some data was not properly saved.');
                } elseif ($cellEditReturn == 'success0') {
                    $cellEditReturnMessage = __('Your request was completed successfully.');
                    $class = 'success';
                }
                echo "<div class='$class'>";
                echo $cellEditReturnMessage;
                echo '</div>';
            }

            //Check if gibbonRubricID specified
            $gibbonRubricID = $_GET['gibbonRubricID'] ?? '';
            if ($gibbonRubricID == '') {
                $page->addError(__('You have not specified one or more required parameters.'));
            } else {

                    $data = array('gibbonRubricID' => $gibbonRubricID);
                    $sql = 'SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);

                if ($result->rowCount() != 1) {
                    $page->addError(__('The specified record does not exist.'));
                } else {
                    //Let's go!
                    $values = $result->fetch();

                    if ($search != '' or $filter2 != '') {
                         $params = [
                            "search" => $search,
                            "filter2" => $filter2,
                        ];
                        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Rubrics', 'rubrics.php')->withQueryParams($params));
                    }

                    $form = Form::create('addRubric', $session->get('absoluteURL').'/modules/'.$session->get('module').'/rubrics_editProcess.php?gibbonRubricID='.$gibbonRubricID.'&search='.$search.'&filter2='.$filter2);
                    $form->setFactory(DatabaseFormFactory::create($pdo));

                    $form->addHiddenValue('address', $session->get('address'));

                    $form->addRow()->addHeading('Rubric Basics', __('Rubric Basics'));

                    $row = $form->addRow();
                        $row->addLabel('scope', 'Scope');
                        $row->addTextField('scope')->required()->readOnly();

                    if ($values['scope'] == 'Learning Area') {
                        $sql = "SELECT name FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID";
                        $result = $pdo->executeQuery(array('gibbonDepartmentID' => $values['gibbonDepartmentID']), $sql);
                        $learningArea = ($result->rowCount() > 0)? $result->fetchColumn(0) : $values['gibbonDepartmentID'];

                        $form->addHiddenValue('gibbonDepartmentID', $values['gibbonDepartmentID']);
                        $row = $form->addRow();
                            $row->addLabel('departmentName', __('Learning Area'));
                            $row->addTextField('departmentName')->required()->readOnly()->setValue($learningArea);
                    }

                    $row = $form->addRow();
                        $row->addLabel('name', __('Name'));
                        $row->addTextField('name')->maxLength(50)->required();

                    $row = $form->addRow();
                        $row->addLabel('active', __('Active'));
                        $row->addYesNo('active')->required();

                    $sql = "SELECT DISTINCT category FROM gibbonRubric ORDER BY category";
                    $result = $pdo->executeQuery(array(), $sql);
                    $categories = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN, 0) : array();

                    $row = $form->addRow();
                        $row->addLabel('category', __('Category'));
                        $row->addTextField('category')->maxLength(100)->autocomplete($categories);

                    $row = $form->addRow();
                        $row->addLabel('description', __('Description'));
                        $row->addTextArea('description')->setRows(5);

                    $row = $form->addRow();
                        $row->addLabel('gibbonYearGroupIDList[]', __('Year Groups'));
                        $row->addCheckboxYearGroup('gibbonYearGroupIDList[]')->addCheckAllNone()->loadFromCSV($values);

                    $sql = "SELECT name FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID";
                    $result = $pdo->executeQuery(array('gibbonScaleID' => $values['gibbonScaleID']), $sql);
                    $gradeScaleName = ($result->rowCount() > 0)? $result->fetchColumn(0) : $values['gibbonScaleID'];

                    $form->addHiddenValue('gibbonScaleID', $values['gibbonScaleID']);
                    $row = $form->addRow();
                        $row->addLabel('gradeScale', __('Grade Scale'))->description(__('Link columns to grades on a scale?'));
                        $row->addTextField('gradeScale')->readOnly()->setValue($gradeScaleName);

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit();

                    $form->loadAllValuesFrom($values);

                    echo $form->getOutput();

					echo '<a name="rubricDesign"></a>';
					echo '<table class="smallIntBorder" cellspacing="0" style="width:100%">';
						echo '<tr class="break">';
							echo '<td colspan=2>';
								echo '<h3>'. __('Rubric Design') .'</h3>';
							echo '</td>';
						echo '</tr>';
					echo '</table>';

                    echo rubricEdit($guid, $connection2, $gibbonRubricID, $gradeScaleName, $search, $filter2);
                }
            }
        }
    }
}
