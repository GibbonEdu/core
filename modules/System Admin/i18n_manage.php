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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/i18n_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__('Manage Languages').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array(
            'error3' => __('Failed to download and install the required files.').' '.sprintf(__('To install a language manually, upload the language folder to %1$s on your server and then refresh this page. After refreshing, the language should appear in the list below.'), '<b><u>'.$_SESSION[$guid]['absolutePath'].'/i18n/</u></b>')
            )
        );
    }

    echo '<h2>';
    echo __('Installed');
    echo '</h2>';
    
    $i18nGateway = $container->get(I18nGateway::class);

    // CRITERIA
    $criteria = $i18nGateway->newQueryCriteria()
        ->sortBy('code')
        ->pageSize(0)
        ->fromArray($_POST);

    $installed = $i18nGateway->queryI18n($criteria);

    $form = Form::create('i18n_manage', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/i18n_manageProcess.php');

    $form->setClass('fullWidth');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->getRenderer()->setWrapper('form', 'div')->setWrapper('row', 'div')->setWrapper('cell', 'fieldset');

    // DATA TABLE
    $table = $form->addRow()->addDataTable('i18n', $criteria)->withData($installed);

    $table->addMetaData('hidePagination', true);

    $table->modifyRows(function ($i18n, $row) use ($guid) {
        $isInstalled = file_exists($_SESSION[$guid]['absolutePath'].'/i18n/'.$i18n['code'].'/LC_MESSAGES/gibbon.mo');
        if (!$isInstalled) return null;
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

    $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth standardForm');
    $table->addRow()->addSubmit();

    echo $form->getOutput();


    echo '<h2>';
    echo __('Not Installed');
    echo '</h2>';

    echo '<p>';
    echo __('Inactive languages are not yet ready for use within the system as they are still under development. They cannot be set to default, nor selected by users.');
    echo '</p>';

    $notInstalled = $i18nGateway->queryI18n($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('i18n', $criteria);

    $table->addMetaData('hidePagination', true);

    $table->modifyRows(function ($i18n, $row) use ($guid) {
        $isInstalled = file_exists($_SESSION[$guid]['absolutePath'].'/i18n/'.$i18n['code'].'/LC_MESSAGES/gibbon.mo');
        if ($isInstalled) return null;
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
                    ->isModal(650, 135)
                    ->setURL('/modules/System Admin/i18n_manage_install.php');
            }
        });

    echo $table->render($notInstalled);
}
