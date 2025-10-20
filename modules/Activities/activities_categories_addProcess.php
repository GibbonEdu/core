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
use Gibbon\Services\Format;
use Gibbon\Domain\Activities\ActivityCategoryGateway;
use Gibbon\Domain\Activities\EventDateGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Activities/activities_categories_add.php';

$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_categories_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $categoryGateway = $container->get(ActivityCategoryGateway::class);

    $data = [
        'gibbonSchoolYearID'    => $gibbonSchoolYearID,
        'name'                  => $_POST['name'] ?? '',
        'nameShort'             => $_POST['nameShort'] ?? '',
        'signUpChoices'         => $_POST['signUpChoices'] ?? '3',
        'description'           => $_POST['description'] ?? '',
        'backgroundImage'       => $_POST['backgroundImage'] ?? '',
        'active'                => $_POST['active'] ?? 'N',
        'viewableDate'          => !empty($_POST['viewableDate'])
                                ? Format::dateConvert($_POST['viewableDate']).' '.($_POST['viewableTime'] ?? '00:00')
                                : null,
        'accessOpenDate'        => !empty($_POST['accessOpenDate'])
                                ? Format::dateConvert($_POST['accessOpenDate']).' '.($_POST['accessOpenTime'] ?? '00:00')
                                : null,
        'accessCloseDate'        => !empty($_POST['accessCloseDate'])
                                ? Format::dateConvert($_POST['accessCloseDate']).' '.($_POST['accessCloseTime'] ?? '00:00')
                                : null,
        'accessEnrolmentDate'   => !empty($_POST['accessEnrolmentDate'])
                                ? Format::dateConvert($_POST['accessEnrolmentDate']).' '.($_POST['accessEnrolmentTime'] ?? '00:00')
                                : null,
    ];

    // Validate the required values are present
    if (empty($data['name']) || empty($data['nameShort']) || empty($data['active'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$categoryGateway->unique($data, ['name', 'gibbonSchoolYearID'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Move attached file, if there is one
    if (!empty($_FILES['backgroundImageFile']['tmp_name'])) {
        $fileUploader = new Gibbon\FileUploader($pdo, $session);
        $fileUploader->getFileExtensions('Graphics/Design');

        $file = $_FILES['backgroundImageFile'] ?? null;

        // Upload the file, return the /uploads relative path
        $data['backgroundImage'] = $fileUploader->uploadFromPost($file, $data['name']);

        if (empty($data['backgroundImage'])) {
            $partialFail = true;
        }
    }

    // Create the record
    $gibbonActivityCategoryID = $categoryGateway->insert($data);

    $URL .= !$gibbonActivityCategoryID
        ? "&return=error2"
        : "&return=success0&editID=$gibbonActivityCategoryID";

    header("Location: {$URL}");
}
