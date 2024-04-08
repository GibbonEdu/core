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
use Gibbon\Tables\DataTable;
use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateFontGateway;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php')
        ->add(__('Manage Assets'));

    $prototypeGateway = $container->get(ReportPrototypeSectionGateway::class);
    $fontGateway = $container->get(ReportTemplateFontGateway::class);

    // COMPONENTS
    $criteria = $prototypeGateway->newQueryCriteria(true)
        ->sortBy(['type', 'category', 'name'])
        ->fromPOST('manageComponents');

    $templates = $prototypeGateway->queryPrototypes($criteria);
    $fonts = $fontGateway->selectFontList()->fetchKeyPair();

    $absolutePath = $session->get('absolutePath');
    $templatePath = $absolutePath.'/modules/Reports/templates';
    $customAssetPath = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'customAssetPath');

    $templates->transform(function (&$template) use (&$fonts, &$absolutePath, &$templatePath, &$customAssetPath) {
        $fontsUsed = array_filter(explode(',', $template['fonts']));
        $fontsMissing = array_filter($fontsUsed, function ($fontName) use (&$fonts) {
            return !isset($fonts[$fontName]);
        });

        if ($template['type'] == 'Core' && !is_file($templatePath.'/'.$template['templateFile'])) {
            $template['status'] = __('Not Installed');
            $template['statusClass'] = 'error';
        } elseif ($template['type'] == 'Additional' && !is_file($absolutePath.$customAssetPath.'/templates/'.$template['templateFile'])) {
            $template['status'] = __('Not Installed');
            $template['statusClass'] = 'error';
        } else if (stripos(basename($template['templateFile']), '.twig.html') === false) {
            $template['status'] = __('Invalid File Type');
            $template['statusClass'] = 'error';
            $template['statusTitle'] = __('The file {file} is missing the extension {ext} and may not work as expected.', ['file' => basename($template['templateFile']), 'ext' => '.twig.html']);
        }else if (!empty($fontsMissing)) {
            $template['status'] = __('Missing Font');
            $template['statusClass'] = 'warning';
            $template['statusTitle'] = implode('<br/>', $fontsMissing);
        } else {
            $template['status'] = __('Installed');
            $template['statusClass'] = 'success';
        }
    });

    // Data TABLE
    $table = DataTable::createPaginated('manageComponents', $criteria);
    $table->setTitle(__('Components'));
    $table->setDescription(__('Place templates in your Custom Asset Path at {path} and scan the directory to update components.', ['path' => '<b><u>'.$customAssetPath.'/templates</u></b>']));

    $table->addHeaderAction('scan', __('Scan Asset Directories'))
        ->setIcon('run')
        ->setURL('/modules/Reports/templates_assets_scanProcess.php')
        ->directLink(true)
        ->displayLabel();
        
    $table->addColumn('name', __('Name'))
        ->format(function ($template) {
            return Format::tooltip(__($template['name']), $template['templateFile']);
        });
    $table->addColumn('type', __('Type'))->translatable();
    $table->addColumn('category', __('Category'))->translatable();
    $table->addColumn('status', __('Status'))
        ->width('20%')
        ->notSortable()
        ->format(function ($template) {
            return '<span class="tag '.($template['statusClass'] ?? '').'" title="'.($template['statusTitle'] ?? '').'">'.$template['status'].'</span>';
        });

    $table->addActionColumn()
        ->addParam('gibbonReportPrototypeSectionID')
        ->format(function ($template, $actions) {
            if ($template['status'] != __('Installed')) {
                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Reports/templates_assets_components_delete.php');
                    return;
            }

            $actions->addAction('view', __('Preview'))
                    ->setURL('/modules/Reports/templates_assets_components_preview.php')
                    ->addParam('TB_iframe', 'true')
                    ->modalWindow(900, 500);
            
            if ($template['type'] == 'Additional') {
                $actions->addAction('edit', __('Edit'))
                        ->addParam('sidebar', 'false')
                        ->setURL('/modules/Reports/templates_assets_components_edit.php');
                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Reports/templates_assets_components_delete.php');
            }

            $actions->addAction('duplicate', __('Duplicate'))
                ->setIcon('copy')
                ->setURL('/modules/Reports/templates_assets_components_duplicate.php');
        });

    echo $table->render($templates);


    // FONTS
    $fontGateway = $container->get(ReportTemplateFontGateway::class);
    $criteria = $fontGateway->newQueryCriteria(true)
        ->sortBy(['fontName'])
        ->fromPOST('manageFonts');

    $fonts = $fontGateway->queryFonts($criteria);
    $absolutePath = $session->get('absolutePath');
    $customAssetPath = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'customAssetPath');

    // Data TABLE
    $table = DataTable::createPaginated('manageFonts', $criteria);
    $table->setTitle(__('Fonts'));
    $table->setDescription(__('Place TTF font files in your Custom Asset Path at {path} and scan the directory to generate font files.', ['path' => '<b><u>'.$customAssetPath.'/fonts</u></b>']));
        
    $table->addColumn('fontName', __('Name'))
        ->format(function ($font) {
            return Format::tooltip($font['fontName'], $font['fontPath']);
        });
        
    $table->addColumn('tcpdf', __('Status'))
        ->width('20%')
        ->notSortable()
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
                    ->setURL('/modules/Reports/templates_assets_fonts_preview.php')
                    ->addParam('TB_iframe', 'true')
                    ->modalWindow(900, 500);

            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Reports/templates_assets_fonts_edit.php');
        });

    echo $table->render($fonts);
}
