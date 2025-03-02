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

use Gibbon\Domain\Interventions\INEligibilityAssessmentTypeGateway;

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/eligibility_assessment_types_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/eligibility_assessment_types_add.php') == false) {
    // Access denied
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $assessmentTypeGateway = $container->get(INEligibilityAssessmentTypeGateway::class);
    
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
    if (!$assessmentTypeGateway->unique('name', $name)) {
        $URL = $URL.'&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    $data = [
        'name' => $name,
        'description' => $description,
        'active' => $active
    ];

    // Insert the record
    $inserted = $assessmentTypeGateway->insert($data);

    // Success or error
    if ($inserted) {
        $URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/eligibility_assessment_types_manage.php&return=success0';
    } else {
        $URL = $URL.'&return=error2';
    }
    
    header("Location: {$URL}");
}
