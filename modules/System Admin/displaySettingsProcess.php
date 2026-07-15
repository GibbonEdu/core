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
use Gibbon\Data\Validator;
use Gibbon\FileUploader;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Filesystem\FileHandler;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/displaySettings.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/displaySettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $partialFail = false;
    $settingGateway = $container->get(SettingGateway::class);

    $settingsToUpdate = [
        'System' => [
            'mainMenuCategoryOrder'  => 'required',
            'themeColour'            => '',
            'organisationLogo'       => 'requiredFile',
            'organisationBackground' => '',
            'notificationIntervalStaff'  => 'required',
            'notificationIntervalOther'  => 'required',
        ]
    ];

    // Validate required fields
    foreach ($settingsToUpdate as $scope => $settings) {
        foreach ($settings as $name => $property) {
            if ($property == 'requiredFile' && empty($_FILES[$name.'File']['tmp_name']) && empty($_POST[$name])) {
                $URL .= '&return=error6';
                header("Location: {$URL}");
                exit;
            }
            if ($property == 'required' && empty($_POST[$name])) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
                exit;
            }
        }
    }

    $fileUploader = new FileUploader($pdo, $session);
    $fileUploader->getFileExtensions('Graphics/Design');
    $logoFileMetaData = null;
    $backgroundFileMetaData = null;

    // Move attached logo file, if there is one
    if (!empty($_FILES['organisationLogoFile']['tmp_name'])) {
        $file = $_FILES['organisationLogoFile'] ?? null;

        // Upload the file, return the /uploads relative path
        $_POST['organisationLogo'] = $fileUploader->uploadFromPost($file, 'logo');

        if (empty($_POST['organisationLogo'])) {
            $partialFail = true;
        } else {
            $logoFileMetaData = $fileUploader->getFileMetaData($_POST['organisationLogo']);
        }
    } else {
        $_POST['organisationLogo'] = $settingGateway->getSettingByScope('System', 'organisationLogo');
    }

    // Move attached background file, if there is one
    if (!empty($_FILES['organisationBackgroundFile']['tmp_name'])) {
        $file = $_FILES['organisationBackgroundFile'] ?? null;

        // Upload the file, return the /uploads relative path
        $_POST['organisationBackground'] = $fileUploader->uploadFromPost($file, 'background');

        if (empty($_POST['organisationBackground'])) {
            $partialFail = true;
        } else {
            $backgroundFileMetaData = $fileUploader->getFileMetaData($_POST['organisationBackground']);
        }
    } else {
        $oldBackground = $settingGateway->getSettingByScope('System', 'organisationBackground');
        $_POST['organisationBackground'] = !empty($_POST['organisationBackground']) ? $oldBackground : '';
    }

    // Update fields
    foreach ($settingsToUpdate as $scope => $settings) {
        foreach ($settings as $name => $property) {
            $value = $_POST[$name] ?? '';

            if ($property == 'skip-empty' && empty($value)) continue;

            $updated = $settingGateway->updateSettingByScope($scope, $name, $value);
            $partialFail &= !$updated;
        }
    }

    // Record file tracking for logo
    if (!empty($logoFileMetaData) ) {
        $logoSettingRecord = $settingGateway->selectBy(['scope' => 'System', 'name' => 'organisationLogo'])->fetch();

        if (!empty($logoSettingRecord)) {
            $gibbonFileID = $container->get(FileHandler::class)->recordFileUpload($logoFileMetaData, 'gibbonSetting', $logoSettingRecord['gibbonSettingID'], 'value');
            
            if (empty($gibbonFileID)) {
                $partialFail = true;
            }
        }
    }

    $backgroundSettingRecord = $settingGateway->selectBy(['scope' => 'System', 'name' => 'organisationBackground'])->fetch();
    // Record file tracking for background
    if (!empty($backgroundFileMetaData) && !empty($backgroundSettingRecord)) {
        $gibbonFileID = $container->get(FileHandler::class)->recordFileUpload($backgroundFileMetaData, 'gibbonSetting', $backgroundSettingRecord['gibbonSettingID'], 'value');
        
        if (empty($gibbonFileID)) {
            $partialFail = true;
        }
    }

    // Handle file deletion for background
    if (!empty($backgroundSettingRecord) && empty($_POST['organisationBackground']) && !empty($oldBackground)) {
        $deleted = $container->get(FileHandler::class)->deleteFile('gibbonSetting', $backgroundSettingRecord['gibbonSettingID'], 'value');
    }

    // Update all the system settings that are stored in the session
    getSystemSettings($guid, $connection2);
    $session->set('pageLoads', null);

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");
}
