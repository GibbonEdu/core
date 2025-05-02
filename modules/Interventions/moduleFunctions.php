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

use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;

/**
 * Format a person's name based on their role
 *
 * @param string $title
 * @param string $preferredName
 * @param string $surname
 * @param string $role
 * @param bool $reverse
 * @return string
 */
function formatName($title, $preferredName, $surname, $role = 'Student', $reverse = false)
{
    return Format::name($title, $preferredName, $surname, $role, $reverse);
}

//$mode can be blank or "disabled". $archive is a serialized array of values previously archived
function printINStatusTable($connection2, $guid, $gibbonPersonID, $mode = '', $archive = '')
{
    $output = "";
    global $container;
    $gateway = $container->get(INInterventionGateway::class);
    if ($archive == '') { //Use live data
        $criteria = $gateway
          ->newQueryCriteria()
          ->filterBy('gibbonPersonID', $gibbonPersonID)
          ->fromPOST();
        $personalDescriptors = $gateway->queryIndividualNeedsPersonDescriptors($criteria)->toArray();
    } else //Use archived data
    {
        $archive = unserialize($archive);
        $personalDescriptors = [];
        foreach ($archive as $archiveEntry) {
            array_push($personalDescriptors, [
            'gibbonINDescriptorID' => $archiveEntry['gibbonINDescriptorID'],
            'gibbonAlertLevelID' => $archiveEntry['gibbonAlertLevelID']
            ]);
        }
    }
    $allDescriptors = $gateway->queryIndividualNeedsDescriptors($gateway->newQueryCriteria())->toArray();
    $alertLevels = $gateway->queryAlertLevels($gateway->newQueryCriteria())->toArray();

    if ($archive != '') {
      /*
       * Given this is archived data, there's a possibility some of the data
       * may no longer exist. Display an error message if this data no longer
       * exists.
       */
        $missingDescriptor = false;
        $missingAlert = false;
        foreach ($personalDescriptors as $pDescriptor) {
            $foundDescriptor = false;
            $foundAlert = false;
            foreach ($allDescriptors as $descriptor) {
                if ($descriptor['gibbonINDescriptorID'] == $pDescriptor['gibbonINDescriptorID']) {
                    $foundDescriptor = true;
                }
            }
            foreach ($alertLevels as $level) {
                if ($level['gibbonAlertLevelID'] == $pDescriptor['gibbonAlertLevelID']) {
                    $foundAlert = true;
                }
            }
            if ($foundDescriptor == false) {
                $missingDescriptor = true;
            }
            if ($foundAlert == false) {
                $missingAlert = true;
            }
        }
        if ($missingAlert) {
            $output .= Format::alert(__('Some of the alert levels present in your provided archive can no longer be found. These alert levels will no longer show in the table below.'));
        }
        if ($missingDescriptor) {
            $output .= Format::alert(__('Some of the descriptors present in your provided archive can no longer be found. These descriptors will no longer show in the table below.'));
        }
    }

    $results = array_map(function ($descriptor) use ($alertLevels, $personalDescriptors) {
        $result = [
        'gibbonINDescriptorID' => $descriptor['gibbonINDescriptorID'],
        'gibbonINDescriptorName' => $descriptor['name'],
        'gibbonINDescriptorNameShort' => $descriptor['nameShort'],
        'gibbonINDescriptorDescription' => $descriptor['description'],
        'gibbonINDescriptorSequenceNumber' => $descriptor['sequenceNumber']
        ];
        foreach ($alertLevels as $alertLevel) {
            $pDescriptorAssigned = false;
            foreach ($personalDescriptors as $pDescriptor) {
                if ($pDescriptor['gibbonINDescriptorID'] == $descriptor['gibbonINDescriptorID']) {
                    if ($pDescriptor['gibbonAlertLevelID'] == $alertLevel['gibbonAlertLevelID']) {
                        $pDescriptorAssigned = true;
                    }
                }
            }
            $result["alert_{$alertLevel['name']}"] = $pDescriptorAssigned;
        }
        return $result;
    }, $allDescriptors);

  //Results array needs to be a dataset so it can be rendered by the table;
    $dataset = new DataSet($results);

    $table = DataTable::create('descriptors');
    $table->addColumn('gibbonINDescriptorName', __('Descriptor'))->context('primary');
    foreach ($alertLevels as $alertLevel) {
        $table
        ->addColumn("alert_{$alertLevel['name']}", $alertLevel['name'])
        ->context('primary')
        ->format(function ($level) use ($alertLevel, $mode) {
            $checked = $level["alert_{$alertLevel['name']}"] == true ? 'checked' : '';
            $disabled = $mode == 'disabled' ? 'disabled' : '';
            $value = "{$level['gibbonINDescriptorID']}-{$alertLevel['gibbonAlertLevelID']}";
            return "<input type='checkbox' name='status[]' value={$value} {$checked} $disabled></input>";
        });
    }
    $output .= $table->render($dataset);
    return $output;
}

/**
 * Checks if a student has completed an eligibility assessment and was deemed eligible
 *
 * @param \PDO $pdo
 * @param int $gibbonPersonID
 * @return bool
 */
