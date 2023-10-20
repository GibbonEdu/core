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

use Faker\Factory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Reports\ReportBuilder;
use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
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

    $debugMode = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'debugMode');
    $prototypeGateway = $container->get(ReportPrototypeSectionGateway::class);
    $twig = $container->get('twig');

    // Set reports to cache in a separate location
    $cachePath = $session->has('cachePath') ? $session->get('cachePath').'/reports' : '/uploads/cache';
    $container->get('twig')->setCache($session->get('absolutePath').$cachePath);

    $gibbonReportTemplateID = $_GET['gibbonReportTemplateID'] ?? '';
    $gibbonReportPrototypeSectionID = $_GET['gibbonReportPrototypeSectionID'] ?? '';
    $prototypeSection = $prototypeGateway->getByID($gibbonReportPrototypeSectionID);

    if (empty($gibbonReportPrototypeSectionID) || empty($prototypeSection)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $reportBuilder = $container->get(ReportBuilder::class);

    // Build Mock Report
    $template = $reportBuilder->createTemplate();

    // Optionally add current template stylesheet, otherwise use the default
    if (!empty($gibbonReportTemplateID)) {
        $templateData = $container->get(ReportTemplateGateway::class)->getByID($gibbonReportTemplateID);
    }

    $template->addData(['stylesheet' => $templateData['stylesheet'] ?? 'reports/stylesheets/style.twig.html']);

    // Add prototype section
    $template->addSection($prototypeSection['templateFile'])
        ->addDataSources($prototypeSection['dataSources']);

    $reports = $reportBuilder->buildReportMock($template);

    // Render
    $renderer = $container->get(HtmlRenderer::class);

    if (isset($page) && $mode != 'export') {
        echo $twig->render('preview.twig.html', $prototypeSection + [
            'pages' => $renderer->render($template, $reports),
            'prototype' => true,
            'marginX' => '10',
            'marginY' => '5',
            'debugData' => $debugMode ? print_r($reports[0], true) : null,
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
