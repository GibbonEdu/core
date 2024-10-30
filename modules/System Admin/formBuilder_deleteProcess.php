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

use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Domain\Forms\FormPageGateway;
use Gibbon\Domain\Forms\FormFieldGateway;

require_once '../../gibbon.php';

$gibbonFormID = $_GET['gibbonFormID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/formBuilder.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonFormID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $formGateway = $container->get(FormGateway::class);
    $formPageGateway = $container->get(FormPageGateway::class);
    $formFieldGateway = $container->get(FormFieldGateway::class);

    $values = $formGateway->getByID($gibbonFormID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Delete the form
    $deleted = $formGateway->delete($gibbonFormID);
    $partialFail &= !$deleted;

    $pages = $formPageGateway->selectBy(['gibbonFormID' => $gibbonFormID], ['gibbonFormPageID'])->fetchAll();

    // Delete all the fields for each page
    foreach ($pages as $page) {
        $deleted = $formFieldGateway->deleteWhere(['gibbonFormPageID' => $page['gibbonFormPageID']]);
        $partialFail &= !$deleted;
    }

    // Delete the pages
    $deleted = $formPageGateway->deleteWhere(['gibbonFormID' => $gibbonFormID]);
    $partialFail &= !$deleted;

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");
}
