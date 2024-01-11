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

use Gibbon\Module\Reports\Domain\ReportTemplateFontGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonReportTemplateFontID = $_POST['gibbonReportTemplateFontID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/templates_assets_fonts_edit.php&gibbonReportTemplateFontID='.$gibbonReportTemplateFontID;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets_fonts_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $fontGateway = $container->get(ReportTemplateFontGateway::class);

    $data = [
        'fontName' => $_POST['fontName'] ?? '',
        'fontFamily' => $_POST['fontFamily'] ?? '',
        'fontType' => $_POST['fontType'] ?? 'R',
    ];

    // Validate the required values are present
    if (empty($data['fontName'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$fontGateway->exists($gibbonReportTemplateFontID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$fontGateway->unique($data, ['fontName'], $gibbonReportTemplateFontID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $fontGateway->update($gibbonReportTemplateFontID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
