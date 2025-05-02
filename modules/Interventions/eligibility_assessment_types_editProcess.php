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

use Gibbon\Module\Interventions\Domain\INEligibilityAssessmentTypeGateway;

require_once '../../gibbon.php';

$gibbonINEligibilityAssessmentTypeID = $_POST['gibbonINEligibilityAssessmentTypeID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/eligibility_assessment_types_edit.php&gibbonINEligibilityAssessmentTypeID='.$gibbonINEligibilityAssessmentTypeID;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/eligibility_assessment_types_edit.php') == false) {
    // Access denied
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    // Check if assessment type specified
    if (empty($gibbonINEligibilityAssessmentTypeID)) {
        $URL = $URL.'&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $assessmentTypeGateway = $container->get(INEligibilityAssessmentTypeGateway::class);
    $values = $assessmentTypeGateway->getByID($gibbonINEligibilityAssessmentTypeID);

    if (empty($values)) {
        $URL = $URL.'&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $active = $_POST['active'] ?? '';

    // Validate the required values are present
    if (empty($name) || empty($active)) {
        $URL = $URL.'&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this name is unique
    if ($name != $values['name'] && !$assessmentTypeGateway->unique('name', $name)) {
        $URL = $URL.'&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $data = [
        'name' => $name,
        'description' => $description,
        'active' => $active
    ];

    // Update the record
    $updated = $assessmentTypeGateway->update($gibbonINEligibilityAssessmentTypeID, $data);

    // Success or error
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/eligibility_assessment_types_manage.php&return='.($updated ? 'success0' : 'error2');
    header("Location: {$URL}");
}
