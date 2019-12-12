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
use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

$_POST['address'] = '/modules/Reports/templates_manage.php';

require_once '../../gibbon.php';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/templates_prototypes.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_prototypes.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;
    $count = 0;

    $prototypeGateway = $container->get(ReportPrototypeSectionGateway::class);
    $yaml = new Yaml();

    $parseAndUpdate = function ($directoryPath, $templateType) use (&$yaml, &$prototypeGateway, &$partialFail, &$count){
        // Get all twig files in this folder and sub-folders
        $directoryPath = '/'.trim($directoryPath, '/');
        $directoryFiles = glob($directoryPath.'{,/*,/*/*,/.../*}/*.twig.html', GLOB_BRACE);

        foreach ($directoryFiles as $filePath) {
            $filename = str_replace($directoryPath.'/', '', $filePath);
            $fileContents = file_get_contents($filePath);

            // Scan the file for the necessary front matter
            if (preg_match_all("/{#<!--([^}]*)-->#}/", $fileContents, $matches)) {
                if (empty($matches[1][0])) continue;
    
                // Parse the front matter from YAML into an array
                try {
                    $config = $yaml::parse($matches[1][0] ?? '');
                } catch (ParseException $e) {
                    // $partialFail = true;
                    $config = [];
                }
    
                if (empty($config['name']) || empty($config['category'])) continue;
    
                // Update the template details in the database
                $data = [
                    'name'           => $config['name'],
                    'type'           => $templateType,
                    'category'       => $config['category'],
                    'types'          => $config['types'] ?? 'Body',
                    'config'         => json_encode($config['config'] ?? []),
                    'templateParams' => json_encode($config['params'] ?? []),
                    'templateFile'   => $filename,
                    'dataSources'    => json_encode($config['sources'] ?? []),
                    'fonts'          => implode(',', $config['fonts'] ?? []),
                ];
    
                $inserted = $prototypeGateway->insertAndUpdate($data, [
                    'name'           => $data['name'],
                    'type'           => $data['type'],
                    'category'       => $data['category'],
                    'types'          => $data['types'],
                    'config'         => $data['config'],
                    'templateParams' => $data['templateParams'],
                    'dataSources'    => $data['dataSources'],
                    'fonts'          => $data['fonts'],
                ]);

                $partialFail &= !$inserted;
                $count += $inserted == true;
            }
        }
    };

    $absolutePath = $gibbon->session->get('absolutePath');
    $customAssetPath = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'customAssetPath');

    $parseAndUpdate($absolutePath.$customAssetPath.'/templates', 'Additional');
    $parseAndUpdate($absolutePath.'/modules/Reports/templates', 'Core');

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}&count={$count}");
}
