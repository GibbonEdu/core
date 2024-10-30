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

namespace Gibbon\Module\Activities;

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityChoiceGateway;
use Gibbon\Domain\Activities\ActivityStudentGateway;
use Gibbon\Domain\Activities\ActivityCategoryGateway;

/**
 * Facilitates turning student choices into a set of potential enrolment groups 
 * for each of the selected activities.
 */
class EnrolmentGenerator 
{
    protected $activityGateway;
    protected $activityStudentGateway;
    protected $activityChoiceGateway;
    protected $activityCategoryGateway;

    protected $newStudentPriority = true;
    protected $yearGroupPriority = true;
    protected $includePastChoices = true;
    protected $includeTimestamps = false;
    
    protected $signUpChoices;
    protected $activities;
    protected $enrolments;
    protected $choices;
    protected $groups;

    public function __construct(ActivityGateway $activityGateway, ActivityStudentGateway $activityStudentGateway, ActivityChoiceGateway $activityChoiceGateway, ActivityCategoryGateway $activityCategoryGateway)
    {
        $this->activityGateway = $activityGateway;
        $this->activityStudentGateway = $activityStudentGateway;
        $this->activityChoiceGateway = $activityChoiceGateway;
        $this->activityCategoryGateway = $activityCategoryGateway;
    }

