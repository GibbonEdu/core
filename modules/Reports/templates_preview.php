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
use Gibbon\Module\Reports\ReportBuilder;
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateSectionGateway;
use Gibbon\Module\Reports\Renderer\HtmlRenderer;
use Gibbon\Module\Reports\Renderer\MpdfRenderer;
use Gibbon\Module\Reports\Renderer\TcpdfRenderer;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_preview.php') == false) {
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

    // Set reports to cache in a separate location
    $cachePath = $session->has('cachePath') ? $session->get('cachePath').'/reports' : '/uploads/cache';
    $twig->setCache($session->get('absolutePath').$cachePath);

    $reportBuilder = $container->get(ReportBuilder::class);

    // Build Reports
    $template = $reportBuilder->buildTemplate($gibbonReportTemplateID);
    $reports = $reportBuilder->buildReportMock($template);

    // Render
    if (isset($page) && $mode != 'export') {
        $renderer = $container->get(HtmlRenderer::class);

        echo $twig->render('preview.twig.html', $values + [
            'pages' => $renderer->render($template, $reports),
            'fontList' => $template->getData('fonts', []),
            'fontURL' => $template->getData('absoluteURL').$template->getData('customAssetPath').'/fonts',
            'debugData' => $debugMode ? print_r($reports[0], true) : null,
        ]);
    } else {
        ini_set('display_errors', $debugMode ? 1 : 0);

        $filename = preg_replace('/[^a-zA-Z0-9-_]/', '', $values['name']).__('Preview').'.pdf';
        $file = tmpfile();
        $path = stream_get_meta_data($file)['uri'];
        
        $renderer = $container->get($template->getData('flags') == 1 ? MpdfRenderer::class : TcpdfRenderer::class);
        $renderer->render($template, $reports, $path);

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
