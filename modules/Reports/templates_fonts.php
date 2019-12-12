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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\Reports\Domain\ReportTemplateFontGateway;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_fonts.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php')
        ->add(__('Manage Fonts'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    // QUERY
    $fontGateway = $container->get(ReportTemplateFontGateway::class);
    $criteria = $fontGateway->newQueryCriteria(true)
        ->sortBy(['fontName'])
        ->fromPOST();

    $fonts = $fontGateway->queryFonts($criteria);
    $absolutePath = $gibbon->session->get('absolutePath');
    $customAssetPath = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'customAssetPath');

    // Data TABLE
    $table = DataTable::createPaginated('manageFonts', $criteria);
    $table->setTitle(__('Manage Fonts'));
    $table->setDescription(__('Place TTF font files in your Custom Asset Path at {path} and scan the directory to generate font files.', ['path' => '<b><u>'.$customAssetPath.'/fonts</u></b>']));

    $table->addHeaderAction('scan', __('Scan Asset Directories'))
        ->setIcon('run')
        ->setURL('/modules/Reports/templates_fonts_scanProcess.php')
        ->directLink(true)
        ->displayLabel();
        
    $table->addColumn('fontName', __('Name'));
    $table->addColumn('fontPath', __('File'));
    $table->addColumn('tcpdf', __('Status'))
        ->format(function ($font) use ($absolutePath) {
            $assetFile = $absolutePath.'/'.$font['fontPath'];
            if (!is_file($assetFile)) {
                return '<span class="tag warning">'.__('Missing Font').'</span>';
            }

            $tcpdfFile = $absolutePath.'/vendor/tecnickcom/tcpdf/fonts/'.$font['fontTCPDF'].'.php';
            if (!is_file($tcpdfFile)) {
                return '<span class="tag error">'.__('Not Installed').'</span>';
            }

            return '<span class="tag success">'.__('Installed').'</span>';
        });

    $table->addActionColumn()
        ->addParam('gibbonReportTemplateFontID')
        ->format(function ($template, $actions) {
            $actions->addAction('view', __('Preview'))
                    ->setURL('/modules/Reports/templates_fonts_preview.php')
                    ->addParam('TB_iframe', 'true')
                    ->modalWindow(900, 500);

            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Reports/templates_fonts_edit.php');
        });

    echo $table->render($fonts);
}