function hasCompletedEligibilityAssessment($pdo, $gibbonPersonID)
{
    $data = ['gibbonPersonIDStudent' => $gibbonPersonID];
    
    $sql = "SELECT gibbonINReferral.gibbonINReferralID, gibbonINReferral.eligibilityDecision 
            FROM gibbonINReferral 
            WHERE gibbonINReferral.gibbonPersonIDStudent=:gibbonPersonIDStudent 
            AND gibbonINReferral.status='Eligibility Complete' 
            AND gibbonINReferral.eligibilityDecision='Eligible'";
    
    $result = $pdo->prepare($sql);
    $result->execute($data);
    
    return ($result->rowCount() > 0);
}

function getReferralCriteriaStrands($includeCognition = false)
{
    $options = array(
        0 => array('name' => 'memory', 'nameHuman' => 'Memory'),
        1 => array('name' => 'selfManagement', 'nameHuman' => 'Self-Management'),
        2 => array('name' => 'attention', 'nameHuman' => 'Attention'),
        3 => array('name' => 'socialInteraction', 'nameHuman' => 'Social Interaction'),
        4 => array('name' => 'communication', 'nameHuman' => 'Communication'),
    );

    if ($includeCognition) {
        array_unshift($options, array('name' => 'cognition', 'nameHuman' => 'Cognition'));
    }

    return $options;
}

/**
 * Get all users who have permission to manage interventions
 *
 * @param \PDO $connection2
 * @return array Array of gibbonPersonID values
 */
function getInterventionCoordinators($connection2)
{
    $coordinators = [];
    
    try {
        $data = [];
        $sql = "SELECT gibbonPerson.gibbonPersonID 
                FROM gibbonPerson 
                JOIN gibbonPermission ON (gibbonPermission.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPermission.gibbonRoleID)
                JOIN gibbonAction ON (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID)
                WHERE gibbonPerson.status='Full' 
                AND gibbonAction.name='Manage Interventions' 
                AND gibbonAction.moduleName='Interventions'";
                
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        while ($row = $result->fetch()) {
            $coordinators[] = $row['gibbonPersonID'];
        }
    } catch (\PDOException $e) {
        // Log error
        error_log($e->getMessage());
    }
    
    return $coordinators;
}

/**
 * Get the appropriate step number based on intervention status
 *
 * @param string $status
 * @return int
 */
function getInterventionStep($status)
{
    switch($status) {
        case 'Referral':
        case 'Form Tutor Review':
            return 1;
        case 'Eligibility Assessment':
            return 2;
        case 'Intervention Required':
            return 3;
        case 'Support Plan Active':
            return 4;
        case 'Ready for Evaluation':
        case 'Resolved':
            return 5;
        default:
            return 1;
    }
}

/**
 * Get the appropriate redirect URL based on whether we're coming from the process page
 *
 * @param string $gibbonINInterventionID
 * @param string $gibbonINInterventionEligibilityAssessmentID
 * @param string $gibbonPersonIDStudent
 * @param string $gibbonFormGroupID
 * @param string $gibbonYearGroupID
 * @param string $status
 * @param bool|string $returnProcess
 * @param int $step
 * @param bool|string $isContributor Whether the user is a contributor completing their assessment
 * @return string
 */
function getInterventionRedirectURL($session, $gibbonINInterventionID, $gibbonINInterventionEligibilityAssessmentID = '', $gibbonPersonIDStudent = '', $gibbonFormGroupID = '', $gibbonYearGroupID = '', $status = '', $returnProcess = '', $isContributor = '', $step = 2)
{
    // Debug logging
    error_log('getInterventionRedirectURL - Parameters:');
    error_log('returnProcess (before): ' . $returnProcess);
    error_log('isContributor (before): ' . $isContributor);
    
    // Convert returnProcess and isContributor to proper booleans
    $returnProcess = filter_var($returnProcess, FILTER_VALIDATE_BOOLEAN);
    $isContributor = filter_var($isContributor, FILTER_VALIDATE_BOOLEAN);
    
    // Debug logging
    error_log('returnProcess (after): ' . ($returnProcess ? 'true' : 'false'));
    error_log('isContributor (after): ' . ($isContributor ? 'true' : 'false'));
    
    // If the user is a contributor completing their assessment, redirect to the contributor dashboard
    if ($isContributor) {
        return $session->get('absoluteURL')."/index.php?q=/modules/Interventions/interventions_contributor_dashboard.php";
    }
    
    // Otherwise, determine if we should return to the process page or the eligibility edit page
    if ($returnProcess) {
        return $session->get('absoluteURL')."/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID=$gibbonINInterventionID&step=$step";
    } else {
        return $session->get('absoluteURL')."/index.php?q=/modules/Interventions/intervention_eligibility_edit.php&gibbonINInterventionID=$gibbonINInterventionID&gibbonINInterventionEligibilityAssessmentID=$gibbonINInterventionEligibilityAssessmentID&gibbonPersonIDStudent=$gibbonPersonIDStudent&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID&status=$status";
    }
}
