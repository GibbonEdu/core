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
use Gibbon\UI\Chart\Chart;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationContributionGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/investigations_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $page->breadcrumbs
            ->add(__('Manage Investigations'), 'investigations_manage.php')
            ->add(__('Edit'));
        $page->scripts->add('chart');

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

        $gibbonINInvestigationID = $_GET['gibbonINInvestigationID'] ?? '';
        if (empty($gibbonINInvestigationID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {
            // Validate the database record exist
            $investigationGateway = $container->get(INInvestigationGateway::class);
            $investigation = $investigationGateway->getInvestigationByID($gibbonINInvestigationID);

            if (empty($investigation)) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                $canEdit = false ;
                if ($highestAction == 'Manage Investigations_all' || ($highestAction == 'Manage Investigations_my' && ($investigation['gibbonPersonIDCreator'] == $session->get('gibbonPersonID')))) {
                    $canEdit = true ;
                }

                $isTutor = false ;
                if ($investigation['gibbonPersonIDTutor'] == $session->get('gibbonPersonID') || $investigation['gibbonPersonIDTutor2'] == $session->get('gibbonPersonID') || $investigation['gibbonPersonIDTutor3'] == $session->get('gibbonPersonID')) {
                    $isTutor = true ;
                }

                if (!$canEdit && !$isTutor) {
                    $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                } else {

                    if ($gibbonPersonID != '' or $gibbonFormGroupID != '' or $gibbonYearGroupID != '') {
                        $params = [
                            "gibbonPersonID" => $gibbonPersonID,
                            "gibbonFormGroupID" => $gibbonFormGroupID,
                            "gibbonYearGroupID" => $gibbonYearGroupID
                        ];
                        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Individual Needs', 'investigations_manage.php')->withQueryParams($params));
                    }

                    $form = Form::create('addform', $session->get('absoluteURL')."/modules/Individual Needs/investigations_manage_editProcess.php?gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID");
                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->addHiddenValue('address', "/modules/Individual Needs/investigations_manage_edit.php");
                    $form->addHiddenValue('gibbonINInvestigationID', $gibbonINInvestigationID);
                    $form->addRow()->addHeading('Basic Information', __('Basic Information'));

                    //Student
                    $row = $form->addRow();
                    	$row->addLabel('gibbonPersonIDStudent', __('Student'));
                    	$row->addSelectStudent('gibbonPersonIDStudent', $session->get('gibbonSchoolYearID'))->placeholder()->selected($gibbonPersonID)->required()->readonly();

                    //Status
                    $row = $form->addRow();
                    	$row->addLabel('statusText', __('Status'));
                    	$row->addTextField('statusText')->setValue(__($investigation['status']))->required()->readonly();

                    //Date
                    $row = $form->addRow();
                    	$row->addLabel('date', __('Date'));
                    	$row->addDate('date')->setValue(date($session->get('i18n')['dateFormatPHP']))->required()->readonly();

            		//Reason
                    $row = $form->addRow();
                        $column = $row->addColumn();
                        $column->addLabel('reason', __('Reason'))->description(__('Why should this student\'s individual needs be investigated?'));;
                    	$column->addTextArea('reason')->setRows(5)->setClass('fullWidth')->required()->readonly(!$canEdit || $investigation['status'] != 'Referral');

                    //Strategies Tried
                    $row = $form->addRow();
                    	$column = $row->addColumn();
                    	$column->addLabel('strategiesTried', __('Strategies Tried'));
                    	$column->addTextArea('strategiesTried')->setRows(5)->setClass('fullWidth')->readonly(!$canEdit || $investigation['status'] != 'Referral');

                    //Parents Informed?
                    $row = $form->addRow();
                        $row->addLabel('parentsInformed', __('Parents Informed?'))->description(__('For example, via a phone call, email, Markbook, meeting or other means.'));
                        $row->addYesNo('parentsInformed')->required()->readonly(!$canEdit || $investigation['status'] != 'Referral')->placeholder();

                    $form->toggleVisibilityByClass('parentsInformedYes')->onSelect('parentsInformed')->when('Y');
                    $form->toggleVisibilityByClass('parentsInformedNo')->onSelect('parentsInformed')->when('N');

                    //Parent Response
                    $row = $form->addRow()->addClass('parentsInformedYes');
                    	$column = $row->addColumn();
                    	$column->addLabel('parentsResponseYes', __('Parent Response'));
                    	$column->addTextArea('parentsResponseYes')->setName('parentsResponse')->setRows(5)->setClass('fullWidth')->readonly(!$canEdit || $investigation['status'] != 'Referral');

                    $row = $form->addRow()->addClass('parentsInformedNo');
                    	$column = $row->addColumn();
                    	$column->addLabel('parentsResponseNo', __('Reason'))->description(__('Reasons why parents are not aware of the situation.'));
                    	$column->addTextArea('parentsResponseNo')->setName('parentsResponse')->setRows(5)->setClass('fullWidth')->readonly(!$canEdit || $investigation['status'] != 'Referral')->required();

                    //Form Tutor Resolution
                    if ($investigation['status'] == 'Resolved' || ($investigation['status'] == 'Referral' && $isTutor)) {
                        $form->addRow()->addHeading('Form Tutor Resolution', __('Form Tutor Resolution'));
                        if ($isTutor && $investigation['status'] == 'Referral') {
                            $row = $form->addRow();
                                $row->addLabel('resolvable', __('Resolvable?'))->description(__('Is form tutor able to resolve without further input? If no, further investigation will be launched.'));
                                $row->addYesNo('resolvable')->required()->placeholder();

                                $form->toggleVisibilityByClass('resolutionDetails')->onSelect('resolvable')->when('Y');
                        }

                        $form->toggleVisibilityByClass('invitationDetails')->onSelect('resolvable')->when('N');

                        //Resolvable by tutor
                        $row = $form->addRow()->addClass('resolutionDetails');
                            $column = $row->addColumn();
                            $column->addLabel('resolutionDetails', __('Resolution Details'));
                            $column->addTextArea('resolutionDetails')->setRows(5)->setClass('fullWidth')->readonly(!$isTutor || $investigation['status'] != 'Referral');

                        //Not resolvable by tutor
                        $resultClass = $investigationGateway->queryTeachersByInvestigation($investigation['gibbonSchoolYearID'], $investigation['gibbonPersonIDStudent']);

                        $resultHOY = $investigationGateway->queryHOYByInvestigation($investigation['gibbonSchoolYearID'], $investigation['gibbonPersonIDStudent']);

                        if ($resultClass->rowCount() < 1 && $resultHOY->rowCount() < 1) {
                            $form->addRow()->addClass('invitationDetails')->addAlert(__('There are no records to display.'), 'warning');

                        }
                        else {
                            $row = $form->addRow()->addClass('invitationDetails');
                            $row->addLabel('invitation', __('Invite Input'))->description(__('Which teachers would you like to gather input from?'));
                            $column = $row->addColumn()->setClass('flex-col items-end');
                            if ($resultHOY->rowCount() == 1) {
                                $rowHOY = $resultHOY->fetch();
                                $column->addCheckbox('gibbonPersonIDHOY')
                                    ->setName('gibbonPersonIDHOY')
                                    ->setValue($rowHOY['gibbonPersonID'])
                                    ->description(Format::name('', $rowHOY['preferredName'], $rowHOY['surname'], 'Student', false).' ('.__('Head of Year').')')
                                    ->readonly(!$isTutor)
                                    ->checked($rowHOY['gibbonPersonID']);
                            }
                            while ($rowClass = $resultClass->fetch()) {
                                $column->addCheckbox('gibbonCourseClassPersonID'.$rowClass['gibbonCourseClassPersonID'])
                                    ->setName('gibbonCourseClassPersonID[]')
                                    ->setValue($rowClass['gibbonPersonID'].'-'.$rowClass['gibbonCourseClassPersonID'])
                                    ->description(Format::name('', $rowClass['preferredName'], $rowClass['surname'], 'Student', false).' ('.$rowClass['course'].'.'.$rowClass['class'].')')
                                    ->readonly(!$isTutor)
                                    ->checked($rowClass['gibbonPersonID'].'-'.$rowClass['gibbonCourseClassPersonID']);
                            }
                        }
                    }

                    if ($investigation['status'] == 'Investigation' || $investigation['status'] == 'Investigation Complete') {
                        $form->addRow()->addHeading('Investigation Details', __('Investigation Details'));

                        $contributionsGateway = $container->get(INInvestigationContributionGateway::class);
                        $criteria2 = $contributionsGateway->newQueryCriteria()
                            ->sortBy(['course', 'class']);
                        $contributions = $contributionsGateway->queryContributionsByInvestigation($criteria2, $gibbonINInvestigationID);

                        //Response overview table
                        $table = DataTable::createPaginated('responseOverviewTable', $criteria2);

                        $table->modifyRows(function ($investigations, $row) {
                            if ($investigations['status'] == 'Complete') $row->addClass('success');
                            if ($investigations['status'] == 'Pending') $row->addClass('warning');
                            return $row;
                        });

                        $table->addExpandableColumn('comment')
                            ->format(function ($investigations) {
                                $output = '';
                                if (!empty($investigations['cognition'])) {
                                    $output .= '<br/><strong>'.__('Cognition').'</strong><br/>';
                                    $output .= '<ul>';
                                        $output .= '<li>'.nl2br(__($investigations['cognition'])).'</li>';
                                    $output .= '</ul>';
                                }
                                $fields = getInvestigationCriteriaStrands();
                                foreach ($fields as $field) {
                                    if (!empty($investigations[$field['name']])) {
                                        $output .= '<br/><strong>'.__($field['nameHuman']).'</strong><br/>';
                                        $output .= '<ul>';
                                        foreach (unserialize($investigations[$field['name']]) as $entry) {
                                            $output .= '<li>'.__($entry).'</li>';
                                        }
                                        $output .= '</ul>';
                                    }
                                }
                                if (!empty($investigations['comment'])) {
                                    $output .= '<br/><strong>'.__('Comment').'</strong><br/>';
                                    $output .= '<ul>';
                                        $output .= '<li>'.nl2br(__($investigations['comment'])).'</li>';
                                    $output .= '</ul>';
                                }
                                return $output;
                            });
                        $table->addColumn('name', __('Name'))
                            ->format(function ($person) {
                                return Format::name('', $person['preferredName'], $person['surname'], 'Student', true);
                            });
                        $table->addColumn('type', __('Type'))->translatable();
                        $table->addColumn('class', __('Class'))
                            ->format(function ($investigations) {
                                if ($investigations['type'] == 'Teacher') {
                                    return ($investigations['course'].'.'.$investigations['class']);
                                }
                            });
                        $table->addColumn('status', __('Status'))->translatable();

                        //Response overview row
                        $row = $form->addRow();
                            $column = $row->addColumn();
                            $column->addLabel('responseOverview', __('Response Details'));
                            $column->addContent($table->render($contributions));


                        //CHARTS!
                        $strands = getInvestigationCriteriaStrands(true);
                        $criteria3 = $contributionsGateway->newQueryCriteria();
                        $stats = $contributionsGateway->queryInvestigationStatistics($criteria3, $gibbonINInvestigationID);

                        $count = 0 ;
                        for ($i = 0; $i < count($strands); $i++) {
                            //Chart

                            $options = getInvestigationCriteriaArray($stats[$i]['nameHuman']) ;
                            $optionsLabels = [];
                            foreach ($options as $option) {
                                array_push($optionsLabels, __($option));
                            }
                            $chart = Chart::create($stats[$i]['name'].'Chart', 'doughnut')
                                ->setOptions(['height' => 150])
                                ->setLabels($optionsLabels);

                            $data = array();
                            foreach ($stats[$i]['data'] as $stat) {
                                array_push($data, $stat);
                            }

                            $chart->addDataset('pie')
                                ->setData($data);

                            //Row
                            $row = $form->addRow();
                                $column = $row->addColumn();
                                $column->addLabel($stats[$i]['name'].'Summary', __($stats[$i]['nameHuman']));
                                $column->addContent($chart->render());
                        }
                    }

                    $row = $form->addRow();
                    	$row->addFooter();
                        if ($investigation['status'] == 'Referral') {
                            $row->addSubmit();
                        }

                    $form->loadAllValuesFrom($investigation);

                    echo $form->getOutput();
                }
            }
        }
    }
}
