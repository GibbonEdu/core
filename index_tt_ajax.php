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

use Gibbon\Services\Format;
use Gibbon\UI\Timetable\Timetable;
use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\Domain\Timetable\TimetableGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\User\UserGateway;

// Gibbon system-wide includes
require_once __DIR__.'/gibbon.php';

// Setup variables
$gibbonTTID = $_REQUEST['gibbonTTID'] ?? null;
$gibbonPersonID = $_REQUEST['gibbonPersonID'] ?? $session->get('gibbonPersonID');
$gibbonSpaceID = $_REQUEST['gibbonSpaceID'] ?? null;
$format = $_REQUEST['format'] ?? '';
$edit = $_REQUEST['edit'] ?? false;

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') == false) {
    // Access denied
    echo Format::alert(__('Your request failed because you do not have access to this action.'), 'error');
} else {
    require_once __DIR__.'/modules/Timetable/moduleFunctions.php';

    // Check if the user has access to the requested person's timetable
    if (!empty($gibbonPersonID)) {
        $highestAction = getHighestGroupedAction($guid, '/modules/Timetable/tt.php', $connection2);
        if ($highestAction == 'View Timetable by Person_allYears' || $highestAction == 'View Timetable by Person') {
            $gibbonPersonID = $_REQUEST['gibbonPersonID'] ?? $session->get('gibbonPersonID');
        } elseif ($highestAction == 'View Timetable by Person_my') {
            $gibbonPersonID = $session->get('gibbonPersonID');
        } elseif ($highestAction == 'View Timetable by Person_myChildren') {
            $children = $container->get(StudentGateway::class)->selectActiveStudentsByFamilyAdult($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))->fetchGroupedUnique();
            if (empty($children[$gibbonPersonID])) {
                echo Format::alert(__('Your request failed because you do not have access to this action.'), 'error');
                exit;
            }
        }
    }

    // Check if the user has access to the requested timetable within the current school year
    if (!empty($gibbonTTID) && $highestAction != 'View Timetable by Person_allYears') {
        $timetable = $container->get(TimetableGateway::class)->selectBy(['gibbonTTID' => $gibbonTTID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearIDCurrent')])->fetch();
        if (empty($timetable)) {
            echo Format::alert(__('Your request failed because you do not have access to this action.'), 'error');
            exit;
        }
    }

    $ttDate = null;

    if (!empty($_REQUEST['ttDateNav'])) {
        $ttDate = $_REQUEST['ttDateNav'];
    } elseif (!empty($_REQUEST['ttDateChooser'])) {
        $ttDate = $_REQUEST['ttDateChooser'];
    } elseif (!empty($_REQUEST['ttDate'])) {
        $ttDate = Format::dateConvert($_REQUEST['ttDate']);
    }

    // Get and update preferences
    $userGateway = $container->get(UserGateway::class);
    if (!empty($gibbonTTID)) {
        $userGateway->setUserPreferenceByScope($session->get('gibbonPersonID'), 'ttOptions', 'gibbonTTID', preg_replace('/[^0-9]/', '', $gibbonTTID));
    }

    // Create timetable context
    $context = $container->get(TimetableContext::class)
        ->set('gibbonSchoolYearID', $session->get('gibbonSchoolYearID'))
        ->set('gibbonPersonID', $gibbonPersonID)
        ->set('gibbonSpaceID', $gibbonSpaceID)
        ->set('gibbonTTID', $gibbonTTID)
        ->set('format', $format)
        ->set('edit', $edit);

    // Build and render timetable
    echo $container->get(Timetable::class)
        ->setDate($ttDate)
        ->setContext($context)
        ->addCoreLayers($container)
        ->getOutput(); 
}
