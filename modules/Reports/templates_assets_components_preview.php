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

use Faker\Factory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Reports\DataFactory;
use Gibbon\Module\Reports\ReportRenderer;
use Gibbon\Module\Reports\ReportTemplate;
use Gibbon\Module\Reports\ReportBuilder;
use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
use Gibbon\Module\Reports\Renderer\HtmlRenderer;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets_components_preview.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    if (isset($page)) {
        $page->breadcrumbs->add(__('Template Preview'));

        $page->stylesheets->add('core', 'resources/assets/css/core.min.css', ['weight' => 10]);
        $page->getTheme()->stylesheets->remove('theme');
        $page->getModule()->stylesheets->remove('module');
    }

    $prototypeGateway = $container->get(ReportPrototypeSectionGateway::class);
    $settingGateway = $container->get(SettingGateway::class);
    $twig = $container->get('twig');

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

    if (isset($page)) {
        echo $twig->render('preview.twig.html', $prototypeSection + [
            'pages' => $renderer->render($template, $reports),
            'prototype' => true,
            'marginX' => '10',
            'marginY' => '5',
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
