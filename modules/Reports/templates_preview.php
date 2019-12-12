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

use Gibbon\Module\Reports\DataFactory;
use Gibbon\Module\Reports\ReportRenderer;
use Gibbon\Module\Reports\ReportTemplate;
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateSectionGateway;
use Faker\Factory;
use Gibbon\Module\Reports\ReportBuilder;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_preview.php') == false) {
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

    $templateGateway = $container->get(ReportTemplateGateway::class);
    $templateSectionGateway = $container->get(ReportTemplateSectionGateway::class);
    $debugMode = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'debugMode');
    $twig = $container->get('twig');

    $gibbonReportTemplateID = $_GET['gibbonReportTemplateID'] ?? '';
    $values = $templateGateway->getByID($gibbonReportTemplateID);

    if (empty($gibbonReportTemplateID) || empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $reportBuilder = $container->get(ReportBuilder::class);

    // Build Reports
    $template = $reportBuilder->buildTemplate($gibbonReportTemplateID);
    $reports = $reportBuilder->buildReportMock($template);

    // Render
    $renderer = new ReportRenderer($template, $twig);

    if (isset($page)) {
        echo $twig->render('preview.twig.html', $values + [
            'pages' => $renderer->renderToHTML($reports),
            'debugData' => $debugMode ? print_r($reports[0], true) : null,
        ]);
    } else {
        $filename = preg_replace('/[^a-zA-Z0-9-_]/', '', $values['name']).__('Preview').'.pdf';
        $file = tmpfile();
        $path = stream_get_meta_data($file)['uri'];
        
        ini_set('display_errors', 0);

        // $path = __DIR__.'/test.pdf';
        $renderer->renderToPDF($reports, $path);

        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="'.htmlentities($filename).'"' );
        // header('Content-Transfer-Encoding: base64');
        // header('Expires: 0');
        // header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        // header('Pragma: public');
        // header('Content-Length: ' . filesize($path));
        echo file_get_contents($path);
        exit;
    }
}
