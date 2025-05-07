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

//Gibbon system-wide includes
include './gibbon.php';

//Set up for i18n via gettext
if (!empty($session->get('i18n')['code']) && function_exists('gettext')) {
    if ($session->get('i18n')['code'] != null) {
        putenv('LC_ALL='.$session->get('i18n')['code']);
        setlocale(LC_ALL, $session->get('i18n')['code']);
        bindtextdomain('gibbon', './i18n');
        textdomain('gibbon');
        bind_textdomain_codeset('gibbon', 'UTF-8');
    }
}

//Setup variables
$output = '';
$gibbonTTID = !empty($_REQUEST['gibbonTTID']) ? $_REQUEST['gibbonTTID'] : null;
$gibbonPersonID = $_REQUEST['gibbonPersonID'] ?? $session->get('gibbonPersonID');
$narrow = $_REQUEST['narrow'] ?? 'trim';

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
    $timetableGateway = $container->get(TimetableGateway::class);

    if (!empty($_REQUEST['gibbonTTID']) && $gibbonPersonID == $session->get('gibbonPersonID')) {
        $userGateway->setUserPreferenceByScope($session->get('gibbonPersonID'), 'tt', 'gibbonTTID', preg_replace('/[^0-9]/', '', $gibbonTTID));
    }

    $preferences = $userGateway->getUserPreferences($session->get('gibbonPersonID'));
    $timetables = $timetableGateway->selectActiveTimetables($session->get('gibbonSchoolYearID'))->fetchKeyPair();

    if (empty($gibbonTTID)) {
        $gibbonTTID = current($timetables);
    }

    // Create timetable context
    $context = $container->get(TimetableContext::class)
        ->set('gibbonSchoolYearID', $session->get('gibbonSchoolYearID'))
        ->set('gibbonPersonID', $gibbonPersonID)
        ->set('gibbonTTID', $preferences['tt']['gibbonTTID'] ?? $gibbonTTID)
        ->set('timetables', $timetables)
        ->set('layerStates', $preferences['ttLayers'] ?? []);

    // Build and render timetable
    echo $container->get(Timetable::class)
        ->setDate($ttDate)
        ->setContext($context)
        ->addCoreLayers($container)
        ->getOutput(); 

    $edit = ($_REQUEST['edit'] ?? false) && isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php');

    $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, Format::timestamp($ttDate), '', '', $narrow, $edit);
    if ($tt != false) {
        $output .= $tt;
    } else {
        echo Format::alert(__('There is no information for the date specified.'), 'empty');
    }

    
}

echo $output;
