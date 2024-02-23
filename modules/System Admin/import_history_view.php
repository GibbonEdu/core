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

use Gibbon\Data\ImportType;
use Gibbon\Domain\System\LogGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\PasswordPolicy;

if (isActionAccessible($guid, $connection2, "/modules/System Admin/import_history_view.php") == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonLogID = $_GET['gibbonLogID'] ?? 0;

    $logGateway = $container->get(LogGateway::class);
    $importLog = $logGateway->getLogByID($gibbonLogID);

    if (empty($importLog)) {
        echo $page->getBlankSlate();
        return;
    }

    $importData = isset($importLog['serialisedArray'])? unserialize($importLog['serialisedArray']) : [];
    $importData['log'] = $importLog;
    $importResults = $importData['results'] ?? [];

    if (empty($importData['results']) || !isset($importData['type'])) {
        echo $page->getBlankSlate();
        return;
    }


    $importType = ImportType::loadImportType($importData['type'], $container->get(SettingGateway::class), $container->get(PasswordPolicy::class), $pdo);
    $importData['name'] = $importType->getDetail('name');

    echo $page->fetchFromTemplate('importer.twig.html', array_merge($importData, $importResults));
}