    public function getActivities()
    {
        return $this->activities;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function setOptions(array $options)
    {
        $this->newStudentPriority = in_array('newStudentPriority', $options);
        $this->yearGroupPriority = in_array('yearGroupPriority', $options);
        $this->includePastChoices = in_array('includePastChoices', $options);
        $this->includeTimestamps = in_array('includeTimestamps', $options);

        return $this;
    }

    public function loadActivities(string $gibbonActivityCategoryID, array $activityList)
    {
        // Filter details to only those checked for this generation process
        $this->activities = $this->activityGateway->selectActivityDetailsByCategory($gibbonActivityCategoryID)->fetchGroupedUnique();
        $this->activities = array_intersect_key($this->activities, $activityList);

        // Update max values for the selected activities
        foreach ($this->activities as $gibbonActivityID => $activity) {
            $activity['maxParticipants'] = $activityList[$gibbonActivityID]['maxParticipants'] ?? $activity['maxParticipants'];

            $this->activityGateway->update($gibbonActivityID, [
                'maxParticipants' => $activity['maxParticipants'],
            ]);

            $this->activities[$gibbonActivityID] = $activity;
        }

        return $this;
    }

    public function loadEnrolments(string $gibbonActivityCategoryID)
    {
        $this->enrolments = $this->activityStudentGateway->selectEnrolmentsByCategory($gibbonActivityCategoryID)->fetchGroupedUnique();

        return $this;
    }

    public function loadChoices(string $gibbonActivityCategoryID)
    {
        $category = $this->activityCategoryGateway->getByID($gibbonActivityCategoryID);
        $this->signUpChoices = $category['signUpChoices'] ?? 3;

        $choices = $this->activityChoiceGateway->selectChoicesByCategory($gibbonActivityCategoryID)->fetchGroupedUnique();
        $this->choices = [];

        foreach ($choices as $gibbonPersonID => $person) {
            if (!empty($this->enrolments[$gibbonPersonID])) continue;
            
            for ($i = 1; $i <= $this->signUpChoices; $i++) {
                $person["choice{$i}"] = str_pad($person["choice{$i}"], 8, '0', STR_PAD_LEFT);
                $person["choice{$i}Name"] = $this->activities[$person["choice{$i}"]]['name'] ?? '';
            }

            $this->choices[$gibbonPersonID] = $person;
        }

        $this->sortChoicesByWeighting($gibbonActivityCategoryID);

        return $this;
    }

    public function generateGroups()
    {
        // Preload any existing enrolments
        foreach ($this->enrolments as $gibbonPersonID => $person) {
            $person['enrolled'] = true;
            $this->groups[$person['gibbonActivityID']][$gibbonPersonID] = $person;
        }

        // Assign choices to groups until the groups fill up
        foreach ($this->choices as $gibbonPersonID => $person) {
            $enrolmentGroup = 0;

            for ($i = 1; $i <= 3; $i++) {
                if (empty($person["choice{$i}"])) continue;

                $choiceActivity = $this->activities[$person["choice{$i}"]] ?? ['maxParticipants' => 0];
                $groupCount = count($this->groups[$person["choice{$i}"]] ?? []);

                if ($groupCount < $choiceActivity['maxParticipants']) {
                    $enrolmentGroup = $person["choice{$i}"];
                    break;
                }
            }

            $this->groups[$enrolmentGroup][$gibbonPersonID] = $person;
        }

        // Sort each resulting group alphabetically
        foreach ($this->groups as $enrolmentGroup => $group) {
            uasort($group, function ($a, $b) {
                if ($a['surname'] != $b['surname']) {
                    return $a['surname'] <=> $b['surname'];
                }

                return $a['preferredName'] <=> $b['preferredName'];
            });

            $this->groups[$enrolmentGroup] = $group;
        }

        return $this;
    }

    public function createEnrolments($gibbonActivityCategoryID, $enrolmentList, $gibbonPersonIDCreated = null) : array
    {
        $results = ['total' => 0, 'choice0' => 0, 'choice1' => 0, 'choice2' => 0, 'choice3' => 0, 'choice4' => 0, 'choice5' => 0, 'unassigned' => 0, 'inserted' => 0, 'updated' => 0, 'error' => 0];

        foreach ($enrolmentList as $gibbonPersonID => $gibbonActivityID) {
            if (empty($gibbonActivityID)) {
                $results['unassigned']++;
                continue;
            }

            // Connect the choice to the enrolment, for future queries and weighting
            $choice = $this->activityChoiceGateway->getChoiceByActivityAndPerson($gibbonActivityID, $gibbonPersonID);
            $choiceNumber = intval($choice['choice'] ?? 0);

            $enrolment = $this->activityStudentGateway->getEnrolmentByCategoryAndPerson($gibbonActivityCategoryID, $gibbonPersonID);

            if (!empty($enrolment)) {
                // Update and existing enrolment
                $data = [
                    'gibbonActivityID'       => $gibbonActivityID,
                    'gibbonActivityChoiceID' => $choice['gibbonActivityChoiceID'] ?? null,
                ];
    
                $updated = $this->activityStudentGateway->update($enrolment['gibbonActivityStudentID'], $data);
                $results['total']++;
                $results['updated']++;
                $results["choice".$choiceNumber]++;
                
            } else {
                // Add a new enrolment
                $data = [
                    'gibbonActivityID'       => $gibbonActivityID,
                    'gibbonActivityChoiceID' => $choice['gibbonActivityChoiceID'] ?? null,
                    'gibbonPersonID'         => $gibbonPersonID,
                    'status'                 => 'Accepted',
                    'timestamp'              => date('Y-m-d H:i:s'),
                ];

                $inserted = $this->activityStudentGateway->insert($data);
                if ($inserted) {
                    $results['total']++;
                    $results['inserted']++;
                    $results["choice".$choiceNumber]++;
                } else {
                    $results['error']++;
                }
            }
        }

        return $results;
    }

    protected function sortChoicesByWeighting(string $gibbonActivityCategoryID)
    {
        $choiceWeights = $this->activityChoiceGateway->selectChoiceWeightingByCategory($gibbonActivityCategoryID)->fetchGroupedUnique();
        $timestampRange = $this->activityChoiceGateway->getTimestampMinMaxByCategory($gibbonActivityCategoryID);
        $yearGroupMax = $this->activityChoiceGateway->getYearGroupWeightingMax();

        foreach ($this->choices as $gibbonPersonID => $person) {
            $choiceWeight = $yearGroupWeight = 0;

            // Weight students who didn't get 1st choice in the past higher (0 - 3.0)
            if ($this->includePastChoices && !empty($choiceWeights[$gibbonPersonID]['choiceCount'])) {
                $choiceWeight += ($choiceWeights[$gibbonPersonID]['choiceCount']) / max(($choiceWeights[$gibbonPersonID]['categoryCount'] ?? 0), 1);
            }

            // Students who are brand new to DL get an extra boost
            if ($this->newStudentPriority && empty($choiceWeights[$gibbonPersonID]['categoryCount'])) {
                $choiceWeight += 1.5;
            }

            // Weight younger year groups more than older ones (0 - 1.0)
            if ($this->yearGroupPriority) {
                $yearGroupWeight = ($yearGroupMax - ($person['yearGroupSequence'] ?? 0)) / max($yearGroupMax, 1);
            }

            // Include timestamps (0 - 1.5), or add some randomization to keep things fresh (0 - 0.5)
            if ($this->includeTimestamps && !empty($timestampRange['max'])) {
                $timestamp = strtotime($person['timestampCreated']) - $timestampRange['min'];
                $timestampUpper = $timestampRange['max'] - $timestampRange['min'];
                $timestampWeight = 1.5 - ((floatval($timestamp) / floatval($timestampUpper)) * 1.5);
            } else {
                $timestampWeight = (mt_rand(0,500) / 1000);
            }

            $this->choices[$gibbonPersonID]['weight'] = $choiceWeight + $yearGroupWeight + $timestampWeight;
        }

        // A higher weighting gives students a higher priority to get their top choices
        uasort($this->choices, function ($a, $b) {
            return $b['weight'] <=> $a['weight'];
        });
    }
    
}
