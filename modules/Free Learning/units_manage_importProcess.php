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

use Gibbon\FileUploader;
use Gibbon\Module\FreeLearning\UnitImporter;

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Free Learning/units_manage.php';

// Override the ini to keep this process alive
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 1800);
set_time_limit(1800);

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $gibbonDepartmentIDList = isset($_POST['gibbonDepartmentIDList']) ? implode(',', $_POST['gibbonDepartmentIDList']) : '';
    $course = $_POST['course'] ?? '';
    $override = $_POST['override'] ?? false;
    $delete = $_POST['delete'] ?? false;

    if (empty($_FILES['file'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $fileUploader = new FileUploader($pdo, $session);
    $zipFile = $fileUploader->uploadFromPost($_FILES['file']);

    if (empty($zipFile)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $importer = $container->get(UnitImporter::class);
    $importer->setDefaults($gibbonDepartmentIDList, $course);
    $importer->setOverride($override == 'Y');
    $importer->setDelete($delete == 'Y');

    $success = $importer->importFromFile($session->get('absolutePath').'/'.$zipFile);

    $URL .= !$success
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");

}
