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

use Gibbon\Services\Format;
use Gibbon\Module\Reports\DataFactory;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets_components_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    echo '<h1>';
    echo __('Help');
    echo '</h1>';

    echo '<p>';
    echo __("This help page gives a listing of all data sources, core and additional. Data sources need to be added to the front matter of a template before they are available within that template. For each data source there is a listing of all available fields. Some fields are arrays, which require a loop in the template to access the fields in that array.");
    echo '</p>';

    echo '<input type="text" class="w-full" id="helpFilter" placeholder="'.__('Filter by data source name').'"/>';

    $displaySchema = function ($schema) use (&$displaySchema) {
        foreach ($schema as $key => $value) {
            if (is_array($value) && is_string($value[0] ?? null)) {
                // Associative array
                $value = $value[0] ?? '';
                echo "<li>{$key}</li>";
            } elseif (is_array($value)) {
                // Numeric array: recurse
                echo '<li>';
                echo $key.' => Array [';
                echo '<ul class="list-disc font-mono text-sm leading-normal">';
                $displaySchema($value);
                echo '</ul>';
                echo ']';
                echo '</li>';
            } else {
                // Non-array
                echo "<li>{$key}</li>";
            }
        }
    };

    $customAssetPath = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'customAssetPath');
    $dataFactory = $container->get(DataFactory::class);
    $dataFactory->setAssetPath($session->get('absolutePath').$customAssetPath);

    $coreSources = glob(__DIR__ . '/src/Sources/*.php');
    
    if (!empty($coreSources)) {
        echo '<h2>';
        echo __('Core');
        echo '</h2>';
        
        foreach ($coreSources as $sourcePath) {
            $className = strchr(basename($sourcePath), '.', true);
            $source = $dataFactory->get($className);

            if (empty($source)) continue;

            echo '<div class="source">';
            echo '<h3 class="sourceName" style="text-transform: none;">';
            echo $className;
            echo '</h3>';

            echo '<ul class="list-disc font-mono text-sm leading-normal">';
            $displaySchema($source->getSchema());
            echo '</ul>';
            echo '</div>';
        }
    }

    $additionalSources = glob($session->get('absolutePath').'/'.$customAssetPath.'/sources/*.php');

    if (!empty($additionalSources)) {
        echo '<h2>';
        echo __('Additional');
        echo '</h2>';

        foreach ($additionalSources as $sourcePath) {
            $className = strchr(basename($sourcePath), '.', true);
            $source = $dataFactory->get($className);

            if (empty($source)) continue;

            echo '<div class="source">';
            echo '<h3 class="sourceName" style="text-transform: none;">';
            echo $className;
            echo '</h3>';

            echo '<ul class="list-disc font-mono text-sm leading-normal">';
            $displaySchema($source->getSchema());
            echo '</ul>';
            echo '</div>';
        }
    }
}
?>

<script type="text/javascript">
    $( document ).ready(function() {
        $('#helpFilter').keyup(function() {
            var value = $(this).val();
            var exp = new RegExp(value, 'i');

            $('.source').each(function() {
                var isMatch = exp.test($('.sourceName', this).text());
                $(this).toggle(isMatch);
            });
        });
    });
</script>
