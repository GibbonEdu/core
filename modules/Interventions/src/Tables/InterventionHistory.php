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

namespace Gibbon\Module\Interventions\Tables;

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Interventions\INInterventionGateway;
use Gibbon\Domain\Interventions\INInterventionContributorGateway;
use Gibbon\Domain\Interventions\INInterventionStrategyGateway;
use Gibbon\Domain\Interventions\INInterventionUpdateGateway;
use Gibbon\Domain\Interventions\INEligibilityAssessmentGateway;

/**
 * InterventionHistory
 *
 * @version v1.0.00
 * @since   v1.0.00
 */
class InterventionHistory
{
    protected $interventionGateway;
    protected $contributorGateway;
    protected $strategyGateway;
    protected $updateGateway;
    protected $assessmentGateway;
    protected $session;

    public function __construct(
        INInterventionGateway $interventionGateway,
        INInterventionContributorGateway $contributorGateway,
        INInterventionStrategyGateway $strategyGateway,
        INInterventionUpdateGateway $updateGateway,
        INEligibilityAssessmentGateway $assessmentGateway,
        $session
    ) {
        $this->interventionGateway = $interventionGateway;
        $this->contributorGateway = $contributorGateway;
        $this->strategyGateway = $strategyGateway;
        $this->updateGateway = $updateGateway;
        $this->assessmentGateway = $assessmentGateway;
        $this->session = $session;
    }

    public function create($gibbonPersonID)
    {
        $output = '';

        // Get all interventions for this student
        $criteria = $this->interventionGateway->newQueryCriteria()
            ->sortBy(['timestampCreated'], 'DESC')
            ->filterBy('gibbonPersonIDStudent', $gibbonPersonID);
        
        $interventions = $this->interventionGateway->queryInterventions($criteria, $this->session->get('gibbonSchoolYearID'));
        
        // INTERVENTION SUMMARY SECTION
        $output .= '<h2>';
        $output .= __('Intervention Summary');
        $output .= '</h2>';

        $output .= $this->getInterventionSummary($gibbonPersonID);

        // CURRENT INTERVENTIONS SECTION
        $output .= '<h2>';
        $output .= __('Current Interventions');
        $output .= '</h2>';

        $table = DataTable::create('interventions');
        $table->setTitle(__('Active Interventions'));

        $table->addColumn('name', __('Name'))
            ->format(function ($intervention) {
                return Format::link('./index.php?q=/modules/Interventions/interventions_manage_edit.php&gibbonINInterventionID='.$intervention['gibbonINInterventionID'], $intervention['name']);
            });
        
        $table->addColumn('status', __('Status'))
            ->format(function ($intervention) {
                return Format::tag(__($intervention['status']), $this->getStatusColor($intervention['status']));
            });
            
        $table->addColumn('formTutorDecision', __('Form Tutor Decision'))
            ->format(function ($intervention) {
                if ($intervention['formTutorDecision'] == 'Pending') {
                    return Format::tag(__('Pending'), 'dull');
                }
                return Format::tag(__($intervention['formTutorDecision']), 'message');
            });
            
        $table->addColumn('strategies', __('Strategies'))
            ->format(function ($intervention) {
                $strategyCriteria = $this->strategyGateway->newQueryCriteria();
                $strategies = $this->strategyGateway->queryStrategiesByIntervention($strategyCriteria, $intervention['gibbonINInterventionID']);
                
                $count = $strategies->getResultCount();
                return $count > 0 ? $count : '-';
            });
            
        $table->addColumn('contributors', __('Contributors'))
            ->format(function ($intervention) {
                $contributorCriteria = $this->contributorGateway->newQueryCriteria();
                $contributors = $this->contributorGateway->queryContributorsByIntervention($contributorCriteria, $intervention['gibbonINInterventionID']);
                
                $count = $contributors->getResultCount();
                return $count > 0 ? $count : '-';
            });
            
        $table->addColumn('timestampCreated', __('Date Created'))
            ->format(function ($intervention) {
                return Format::date($intervention['timestampCreated']);
            });

        // Only show active interventions
        $activeInterventions = $interventions->toArray();
        $activeInterventions = array_filter($activeInterventions, function($intervention) {
            return $intervention['status'] != 'Resolved' && $intervention['status'] != 'Completed';
        });
        
        if (count($activeInterventions) > 0) {
            $output .= $table->render(new \ArrayObject($activeInterventions));
        } else {
            $output .= Format::alert(__('There are no active interventions for this student.'), 'message');
        }

        // INTERVENTION HISTORY SECTION
        $output .= '<h2>';
        $output .= __('Intervention History');
        $output .= '</h2>';

        $historyTable = DataTable::create('interventionHistory');
        $historyTable->setTitle(__('Past Interventions'));

        $historyTable->addColumn('name', __('Name'))
            ->format(function ($intervention) {
                return Format::link('./index.php?q=/modules/Interventions/interventions_manage_edit.php&gibbonINInterventionID='.$intervention['gibbonINInterventionID'], $intervention['name']);
            });
        
        $historyTable->addColumn('status', __('Status'))
            ->format(function ($intervention) {
                return Format::tag(__($intervention['status']), $this->getStatusColor($intervention['status']));
            });
            
        $historyTable->addColumn('outcomeDecision', __('Outcome'))
            ->format(function ($intervention) {
                if (empty($intervention['outcomeDecision']) || $intervention['outcomeDecision'] == 'Pending') {
                    return Format::tag(__('Pending'), 'dull');
                }
                return Format::tag(__($intervention['outcomeDecision']), 'success');
            });
            
        $historyTable->addColumn('timestampCreated', __('Date Created'))
            ->format(function ($intervention) {
                return Format::date($intervention['timestampCreated']);
            });
            
        $historyTable->addColumn('timestampModified', __('Last Updated'))
            ->format(function ($intervention) {
                return Format::date($intervention['timestampModified']);
            });

        // Only show completed interventions
        $completedInterventions = $interventions->toArray();
        $completedInterventions = array_filter($completedInterventions, function($intervention) {
            return $intervention['status'] == 'Resolved' || $intervention['status'] == 'Completed';
        });
        
        if (count($completedInterventions) > 0) {
            $output .= $historyTable->render(new \ArrayObject($completedInterventions));
        } else {
            $output .= Format::alert(__('There are no completed interventions for this student.'), 'message');
        }

        // ELIGIBILITY ASSESSMENTS SECTION
        $output .= '<h2>';
        $output .= __('Eligibility Assessments');
        $output .= '</h2>';

        $output .= $this->getEligibilityAssessments($gibbonPersonID);

        return $output;
    }

