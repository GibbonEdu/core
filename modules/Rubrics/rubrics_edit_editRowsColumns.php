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
use Gibbon\Domain\Rubrics\RubricGateway;
use Gibbon\Domain\Planner\OutcomeGateway;
use Gibbon\Domain\Departments\DepartmentGateway;
use Gibbon\Domain\School\GradeScaleGateway;

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

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_edit_editRowsColumns.php') == false) {
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
            $gibbonRubricID = $_GET['gibbonRubricID'] ?? '';
            
            $params = [
                "gibbonRubricID" => $gibbonRubricID,
                "search" => $search,
                "filter2" => $filter2,
                "sidebar" => false
            ];     
                
            $page->breadcrumbs
                ->add(__('Manage Rubrics'), 'rubrics.php', ['search' => $search, 'filter2' => $filter2])
                ->add(__('Edit Rubric'), 'rubrics_edit.php', $params)
                ->add(__('Edit Rubric Rows & Columns'));

            if ($search != '' or $filter2 != '') {
                $page->navigator->addHeaderAction('back', __('Back'))
                    ->setURL('/modules/Rubrics/rubrics_edit.php')
                    ->addParams($params);
            }

            //Check if gibbonRubricID specified
            if ($gibbonRubricID == '') {
                $page->addError(__('You have not specified one or more required parameters.'));
            } else {
                
                $result = $container->get(RubricGateway::class)->selectBy(['gibbonRubricID' => $gibbonRubricID]);

                if ($result->rowCount() != 1) {
                    $page->addError(__('The specified record does not exist.'));
                } else {
                    //Let's go!
					$values = $result->fetch(); 
					
					$form = Form::create('addRubric', $session->get('absoluteURL').'/modules/'.$session->get('module').'/rubrics_edit_editRowsColumnsProcess.php?gibbonRubricID='.$gibbonRubricID.'&search='.$search.'&filter2='.$filter2);

                    $form->addHiddenValue('address', $session->get('address'));
                    
                    $form->addRow()->addHeading('Rubric Basics', __('Rubric Basics'));

                    $row = $form->addRow();
                        $row->addLabel('scope', 'Scope');
                        $row->addTextField('scope')->required()->readOnly();

                    if ($values['scope'] == 'Learning Area') {

                        $result = $container->get(DepartmentGateway::class)->selectBy(['gibbonDepartmentID' => $values['gibbonDepartmentID']], ['name']);

                        $learningArea = ($result->rowCount() > 0)? $result->fetchColumn(0) : $values['gibbonDepartmentID'];

                        $form->addHiddenValue('gibbonDepartmentID', $values['gibbonDepartmentID']);
                        $row = $form->addRow();
                            $row->addLabel('departmentName', __('Learning Area'));
                            $row->addTextField('departmentName')->required()->readOnly()->setValue($learningArea);
					}

					$row = $form->addRow();
                        $row->addLabel('name', __('Name'));
						$row->addTextField('name')->maxLength(50)->required()->readOnly();
						
					$form->addRow()->addHeading('Rows', __('Rows'));

					// Get outcomes by year group

					$result = $container->get(OutcomeGateway::class)->selectOutcomesByYearGroup($values['gibbonYearGroupIDList']);
					
					// Build a set of outcomes grouped by scope
					$outcomes = ($result->rowCount() > 0)? $result->fetchAll() : array();
					$outcomes = array_reduce($outcomes, function($group, $item) {
						$name = !empty($item['category'])? $item['category'].' - '.$item['name'] : $item['name'];
 						$group[$item['scope'].' '.__('Outcomes')][$item['gibbonOutcomeID']] = $name;
						return $group;
					}, array());

					$typeOptions = array('Standalone' => __('Standalone'), 'Outcome Based' => __('Outcome Based'));

                    $result = $container->get(RubricGateway::class)->selectRowsInfoByRubric($gibbonRubricID);

					if ($result->rowCount() <= 0) {
						$form->addRow()->addAlert(__('There are no records to display.'), 'error');
					} else {
						$count = 0;
						while ($rubricRow = $result->fetch()) {
							$type = ($rubricRow['gibbonOutcomeID'] != '')? 'Outcome Based' : 'Standalone';

							$row = $form->addRow();
								$row->addLabel('rowName'.$count, sprintf(__('Row %1$s Title'), ($count + 1)) );
                                $column = $row->addColumn()->addClass('flex-col');
                                
                                $column->addRadio('type'.$count)->fromArray($typeOptions)->inline()->checked($type);
                                $col = $column->addColumn()->addClass('flex');
								$col->addTextField('rowTitle['.$count.']')
									->setID('rowTitle'.$count)
									->addClass('flex-1 rowTitle'.$count)
									->maxLength(40)
									->required()
									->setValue($rubricRow['title']);
								$col->addSelect('gibbonOutcomeID['.$count.']')
									->setID('gibbonOutcomeID'.$count)
									->addClass('flex-1 gibbonOutcomeID'.$count)
									->fromArray($outcomes)
									->required()
									->placeholder()
                                    ->selected($rubricRow['gibbonOutcomeID']);
                                    
                                $column->addColor('rowColor['.$count.']')
                                    ->setID('rowColor'.$count)
                                    ->setValue($rubricRow['backgroundColor'])
                                    ->setTitle(__('Background Colour'));

							$form->toggleVisibilityByClass('rowTitle'.$count)->onRadio('type'.$count)->when('Standalone');
							$form->toggleVisibilityByClass('gibbonOutcomeID'.$count)->onRadio('type'.$count)->when('Outcome Based');
							$form->addHiddenValue('gibbonRubricRowID['.$count.']', $rubricRow['gibbonRubricRowID']);
								
							$count++;
						}
					}

                    $row = $form->addRow();
                        $row->addHeading('Columns', __('Columns'));
                        $row->addContent(__('Visualise?'))->setClass('font-bold text-center');
                        $row->addContent()->setClass('w-full sm:max-w-sm');

                    $result = $container->get(RubricGateway::class)->selectsColumnsInfoByRubric($gibbonRubricID);
                   
					if ($result->rowCount() <= 0) {
						$form->addRow()->addAlert(__('There are no records to display.'), 'error');
					} else {
						$count = 0;
						while ($rubricColumn = $result->fetch()) {
							$row = $form->addRow();
                            $row->addLabel('columnName'.$count, sprintf(__('Column %1$s Title'), ($count + 1)));
                            
                            
                            $row->addCheckbox('columnVisualise['.$count.']')
                                ->setValue('Y')
                                ->alignCenter()
                                ->checked($rubricColumn['visualise'])
                                ->setClass('textCenter flex-1 self-center');
                            $column = $row->addColumn()->setClass('sm:max-w-sm');
                            $col = $column->addColumn()->setClass('flex flex-col -mb-1');

							// Handle non-grade scale columns as a text field, otherwise a dropdown
							if ($values['gibbonScaleID'] == '') {
								$col->addTextField('columnTitle['.$count.']')
									->setID('columnTitle'.$count)
                                    ->maxLength(20)
									->required()
                                    ->setClass('flex-1 w-full')
									->setValue($rubricColumn['title']);
							} else {
                                $results = $container->get(GradeScaleGateway::class)->selectGradesByScale($values['gibbonScaleID']);
                                
								$col->addSelect('gibbonScaleGradeID['.$count.']')
									->setID('gibbonScaleGradeID'.$count)
									->fromResults($results)
                                    ->required()
                                    ->setClass('flex-1 w-full')
									->selected($rubricColumn['gibbonScaleGradeID']);
                            }
                            
                            $col->addColor('columnColor['.$count.']')
                                ->setID('columnColor'.$count)
                                ->setValue($rubricColumn['backgroundColor'])
                                ->setTitle(__('Background Colour'));

							$form->addHiddenValue('gibbonRubricColumnID['.$count.']', $rubricColumn['gibbonRubricColumnID']);

							$count++;
						}
					}

					$row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit();

                    $form->loadAllValuesFrom($values);
                    
					echo $form->getOutput();
                }
            }
        }
    }
}
