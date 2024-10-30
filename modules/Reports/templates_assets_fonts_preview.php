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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Reports\ReportRenderer;
use Gibbon\Module\Reports\ReportBuilder;
use Gibbon\Module\Reports\Domain\ReportTemplateFontGateway;
use Gibbon\Module\Reports\Renderer\HtmlRenderer;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets_components_preview.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $mode = strtolower(basename($_SERVER['SCRIPT_NAME'], '.php'));

    if (isset($page) && $mode != 'export') {
        $page->breadcrumbs->add(__('Template Preview'));

        $page->stylesheets->add('core', 'resources/assets/css/core.min.css', ['weight' => 10]);
        $page->getTheme()->stylesheets->remove('theme');
        $page->getModule()->stylesheets->remove('module');
    }

    $fontGateway = $container->get(ReportTemplateFontGateway::class);
    $settingGateway = $container->get(SettingGateway::class);
    $twig = $container->get('twig');


    $gibbonReportTemplateFontID = $_GET['gibbonReportTemplateFontID'] ?? '';
    $font = $fontGateway->getByID($gibbonReportTemplateFontID);
    if (empty($font)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $reportBuilder = $container->get(ReportBuilder::class);

    // Build Mock Report
    $template = $reportBuilder->createTemplate();

    // Add prototype section
    $template->addSection('font.twig.html');
    $template->addData([
        'fontName'  => $font['fontName'],
        'fontPath'  => $font['fontPath'],
    ]);
    $reports = $reportBuilder->buildReportMock($template);

    // Render
    $renderer = $container->get(HtmlRenderer::class);

    if (isset($page) && $mode != 'export') {
        echo $twig->render('preview.twig.html', [
            'pages'     => $renderer->render($template, $reports),
            'prototype' => true,
            'name'      => $font['fontName'],
            'marginX'   => '10',
            'marginY'   => '10',
        ]);
    } else {
        $filename = preg_replace('/[^a-zA-Z0-9-_]/', '', $prototypeSection['name']).__('Preview').'.pdf';
        $file = tmpfile();
        $path = stream_get_meta_data($file)['uri'];
        $renderer->renderToPDF($reports, $path);

        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="'.htmlentities($filename).'"' );
        echo file_get_contents($path);
        exit;
    }
}
