<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright 2010, Gibbon Foundation
Gibbon, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Interventions\INInterventionGateway;
use Gibbon\Domain\Interventions\INInterventionEligibilityAssessmentGateway;
use Gibbon\Domain\Interventions\INEligibilityAssessmentTypeGateway;
use Gibbon\FileUploader;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_eligibility_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        // Proceed!
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $status = $_GET['status'] ?? '';
        $gibbonINInterventionID = $_GET['gibbonINInterventionID'] ?? '';
        $gibbonPersonIDStudent = $_GET['gibbonPersonIDStudent'] ?? '';
        $action = $_GET['action'] ?? '';

        $page->breadcrumbs
            ->add(__('Manage Interventions'), 'interventions_manage.php', [
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Edit Intervention'), 'interventions_manage_edit.php', [
                'gibbonINInterventionID' => $gibbonINInterventionID,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Edit Eligibility Assessment'));

        // Check if we're creating a new assessment or editing an existing one
        if ($action == 'create' && !empty($gibbonINInterventionID)) {
            // Create a new eligibility assessment
            $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
            
            // Check if an assessment already exists
            $existingAssessment = $eligibilityAssessmentGateway->getByInterventionID($gibbonINInterventionID);
            
            if (empty($existingAssessment)) {
                // Create a new assessment
                $data = [
                    'gibbonINInterventionID' => $gibbonINInterventionID,
                    'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
                    'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
                    'status' => 'In Progress',
                    'timestampCreated' => date('Y-m-d H:i:s')
                ];
                
                $gibbonINInterventionEligibilityAssessmentID = $eligibilityAssessmentGateway->insert($data);
                
                if (!$gibbonINInterventionEligibilityAssessmentID) {
                    $page->addError(__('Could not create eligibility assessment.'));
                    return;
                }
                
                // Redirect to the edit page for the new assessment
                $url = './index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonPersonIDStudent='.$gibbonPersonIDStudent;
                header("Location: {$url}");
                exit;
            } else {
                // Assessment already exists, redirect to edit it
                $url = './index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionEligibilityAssessmentID='.$existingAssessment['gibbonINInterventionEligibilityAssessmentID'].'&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonPersonIDStudent='.$gibbonPersonIDStudent;
                header("Location: {$url}");
                exit;
            }
        }

        // Get the eligibility assessment
        $gibbonINInterventionEligibilityAssessmentID = $_GET['gibbonINInterventionEligibilityAssessmentID'] ?? '';
        
        if (empty($gibbonINInterventionEligibilityAssessmentID) && empty($gibbonINInterventionID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        if (empty($gibbonINInterventionID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        $interventionGateway = $container->get(INInterventionGateway::class);
        $intervention = $interventionGateway->getInterventionByID($gibbonINInterventionID);

        if (empty($intervention)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Check access based on the highest action level
        if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }

        // Get eligibility assessment if ID is provided
        $assessment = null;
        if (!empty($gibbonINInterventionEligibilityAssessmentID)) {
            $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
            $assessment = $eligibilityAssessmentGateway->getByID($gibbonINInterventionEligibilityAssessmentID);
            
            if (empty($assessment)) {
                $page->addError(__('The specified record cannot be found.'));
                return;
            }
        }

        // Get student details
        $studentGateway = $container->get(StudentGateway::class);
        $student = $studentGateway->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $intervention['gibbonPersonIDStudent'])->fetch();

        if (empty($student)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        $studentName = Format::name('', $student['preferredName'], $student['surname'], 'Student', true);

        // Display Contributors Section
        if (!empty($gibbonINInterventionEligibilityAssessmentID)) {
            echo '<h2>'.__('Contributors').'</h2>';
            
            // Get contributors
            $sql = "SELECT c.*, p.title, p.preferredName, p.surname, t.name as assessmentTypeName,
                    CONCAT(p.surname, ', ', p.preferredName) as contributorSort
                    FROM gibbonINInterventionEligibilityContributor AS c 
                    JOIN gibbonPerson AS p ON (c.gibbonPersonIDContributor=p.gibbonPersonID) 
                    LEFT JOIN gibbonINEligibilityAssessmentType AS t ON (c.gibbonINEligibilityAssessmentTypeID=t.gibbonINEligibilityAssessmentTypeID)
                    WHERE c.gibbonINInterventionEligibilityAssessmentID=:gibbonINInterventionEligibilityAssessmentID 
                    ORDER BY contributorSort, t.name";
            
            $result = $pdo->select($sql, ['gibbonINInterventionEligibilityAssessmentID' => $gibbonINInterventionEligibilityAssessmentID]);
            
            if ($result->rowCount() == 0) {
                echo "<div class='message warning'>".__('There are no contributors to display.')."</div>";
            } else {
                // Create a table for contributors
                $table = DataTable::create('contributors');
                $table->setTitle(__('Contributors'));
                
                $table->addColumn('contributor', __('Contributor'))
                    ->format(function($contributor) {
                        return Format::name($contributor['title'], $contributor['preferredName'], $contributor['surname'], 'Staff', false, true);
                    });
                    
                $table->addColumn('assessmentTypeName', __('Assessment Type'))
                    ->format(function ($contributor) {
                        if (empty($contributor['assessmentTypeName'])) {
                            return '<span class="tag dull">'.__('Not Selected').'</span>';
                        } else {
                            return $contributor['assessmentTypeName'];
                        }
                    });
                    
                $table->addColumn('status', __('Status'))
                    ->format(function ($contributor) {
                        if ($contributor['status'] == 'Complete') {
                            return '<span class="tag success">'.__('Complete').'</span>';
                        } else {
                            return '<span class="tag dull">'.__('Pending').'</span>';
                        }
                    });
                    
                $table->addColumn('ratings', __('Ratings'))
                    ->format(function ($contributor) use ($pdo) {
                        // If the contributor has no assessment type or is not complete, return empty
                        if (empty($contributor['gibbonINEligibilityAssessmentTypeID']) || $contributor['status'] != 'Complete') {
                            return '';
                        }
                        
                        // Get ratings for this contributor
                        $sql = "SELECT r.*, s.name as subfieldName 
                                FROM gibbonINInterventionEligibilityContributorRating AS r 
                                JOIN gibbonINEligibilityAssessmentSubfield AS s ON (r.gibbonINEligibilityAssessmentSubfieldID=s.gibbonINEligibilityAssessmentSubfieldID) 
                                WHERE r.gibbonINInterventionEligibilityContributorID=:gibbonINInterventionEligibilityContributorID 
                                ORDER BY s.sequenceNumber";
                        $result = $pdo->select($sql, ['gibbonINInterventionEligibilityContributorID' => $contributor['gibbonINInterventionEligibilityContributorID']]);
                        
                        if ($result->rowCount() == 0) {
                            return '';
                        }
                        
                        // Create a tooltip with all ratings
                        $output = '<div class="text-xs font-bold">';
                        $output .= __('Average Rating').': ';
                        
                        // Calculate average rating (excluding 0 ratings)
                        $totalRating = 0;
                        $ratingCount = 0;
                        $ratingDetails = [];
                        
                        while ($rating = $result->fetch()) {
                            if ($rating['rating'] > 0) {
                                $totalRating += $rating['rating'];
                                $ratingCount++;
                            }
                            $ratingDetails[] = $rating['subfieldName'] . ': ' . $rating['rating'];
                        }
                        
                        $averageRating = $ratingCount > 0 ? round($totalRating / $ratingCount, 1) : 0;
                        $output .= $averageRating;
                        
                        // Add tooltip with details
                        $output .= ' <i class="fas fa-info-circle fa-lg ml-2" data-toggle="tooltip" title="';
                        $output .= implode('<br/>', $ratingDetails);
                        $output .= '"></i>';
                        
                        $output .= '</div>';
                        
                        return $output;
                    });
                    
                $table->addColumn('recommendation', __('Recommendation'))
                    ->format(function($contributor) {
                        if ($contributor['recommendation'] == 'Eligible for IEP') {
                            return '<span class="tag success">'.__('Eligible for IEP').'</span>';
                        } else if ($contributor['recommendation'] == 'Needs Intervention') {
                            return '<span class="tag warning">'.__('Needs Intervention').'</span>';
                        } else {
                            return '<span class="tag dull">'.__('Pending').'</span>';
                        }
                    });
                    
                $table->addColumn('timestampCreated', __('Date'))
                    ->format(Format::using('dateTime', ['timestampCreated']));
                    
                $table->addActionColumn()
                    ->addParam('gibbonINInterventionEligibilityContributorID')
                    ->addParam('gibbonINInterventionEligibilityAssessmentID', $gibbonINInterventionEligibilityAssessmentID)
                    ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
                    ->format(function ($contributor, $actions) {
                        $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Interventions/intervention_eligibility_contributor_edit.php');
                            
                        $actions->addAction('delete', __('Delete'))
                            ->setURL('/modules/Interventions/intervention_eligibility_contributor_delete.php');
                    });
                
                // Convert the database result to an array for the DataTable
                $contributors = $result->fetchAll();
                
                // Create a DataSet object from the array
                $dataSet = new Gibbon\Domain\DataSet($contributors);
                
                echo $table->render($dataSet);
            }
            
            // Add button for adding contributors
            echo "<div class='linkTop'>";
            echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Interventions/intervention_eligibility_contributor_add.php&gibbonINInterventionEligibilityAssessmentID=$gibbonINInterventionEligibilityAssessmentID&gibbonINInterventionID=$gibbonINInterventionID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID&status=$status'>".__('Add Contributor')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$session->get('gibbonThemeName')."/img/plus.png'/></a>";
            echo "</div>";
            
            // Display Rating Scale Legend
            echo '<h3>'.__('Rating Scale').'</h3>';
            echo '<table class="smallIntBorder" cellspacing="0" style="width:100%">';
            echo '<tr><th style="width:10%">'.__('Rating').'</th><th>'.__('Description').'</th></tr>';
            echo '<tr><td>0</td><td>'.__('Not Evaluated').'</td></tr>';
            echo '<tr><td>1</td><td>'.__('No Concern').'</td></tr>';
            echo '<tr><td>2</td><td>'.__('Mild Concern').'</td></tr>';
            echo '<tr><td>3</td><td>'.__('Moderate Concern').'</td></tr>';
            echo '<tr><td>4</td><td>'.__('Significant Concern').'</td></tr>';
            echo '<tr><td>5</td><td>'.__('High Concern').'</td></tr>';
            echo '</table>';
            
            // Display Assessment Summary
            echo '<h3>'.__('Assessment Summary').'</h3>';
            
            // Get all contributors with complete status
            $sql = "SELECT c.*, t.name as assessmentTypeName
                    FROM gibbonINInterventionEligibilityContributor AS c 
                    LEFT JOIN gibbonINEligibilityAssessmentType AS t ON (c.gibbonINEligibilityAssessmentTypeID=t.gibbonINEligibilityAssessmentTypeID)
                    WHERE c.gibbonINInterventionEligibilityAssessmentID=:gibbonINInterventionEligibilityAssessmentID 
                    AND c.status='Complete'";
            $contributors = $pdo->select($sql, ['gibbonINInterventionEligibilityAssessmentID' => $gibbonINInterventionEligibilityAssessmentID])->fetchAll();
            
            if (count($contributors) == 0) {
                echo "<div class='message warning'>".__('There are no completed assessments to display.')."</div>";
            } else {
                // Get all assessment types used by contributors
                $assessmentTypes = [];
                foreach ($contributors as $contributor) {
                    if (!empty($contributor['gibbonINEligibilityAssessmentTypeID']) && !isset($assessmentTypes[$contributor['gibbonINEligibilityAssessmentTypeID']])) {
                        $assessmentTypes[$contributor['gibbonINEligibilityAssessmentTypeID']] = [
                            'id' => $contributor['gibbonINEligibilityAssessmentTypeID'],
                            'name' => $contributor['assessmentTypeName'],
                            'subfields' => []
                        ];
                    }
                }
                
                // Get all subfields for these assessment types
                foreach ($assessmentTypes as $typeID => $type) {
                    $sql = "SELECT * FROM gibbonINEligibilityAssessmentSubfield 
                            WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID 
                            AND active='Y' 
                            ORDER BY sequenceNumber";
                    $subfields = $pdo->select($sql, ['gibbonINEligibilityAssessmentTypeID' => $typeID])->fetchAll();
                    
                    foreach ($subfields as $subfield) {
                        $assessmentTypes[$typeID]['subfields'][$subfield['gibbonINEligibilityAssessmentSubfieldID']] = [
                            'id' => $subfield['gibbonINEligibilityAssessmentSubfieldID'],
                            'name' => $subfield['name'],
                            'description' => $subfield['description'],
                            'ratings' => [],
                            'averageRating' => 0
                        ];
                    }
                }
                
                // Get all ratings for all contributors
                foreach ($contributors as $contributor) {
                    if (empty($contributor['gibbonINEligibilityAssessmentTypeID'])) {
                        continue;
                    }
                    
                    $sql = "SELECT r.*, s.name as subfieldName 
                            FROM gibbonINInterventionEligibilityContributorRating AS r 
                            JOIN gibbonINEligibilityAssessmentSubfield AS s ON (r.gibbonINEligibilityAssessmentSubfieldID=s.gibbonINEligibilityAssessmentSubfieldID) 
                            WHERE r.gibbonINInterventionEligibilityContributorID=:gibbonINInterventionEligibilityContributorID";
                    $ratings = $pdo->select($sql, ['gibbonINInterventionEligibilityContributorID' => $contributor['gibbonINInterventionEligibilityContributorID']])->fetchAll();
                    
                    foreach ($ratings as $rating) {
                        if (isset($assessmentTypes[$contributor['gibbonINEligibilityAssessmentTypeID']]['subfields'][$rating['gibbonINEligibilityAssessmentSubfieldID']])) {
                            $assessmentTypes[$contributor['gibbonINEligibilityAssessmentTypeID']]['subfields'][$rating['gibbonINEligibilityAssessmentSubfieldID']]['ratings'][] = $rating['rating'];
                        }
                    }
                }
                
                // Calculate average ratings
                foreach ($assessmentTypes as $typeID => $type) {
                    foreach ($type['subfields'] as $subfieldID => $subfield) {
                        $totalRating = 0;
                        $ratingCount = 0;
                        
                        foreach ($subfield['ratings'] as $rating) {
                            if ($rating > 0) {
                                $totalRating += $rating;
                                $ratingCount++;
                            }
                        }
                        
                        $assessmentTypes[$typeID]['subfields'][$subfieldID]['averageRating'] = $ratingCount > 0 ? round($totalRating / $ratingCount, 1) : 0;
                    }
                }
                
                // Display summary tables for each assessment type
                foreach ($assessmentTypes as $type) {
                    echo '<h4>'.$type['name'].'</h4>';
                    
                    echo '<table class="smallIntBorder" cellspacing="0" style="width:100%">';
                    echo '<tr>';
                    echo '<th style="width:40%">'.__('Subfield').'</th>';
                    echo '<th style="width:20%">'.__('Average Rating').'</th>';
                    echo '<th>'.__('Interpretation').'</th>';
                    echo '</tr>';
                    
                    foreach ($type['subfields'] as $subfield) {
                        echo '<tr>';
                        echo '<td>'.$subfield['name'].'<br/><span class="small emphasis">'.$subfield['description'].'</span></td>';
                        
                        // Display average rating with color coding
                        $ratingClass = '';
                        if ($subfield['averageRating'] >= 4) {
                            $ratingClass = 'error';
                        } elseif ($subfield['averageRating'] >= 2.5) {
                            $ratingClass = 'warning';
                        } elseif ($subfield['averageRating'] > 0) {
                            $ratingClass = 'success';
                        }
                        
                        echo '<td class="'.$ratingClass.'">'.$subfield['averageRating'].'</td>';
                        
                        // Display interpretation
                        $interpretation = '';
                        if ($subfield['averageRating'] == 0) {
                            $interpretation = __('Not Evaluated');
                        } elseif ($subfield['averageRating'] < 2) {
                            $interpretation = __('No/Minimal Concern');
                        } elseif ($subfield['averageRating'] < 3.5) {
                            $interpretation = __('Moderate Concern');
                        } else {
                            $interpretation = __('Significant/High Concern');
                        }
                        
                        echo '<td>'.$interpretation.'</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                }
            }
        }

        // Main assessment form
        $form = Form::create('eligibility', $session->get('absoluteURL').'/modules/Interventions/intervention_eligibility_editProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
        $form->addHiddenValue('gibbonINInterventionEligibilityAssessmentID', $gibbonINInterventionEligibilityAssessmentID);
        $form->addHiddenValue('gibbonPersonIDStudent', $intervention['gibbonPersonIDStudent']);
        $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
        $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);
        $form->addHiddenValue('status', $status);

        $form->addRow()->addHeading(__('Student Details'));

        $row = $form->addRow();
            $row->addLabel('studentName', __('Student'));
            $row->addTextField('studentName')->setValue($studentName)->readonly();

        $row = $form->addRow();
            $row->addLabel('formGroup', __('Form Group'));
            $row->addTextField('formGroup')->setValue($student['formGroup'])->readonly();

        $row = $form->addRow();
            $row->addLabel('yearGroup', __('Year Group'));
            $row->addTextField('yearGroup')->setValue($student['yearGroup'])->readonly();

        $form->addRow()->addHeading(__('Assessment Details'));

        $row = $form->addRow();
            $row->addLabel('interventionName', __('Intervention'));
            $row->addTextField('interventionName')->setValue($intervention['name'])->readonly();

        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $options = [
                'In Progress' => __('In Progress'),
                'Complete' => __('Complete')
            ];
            $row->addSelect('status')->fromArray($options)->required()->selected($assessment['status'] ?? 'In Progress');

        $row = $form->addRow();
            $row->addLabel('eligibilityDecision', __('Eligibility Decision'))->description(__('Decision based on assessment results'));
            $options = [
                'Pending' => __('Pending'),
                'Eligible for IEP' => __('Eligible for IEP'),
                'Needs Intervention' => __('Needs Intervention')
            ];
            $row->addSelect('eligibilityDecision')->fromArray($options)->required()->selected($assessment['eligibilityDecision'] ?? 'Pending');

        $row = $form->addRow();
            $row->addLabel('notes', __('Assessment Notes'))->description(__('Detailed findings from the assessment'));
            $row->addTextArea('notes')->setRows(10)->setValue($assessment['notes'] ?? '');

        // File upload
        $fileUploader = new FileUploader($pdo, $session);
        $row = $form->addRow();
            $row->addLabel('documentFile', __('Upload Document'))->description(__('Upload any supporting documentation'));
            $row->addFileUpload('documentFile')
                ->accepts($fileUploader->getFileExtensions())
                ->setMaxUpload(false);

        if (!empty($assessment['documentPath'])) {
            $row = $form->addRow();
                $row->addLabel('currentDocument', __('Current Document'));
                $row->addContent('<a href="'.$session->get('absoluteURL').'/'.$assessment['documentPath'].'" target="_blank">'.__('View Document').'</a>');
        }

        // Add explanation text for the decision options
        $row = $form->addRow();
        $row->addContent('<div class="message emphasis">');
        $row->addContent('<p><strong>'.__('Decision Options').':</strong></p>');
        $row->addContent('<ul>');
        $row->addContent('<li>'.__('Eligible for IEP: Student will follow the IEP path').'</li>');
        $row->addContent('<li>'.__('Needs Intervention: Student will receive interventions before considering an IEP').'</li>');
        $row->addContent('</ul>');
        $row->addContent('</div>');

        $row = $form->addRow();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
