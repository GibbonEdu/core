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
use Gibbon\Services\Format;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\DataRetentionGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/dataRetention.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Data Retention'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, ['success0' => __('{count} records were successfully scrubbed. These records still exist in the database, but their personal data has now been removed.', ['count' => $_GET['count'] ?? 0])]);
    }

    $form = Form::create('dataRetention', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/dataRetentionProcess.php');
    
    $form->setDescription(__('Comply with privacy regulations by flushing older, non-academic, data from the system.')." ".__('This action will scrub selected data for all users in the specified category whose status is Left, and whose end date preceeds the specified data. This process clears certain fields, rather than removing any database rows.'));
    $form->addConfirmation(__('Are you sure you wish to process this action? It cannot be undone.'));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $schoolYear = $container->get(SchoolYearGateway::class)->getByID($gibbon->session->get('gibbonSchoolYearIDCurrent'), ['firstDay']);

    $row = $form->addRow();
        $row->addLabel('date', __('Cutoff Date'))->description(__("Include users with an end date preceding this date. Last login is used as a fallback. Must be prior to the current school year."));
        $row->addDate('date')->required()->maximum($schoolYear['firstDay'] ?? date('Y-m-d'));

    $dataRetentionGateway = $container->get(DataRetentionGateway::class);
    $checked = explode(',', $container->get(SettingGateway::class)->getSettingByScope('System', 'dataRetentionDomains'));
    $domains = $dataRetentionGateway->getDomains();

    $col = $form->addRow()->addColumn();
        $col->addLabel('domains', __('Category'))
            ->description(__('Areas of the system to scrub. The current selection will persist.'));

        foreach ($domains as $name => $domain) {
            $description = '<div class="flex-1 text-left"><span class="text-base leading-normal">'.__($name).'</span><br/><span class="text-xxs text-gray-600">'.($domain['description'] ?? '' ).'</span></div>';
            $col->addCheckbox("domains[{$name}]")
                ->setValue($name)
                ->checked(in_array($name, $checked) ? $name : '')
                ->description($description)
                ->alignRight()
                ->setLabelClass('w-full')
                ->addClass('border rounded p-6 my-2 bg-blue-100');
        }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
