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

use Gibbon\Data\Validator;
use Gibbon\Domain\Activities\ActivityChoiceGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonActivityCategoryID = $_POST['gibbonActivityCategoryID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Activities/choices_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/choices_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonActivityCategoryID) || empty($gibbonPersonID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $choiceGateway = $container->get(ActivityChoiceGateway::class);

    $choices = $container->get(ActivityChoiceGateway::class)->selectChoicesByPerson($gibbonActivityCategoryID, $gibbonPersonID);
    if (empty($choices)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $deleted = $choiceGateway->deleteWhere(['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'gibbonPersonID' => $gibbonPersonID]);

    $URL .= !$deleted
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