    private function getInterventionSummary($gibbonPersonID)
    {
        $output = '';

        // Get counts of interventions by status
        $criteria = $this->interventionGateway->newQueryCriteria()
            ->filterBy('gibbonPersonIDStudent', $gibbonPersonID);
        
        $interventions = $this->interventionGateway->queryInterventions($criteria, $this->session->get('gibbonSchoolYearID'))->toArray();
        
        $totalInterventions = count($interventions);
        $activeInterventions = 0;
        $completedInterventions = 0;
        $pendingReview = 0;
        $successfulOutcomes = 0;
        
        foreach ($interventions as $intervention) {
            if ($intervention['status'] == 'Resolved' || $intervention['status'] == 'Completed') {
                $completedInterventions++;
                if ($intervention['outcomeDecision'] == 'Success') {
                    $successfulOutcomes++;
                }
            } else {
                $activeInterventions++;
                if ($intervention['formTutorDecision'] == 'Pending') {
                    $pendingReview++;
                }
            }
        }
        
        // Create summary grid
        $output .= '<div class="column-grid">';
        
        $output .= '<div class="column-block bg-blue-100 text-center p-8">';
        $output .= '<div class="text-4xl font-bold">'.$totalInterventions.'</div>';
        $output .= '<div class="text-sm uppercase">'.__('Total Interventions').'</div>';
        $output .= '</div>';
        
        $output .= '<div class="column-block bg-yellow-100 text-center p-8">';
        $output .= '<div class="text-4xl font-bold">'.$activeInterventions.'</div>';
        $output .= '<div class="text-sm uppercase">'.__('Active Interventions').'</div>';
        $output .= '</div>';
        
        $output .= '<div class="column-block bg-green-100 text-center p-8">';
        $output .= '<div class="text-4xl font-bold">'.$completedInterventions.'</div>';
        $output .= '<div class="text-sm uppercase">'.__('Completed Interventions').'</div>';
        $output .= '</div>';
        
        $output .= '<div class="column-block bg-red-100 text-center p-8">';
        $output .= '<div class="text-4xl font-bold">'.$pendingReview.'</div>';
        $output .= '<div class="text-sm uppercase">'.__('Pending Review').'</div>';
        $output .= '</div>';
        
        $output .= '<div class="column-block bg-purple-100 text-center p-8">';
        $output .= '<div class="text-4xl font-bold">'.$successfulOutcomes.'</div>';
        $output .= '<div class="text-sm uppercase">'.__('Successful Outcomes').'</div>';
        $output .= '</div>';
        
        $output .= '</div>';
        
        return $output;
    }

    private function getEligibilityAssessments($gibbonPersonID)
    {
        $output = '';

        // Get eligibility assessments for this student
        $criteria = $this->assessmentGateway->newQueryCriteria()
            ->sortBy(['dateCompleted'], 'DESC');
        
        $assessments = $this->assessmentGateway->queryAssessmentsByStudent($criteria, $gibbonPersonID);
        
        if ($assessments->getResultCount() > 0) {
            $table = DataTable::create('eligibilityAssessments');
            
            $table->addColumn('type', __('Assessment Type'));
            
            $table->addColumn('recommendation', __('Recommendation'))
                ->format(function ($assessment) {
                    if ($assessment['recommendation'] == 'Pending') {
                        return Format::tag(__('Pending'), 'dull');
                    } elseif ($assessment['recommendation'] == 'Eligible') {
                        return Format::tag(__('Eligible'), 'success');
                    } else {
                        return Format::tag(__('Not Eligible'), 'warning');
                    }
                });
                
            $table->addColumn('contributor', __('Contributor'))
                ->format(function ($assessment) {
                    return Format::name($assessment['title'], $assessment['preferredName'], $assessment['surname'], 'Staff');
                });
                
            $table->addColumn('dateCompleted', __('Date Completed'))
                ->format(function ($assessment) {
                    return !empty($assessment['dateCompleted']) ? Format::date($assessment['dateCompleted']) : Format::tag(__('Incomplete'), 'dull');
                });
                
            $output .= $table->render($assessments);
        } else {
            $output .= Format::alert(__('There are no eligibility assessments for this student.'), 'message');
        }
        
        return $output;
    }

    private function getStatusColor($status)
    {
        switch ($status) {
            case 'Referral':
                return 'message';
            case 'Form Tutor Review':
                return 'warning';
            case 'Intervention':
                return 'success';
            case 'IEP':
                return 'success';
            case 'Resolved':
                return 'dull';
            case 'Completed':
                return 'dull';
            default:
                return 'dull';
        }
    }
}
