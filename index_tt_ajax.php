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
use Gibbon\UI\Timetable\TimetableLayer;
use Gibbon\UI\Timetable\Layers\TestLayer;

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
$gibbonTTID = !empty($_REQUEST['gibbonTTID']) ? $_REQUEST['gibbonTTID'] : '00000015';
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

    echo $container->get(Timetable::class)
        ->setDate($ttDate)
        ->setTimetable($gibbonTTID, $gibbonPersonID)
        // ->addLayer($container->get(TestLayer::class))
        ->addCoreLayers($container)
        ->getOutput(); 

    $edit = ($_REQUEST['edit'] ?? false) && isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php');

    // $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, Format::timestamp($ttDate), '', '', $narrow, $edit);
    // if ($tt != false) {
    //     $output .= $tt;
    // } else {
    //     echo Format::alert(__('There is no information for the date specified.'), 'empty');
    // }
}

echo $output;
