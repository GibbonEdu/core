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

use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\IndividualNeeds\StudentSupportPlanGateway;

require_once '../../gibbon.php';

$gibbonStudentSupportPlanID = $_GET['gibbonStudentSupportPlanID'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$action = $_GET['action'] ?? 'download';

$returnPath = $session->get('absoluteURL').'/index.php?q=/modules/Individual Needs/in_supportPlan_manage.php&gibbonPersonID='.$gibbonPersonID;

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_supportPlan_download.php') == false) {
    header("Location: {$returnPath}&return=error0");
    exit;
}

if (empty($gibbonStudentSupportPlanID) || empty($gibbonPersonID)) {
    header("Location: {$returnPath}&return=error1");
    exit;
}

$highestAction = getHighestGroupedAction($guid, '/modules/Individual Needs/in_supportPlan_download.php', $connection2);

// Update return path for parents
if ($highestAction == 'View Individual Education Plans_myChildren') {
    $returnPath = $session->get('absoluteURL').'/index.php?q=/modules/Individual Needs/iep_view_myChildren.php&gibbonPersonID='.$gibbonPersonID;
}

$studentSupportPlanGateway = $container->get(StudentSupportPlanGateway::class);
$plan = $studentSupportPlanGateway->getByID($gibbonStudentSupportPlanID);

if (empty($plan)) {
    header("Location: {$returnPath}&return=error2");
    exit;
}

// plan must belong to the requested person
if ($plan['gibbonPersonID'] != $gibbonPersonID) {
    header("Location: {$returnPath}&return=error0");
    exit;
}

// Access control per role
if ($highestAction == 'Student Support Plans_manage') {
    // No further restriction
} elseif ($highestAction == 'Student Support Plans_view') {
    if ($plan['viewableStaff'] != 'Y' || $plan['active'] != 'Y') {
        header("Location: {$returnPath}&return=error0");
        exit;
    }
} elseif ($highestAction == 'View Individual Education Plans_myChildren') {
    // Parent: validate family relationship
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $children = $container->get(StudentGateway::class)
        ->selectAnyStudentsByFamilyAdult($gibbonSchoolYearID, $session->get('gibbonPersonID'))
        ->fetchGroupedUnique();

    if (empty($children[$gibbonPersonID])) {
        header("Location: {$returnPath}&return=error0");
        exit;
    }

    if ($plan['viewableParents'] != 'Y') {
        header("Location: {$returnPath}&return=error0");
        exit;
    }
} else {
    header("Location: {$returnPath}&return=error0");
    exit;
}

// Serve content
if ($plan['type'] == 'Link') {
    header('Location: '.$plan['filePath']);
    exit;
}

// File type
$absolutePath = $session->get('absolutePath');
$filePath = realpath($absolutePath.'/'.$plan['filePath']);

// Realpath guard
if ($filePath === false || !str_starts_with($filePath, realpath($absolutePath.'/uploads'))) {
    header("Location: {$returnPath}&return=error2");
    exit;
}

if (!file_exists($filePath)) {
    header("Location: {$returnPath}&return=error2");
    exit;
}

$disposition = ($action === 'view') ? 'inline' : 'attachment';
$safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $plan['name']);

header('Content-Type: application/pdf');
header('Content-Disposition: '.$disposition.'; filename="'.$safeName.'.pdf"');
header('Content-Length: '.filesize($filePath));
header('Cache-Control: private');
readfile($filePath);
exit;
