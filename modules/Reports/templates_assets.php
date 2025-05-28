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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateFontGateway;
use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php')
        ->add(__('Manage Assets'));

    $search = (isset($_GET['search']) ? $_GET['search'] : '');
    $prototypeGateway = $container->get(ReportPrototypeSectionGateway::class);
    $fontGateway = $container->get(ReportTemplateFontGateway::class);

    // CRITERIA
    $criteria = $prototypeGateway->newQueryCriteria(true)
        ->searchBy($prototypeGateway->getSearchableColumns(), $search)
        ->sortBy(['type', 'category', 'name'])
        ->filterBy('active', $_GET['active'] ?? 'Y')
        ->fromPOST();

    // SEARCH FORM
    $form = Form::create('searchForm', $session->get('absoluteURL') . '/index.php', 'get');
    $form->setTitle(__('Search'));
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('q', '/modules/Reports/templates_assets.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Name, type, category.'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);
    
    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

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

     // Bulk Action FORM
     $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/'.$session->get('module').'/templates_assetsProcessBulk.php');
     $form->addHiddenValue('search', $search);
 
     $bulkActions = ['ActiveStatus' => __('Set to Active'), 'InactiveStatus' => __('Set to Inactive')];
 
     $col = $form->createBulkActionColumn($bulkActions);
         $col->addSubmit(__('Go'));

    // Data TABLE
    $table = $form->addRow()->addDataTable('manageComponents', $criteria)->withData($templates);
    $table->setTitle(__('Assets'));
    $table->setDescription(__('Place templates in your Custom Asset Path at {path} and scan the directory to update components.', ['path' => '<b><u>'.$customAssetPath.'/templates</u></b>']));

    $table->addHeaderAction('scan', __('Scan Asset Directories'))
        ->setIcon('run')
        ->setURL('/modules/Reports/templates_assets_scanProcess.php')
        ->directLink(true)
        ->displayLabel();
    
    $table->addMetaData('filterOptions', [
        'active:Y'  => __('Active').': '.__('Yes'),
        'active:N'  => __('Active').': '.__('No'),
        'type:Core'  => __('Type').': '.__('Core'),
        'type:Additional'  => __('Type').': '.__('Additional'),
    ]);

    $table->addMetaData('bulkActions', $col);

    $table->modifyRows(function($values, $row) {
        if (!empty($values['active']) && $values['active'] != 'Y') $row->addClass('error');
        return $row;
    });
    
    $table->addColumn('name', __('Name'))
        ->format(function ($template) {
            return Format::tooltip(__($template['name']), $template['templateFile']);
        });
    $table->addColumn('type', __('Type'))->translatable();
    $table->addColumn('category', __('Category'))->translatable();
    $table->addColumn('status', __('Status'))
        ->width('10%')
        ->notSortable()
        ->format(function ($template) {
            return '<span class="tag '.($template['statusClass'] ?? '').'" title="'.($template['statusTitle'] ?? '').'">'.$template['status'].'</span>';
        });

    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

    $userGateway = $container->get(UserGateway::class);
    $table->addColumn('gibbonPersonIDLastEdit', __('Edited By'))
            ->format(function ($row) use ($userGateway) {
            if(empty($row['gibbonPersonIDLastEdit'])) {
                return __('N/A');
            }
            $person = $userGateway->getByID($row['gibbonPersonIDLastEdit']);
            $output = Format::name('', $person['preferredName'], $person['surname'], 'Staff', false, true);
            return $output;
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
            }
            
            if ($template['type'] == 'Additional' && $template['active'] == 'N') {
                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Reports/templates_assets_components_delete.php');
            }

            $actions->addAction('duplicate', __('Duplicate'))
                    ->setIcon('copy')
                    ->setURL('/modules/Reports/templates_assets_components_duplicate.php');
        });

    $table->addCheckboxColumn('gibbonReportPrototypeSectionID');
    echo $form->getOutput();
}
