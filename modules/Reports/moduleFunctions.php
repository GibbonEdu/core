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

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

function parseComponent($directoryPath, $filePath, $templateType = 'Additional', Yaml $yaml = null)
{
    if (empty($yaml)) $yaml = new Yaml();

    $filename = str_replace($directoryPath.'/', '', $filePath);
    $fileContents = file_get_contents($filePath);

    // Scan the file for the necessary front matter
    if (preg_match_all("/{#<!--([^}]*)-->#}/", $fileContents, $matches)) {
        if (empty($matches[1][0])) return [];

        // Parse the front matter from YAML into an array
        try {
            $config = $yaml::parse($matches[1][0] ?? '');
        } catch (ParseException $e) {
            // $partialFail = true;
            $config = [];
        }

        if (empty($config['name']) || empty($config['category'])) return [];

        // Update the template details in the database
        return [
            'name'           => $config['name'],
            'type'           => $templateType,
            'category'       => $config['category'],
            'types'          => $config['types'] ?? 'Body',
            'config'         => json_encode($config['config'] ?? []),
            'templateParams' => json_encode($config['params'] ?? []),
            'templateFile'   => $filename,
            'dataSources'    => json_encode($config['sources'] ?? []),
            'fonts'          => implode(',', $config['fonts'] ?? []),
            'icon'           => $config['icon'] ?? '',
        ];
    }

    return [];
}
