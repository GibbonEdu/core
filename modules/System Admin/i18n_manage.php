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

use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\System\I18nGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/i18n_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Languages'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array(
            'error3' => __('Failed to download and install the required files.').' '.sprintf(__('To install a language manually, upload the language folder to %1$s on your server and then refresh this page. After refreshing, the language should appear in the list below.'), '<b><u>'.$_SESSION[$guid]['absolutePath'].'/i18n/</u></b>')
            )
        );
    }

    // Update any existing languages that may have been installed manually
    i18nCheckAndUpdateVersion($container, $version);

    $i18nGateway = $container->get(I18nGateway::class);

    // CRITERIA
    $criteria = $i18nGateway->newQueryCriteria()
        ->sortBy('code')
        ->fromPOST('i18n_installed');

    $languages = $i18nGateway->queryI18n($criteria, 'Y');

    $languages->transform(function(&$i18n) use ($guid)  {
        $i18n['isInstalled'] = i18nFileExists($_SESSION[$guid]['absolutePath'], $i18n['code']);
    });

    $form = Form::create('i18n_manage', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/i18n_manageProcess.php');
    $form->setTitle(__('Installed'));
    $form->setClass('fullWidth');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->setClass('w-full blank');

    // DATA TABLE
    $table = $form->addRow()->addDataTable('i18n_installed', $criteria)->withData($languages);

    $table->addMetaData('hidePagination', true);

    $table->modifyRows(function ($i18n, $row){
        if (!$i18n['isInstalled']) return null;
        if ($i18n['active'] == 'N') $row->addClass('error');

        return $row;
    });

    $table->addColumn('name', __('Name'))->width('50%');
    $table->addColumn('code', __('Code'))->width('10%');
    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));
    $table->addColumn('default', __('Default'))
        ->notSortable()
        ->format(function($i18n) use ($form) {
            if ($i18n['active'] == 'Y') {
                $checked = ($i18n['systemDefault'] == 'Y')? $i18n['gibboni18nID'] : '';

                return $form->getFactory()
                    ->createRadio('gibboni18nID')
                    ->addClass('inline right')
                    ->fromArray(array($i18n['gibboni18nID'] => ''))
                    ->checked($checked)
                    ->getOutput();
            }

            return '';
        });

    $table->addActionColumn()
        ->addParam('gibboni18nID')
        ->format(function ($i18n, $actions) use ($version) {
            
            if (version_compare($version, $i18n['version'], '>')) {
                $actions->addAction('update', __('Update'))
                    ->setIcon('delivery2')
                    ->modalWindow(650, 135)
                    ->addParam('mode', 'update')
                    ->setURL('/modules/System Admin/i18n_manage_install.php');
            }
        });

    $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth standardForm');
    $table->addRow()->addSubmit();

    $installedCount = array_reduce($languages->toArray(), function ($count, $i18n) {
        return ($i18n['isInstalled'])? $count + 1 : $count;
    }, 0);

    if ($installedCount == 0) {
        echo '<div class="message">';
        echo __('There are no language files installed. Your system is currently using the default language.').' '.__('Use the list below to install a new language.');
        echo '</div><br/>';
    } else {
        echo $form->getOutput();
    }

    // CRITERIA
    $criteria = $i18nGateway->newQueryCriteria()
        ->sortBy('code')
        ->fromPOST('i18n');

    $languages = $i18nGateway->queryI18n($criteria, 'N');

    $languages->transform(function(&$i18n) use ($guid)  {
        $i18n['isInstalled'] = i18nFileExists($_SESSION[$guid]['absolutePath'], $i18n['code']);
    });

    // DATA TABLE
    $table = DataTable::createPaginated('i18n', $criteria);
    $table->setTitle(__('Not Installed'));
    $table->setDescription(__('Inactive languages are not yet ready for use within the system as they are still under development. They cannot be set to default, nor selected by users.'));

    $table->addMetaData('hidePagination', true);

    $table->modifyRows(function ($i18n, $row) use ($guid) {
        // if ($i18n['isInstalled']) return null;
        if ($i18n['active'] == 'N') $row->addClass('error');
        return $row;
    });

    // COLUMNS
    $table->addColumn('name', __('Name'))->width('50%');
    $table->addColumn('code', __('Code'))->width('10%');
    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

    $table->addActionColumn()
        ->addParam('gibboni18nID')
        ->format(function ($i18n, $actions) {
            if ($i18n['active'] == 'Y') {
                $actions->addAction('install', __('Install'))
                    ->setIcon('page_new')
                    ->modalWindow(650, 135)
                    ->addParam('mode', 'install')
                    ->setURL('/modules/System Admin/i18n_manage_install.php');
            }
        });

    echo $table->render($languages);
}
