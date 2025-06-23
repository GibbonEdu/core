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

use Gibbon\Domain\Timetable\TimetableGateway;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\UI\Timetable\Timetable;
use Gibbon\UI\Timetable\TimetableContext;

// Gibbon system-wide includes
include './gibbon.php';

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
    include './modules/Timetable/moduleFunctions.php';

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
