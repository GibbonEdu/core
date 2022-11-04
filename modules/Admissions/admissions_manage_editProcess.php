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

use Gibbon\Services\Module\Action;
use Gibbon\Http\Url;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;

require_once '../../gibbon.php';

$gibbonAdmissionsAccountID = $_POST['gibbonAdmissionsAccountID'] ?? '';
$search = $_POST['search'] ?? '';

$URL = Url::fromModuleRoute('Admissions', 'admissions_manage_edit')->withQueryParams(['gibbonAdmissionsAccountID' => $gibbonAdmissionsAccountID, 'search' => $search]);

if (isActionAccessible($guid, $connection2, Action::fromRoute('Admissions', 'admissions_manage_edit')) == false) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} else {
    // Proceed!
    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);

    $data = [
        'email' => $_POST['email'] ?? '',
    ];

    // Validate the required values are present
    if (empty($data['email']) || empty($gibbonAdmissionsAccountID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$admissionsAccountGateway->exists($gibbonAdmissionsAccountID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$admissionsAccountGateway->unique($data, ['email'], $gibbonAdmissionsAccountID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $admissionsAccountGateway->update($gibbonAdmissionsAccountID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
