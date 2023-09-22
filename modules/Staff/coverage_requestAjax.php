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
use Gibbon\Module\Staff\Forms\CoverageRequestForm;

$_POST['address'] = '/modules/Staff/coverage_request.php';

require_once '../../gibbon.php';

$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$dateStart = $_POST['dateStart'] ?? '';
$dateEnd = $_POST['dateEnd'] ?? '';

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php') == false) {
    die(Format::alert(__('You do not have access to this action.'), 'error flex-1'));
} elseif (empty($gibbonPersonID) || empty($dateStart) || empty($dateEnd)) {
    die(Format::alert(__('You have not specified one or more required parameters.'), 'error flex-1'));
} else {
    // Proceed!
    $dateStart = Format::dateConvert($dateStart);
    $dateEnd = Format::dateConvert($dateEnd);
    $allDay = $_POST['allDay'] ?? '';
    $timeStart = $_POST['timeStart'] ?? '';
    $timeEnd = $_POST['timeEnd'] ?? '';
    
    // FORM
    $form = $container->get(CoverageRequestForm::class)->createForm($gibbonPersonID, $dateStart, $dateEnd, $allDay, $timeStart, $timeEnd);
    $form->setClass('blank standardForm flex-1');
    $form->setAction('ajax');

    echo $form->getOutput();
}
