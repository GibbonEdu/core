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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateFontGateway;
use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;
use Symfony\Component\Yaml\Yaml;
use TCPDF_FONTS;

$_POST['address'] = '/modules/Reports/templates_assets.php';

require_once '../../gibbon.php';
require_once __DIR__.'/moduleFunctions.php';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/templates_assets.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;
    $count = 0;

    $absolutePath = $gibbon->session->get('absolutePath');
    $customAssetPath = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'customAssetPath');

    // COMPONENTS
    $prototypeGateway = $container->get(ReportPrototypeSectionGateway::class);
    $yaml = new Yaml();

    $parseAndUpdateComponents = function ($directoryPath, $templateType) use (&$prototypeGateway, &$yaml, &$partialFail, &$count) {
        // Get all twig files in this folder and sub-folders
        $directoryPath = '/'.trim($directoryPath, '/');
        $directoryFiles = glob($directoryPath.'{,/*,/*/*,/.../*}/*.twig.html', GLOB_BRACE);

        foreach ($directoryFiles as $filePath) {
            // Scan the file for the necessary front matter
            if ($data = parseComponent($directoryPath, $filePath, $templateType, $yaml)) {
                $inserted = $prototypeGateway->insertAndUpdate($data, [
                    'name'           => $data['name'],
                    'type'           => $data['type'],
                    'category'       => $data['category'],
                    'types'          => $data['types'],
                    'config'         => $data['config'],
                    'templateParams' => $data['templateParams'],
                    'dataSources'    => $data['dataSources'],
                    'fonts'          => $data['fonts'],
                    'icon'           => $data['icon'],
                ]);

                $partialFail &= !$inserted;
                $count += $inserted == true;
            }
        }
    };

    $parseAndUpdateComponents($absolutePath.$customAssetPath.'/templates', 'Additional');
    $parseAndUpdateComponents($absolutePath.'/modules/Reports/templates', 'Core');


    // FONTS
    $fontGateway = $container->get(ReportTemplateFontGateway::class);
    $parseAndUpdateFonts = function ($directoryPath) use (&$absolutePath, &$fontGateway, &$partialFail, &$count) {
        // Get all font files in this folder and sub-folders
        $directoryPath = '/'.trim($directoryPath, '/');
        $directoryFiles = glob($directoryPath.'{,/*,/.../*}/*.ttf', GLOB_BRACE);

        foreach ($directoryFiles as $filePath) {
            $fontTCPDF = \TCPDF_FONTS::addTTFfont($filePath, 'TrueTypeUnicode', '', 32);
            
            if (empty($fontTCPDF)) continue;

            // Update the font details in the database
            $data = [
                'fontName' => str_replace(['.ttf'], [''], basename($filePath)),
                'fontPath' => str_replace($absolutePath.'/', '', $filePath),
                'fontTCPDF' => $fontTCPDF,
            ];

            $inserted = $fontGateway->insertAndUpdate($data, [
                'fontPath'  => $data['fontPath'],
            ]);

            $partialFail &= !$inserted;
            $count += $inserted == true;
        }
    };

    $parseAndUpdateFonts($absolutePath.$customAssetPath.'/fonts', 'Additional');
    $parseAndUpdateFonts($absolutePath.'/resources/assets/fonts', 'Core');

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}&count={$count}");
}
