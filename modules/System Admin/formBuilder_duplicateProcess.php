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

$gibbonFormID = $_POST['gibbonFormID'] ?? '';
$name = $_POST['name'] ?? '';
$type = $_POST['type'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/formBuilder.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_duplicate.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonFormID) || empty($name) || empty($type)) {
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

    // Validate the database relationships exist
    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $data = [
        'name'                  => $name,
        'type'                  => $type,
        'description'           => $values['description'],
        'active'                => 'N',
        'public'                => $values['public'],
        'gibbonYearGroupIDList' => $values['gibbonYearGroupIDList'],
        'config'                => $values['config'],
    ];

    // Validate that this record is unique
    if (!$formGateway->unique($data, ['name'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Duplicate the form
    $gibbonFormIDCopy = $formGateway->insert($data);
    $partialFail &= !$gibbonFormIDCopy;

    $pages = $formPageGateway->selectBy(['gibbonFormID' => $gibbonFormID])->fetchAll();

    // Duplicate the pages
    foreach ($pages as $page) {
        $data = $page;
        $data['gibbonFormID'] = $gibbonFormIDCopy;

        $gibbonFormPageIDCopy = $formPageGateway->insert($data);
        $partialFail &= !$gibbonFormPageIDCopy;

        $fields = $formFieldGateway->selectBy(['gibbonFormPageID' => $page['gibbonFormPageID']])->fetchAll();

        // Duplicate all the fields for each page
        foreach ($fields as $field) {
            $data = $field;
            $data['gibbonFormPageID'] = $gibbonFormPageIDCopy;

            $gibbonFormFieldIDCopy = $formFieldGateway->insert($data);
            $partialFail &= !$gibbonFormFieldIDCopy;
        }
    }
    
    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");
}
