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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateFontGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php')
        ->add(__('Manage Fonts'));

    // FONTS
    $search = (isset($_GET['search']) ? $_GET['search'] : '');
    $fontGateway = $container->get(ReportTemplateFontGateway::class);

    // CRITERIA
    $criteria = $fontGateway->newQueryCriteria(true)
        ->searchBy($fontGateway->getSearchableColumns(), $search)
        ->sortBy(['fontName'])
        ->fromPOST();

    // SEARCH FORM
    $form = Form::create('searchForm', $session->get('absoluteURL') . '/index.php', 'get');
    $form->setTitle(__('Search'));
    $form->setClass('noIntBorder w-full');
    
    $form->addHiddenValue('q', '/modules/Reports/templates_assets_fonts.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Name'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

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
            if (!empty($font['fontTCPDF']) && !is_file($tcpdfFile)) {
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
